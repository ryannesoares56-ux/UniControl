<?php
/**
 * UniControl - Tela de Autenticação (RF01)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, redireciona para o dashboard
if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/alerts.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha o e-mail e a senha.';
    } else {
        $pdo = getDBConnection();
        // Consulta segura com Prepared Statements
        $stmt = $pdo->prepare('
            SELECT u.id, u.nome, u.email, u.senha, u.nivel, u.status, u.empresa_id, e.nome AS empresa_nome 
            FROM usuarios u
            INNER JOIN empresas e ON u.empresa_id = e.id
            WHERE u.email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            if ($user['status'] !== 'ativo') {
                $error = 'Este usuário encontra-se inativo no sistema.';
            } else {
                // Login com sucesso: define dados na sessão
                $_SESSION['usuario_id']    = $user['id'];
                $_SESSION['usuario_nome']  = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_nivel'] = $user['nivel'];
                $_SESSION['empresa_id']    = $user['empresa_id'];
                $_SESSION['empresa_nome']  = $user['empresa_nome'];

                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniControl</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

<div class="auth-card">
    <div class="auth-header">
        <h1 class="auth-logo">UniControl</h1>
        <p class="auth-subtitle">Sistema de Controle de Estoque de Uniformes</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
        <div class="form-group">
            <label class="form-label" for="email">E-mail de Acesso</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="exemplo@unicontrol.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="senha">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
            Entrar no Sistema
        </button>
    </form>

    <div class="demo-credentials-box">
        <strong>Credenciais Padrão para Avaliação:</strong>
        <div>• <b>Admin:</b> admin@unicontrol.com | <code>admin123</code></div>
        <div>• <b>Operador:</b> operador@unicontrol.com | <code>operador123</code></div>
    </div>
</div>

</body>
</html>
