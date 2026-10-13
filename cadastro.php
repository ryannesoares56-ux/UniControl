<?php
/**
 * UniControl - Cadastro de Nova Conta e Empresa (Auto-Registro)
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
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = trim($_POST['nome'] ?? '');
    $empresaNome    = trim($_POST['empresa_nome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $senha          = trim($_POST['senha'] ?? '');
    $senhaConfirma  = trim($_POST['senha_confirma'] ?? '');

    if (empty($nome) || empty($empresaNome) || empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $senhaConfirma) {
        $error = 'As senhas informadas não coincidem.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um endereço de e-mail válido.';
    } else {
        $pdo = getDBConnection();

        // Verifica se o e-mail já existe
        $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            $error = 'Este e-mail já está cadastrado no sistema. Faça login ou use outro e-mail.';
        } else {
            // Cria a Empresa e o Usuário Administrador em transação atômica
            $pdo->beginTransaction();
            try {
                // 1. Cadastra a nova Empresa
                $stmtEmp = $pdo->prepare('INSERT INTO empresas (nome, status) VALUES (:nome, "ativo")');
                $stmtEmp->execute([':nome' => $empresaNome]);
                $empresaId = (int)$pdo->lastInsertId();

                // 2. Cadastra o Usuário Administrador
                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
                $stmtUser = $pdo->prepare('
                    INSERT INTO usuarios (empresa_id, nome, email, senha, nivel, status)
                    VALUES (:empresa_id, :nome, :email, :senha, "administrador", "ativo")
                ');
                $stmtUser->execute([
                    ':empresa_id' => $empresaId,
                    ':nome'       => $nome,
                    ':email'      => $email,
                    ':senha'      => $hashSenha
                ]);
                $usuarioId = (int)$pdo->lastInsertId();

                $pdo->commit();

                // 3. Realiza o login automático
                $_SESSION['usuario_id']    = $usuarioId;
                $_SESSION['usuario_nome']  = $nome;
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_nivel'] = 'administrador';
                $_SESSION['empresa_id']    = $empresaId;
                $_SESSION['empresa_nome']  = $empresaNome;

                set_flash('success', "Conta criada com sucesso! Bem-vindo à gestão da sua empresa '{$empresaNome}'.");
                header('Location: dashboard.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Erro ao realizar o cadastro: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Conta - UniControl</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

<div class="auth-card" style="max-width: 480px;">
    <div class="auth-header">
        <h1 class="auth-logo">UniControl</h1>
        <p class="auth-subtitle">Criar Conta e Cadastrar Nova Empresa</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="cadastro.php" autocomplete="off">
        <div class="form-group">
            <label class="form-label" for="nome">Seu Nome Completo <span class="req">*</span></label>
            <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Roberto Silva" required autofocus value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="empresa_nome">Nome da sua Empresa / Organização <span class="req">*</span></label>
            <input type="text" id="empresa_nome" name="empresa_nome" class="form-control" placeholder="Ex: Transportadora Rápida Ltda" required value="<?= htmlspecialchars($_POST['empresa_nome'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">E-mail Profissional <span class="req">*</span></label>
            <input type="email" id="email" name="email" class="form-control" placeholder="seuemail@empresa.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
                <label class="form-label" for="senha">Senha <span class="req">*</span></label>
                <input type="password" id="senha" name="senha" class="form-control" placeholder="Mínimo 6 dígitos" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="senha_confirma">Confirmar Senha <span class="req">*</span></label>
                <input type="password" id="senha_confirma" name="senha_confirma" class="form-control" placeholder="Repita a senha" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 14px; padding: 12px; font-size: 1rem;">
            Cadastrar e Começar a Usar
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); font-size: 0.92rem;">
        Já possui uma conta cadastrada? 
        <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">
            Fazer Login ➔
        </a>
    </div>

    <div style="text-align: center; margin-top: 10px; font-size: 0.85rem;">
        <a href="index.php" style="color: var(--text-muted); text-decoration: none;">
            ⬅ Voltar à página inicial
        </a>
    </div>
</div>

</body>
</html>
