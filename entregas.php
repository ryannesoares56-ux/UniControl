<?php
/**
 * UniControl - Registro e Controle de Entregas a Colaboradores (RF08, RF09)
 * Abatimento automático de estoque e bloqueio em caso de saldo insuficiente.
 */

$pageTitle = 'Entregas de Uniformes';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();
$userId = current_user()['id'];

// Processamento de Nova Entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionarioId = (int)($_POST['funcionario_id'] ?? 0);
    $variacaoId    = (int)($_POST['variacao_id'] ?? 0);
    $quantidade    = (int)($_POST['quantidade'] ?? 0);
    $observacao    = trim($_POST['observacao'] ?? '');

    if ($funcionarioId <= 0 || $variacaoId <= 0 || $quantidade <= 0) {
        set_flash('error', 'Selecione o funcionário, a peça de uniforme e informe uma quantidade maior que zero.');
    } else {
        // Valida se o funcionário pertence à empresa do usuário
        $stmtFunc = $pdo->prepare('SELECT id, nome FROM funcionarios WHERE id = :id AND empresa_id = :empresa_id AND status = "ativo"');
        $stmtFunc->execute([':id' => $funcionarioId, ':empresa_id' => $empresaId]);
        $funcionario = $stmtFunc->fetch();

        // Valida se a variação pertence à empresa do usuário e obtém o saldo atual
        $stmtVar = $pdo->prepare('
            SELECT uv.id, uv.tamanho, uv.cor, u.nome AS uniforme_nome, COALESCE(e.quantidade, 0) AS estoque_atual
            FROM uniforme_variacoes uv
            INNER JOIN uniformes u ON uv.uniforme_id = u.id
            LEFT JOIN estoque e ON uv.id = e.variacao_id
            WHERE uv.id = :id AND u.empresa_id = :empresa_id
        ');
        $stmtVar->execute([':id' => $variacaoId, ':empresa_id' => $empresaId]);
        $variacao = $stmtVar->fetch();

        if (!$funcionario) {
            set_flash('error', 'Funcionário não encontrado ou inativo.');
        } elseif (!$variacao) {
            set_flash('error', 'Peça/variação não encontrada.');
        } elseif ($quantidade > $variacao['estoque_atual']) {
            // RF09: Critério de aceite - bloqueia a operação e informa a quantidade disponível
            set_flash('error', "Operação bloqueada: A quantidade solicitada ({$quantidade} un.) é maior que o estoque disponível ({$variacao['estoque_atual']} un.).");
        } else {
            // Realiza a entrega em transação atômica (RF08)
            $pdo->beginTransaction();
            try {
                // 1. Registra a entrega
                $stmtEntrega = $pdo->prepare('
                    INSERT INTO entregas (funcionario_id, variacao_id, usuario_id, quantidade, observacao)
                    VALUES (:funcionario_id, :variacao_id, :usuario_id, :quantidade, :observacao)
                ');
                $stmtEntrega->execute([
                    ':funcionario_id' => $funcionarioId,
                    ':variacao_id'    => $variacaoId,
                    ':usuario_id'     => $userId,
                    ':quantidade'     => $quantidade,
                    ':observacao'     => $observacao ?: null
                ]);

                // 2. Abate do estoque automaticamente (RF08)
                $stmtEstoque = $pdo->prepare('
                    UPDATE estoque 
                    SET quantidade = quantidade - :quantidade 
                    WHERE variacao_id = :variacao_id
                ');
                $stmtEstoque->execute([
                    ':quantidade'  => $quantidade,
                    ':variacao_id' => $variacaoId
                ]);

                // 3. Registra saída em movimentações para trilha de auditoria (RF07)
                $stmtMov = $pdo->prepare('
                    INSERT INTO movimentacoes (variacao_id, usuario_id, tipo, quantidade, motivo)
                    VALUES (:variacao_id, :usuario_id, "saida", :quantidade, :motivo)
                ');
                $motivoAuditoria = "Entrega ao funcionário: {$funcionario['nome']}" . ($observacao ? " ({$observacao})" : "");
                $stmtMov->execute([
                    ':variacao_id' => $variacaoId,
                    ':usuario_id'  => $userId,
                    ':quantidade'  => $quantidade,
                    ':motivo'      => $motivoAuditoria
                ]);

                $pdo->commit();
                set_flash('success', "Entrega de {$quantidade} un. de '{$variacao['uniforme_nome']} ({$variacao['tamanho']})' para {$funcionario['nome']} registrada com sucesso. Estoque atualizado.");
                header('Location: entregas.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('error', 'Falha ao processar a entrega: ' . $e->getMessage());
            }
        }
    }
}

// Filtro opcional por funcionário
$filtroFuncionarioId = (int)($_GET['funcionario_id'] ?? 0);

// Busca lista de funcionários ativos para o select
$stmtFuncs = $pdo->prepare('SELECT id, nome, matricula, setor FROM funcionarios WHERE empresa_id = :empresa_id AND status = "ativo" ORDER BY nome ASC');
$stmtFuncs->execute([':empresa_id' => $empresaId]);
$funcionarios = $stmtFuncs->fetchAll();

// Busca variações disponíveis para o select com saldo em tempo real
$stmtVariacoes = $pdo->prepare('
    SELECT uv.id, u.nome AS uniforme_nome, uv.tamanho, uv.cor, uv.sku, COALESCE(e.quantidade, 0) AS estoque_atual
    FROM uniforme_variacoes uv
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    LEFT JOIN estoque e ON uv.id = e.variacao_id
    WHERE u.empresa_id = :empresa_id
    ORDER BY u.nome ASC, uv.tamanho ASC
');
$stmtVariacoes->execute([':empresa_id' => $empresaId]);
$variacoes = $stmtVariacoes->fetchAll();

// Histórico de entregas
$sqlEntregas = '
    SELECT ent.*, f.nome AS funcionario_nome, f.matricula, f.setor,
           u.nome AS uniforme_nome, uv.tamanho, uv.cor, uv.sku, usr.nome AS usuario_nome
    FROM entregas ent
    INNER JOIN funcionarios f ON ent.funcionario_id = f.id
    INNER JOIN uniforme_variacoes uv ON ent.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    INNER JOIN usuarios usr ON ent.usuario_id = usr.id
    WHERE f.empresa_id = :empresa_id
';
$paramsEntregas = [':empresa_id' => $empresaId];

if ($filtroFuncionarioId > 0) {
    $sqlEntregas .= ' AND ent.funcionario_id = :filtro_func';
    $paramsEntregas[':filtro_func'] = $filtroFuncionarioId;
}

$sqlEntregas .= ' ORDER BY ent.id DESC LIMIT 50';

$stmtEntregas = $pdo->prepare($sqlEntregas);
$stmtEntregas->execute($paramsEntregas);
$entregas = $stmtEntregas->fetchAll();

$variacaoPreselecionada = (int)($_GET['variacao_id'] ?? 0);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Entregas de Uniformes</h1>
        <p class="page-subtitle">Distribuição e protocolo de entrega de peças aos colaboradores</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">+ Registrar Entrega de Uniforme (RF08)</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="entregas.php" class="form-grid">
            <div class="form-group">
                <label class="form-label" for="funcionario_id">Colaborador / Funcionário <span class="req">*</span></label>
                <select id="funcionario_id" name="funcionario_id" class="form-control" required>
                    <option value="">Selecione o colaborador...</option>
                    <?php foreach ($funcionarios as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $filtroFuncionarioId === $f['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nome']) ?> (<?= htmlspecialchars($f['matricula']) ?> - <?= htmlspecialchars($f['setor']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="variacao_id">Peça / Variação <span class="req">*</span></label>
                <select id="variacao_id" name="variacao_id" class="form-control" required>
                    <option value="">Selecione a peça...</option>
                    <?php foreach ($variacoes as $v): ?>
                        <option value="<?= $v['id'] ?>" data-saldo="<?= $v['estoque_atual'] ?>" <?= $variacaoPreselecionada === $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['uniforme_nome']) ?> - Tam: <?= htmlspecialchars($v['tamanho']) ?> (Cor: <?= htmlspecialchars($v['cor']) ?>) [Disponível: <?= $v['estoque_atual'] ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="saldo_disponivel_display" style="font-weight: 600; margin-top: 4px;"></small>
            </div>

            <div class="form-group">
                <label class="form-label" for="quantidade_input">Quantidade a Entregar <span class="req">*</span></label>
                <input type="number" id="quantidade_input" name="quantidade" class="form-control" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="observacao">Finalidade / Observação</label>
                <input type="text" id="observacao" name="observacao" class="form-control" placeholder="Ex: Kit Admissão, Troca semestral, Reposição por dano">
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-success">Confirmar Entrega e Baixar Estoque</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            Protocolos de Entregas Realizadas 
            <?= $filtroFuncionarioId > 0 ? '(Filtrado por Funcionário)' : '' ?>
        </h2>
        <?php if ($filtroFuncionarioId > 0): ?>
            <a href="entregas.php" class="btn btn-secondary btn-sm">Ver Todas as Entregas</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Setor</th>
                        <th>Uniforme</th>
                        <th>Variação</th>
                        <th>Qtd.</th>
                        <th>Observação</th>
                        <th>Entregue por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entregas)): ?>
                        <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">Nenhuma entrega registrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $e): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($e['data_entrega'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($e['funcionario_nome']) ?></strong>
                                    <br><small style="color:var(--text-muted);">Mat: <?= htmlspecialchars($e['matricula']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($e['setor']) ?></td>
                                <td><?= htmlspecialchars($e['uniforme_nome']) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($e['tamanho']) ?> - <?= htmlspecialchars($e['cor']) ?></span></td>
                                <td><strong style="color:var(--primary); font-size:1.05rem;"><?= $e['quantidade'] ?> un.</strong></td>
                                <td><?= htmlspecialchars($e['observacao'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($e['usuario_nome']) ?></small></td>
                                <td>
                                    <a href="recibo_entrega.php?id=<?= $e['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="Imprimir Termo de Recebimento">
                                        🖨️ Recibo
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
