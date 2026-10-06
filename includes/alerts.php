<?php
/**
 * UniControl - Mensagens Flash de Feedback
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Define uma mensagem flash na sessão
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Renderiza e limpa as mensagens flash
 */
function render_flash(): string {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $html = '<div class="flash-messages">';
    foreach ($_SESSION['flash_messages'] as $flash) {
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);
        $html .= "<div class=\"alert alert-{$type}\">{$msg}</div>";
    }
    $html .= '</div>';

    unset($_SESSION['flash_messages']);
    return $html;
}
