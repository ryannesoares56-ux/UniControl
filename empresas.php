<?php
/**
 * UniControl - Gestão de Empresas Clientes (RF02, RF03)
 * Restrito exclusivamente ao perfil Administrador.
 */

$pageTitle = 'Empresas Clientes';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/alerts.php';

// Bloqueio rigoroso de perfil operador no servidor (RF02)
check_admin();

$pdo = getDBConnection();

// Processamento de Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['ativo', 'inativo']) ? $_POST['status'] : 'ativo';

    if (empty($nome)) {
        set_flash('error', 'O nome da empresa é obrigatório.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO empresas (nome, cnpj, status) VALUES (:nome, :cnpj, :status)');
        $stmt->execute([
            ':nome'   => $nome,
            ':cnpj'   => $cnpj ?: null,
            ':status' => $status
        ]);
        set_flash('success', "Empresa '{$nome}' cadastrada com sucesso e disponível para novos vínculos.");
        header('Location: empresas.php');
        exit;
    }
}

// Listagem de todas as empresas
$stmt = $pdo->query('
    SELECT e.*, 
           (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id = e.id) AS total_usuarios,
           (SELECT COUNT(*) FROM funcionarios f WHERE f.empresa_id = e.id) AS total_funcionarios
    FROM empresas e 
    ORDER BY e.nome ASC
');
$empresas = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Empresas Clientes</h1>
        <p class="page-subtitle">Gestão multiempresa da plataforma (Exclusivo Administrador)</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">+ Cadastrar Nova Empresa</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="empresas.php" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label" for="nome">Razão Social / Nome Fantasia <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Indústria Alfa S/A" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="cnpj">CNPJ</label>
                <input type="text" id="cnpj" name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Situação</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Salvar Empresa</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Empresas Cadastradas (<?= count($empresas) ?>)</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Usuários</th>
                        <th>Funcionários</th>
                        <th>Status</th>
                        <th>Cadastrada em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($empresas)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Nenhuma empresa cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($empresas as $emp): ?>
                            <tr>
                                <td>#<?= $emp['id'] ?></td>
                                <td><strong><?= htmlspecialchars($emp['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($emp['cnpj'] ?? 'Não informado') ?></td>
                                <td><span class="badge badge-secondary"><?= $emp['total_usuarios'] ?></span></td>
                                <td><span class="badge badge-primary"><?= $emp['total_funcionarios'] ?></span></td>
                                <td>
                                    <?php if ($emp['status'] === 'ativo'): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($emp['criado_em'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
