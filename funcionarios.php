<?php
/**
 * UniControl - Gestão de Funcionários (RF04)
 * Cadastro, edição, listagem e alternância de status ativo/inativo.
 */

$pageTitle = 'Funcionários';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$empresaId = current_empresa_id();

// 1. Alternar Status (Ativo <-> Inativo)
if (isset($_GET['alternar_id'])) {
    $funcId = (int)$_GET['alternar_id'];
    $stmtStatus = $pdo->prepare('SELECT id, nome, status FROM funcionarios WHERE id = :id AND empresa_id = :empresa_id');
    $stmtStatus->execute([':id' => $funcId, ':empresa_id' => $empresaId]);
    $f = $stmtStatus->fetch();

    if ($f) {
        $novoStatus = ($f['status'] === 'ativo') ? 'inativo' : 'ativo';
        $stmtUp = $pdo->prepare('UPDATE funcionarios SET status = :status WHERE id = :id AND empresa_id = :empresa_id');
        $stmtUp->execute([':status' => $novoStatus, ':id' => $funcId, ':empresa_id' => $empresaId]);
        set_flash('info', "Situação do colaborador '{$f['nome']}' alterada para {$novoStatus}.");
    }
    header('Location: funcionarios.php');
    exit;
}

// 2. Modo Edição (busca dados se editar_id for passado)
$funcEdit = null;
if (isset($_GET['editar_id'])) {
    $stmtEdit = $pdo->prepare('SELECT * FROM funcionarios WHERE id = :id AND empresa_id = :empresa_id');
    $stmtEdit->execute([':id' => (int)$_GET['editar_id'], ':empresa_id' => $empresaId]);
    $funcEdit = $stmtEdit->fetch();
}

// 3. Processamento de Cadastro ou Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idEdit    = (int)($_POST['funcionario_id'] ?? 0);
    $nome      = trim($_POST['nome'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $setor     = trim($_POST['setor'] ?? '');
    $cargo     = trim($_POST['cargo'] ?? '');
    $status    = in_array($_POST['status'] ?? '', ['ativo', 'inativo']) ? $_POST['status'] : 'ativo';

    if (empty($nome) || empty($matricula) || empty($setor) || empty($cargo)) {
        set_flash('error', 'Todos os campos são obrigatórios (nome, matrícula, setor e cargo).');
    } else {
        // Verifica unicidade de matrícula na mesma empresa
        $sqlCheck = 'SELECT id FROM funcionarios WHERE empresa_id = :empresa_id AND matricula = :matricula';
        $paramsCheck = [':empresa_id' => $empresaId, ':matricula' => $matricula];
        if ($idEdit > 0) {
            $sqlCheck .= ' AND id != :id';
            $paramsCheck[':id'] = $idEdit;
        }
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($paramsCheck);

        if ($stmtCheck->fetch()) {
            set_flash('error', "Já existe um funcionário com a matrícula '{$matricula}' cadastrado nesta empresa.");
        } else {
            if ($idEdit > 0) {
                // Atualização
                $stmtUpdate = $pdo->prepare('
                    UPDATE funcionarios 
                    SET nome = :nome, matricula = :matricula, setor = :setor, cargo = :cargo, status = :status
                    WHERE id = :id AND empresa_id = :empresa_id
                ');
                $stmtUpdate->execute([
                    ':nome'       => $nome,
                    ':matricula'  => $matricula,
                    ':setor'      => $setor,
                    ':cargo'      => $cargo,
                    ':status'     => $status,
                    ':id'         => $idEdit,
                    ':empresa_id' => $empresaId
                ]);
                set_flash('success', "Dados do colaborador '{$nome}' atualizados com sucesso.");
            } else {
                // Inserção
                $stmtInsert = $pdo->prepare('
                    INSERT INTO funcionarios (empresa_id, nome, matricula, setor, cargo, status) 
                    VALUES (:empresa_id, :nome, :matricula, :setor, :cargo, :status)
                ');
                $stmtInsert->execute([
                    ':empresa_id' => $empresaId,
                    ':nome'       => $nome,
                    ':matricula'  => $matricula,
                    ':setor'      => $setor,
                    ':cargo'      => $cargo,
                    ':status'     => $status
                ]);
                set_flash('success', "Funcionário '{$nome}' cadastrado com sucesso.");
            }
            header('Location: funcionarios.php');
            exit;
        }
    }
}

// 4. Listagem
$stmt = $pdo->prepare('
    SELECT f.*,
           (SELECT COUNT(*) FROM entregas e WHERE e.funcionario_id = f.id) AS total_entregas
    FROM funcionarios f
    WHERE f.empresa_id = :empresa_id
    ORDER BY f.status ASC, f.nome ASC
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
        <h2 class="card-title">
            <?= $funcEdit ? '✏️ Editar Colaborador' : '+ Cadastrar Novo Funcionário' ?>
        </h2>
        <?php if ($funcEdit): ?>
            <a href="funcionarios.php" class="btn btn-secondary btn-sm">Cancelar Edição</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="funcionarios.php" class="form-grid">
            <input type="hidden" name="funcionario_id" value="<?= $funcEdit['id'] ?? 0 ?>">

            <div class="form-group col-span-2">
                <label class="form-label" for="nome">Nome Completo <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Lucas Henrique Silva" required value="<?= htmlspecialchars($funcEdit['nome'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="matricula">Matrícula <span class="req">*</span></label>
                <input type="text" id="matricula" name="matricula" class="form-control" placeholder="Ex: MAT-2045" required value="<?= htmlspecialchars($funcEdit['matricula'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="setor">Setor / Departamento <span class="req">*</span></label>
                <input type="text" id="setor" name="setor" class="form-control" placeholder="Ex: Produção, Manutenção, Logística" required value="<?= htmlspecialchars($funcEdit['setor'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="cargo">Cargo / Função <span class="req">*</span></label>
                <input type="text" id="cargo" name="cargo" class="form-control" placeholder="Ex: Mecânico Industrial" required value="<?= htmlspecialchars($funcEdit['cargo'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Situação</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo" <?= ($funcEdit['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($funcEdit['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group col-span-2" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <?= $funcEdit ? 'Salvar Alterações' : 'Salvar Funcionário' ?>
                </button>
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
                                    <div style="display:flex; gap:6px;">
                                        <a href="funcionarios.php?editar_id=<?= $func['id'] ?>" class="btn btn-secondary btn-sm" title="Editar Dados">
                                            ✏️
                                        </a>
                                        <a href="funcionarios.php?alternar_id=<?= $func['id'] ?>" class="btn btn-secondary btn-sm" title="<?= $func['status'] === 'ativo' ? 'Inativar' : 'Ativar' ?>" data-confirm="Deseja alterar a situação deste colaborador?">
                                            <?= $func['status'] === 'ativo' ? '🚫' : '✅' ?>
                                        </a>
                                        <a href="entregas.php?funcionario_id=<?= $func['id'] ?>" class="btn btn-primary btn-sm" title="Ver Histórico de Entregas">
                                            Entregas
                                        </a>
                                    </div>
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
