<?php
/**
 * UniControl - Header Padrão
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/alerts.php';

// Garante que o usuário esteja logado
check_auth();

// Processa alternância de empresa ativa para administradores
if (is_admin() && isset($_POST['action_switch_empresa'])) {
    $novaEmpresa = (int)($_POST['switch_empresa_id'] ?? 0);
    if (switch_empresa($novaEmpresa)) {
        set_flash('info', 'Empresa ativa alterada com sucesso.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);

// Lista empresas para o switcher do administrador
$todasEmpresas = [];
if (is_admin()) {
    require_once __DIR__ . '/../config/database.php';
    $dbHeader = getDBConnection();
    $todasEmpresas = $dbHeader->query('SELECT id, nome FROM empresas WHERE status = "ativo" ORDER BY nome ASC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>UniControl</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="brand-logo">
                <span>UniControl</span>
                <span class="brand-badge"><?= strtoupper(htmlspecialchars($user['nivel'])) ?></span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                📊 Dashboard
            </a>

            <div class="nav-section-title">Controle de Estoque</div>
            <a href="estoque.php" class="nav-link <?= $currentPage === 'estoque.php' ? 'active' : '' ?>">
                📦 Estoque Geral
            </a>
            <a href="uniformes.php" class="nav-link <?= $currentPage === 'uniformes.php' ? 'active' : '' ?>">
                👔 Catálogo de Peças
            </a>
            <a href="movimentacoes.php" class="nav-link <?= $currentPage === 'movimentacoes.php' ? 'active' : '' ?>">
                🔄 Movimentações
            </a>
            <a href="entregas.php" class="nav-link <?= $currentPage === 'entregas.php' ? 'active' : '' ?>">
                🤝 Entregas a Colaboradores
            </a>

            <div class="nav-section-title">Cadastros</div>
            <a href="funcionarios.php" class="nav-link <?= $currentPage === 'funcionarios.php' ? 'active' : '' ?>">
                👥 Funcionários
            </a>

            <?php if (is_admin()): ?>
            <div class="nav-section-title">Administração</div>
            <a href="empresas.php" class="nav-link <?= $currentPage === 'empresas.php' ? 'active' : '' ?>">
                🏢 Empresas Clientes
            </a>
            <a href="usuarios.php" class="nav-link <?= $currentPage === 'usuarios.php' ? 'active' : '' ?>">
                👤 Usuários do Sistema
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info-brief">
                <span class="user-name"><?= htmlspecialchars($user['nome']) ?></span>
                <span class="user-role"><?= htmlspecialchars($user['nivel']) ?></span>
            </div>
            <a href="logout.php" title="Encerrar Sessão" style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.85rem;">
                Sair ➔
            </a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <?php if (is_admin() && count($todasEmpresas) > 1): ?>
                    <form method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
                        <input type="hidden" name="action_switch_empresa" value="1">
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Empresa Ativa:</span>
                        <select name="switch_empresa_id" onchange="this.form.submit()" class="form-control" style="padding: 5px 12px; font-size: 0.85rem; width: auto; font-weight: 600; cursor: pointer; border-color: var(--primary);">
                            <?php foreach ($todasEmpresas as $empOption): ?>
                                <option value="<?= $empOption['id'] ?>" <?= $empOption['id'] == $user['empresa_id'] ? 'selected' : '' ?>>
                                    🏢 <?= htmlspecialchars($empOption['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="company-badge">
                        🏢 <?= htmlspecialchars($user['empresa_nome']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <span style="font-size:0.88rem; color:var(--text-muted);">
                    Logado como <strong><?= htmlspecialchars($user['nome']) ?></strong>
                </span>
            </div>
        </header>

        <!-- Corpo da Página -->
        <main class="content-body">
            <?= render_flash() ?>
