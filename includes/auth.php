<?php
/**
 * UniControl - Gerenciamento de Sessão e Autenticação
 * Controla os níveis de acesso (Administrador vs Operador) e isolamento multiempresa.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Garante que o usuário esteja autenticado no sistema.
 * Redireciona para login.php se não estiver autenticado (Critério de Aceite RF01).
 */
function check_auth(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Garante que apenas usuários com nível 'administrador' acessem a página.
 * Bloqueia operadores (Critério de Aceite RF02).
 */
function check_admin(): void {
    check_auth();
    if ($_SESSION['usuario_nivel'] !== 'administrador') {
        http_response_code(403);
        require_once __DIR__ . '/alerts.php';
        set_flash('error', 'Acesso negado: Você não tem permissão de administrador para acessar esta tela.');
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Retorna se o usuário logado é administrador.
 */
function is_admin(): bool {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'administrador';
}

/**
 * Retorna o ID da empresa do usuário logado para isolamento multiempresa.
 */
function current_empresa_id(): int {
    return (int)($_SESSION['empresa_id'] ?? 0);
}

/**
 * Retorna os dados resumidos do usuário autenticado.
 */
function current_user(): array {
    return [
        'id'           => $_SESSION['usuario_id'] ?? null,
        'nome'         => $_SESSION['usuario_nome'] ?? 'Visitante',
        'email'        => $_SESSION['usuario_email'] ?? '',
        'nivel'        => $_SESSION['usuario_nivel'] ?? 'operador',
        'empresa_id'   => $_SESSION['empresa_id'] ?? null,
        'empresa_nome' => $_SESSION['empresa_nome'] ?? 'Empresa'
    ];
}
