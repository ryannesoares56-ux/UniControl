<?php
/**
 * UniControl - Movimentações de Estoque (RF07, RF09)
 * Gerencia Entradas, Saídas, Devoluções e Baixas com atualização de saldo em tempo real.
 */

$pageTitle = 'Movimentações de Estoque';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();
$userId = current_user()['id'];

// Processamento de Registro de Movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variacaoId = (int)($_POST['variacao_id'] ?? 0);
    $tipo       = $_POST['tipo'] ?? '';
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $motivo     = trim($_POST['motivo'] ?? '');

    $tiposValidos = ['entrada', 'saida', 'devolucao', 'baixa'];

    if (!in_array($tipo, $tiposValidos) || $quantidade <= 0 || $variacaoId <= 0) {
        set_flash('error', 'Informe uma variação válida, o tipo de movimentação e uma quantidade maior que zero.');
    } else {
        // Valida se a variação pertence à empresa do usuário
        $stmtVar = $pdo->prepare('
            SELECT uv.id, uv.tamanho, uv.cor, u.nome AS uniforme_nome, COALESCE(e.quantidade, 0) AS estoque_atual
            FROM uniforme_variacoes uv
            INNER JOIN uniformes u ON uv.uniforme_id = u.id
            LEFT JOIN estoque e ON uv.id = e.variacao_id
            WHERE uv.id = :id AND u.empresa_id = :empresa_id
        ');
        $stmtVar->execute([':id' => $variacaoId, ':empresa_id' => $empresaId]);
        $item = $stmtVar->fetch();

        if (!$item) {
            set_flash('error', 'Variação não encontrada ou não pertence à sua empresa.');
        } else {
            // RF09: Se for saída ou baixa, impedir se quantidade for maior que a disponível
            if (in_array($tipo, ['saida', 'baixa']) && $quantidade > $item['estoque_atual']) {
                set_flash('error', "Operação bloqueada: A quantidade solicitada ({$quantidade} un.) é superior ao saldo disponível em estoque ({$item['estoque_atual']} un.).");
            } else {
                $pdo->beginTransaction();
                try {
                    // Calcula o novo saldo
                    $delta = in_array($tipo, ['entrada', 'devolucao']) ? $quantidade : -$quantidade;
                    
                    // Atualiza a tabela estoque (RF07)
                    $stmtUpdate = $pdo->prepare('
                        INSERT INTO estoque (variacao_id, quantidade) 
                        VALUES (:variacao_id, :delta)
                        ON DUPLICATE KEY UPDATE quantidade = quantidade + :delta_up
                    ');
                    $stmtUpdate->execute([
                        ':variacao_id' => $variacaoId,
                        ':delta'       => max(0, $delta),
                        ':delta_up'    => $delta
                    ]);

                    // Insere registro no histórico de movimentações
                    $stmtMov = $pdo->prepare('
                        INSERT INTO movimentacoes (variacao_id, usuario_id, tipo, quantidade, motivo)
                        VALUES (:variacao_id, :usuario_id, :tipo, :quantidade, :motivo)
                    ');
                    $stmtMov->execute([
                        ':variacao_id' => $variacaoId,
                        ':usuario_id'  => $userId,
                        ':tipo'        => $tipo,
                        ':quantidade'  => $quantidade,
                        ':motivo'      => $motivo ?: null
                    ]);

                    $pdo->commit();
                    $tipoFormatado = ucfirst($tipo);
                    set_flash('success', "Movimentação de {$tipoFormatado} de {$quantidade} un. de '{$item['uniforme_nome']} ({$item['tamanho']})' registrada com sucesso.");
                    header('Location: movimentacoes.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    set_flash('error', 'Erro ao registrar movimentação: ' . $e->getMessage());
                }
            }
        }
    }
}

// Busca variações para o select
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

// Busca histórico recente de movimentações
$stmtHistorico = $pdo->prepare('
    SELECT m.*, u.nome AS uniforme_nome, uv.tamanho, uv.cor, uv.sku, usr.nome AS usuario_nome
    FROM movimentacoes m
    INNER JOIN uniforme_variacoes uv ON m.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    INNER JOIN usuarios usr ON m.usuario_id = usr.id
    WHERE u.empresa_id = :empresa_id
    ORDER BY m.id DESC
    LIMIT 50
');
$stmtHistorico->execute([':empresa_id' => $empresaId]);
$historico = $stmtHistorico->fetchAll();

$variacaoPreselecionada = (int)($_GET['variacao_id'] ?? 0);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Movimentações de Estoque</h1>
        <p class="page-subtitle">Registro de entradas, saídas, devoluções e baixas com auditoria de saldo</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">+ Registrar Nova Movimentação</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="movimentacoes.php" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label" for="variacao_id">Peça / Variação <span class="req">*</span></label>
                <select id="variacao_id" name="variacao_id" class="form-control" required>
                    <option value="">Selecione o uniforme e a variação...</option>
                    <?php foreach ($variacoes as $v): ?>
                        <option value="<?= $v['id'] ?>" data-saldo="<?= $v['estoque_atual'] ?>" <?= $variacaoPreselecionada === $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['uniforme_nome']) ?> - Tam: <?= htmlspecialchars($v['tamanho']) ?> (Cor: <?= htmlspecialchars($v['cor']) ?>) [Saldo Atual: <?= $v['estoque_atual'] ?> un.]
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="saldo_disponivel_display" style="font-weight: 600; margin-top: 4px;"></small>
            </div>

            <div class="form-group">
                <label class="form-label" for="tipo">Tipo de Operação <span class="req">*</span></label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="entrada">Entrada (Compra / Reposição de Fornecedor)</option>
                    <option value="devolucao">Devolução (Retorno ao estoque)</option>
                    <option value="saida">Saída (Descarte / Transferência)</option>
                    <option value="baixa">Baixa (Avaria / Perda / Desgaste)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="quantidade_input">Quantidade de Peças <span class="req">*</span></label>
                <input type="number" id="quantidade_input" name="quantidade" class="form-control" min="1" placeholder="Ex: 10" required>
            </div>

            <div class="form-group col-span-2">
                <label class="form-label" for="motivo">Motivo / Justificativa / Nota Fiscal</label>
                <input type="text" id="motivo" name="motivo" class="form-control" placeholder="Ex: NF-e 4598 do fornecedor Alfa Têxtil">
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Gravar Movimentação</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Histórico de Movimentações Recentes</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Peça</th>
                        <th>Variação</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Motivo / Obs</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Nenhuma movimentação registrada até o momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($h['data_movimentacao'])) ?></td>
                                <td><strong><?= htmlspecialchars($h['uniforme_nome']) ?></strong></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($h['tamanho']) ?> - <?= htmlspecialchars($h['cor']) ?></span></td>
                                <td>
                                    <?php if ($h['tipo'] === 'entrada'): ?>
                                        <span class="badge badge-success">Entrada</span>
                                    <?php elseif ($h['tipo'] === 'devolucao'): ?>
                                        <span class="badge badge-primary">Devolução</span>
                                    <?php elseif ($h['tipo'] === 'saida'): ?>
                                        <span class="badge badge-warning">Saída</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Baixa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>
                                        <?= in_array($h['tipo'], ['entrada', 'devolucao']) ? '+' : '-' ?><?= $h['quantidade'] ?> un.
                                    </strong>
                                </td>
                                <td><?= htmlspecialchars($h['motivo'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($h['usuario_nome']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
