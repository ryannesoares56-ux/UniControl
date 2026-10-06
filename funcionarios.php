<?php
/**
 * UniControl - Gestão de Funcionários (RF04)
 */

$pageTitle = 'Funcionários';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();

// Cadastro de Funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $setor     = trim($_POST['setor'] ?? '');
    $cargo     = trim($_POST['cargo'] ?? '');
    $status    = in_array($_POST['status'] ?? '', ['ativo', 'inativo']) ? $_POST['status'] : 'ativo';

    if (empty($nome) || empty($matricula) || empty($setor) || empty($cargo)) {
        set_flash('error', 'Todos os campos são obrigatórios (nome, matrícula, setor e cargo).');
    } else {
        // Verifica se a matrícula já existe para esta empresa
        $stmtCheck = $pdo->prepare('SELECT id FROM funcionarios WHERE empresa_id = :empresa_id AND matricula = :matricula LIMIT 1');
        $stmtCheck->execute([':empresa_id' => $empresaId, ':matricula' => $matricula]);
        if ($stmtCheck->fetch()) {
            set_flash('error', "Já existe um funcionário com a matrícula '{$matricula}' cadastrado nesta empresa.");
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO funcionarios (empresa_id, nome, matricula, setor, cargo, status) 
                VALUES (:empresa_id, :nome, :matricula, :setor, :cargo, :status)
            ');
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':nome'       => $nome,
                ':matricula'  => $matricula,
                ':setor'      => $setor,
                ':cargo'      => $cargo,
                ':status'     => $status
            ]);

            set_flash('success', "Funcionário '{$nome}' cadastrado com sucesso.");
            header('Location: funcionarios.php');
            exit;
        }
    }
}

// Listagem com isolamento multiempresa
$stmt = $pdo->prepare('
    SELECT f.*,
           (SELECT COUNT(*) FROM entregas e WHERE e.funcionario_id = f.id) AS total_entregas
    FROM funcionarios f
    WHERE f.empresa_id = :empresa_id
    ORDER BY f.nome ASC
');
$stmt->execute([':empresa_id' => $empresaId]);
$funcionarios = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Funcionários</h1>
        <p class="page-subtitle">Colaboradores da empresa aptos a receber uniformes</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">+ Cadastrar Novo Funcionário</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="funcionarios.php" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label" for="nome">Nome Completo <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Lucas Henrique Silva" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="matricula">Matrícula <span class="req">*</span></label>
                <input type="text" id="matricula" name="matricula" class="form-control" placeholder="Ex: MAT-2045" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="setor">Setor / Departamento <span class="req">*</span></label>
                <input type="text" id="setor" name="setor" class="form-control" placeholder="Ex: Produção, Manutenção, Logística" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="cargo">Cargo / Função <span class="req">*</span></label>
                <input type="text" id="cargo" name="cargo" class="form-control" placeholder="Ex: Mecânico Industrial" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Situação</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Salvar Funcionário</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Funcionários (<?= count($funcionarios) ?>)</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Setor</th>
                        <th>Cargo</th>
                        <th>Status</th>
                        <th>Entregas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionarios)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Nenhum funcionário cadastrado nesta empresa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($funcionarios as $func): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($func['matricula']) ?></code></td>
                                <td><strong><?= htmlspecialchars($func['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($func['setor']) ?></td>
                                <td><?= htmlspecialchars($func['cargo']) ?></td>
                                <td>
                                    <?php if ($func['status'] === 'ativo'): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-secondary"><?= $func['total_entregas'] ?> un.</span></td>
                                <td>
                                    <a href="entregas.php?funcionario_id=<?= $func['id'] ?>" class="btn btn-secondary btn-sm">Ver Entregas</a>
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
