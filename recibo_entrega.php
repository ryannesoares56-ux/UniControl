<?php
/**
 * UniControl - Recibo / Termo de Entrega de Uniforme para Impressão
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

check_auth();

$entregaId = (int)($_GET['id'] ?? 0);
$empresaId = current_empresa_id();
$pdo = getDBConnection();

$stmt = $pdo->prepare('
    SELECT ent.*, 
           f.nome AS funcionario_nome, f.matricula, f.setor, f.cargo,
           u.nome AS uniforme_nome, u.categoria, uv.tamanho, uv.cor, uv.sku,
           usr.nome AS usuario_nome,
           emp.nome AS empresa_nome, emp.cnpj AS empresa_cnpj
    FROM entregas ent
    INNER JOIN funcionarios f ON ent.funcionario_id = f.id
    INNER JOIN uniforme_variacoes uv ON ent.variacao_id = uv.id
    INNER JOIN uniformes u ON uv.uniforme_id = u.id
    INNER JOIN usuarios usr ON ent.usuario_id = usr.id
    INNER JOIN empresas emp ON f.empresa_id = emp.id
    WHERE ent.id = :id AND f.empresa_id = :empresa_id
');
$stmt->execute([':id' => $entregaId, ':empresa_id' => $empresaId]);
$entrega = $stmt->fetch();

if (!$entrega) {
    die('Entrega não encontrada ou sem permissão para visualização.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo de Entrega #<?= $entrega['id'] ?> - UniControl</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111;
            margin: 0;
            padding: 40px;
            background: #f8fafc;
        }
        .recibo-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 5px 0;
            color: #0f172a;
        }
        .subtitle {
            font-size: 13px;
            color: #64748b;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f1f5f9;
            padding: 8px 12px;
            margin: 20px 0 12px 0;
            border-left: 4px solid #2563eb;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .info-item strong {
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            font-weight: bold;
        }
        .termo-legal {
            font-size: 12px;
            line-height: 1.6;
            color: #475569;
            margin: 24px 0;
            text-align: justify;
            border: 1px dashed #cbd5e1;
            padding: 14px;
            border-radius: 6px;
        }
        .assinaturas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
            text-align: center;
        }
        .linha-assinatura {
            border-top: 1px solid #000;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }
        .no-print {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            max-width: 800px;
            margin: 0 auto 20px auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            border: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #e2e8f0; color: #0f172a; }

        @media print {
            body { background: #fff; padding: 0; }
            .recibo-container { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="entregas.php" class="btn btn-back">⬅ Voltar às Entregas</a>
    <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir Termo</button>
</div>

<div class="recibo-container">
    <div class="header">
        <div style="font-weight: bold; font-size: 16px; color: #2563eb;"><?= htmlspecialchars($entrega['empresa_nome']) ?></div>
        <?php if ($entrega['empresa_cnpj']): ?>
            <div style="font-size: 12px; color: #64748b;">CNPJ: <?= htmlspecialchars($entrega['empresa_cnpj']) ?></div>
        <?php endif; ?>
        <div class="title">Termo de Recebimento de Uniforme</div>
        <div class="subtitle">Protocolo de Entrega Nº #<?= str_pad($entrega['id'], 5, '0', STR_PAD_LEFT) ?></div>
    </div>

    <div class="section-title">1. Dados do Colaborador</div>
    <div class="info-grid">
        <div class="info-item"><strong>Nome:</strong> <?= htmlspecialchars($entrega['funcionario_nome']) ?></div>
        <div class="info-item"><strong>Matrícula:</strong> <?= htmlspecialchars($entrega['matricula']) ?></div>
        <div class="info-item"><strong>Setor:</strong> <?= htmlspecialchars($entrega['setor']) ?></div>
        <div class="info-item"><strong>Cargo:</strong> <?= htmlspecialchars($entrega['cargo']) ?></div>
    </div>

    <div class="section-title">2. Itens Fornecidos</div>
    <table>
        <thead>
            <tr>
                <th>Item / Uniforme</th>
                <th>Categoria</th>
                <th>Tamanho / Cor</th>
                <th>Código SKU</th>
                <th style="text-align: center;">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><?= htmlspecialchars($entrega['uniforme_nome']) ?></strong></td>
                <td><?= htmlspecialchars($entrega['categoria']) ?></td>
                <td><?= htmlspecialchars($entrega['tamanho']) ?> - <?= htmlspecialchars($entrega['cor']) ?></td>
                <td><code><?= htmlspecialchars($entrega['sku'] ?? 'N/D') ?></code></td>
                <td style="text-align: center; font-weight: bold; font-size: 16px;"><?= $entrega['quantidade'] ?> un.</td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($entrega['observacao'])): ?>
        <div style="font-size: 13px; margin-bottom: 16px;">
            <strong>Finalidade / Observações:</strong> <?= htmlspecialchars($entrega['observacao']) ?>
        </div>
    <?php endif; ?>

    <div class="termo-legal">
        <strong>Declaração de Recebimento e Responsabilidade:</strong><br>
        Declaro ter recebido da empresa o(s) uniforme(s) acima discriminado(s), em perfeito estado de conservação e uso. Comprometo-me a utilizá-lo(s) exclusivamente durante a jornada de trabalho, zelar pela sua correta higienização e guarda, bem como devolvê-lo(s) à empresa por ocasião da rescisão contratual ou quando for solicitada a substituição periódica.
    </div>

    <div style="font-size: 13px; text-align: right; margin-top: 30px;">
        Data da Entrega: <strong><?= date('d/m/Y H:i', strtotime($entrega['data_entrega'])) ?></strong>
    </div>

    <div class="assinaturas">
        <div>
            <div class="linha-assinatura"><?= htmlspecialchars($entrega['funcionario_nome']) ?></div>
            <div style="font-size: 11px; color: #64748b;">Assinatura do Colaborador</div>
        </div>
        <div>
            <div class="linha-assinatura"><?= htmlspecialchars($entrega['usuario_nome']) ?></div>
            <div style="font-size: 11px; color: #64748b;">Responsável pela Entrega (Almoxarifado)</div>
        </div>
    </div>
</div>

</body>
</html>
