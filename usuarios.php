<?php
/**
 * UniControl - Gestão de Usuários e Níveis de Acesso (RF02)
 * Restrito exclusivamente ao perfil Administrador.
 */

$pageTitle = 'Usuários do Sistema';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/alerts.php';

// Bloqueio rigoroso de operadores (RF02)
check_admin();

$pdo = getDBConnection();
$meuId = (int)current_user()['id'];

// 1. Alternar Situação do Usuário (Ativo <-> Inativo)
if (isset($_GET['alternar_id'])) {
    $usrId = (int)$_GET['alternar_id'];
    if ($usrId === $meuId) {
        set_flash('error', 'Você não pode inativar sua própria conta em uso.');
    } else {
        $stmtStatus = $pdo->prepare('SELECT id, nome, status FROM usuarios WHERE id = :id');
        $stmtStatus->execute([':id' => $usrId]);
        $u = $stmtStatus->fetch();

        if ($u) {
            $novoStatus = ($u['status'] === 'ativo') ? 'inativo' : 'ativo';
            $stmtUp = $pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
            $stmtUp->execute([':status' => $novoStatus, ':id' => $usrId]);
            set_flash('info', "Situação do usuário '{$u['nome']}' alterada para {$novoStatus}.");
        }
    }
    header('Location: usuarios.php');
    exit;
}

// 2. Redefinir Senha do Usuário para '123456'
if (isset($_GET['reset_id'])) {
    $usrId = (int)$_GET['reset_id'];
    $stmtUser = $pdo->prepare('SELECT id, nome FROM usuarios WHERE id = :id');
    $stmtUser->execute([':id' => $usrId]);
    $u = $stmtUser->fetch();

    if ($u) {
        $novaSenhaHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmtReset = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
        $stmtReset->execute([':senha' => $novaSenhaHash, ':id' => $usrId]);
        set_flash('success', "A senha do usuário '{$u['nome']}' foi redefinida para '123456'.");
    }
    header('Location: usuarios.php');
    exit;
}

// 3. Cadastro de novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresaId = (int)($_POST['empresa_id'] ?? 0);
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $senha     = trim($_POST['senha'] ?? '');
    $nivel     = in_array($_POST['nivel'] ?? '', ['administrador', 'operador']) ? $_POST['nivel'] : 'operador';
    $status    = in_array($_POST['status'] ?? '', ['ativo', 'inativo']) ? $_POST['status'] : 'ativo';

    if (empty($nome) || empty($email) || empty($senha) || $empresaId <= 0) {
        set_flash('error', 'Preencha todos os campos obrigatórios (nome, e-mail, senha e empresa).');
    } else {
        // Verifica se o e-mail já existe
        $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            set_flash('error', 'Este e-mail já está em uso por outro usuário.');
        } else {
            // Hash seguro com bcrypt (Requisito Não Funcional de Segurança)
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('
                INSERT INTO usuarios (empresa_id, nome, email, senha, nivel, status) 
                VALUES (:empresa_id, :nome, :email, :senha, :nivel, :status)
            ');
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':nome'       => $nome,
                ':email'      => $email,
                ':senha'      => $hashSenha,
                ':nivel'      => $nivel,
                ':status'     => $status
            ]);

            set_flash('success', "Usuário '{$nome}' cadastrado com sucesso com nível {$nivel}.");
            header('Location: usuarios.php');
            exit;
        }
    }
}

// 4. Busca empresas para o select
$empresas = $pdo->query('SELECT id, nome FROM empresas WHERE status = "ativo" ORDER BY nome ASC')->fetchAll();

// 5. Lista usuários
$usuarios = $pdo->query('
    SELECT u.id, u.nome, u.email, u.nivel, u.status, u.criado_em, e.nome AS empresa_nome
    FROM usuarios u
    INNER JOIN empresas e ON u.empresa_id = e.id
    ORDER BY u.id DESC
')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Usuários do Sistema</h1>
        <p class="page-subtitle">Controle de credenciais e níveis de acesso (Exclusivo Administrador)</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">+ Cadastrar Novo Usuário</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="usuarios.php" class="form-grid">
            <div class="form-group">
                <label class="form-label" for="empresa_id">Empresa Vinculada <span class="req">*</span></label>
                <select id="empresa_id" name="empresa_id" class="form-control" required>
                    <option value="">Selecione uma empresa...</option>
                    <?php foreach ($empresas as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="nome">Nome Completo <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Roberto Alves" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">E-mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="exemplo@empresa.com" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="senha">Senha Inicial <span class="req">*</span></label>
                <input type="password" id="senha" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="nivel">Nível de Acesso <span class="req">*</span></label>
                <select id="nivel" name="nivel" class="form-control" required>
                    <option value="operador">Operador (Estoque, entregas e dashboard)</option>
                    <option value="administrador">Administrador (Acesso total)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Situação</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Usuários Cadastrados (<?= count($usuarios) ?>)</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Empresa</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usr): 
                        $isEu = ($usr['id'] == $meuId);
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($usr['nome']) ?></strong>
                                <?php if ($isEu): ?>
                                    <span class="badge badge-primary" style="margin-left: 6px;">Você</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($usr['email']) ?></td>
                            <td><?= htmlspecialchars($usr['empresa_nome']) ?></td>
                            <td>
                                <?php if ($usr['nivel'] === 'administrador'): ?>
                                    <span class="badge badge-primary">Administrador</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Operador</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($usr['status'] === 'ativo'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($usr['criado_em'])) ?></td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <?php if (!$isEu): ?>
                                        <a href="usuarios.php?alternar_id=<?= $usr['id'] ?>" class="btn btn-secondary btn-sm" title="<?= $usr['status'] === 'ativo' ? 'Inativar usuário' : 'Ativar usuário' ?>" data-confirm="Deseja alterar o status deste usuário?">
                                            <?= $usr['status'] === 'ativo' ? '🚫' : '✅' ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="usuarios.php?reset_id=<?= $usr['id'] ?>" class="btn btn-secondary btn-sm" title="Redefinir senha para '123456'" data-confirm="Redefinir a senha deste usuário para '123456'?">
                                        🔑 Reset
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
