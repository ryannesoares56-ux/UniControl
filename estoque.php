<?php
/**
 * UniControl - Gestão e Monitoramento de Estoque (RF11)
 * Lista todas as variações com destaque visual para itens em estoque baixo.
 */

$pageTitle = 'Estoque Geral';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();

// Filtro opcional por status ou busca
$busca = trim($_GET['busca'] ?? '');
$apenasBaixo = isset($_GET['apenas_baixo']) && $_GET['apenas_baixo'] == '1';

$sql = '
    SELECT 
        uv.id AS variacao_id,
        u.nome AS uniforme_nome,
        u.categoria,
        uv.tamanho,
        uv.cor,
        uv.sku,
        uv.estoque_minimo,
        COALESCE(e.quantidade, 0) AS quantidade,
        e.atualizado_em
    FROM uniforme_variacoes uv
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    LEFT JOIN estoque e ON uv.id = e.variacao_id
    WHERE u.empresa_id = :empresa_id
';

$params = [':empresa_id' => $empresaId];

if (!empty($busca)) {
    $sql .= ' AND (u.nome LIKE :busca OR u.categoria LIKE :busca OR uv.sku LIKE :busca OR uv.cor LIKE :busca)';
    $params[':busca'] = "%{$busca}%";
}

if ($apenasBaixo) {
    $sql .= ' AND COALESCE(e.quantidade, 0) <= uv.estoque_minimo';
}

$sql .= ' ORDER BY (uv.estoque_minimo - COALESCE(e.quantidade, 0)) DESC, u.nome ASC, uv.tamanho ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itensEstoque = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Estoque Geral de Peças</h1>
        <p class="page-subtitle">Acompanhe saldos em tempo real e identifique necessidades de compra e reposição</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="movimentacoes.php?action=nova" class="btn btn-primary">+ Entrada de Estoque</a>
        <a href="entregas.php?action=nova" class="btn btn-success">+ Entrega a Funcionário</a>
    </div>
</div>

<!-- Filtros de Busca -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="estoque.php" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="busca" class="form-control" style="max-width: 300px;" placeholder="Buscar peça, SKU ou cor..." value="<?= htmlspecialchars($busca) ?>">
            
            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                <input type="checkbox" name="apenas_baixo" value="1" <?= $apenasBaixo ? 'checked' : '' ?> onchange="this.form.submit()">
                Filtrar apenas estoque baixo
            </label>

            <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
            <?php if (!empty($busca) || $apenasBaixo): ?>
                <a href="estoque.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Limpar Filtros</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Listagem de Estoque Físico (<?= count($itensEstoque) ?> itens)</h2>
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
                        <th>Saldo Atual</th>
                        <th>Status</th>
                        <th>Última Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itensEstoque)): ?>
                        <tr><td colspan="10" style="text-align:center; color:var(--text-muted);">Nenhum item encontrado no estoque com os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($itensEstoque as $item): 
                            $isBaixo = $item['quantidade'] <= $item['estoque_minimo'];
                            $isEsgotado = $item['quantidade'] == 0;
                            $rowClass = $isEsgotado ? 'row-danger' : ($isBaixo ? 'row-low-stock' : '');
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td><strong><?= htmlspecialchars($item['uniforme_nome']) ?></strong></td>
                                <td><?= htmlspecialchars($item['categoria']) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($item['tamanho']) ?></span></td>
                                <td><?= htmlspecialchars($item['cor']) ?></td>
                                <td><code><?= htmlspecialchars($item['sku'] ?? 'N/D') ?></code></td>
                                <td><?= $item['estoque_minimo'] ?> un.</td>
                                <td>
                                    <strong style="font-size: 1.05rem; color: <?= $isBaixo ? 'var(--danger)' : 'var(--text-main)' ?>;">
                                        <?= $item['quantidade'] ?> un.
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($isEsgotado): ?>
                                        <span class="badge badge-danger">Esgotado</span>
                                    <?php elseif ($isBaixo): ?>
                                        <span class="badge badge-warning">Estoque Baixo</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted);">
                                        <?= $item['atualizado_em'] ? date('d/m/Y H:i', strtotime($item['atualizado_em'])) : 'Sem movimentação' ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="movimentacoes.php?action=nova&variacao_id=<?= $item['variacao_id'] ?>" class="btn btn-primary btn-sm" title="Entrada / Reposição">+</a>
                                        <a href="entregas.php?action=nova&variacao_id=<?= $item['variacao_id'] ?>" class="btn btn-success btn-sm" title="Entregar a Funcionário">🤝</a>
                                    </div>
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
