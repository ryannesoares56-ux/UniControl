<?php
/**
 * UniControl - Header Padrão
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/alerts.php';

// Garante que o usuário esteja logado
check_auth();

$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
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
            <div>
                <span class="company-badge">
                    🏢 <?= htmlspecialchars($user['empresa_nome']) ?>
                </span>
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
