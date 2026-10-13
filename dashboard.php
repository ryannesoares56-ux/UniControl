<?php
/**
 * UniControl - Dashboard Principal (RF10)
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();

// 1. Total de Peças em Estoque
$stmtPecas = $pdo->prepare('
    SELECT COALESCE(SUM(e.quantidade), 0) AS total
    FROM estoque e
    INNER JOIN uniforme_variacoes uv ON e.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    WHERE u.empresa_id = :empresa_id
');
$stmtPecas->execute([':empresa_id' => $empresaId]);
$totalPecas = (int)$stmtPecas->fetchColumn();

// 2. Número de Funcionários Ativos
$stmtFunc = $pdo->prepare('
    SELECT COUNT(*) AS total
    FROM funcionarios
    WHERE empresa_id = :empresa_id AND status = "ativo"
');
$stmtFunc->execute([':empresa_id' => $empresaId]);
$totalFuncionarios = (int)$stmtFunc->fetchColumn();

// 3. Itens com Estoque Baixo (Quantidade <= Estoque Mínimo)
$stmtBaixo = $pdo->prepare('
    SELECT COUNT(*) AS total
    FROM estoque e
    INNER JOIN uniforme_variacoes uv ON e.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    WHERE u.empresa_id = :empresa_id AND e.quantidade <= uv.estoque_minimo
');
$stmtBaixo->execute([':empresa_id' => $empresaId]);
$totalEstoqueBaixo = (int)$stmtBaixo->fetchColumn();

// 4. Total de Entregas do Mês
$inicioMes = date('Y-m-01 00:00:00');
$fimMes    = date('Y-m-t 23:59:59');
$stmtEntregas = $pdo->prepare('
    SELECT COALESCE(SUM(ent.quantidade), 0) AS total
    FROM entregas ent
    INNER JOIN funcionarios f ON ent.funcionario_id = f.id
    WHERE f.empresa_id = :empresa_id 
      AND ent.data_entrega >= :inicio_mes
      AND ent.data_entrega <= :fim_mes
');
$stmtEntregas->execute([
    ':empresa_id'  => $empresaId,
    ':inicio_mes'  => $inicioMes,
    ':fim_mes'     => $fimMes
]);
$totalEntregasMes = (int)$stmtEntregas->fetchColumn();

// Lista detalhada dos itens com estoque baixo
$stmtAlertas = $pdo->prepare('
    SELECT 
        u.nome AS uniforme_nome, 
        u.categoria, 
        uv.tamanho, 
        uv.cor, 
        uv.sku, 
        uv.estoque_minimo, 
        e.quantidade
    FROM estoque e
    INNER JOIN uniforme_variacoes uv ON e.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    WHERE u.empresa_id = :empresa_id AND e.quantidade <= uv.estoque_minimo
    ORDER BY (uv.estoque_minimo - e.quantidade) DESC, e.quantidade ASC
');
$stmtAlertas->execute([':empresa_id' => $empresaId]);
$alertasEstoque = $stmtAlertas->fetchAll();

// 5. Distribuição por Categoria
$stmtCategorias = $pdo->prepare('
    SELECT u.categoria, COALESCE(SUM(e.quantidade), 0) AS total_pecas
    FROM uniformes u
    INNER JOIN uniforme_variacoes uv ON u.id = uv.uniforme_id
    LEFT JOIN estoque e ON uv.id = e.variacao_id
    WHERE u.empresa_id = :empresa_id
    GROUP BY u.categoria
    ORDER BY total_pecas DESC
');
$stmtCategorias->execute([':empresa_id' => $empresaId]);
$categoriasEstoque = $stmtCategorias->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Painel de Controle</h1>
        <p class="page-subtitle">Visão consolidada do estoque e movimentações da sua empresa</p>
    </div>
    <div>
        <a href="movimentacoes.php?action=nova" class="btn btn-primary">+ Nova Entrada</a>
        <a href="entregas.php?action=nova" class="btn btn-success">+ Nova Entrega</a>
    </div>
</div>

<!-- 4 Indicadores Principais (RF10) -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">Peças em Estoque</span>
            <div class="kpi-icon blue">📦</div>
        </div>
        <div class="kpi-value"><?= number_format($totalPecas, 0, ',', '.') ?></div>
        <div class="kpi-subtext">Saldo físico disponível</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">Funcionários Ativos</span>
            <div class="kpi-icon green">👥</div>
        </div>
        <div class="kpi-value"><?= number_format($totalFuncionarios, 0, ',', '.') ?></div>
        <div class="kpi-subtext">Aptos a receber uniformes</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">Estoque Baixo</span>
            <div class="kpi-icon amber">⚠️</div>
        </div>
        <div class="kpi-value" style="color: <?= $totalEstoqueBaixo > 0 ? 'var(--danger)' : 'inherit' ?>;">
            <?= number_format($totalEstoqueBaixo, 0, ',', '.') ?>
        </div>
        <div class="kpi-subtext">Itens no limite ou abaixo</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">Entregas no Mês</span>
            <div class="kpi-icon purple">🤝</div>
        </div>
        <div class="kpi-value"><?= number_format($totalEntregasMes, 0, ',', '.') ?></div>
        <div class="kpi-subtext">Peças distribuídas este mês</div>
    </div>
</div>

<!-- Distribuição por Categoria -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Distribuição do Estoque por Categoria</h2>
    </div>
    <div class="card-body">
        <?php if (empty($categoriasEstoque)): ?>
            <p style="color: var(--text-muted); font-style: italic;">Nenhum produto cadastrado no catálogo.</p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <?php foreach ($categoriasEstoque as $cat): 
                    $percent = ($totalPecas > 0) ? round(($cat['total_pecas'] / $totalPecas) * 100, 1) : 0;
                ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">
                            <span><?= htmlspecialchars($cat['categoria']) ?></span>
                            <span><?= $cat['total_pecas'] ?> un. (<?= $percent ?>%)</span>
                        </div>
                        <div style="width: 100%; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?= $percent ?>%; background: var(--primary); height: 100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de Alertas de Estoque Baixo -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">⚠️ Alertas de Reposição Necessária (Estoque Baixo)</h2>
        <a href="estoque.php" class="btn btn-secondary btn-sm">Ver Estoque Completo</a>
    </div>
    <div class="card-body">
        <?php if (empty($alertasEstoque)): ?>
            <p style="color: var(--text-muted); font-style: italic; padding: 10px 0;">
                Excelente! Todas as variações de uniformes estão com saldo acima do estoque mínimo.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Peça</th>
                            <th>Categoria</th>
                            <th>Variação</th>
                            <th>SKU</th>
                            <th>Estoque Mínimo</th>
                            <th>Estoque Atual</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertasEstoque as $alerta): ?>
                            <tr class="row-low-stock">
                                <td><strong><?= htmlspecialchars($alerta['uniforme_nome']) ?></strong></td>
                                <td><?= htmlspecialchars($alerta['categoria']) ?></td>
                                <td><?= htmlspecialchars($alerta['tamanho']) ?> - <?= htmlspecialchars($alerta['cor']) ?></td>
                                <td><code><?= htmlspecialchars($alerta['sku'] ?? 'N/D') ?></code></td>
                                <td><?= $alerta['estoque_minimo'] ?> un.</td>
                                <td>
                                    <strong style="color: var(--danger); font-size: 1.05rem;">
                                        <?= $alerta['quantidade'] ?> un.
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($alerta['quantidade'] == 0): ?>
                                        <span class="badge badge-danger">Esgotado</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Abaixo do Mínimo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="movimentacoes.php?action=nova" class="btn btn-primary btn-sm">+ Repor</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
