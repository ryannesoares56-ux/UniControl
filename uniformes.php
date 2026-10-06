<?php
/**
 * UniControl - Catálogo de Uniformes e Variações (RF05, RF06)
 */

$pageTitle = 'Catálogo de Peças';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();
$userId = current_user()['id'];

// 1. Cadastrar Peça de Uniforme (RF05)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_uniforme') {
    $nome      = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome) || empty($categoria)) {
        set_flash('error', 'Nome e categoria do uniforme são obrigatórios.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO uniformes (empresa_id, nome, categoria, descricao) VALUES (:empresa_id, :nome, :categoria, :descricao)');
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':nome'       => $nome,
            ':categoria'  => $categoria,
            ':descricao'  => $descricao ?: null
        ]);
        set_flash('success', "Peça '{$nome}' cadastrada no catálogo. Agora adicione as variações (tamanhos/cores).");
        header('Location: uniformes.php');
        exit;
    }
}

// 2. Cadastrar Variação com Estoque Inicial (RF06 e Critério de Aceite)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_variacao') {
    $uniformeId    = (int)($_POST['uniforme_id'] ?? 0);
    $tamanho       = trim($_POST['tamanho'] ?? '');
    $cor           = trim($_POST['cor'] ?? '');
    $sku           = trim($_POST['sku'] ?? '');
    $estoqueMinimo = max(0, (int)($_POST['estoque_minimo'] ?? 5));
    $estoqueInicial= max(0, (int)($_POST['estoque_inicial'] ?? 0));

    // Valida se o uniforme pertence à empresa do usuário
    $stmtUni = $pdo->prepare('SELECT id, nome FROM uniformes WHERE id = :id AND empresa_id = :empresa_id');
    $stmtUni->execute([':id' => $uniformeId, ':empresa_id' => $empresaId]);
    $uniforme = $stmtUni->fetch();

    if (!$uniforme || empty($tamanho) || empty($cor)) {
        set_flash('error', 'Selecione uma peça válida e preencha tamanho e cor.');
    } else {
        $pdo->beginTransaction();
        try {
            // Cria a variação
            $stmtVar = $pdo->prepare('
                INSERT INTO uniforme_variacoes (uniforme_id, tamanho, cor, sku, estoque_minimo)
                VALUES (:uniforme_id, :tamanho, :cor, :sku, :estoque_minimo)
            ');
            $stmtVar->execute([
                ':uniforme_id'    => $uniformeId,
                ':tamanho'        => $tamanho,
                ':cor'            => $cor,
                ':sku'            => $sku ?: null,
                ':estoque_minimo' => $estoqueMinimo
            ]);
            $variacaoId = (int)$pdo->lastInsertId();

            // Inicializa a tabela de estoque (1 para 1)
            $stmtEstoque = $pdo->prepare('INSERT INTO estoque (variacao_id, quantidade) VALUES (:variacao_id, :quantidade)');
            $stmtEstoque->execute([
                ':variacao_id' => $variacaoId,
                ':quantidade'  => $estoqueInicial
            ]);

            // Se informado estoque inicial > 0, registra a movimentação de entrada inicial
            if ($estoqueInicial > 0) {
                $stmtMov = $pdo->prepare('
                    INSERT INTO movimentacoes (variacao_id, usuario_id, tipo, quantidade, motivo)
                    VALUES (:variacao_id, :usuario_id, "entrada", :quantidade, "Carga de estoque inicial")
                ');
                $stmtMov->execute([
                    ':variacao_id' => $variacaoId,
                    ':usuario_id'  => $userId,
                    ':quantidade'  => $estoqueInicial
                ]);
            }

            $pdo->commit();
            set_flash('success', "Variação {$tamanho}/{$cor} cadastrada com sucesso com estoque inicial de {$estoqueInicial} un.");
            header('Location: uniformes.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Falha ao cadastrar variação: ' . $e->getMessage());
        }
    }
}

// Busca uniformes da empresa
$stmtUniformes = $pdo->prepare('SELECT * FROM uniformes WHERE empresa_id = :empresa_id ORDER BY nome ASC');
$stmtUniformes->execute([':empresa_id' => $empresaId]);
$uniformes = $stmtUniformes->fetchAll();

// Busca variações com estoque atual
$stmtVariacoes = $pdo->prepare('
    SELECT uv.*, u.nome AS uniforme_nome, u.categoria, COALESCE(e.quantidade, 0) AS quantidade_atual
    FROM uniforme_variacoes uv
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    LEFT JOIN estoque e ON uv.id = e.variacao_id
    WHERE u.empresa_id = :empresa_id
    ORDER BY u.nome ASC, uv.tamanho ASC
');
$stmtVariacoes->execute([':empresa_id' => $empresaId]);
$variacoes = $stmtVariacoes->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Catálogo de Uniformes e Peças</h1>
        <p class="page-subtitle">Cadastro de itens do catálogo e suas variações de tamanho, cor e SKU</p>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 24px;">
    <!-- Formulário 1: Nova Peça Principal -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">1. Cadastrar Nova Peça (Catálogo)</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="uniformes.php">
                <input type="hidden" name="action" value="cadastrar_uniforme">
                <div class="form-group">
                    <label class="form-label" for="nome">Nome da Peça <span class="req">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Camiseta Básica Algodão" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="categoria">Categoria <span class="req">*</span></label>
                    <input type="text" id="categoria" name="categoria" class="form-control" placeholder="Ex: Camisas, Calças, EPIs, Casacos" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição / Detalhes</label>
                    <textarea id="descricao" name="descricao" class="form-control" placeholder="Tecido, composição, instruções de uso..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Salvar Peça</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário 2: Nova Variação com Estoque Inicial -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">2. Adicionar Variação a uma Peça</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="uniformes.php">
                <input type="hidden" name="action" value="cadastrar_variacao">

                <div class="form-group">
                    <label class="form-label" for="uniforme_id">Selecione a Peça <span class="req">*</span></label>
                    <select id="uniforme_id" name="uniforme_id" class="form-control" required>
                        <option value="">Selecione uma peça cadastrada...</option>
                        <?php foreach ($uniformes as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?> (<?= htmlspecialchars($u['categoria']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="tamanho">Tamanho <span class="req">*</span></label>
                        <input type="text" id="tamanho" name="tamanho" class="form-control" placeholder="Ex: P, M, G, 42, Único" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cor">Cor <span class="req">*</span></label>
                        <input type="text" id="cor" name="cor" class="form-control" placeholder="Ex: Azul Marinho, Branco" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="sku">Código SKU</label>
                        <input type="text" id="sku" name="sku" class="form-control" placeholder="Ex: POLO-AZ-M">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estoque_minimo">Estoque Mínimo <span class="req">*</span></label>
                        <input type="number" id="estoque_minimo" name="estoque_minimo" class="form-control" min="0" value="5" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="estoque_inicial">Estoque Inicial (Unidades)</label>
                    <input type="number" id="estoque_inicial" name="estoque_inicial" class="form-control" min="0" value="0">
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-success">Salvar Variação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Listagem de Variações Existentes -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Variações Cadastradas (<?= count($variacoes) ?>)</h2>
        <a href="estoque.php" class="btn btn-secondary btn-sm">Ver no Estoque Geral ➔</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Peça</th>
                        <th>Categoria</th>
                        <th>Tamanho</th>
                        <th>Cor</th>
                        <th>SKU</th>
                        <th>Estoque Mínimo</th>
                        <th>Estoque Atual</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variacoes)): ?>
                        <tr><td colspan="8" style="text-align:center; color:var(--text-muted);">Nenhuma variação cadastrada ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($variacoes as $v): ?>
                            <tr class="<?= $v['quantidade_atual'] <= $v['estoque_minimo'] ? 'row-low-stock' : '' ?>">
                                <td><strong><?= htmlspecialchars($v['uniforme_nome']) ?></strong></td>
                                <td><?= htmlspecialchars($v['categoria']) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($v['tamanho']) ?></span></td>
                                <td><?= htmlspecialchars($v['cor']) ?></td>
                                <td><code><?= htmlspecialchars($v['sku'] ?? 'N/D') ?></code></td>
                                <td><?= $v['estoque_minimo'] ?> un.</td>
                                <td>
                                    <strong><?= $v['quantidade_atual'] ?> un.</strong>
                                    <?php if ($v['quantidade_atual'] <= $v['estoque_minimo']): ?>
                                        <span class="badge badge-warning" style="margin-left:6px;">Baixo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="movimentacoes.php?action=nova&variacao_id=<?= $v['id'] ?>" class="btn btn-primary btn-sm">+ Entrada</a>
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
