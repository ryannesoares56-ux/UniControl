<?php
/**
 * UniControl - Vitrine & Apresentação Oficial do Produto (Landing Page)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['usuario_id']);
$userName   = $_SESSION['usuario_nome'] ?? '';
$userNivel  = $_SESSION['usuario_nivel'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniControl — Sistema Inteligente de Estoque de Uniformes</title>
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="lp-body">

<!-- Barra de Notificação se já estiver conectado -->
<?php if ($isLoggedIn): ?>
    <div style="background: linear-gradient(90deg, #1e3a8a, #2563eb); color: #fff; text-align: center; padding: 10px 20px; font-size: 0.9rem; font-weight: 500;">
        👋 Olá, <strong><?= htmlspecialchars($userName) ?></strong> (<?= htmlspecialchars($userNivel) ?>)! Você já está conectado.
        <a href="dashboard.php" style="color: #67e8f9; margin-left: 10px; font-weight: 700; text-decoration: underline;">
            Acessar o Painel de Controle ➔
        </a>
    </div>
<?php endif; ?>

<!-- Navbar -->
<nav class="lp-navbar">
    <a href="index.php" class="lp-brand">
        <span>UniControl</span>
        <span class="lp-brand-tag">2.0</span>
    </a>

    <div class="lp-nav-links">
        <a href="#habilidades">Habilidades Únicas</a>
        <a href="#simulador">Simulador de Economia</a>
        <a href="#comparativo">Comparativo</a>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="lp-btn lp-btn-primary">Ir para o Painel</a>
        <?php else: ?>
            <a href="login.php" class="lp-btn lp-btn-secondary">Entrar</a>
            <a href="cadastro.php" class="lp-btn lp-btn-primary">Criar Conta</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero Section -->
<header class="lp-hero">
    <div class="lp-badge-announcement">
        ✨ Inovação em Almoxarifado • Adequado à NR-6 e CLT
    </div>

    <h1 class="lp-hero-title">
        Controle de Uniformes <span class="lp-hero-gradient-text">sem papel, sem planilhas</span> e sem furos de estoque.
    </h1>

    <p class="lp-hero-subtitle">
        A plataforma web inteligente que centraliza o catálogo de peças, bloqueia entregas sem saldo físico, emite termos formais de recebimento e audita todas as movimentações multiempresa em tempo real.
    </p>

    <!-- Acesso Dinâmico para Demonstração -->
    <div class="lp-hero-actions">
        <a href="cadastro.php" class="lp-btn lp-btn-primary" style="padding: 14px 28px; font-size: 1.05rem; background: linear-gradient(135deg, #10b981, #059669);">
            ✨ Criar Conta Grátis
        </a>

        <a href="login.php" class="lp-btn lp-btn-secondary" style="padding: 14px 24px; font-size: 1.05rem;">
            🔑 Fazer Login
        </a>

        <form method="POST" action="login.php" style="display:inline; margin:0;">
            <input type="hidden" name="email" value="admin@unicontrol.com">
            <input type="hidden" name="senha" value="admin123">
            <button type="submit" class="lp-btn lp-btn-demo" style="padding: 14px 24px; font-size: 1.05rem;">
                ⚡ Entrar como Admin (Demo)
            </button>
        </form>

        <form method="POST" action="login.php" style="display:inline; margin:0;">
            <input type="hidden" name="email" value="operador@unicontrol.com">
            <input type="hidden" name="senha" value="operador123">
            <button type="submit" class="lp-btn lp-btn-secondary" style="padding: 14px 24px; font-size: 1.05rem;">
                👤 Operador (Demo)
            </button>
        </form>
    </div>

    <!-- Faixa de Estatísticas de Impacto -->
    <div class="lp-stats-strip">
        <div class="lp-stat-item">
            <span class="lp-stat-number" style="color:#60a5fa;">0%</span>
            <span class="lp-stat-label">Furos de Estoque</span>
        </div>
        <div class="lp-stat-item">
            <span class="lp-stat-number" style="color:#34d399;">100%</span>
            <span class="lp-stat-label">Rastreabilidade com Recibo</span>
        </div>
        <div class="lp-stat-item">
            <span class="lp-stat-number" style="color:#a78bfa;">Multi</span>
            <span class="lp-stat-label">Gestão de Filiais/Empresas</span>
        </div>
        <div class="lp-stat-item">
            <span class="lp-stat-number" style="color:#fbbf24;">Tempo Real</span>
            <span class="lp-stat-label">Alertas de Estoque Mínimo</span>
        </div>
    </div>
</header>

<!-- Seção: Habilidades Únicas do Sistema -->
<section id="habilidades" class="lp-section">
    <div class="lp-section-header">
        <span class="lp-section-tag">Diferenciais Tecnológicos</span>
        <h2 class="lp-section-title">O que torna o UniControl indispensável?</h2>
        <p style="color: var(--lp-text-muted); max-width: 650px; margin: 12px auto 0 auto;">
            Projetado especialmente para resolver as dores reais de compras, almoxarifado e departamento pessoal.
        </p>
    </div>

    <div class="lp-features-grid">
        <!-- Card 1 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon blue">🛡️</div>
            <h3 class="lp-feature-title">Trava Anti-Furo de Estoque</h3>
            <p class="lp-feature-desc">
                O sistema valida no servidor a quantidade real disponível. É impossível entregar ou baixar mais peças do que o saldo físico existente, eliminando estoques negativos.
            </p>
            <span class="lp-feature-badge">Regra RF09 Ativa</span>
        </div>

        <!-- Card 2 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon green">🖨️</div>
            <h3 class="lp-feature-title">Termo de Recebimento com Assinatura</h3>
            <p class="lp-feature-desc">
                Gera instantaneamente um recibo formal pronto para impressão com cláusulas de zelo e campos de assinatura do colaborador e do almoxarife, respaldando juridicamente a empresa.
            </p>
            <span class="lp-feature-badge">Conformidade Trabalhista</span>
        </div>

        <!-- Card 3 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon amber">⚠️</div>
            <h3 class="lp-feature-title">Alertas Inteligentes de Reposição</h3>
            <p class="lp-feature-desc">
                Identificação visual imediata (linhas destacadas em amarelo e vermelho) para qualquer variação cujo estoque atinja ou fique abaixo do limite mínimo cadastrado.
            </p>
            <span class="lp-feature-badge">Dashboard & Estoque</span>
        </div>

        <!-- Card 4 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon purple">🏢</div>
            <h3 class="lp-feature-title">Arquitetura Multiempresa</h3>
            <p class="lp-feature-desc">
                Atenda matriz e filiais ou múltiplos clientes corporativos na mesma plataforma com isolamento absoluto de dados, funcionários e movimentações de cada empresa.
            </p>
            <span class="lp-feature-badge">Multi-Tenant Nativo</span>
        </div>

        <!-- Card 5 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon blue">👔</div>
            <h3 class="lp-feature-title">Grade Completa por Tamanho e SKU</h3>
            <p class="lp-feature-desc">
                Organize peças por categoria, cor, tamanho (P, M, G, GG, 40 a 46) e código de barras SKU, mantendo saldo individualizado por cada variação física.
            </p>
            <span class="lp-feature-badge">Catálogo Inteligente</span>
        </div>

        <!-- Card 6 -->
        <div class="lp-feature-card">
            <div class="lp-feature-icon rose">🔒</div>
            <h3 class="lp-feature-title">Segurança & Auditoria Total</h3>
            <p class="lp-feature-desc">
                Senhas protegidas com bcrypt, transações atômicas com PDO contra SQL Injection e histórico imutável com data, hora e responsável por cada entrega.
            </p>
            <span class="lp-feature-badge">Segurança Robusta</span>
        </div>
    </div>
</section>

<!-- Seção: Simulador Interativo de Economia (Dinâmico) -->
<section id="simulador" class="lp-section">
    <div class="lp-section-header">
        <span class="lp-section-tag">Simulador Dinâmico</span>
        <h2 class="lp-section-title">Calcule o impacto do UniControl na sua empresa</h2>
        <p style="color: var(--lp-text-muted); max-width: 600px; margin: 10px auto 0 auto;">
            Arraste os controles abaixo para calcular a estimativa de redução de extravios e tempo economizado.
        </p>
    </div>

    <div class="lp-simulator-box">
        <div>
            <!-- Slider 1 -->
            <div class="lp-slider-group">
                <div class="lp-slider-header">
                    <span class="lp-slider-label">Número de Colaboradores:</span>
                    <span class="lp-slider-value" id="valColaboradores">100</span>
                </div>
                <input type="range" id="inputColaboradores" class="lp-range-input" min="10" max="500" value="100" step="5">
            </div>

            <!-- Slider 2 -->
            <div class="lp-slider-group">
                <div class="lp-slider-header">
                    <span class="lp-slider-label">Kits de Uniforme por Ano (por pessoa):</span>
                    <span class="lp-slider-value" id="valKits">2</span>
                </div>
                <input type="range" id="inputKits" class="lp-range-input" min="1" max="5" value="2" step="1">
            </div>

            <!-- Slider 3 -->
            <div class="lp-slider-group">
                <div class="lp-slider-header">
                    <span class="lp-slider-label">Custo Médio do Kit Completo (R$):</span>
                    <span class="lp-slider-value" id="valCusto">R$ 150</span>
                </div>
                <input type="range" id="inputCusto" class="lp-range-input" min="50" max="400" value="150" step="10">
            </div>
        </div>

        <div class="lp-result-card">
            <div style="font-size: 0.85rem; text-transform: uppercase; color: var(--lp-text-muted); letter-spacing: 0.05em; font-weight: 700;">
                Economia Anual Estimada (Redução de Extravios)
            </div>
            <div class="lp-result-number" id="displayEconomia">R$ 6.000,00</div>
            <div style="font-size: 0.95rem; color: #cbd5e1; margin-top: 8px;">
                ⚡ <strong id="displayHoras">20 horas</strong> de trabalho economizadas por mês no almoxarifado.
            </div>
            <div style="margin-top: 24px;">
                <a href="login.php" class="lp-btn lp-btn-primary" style="width: 100%;">
                    Começar a Controlar Agora ➔
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Seção: Comparativo UniControl vs Planilha/Papel -->
<section id="comparativo" class="lp-section">
    <div class="lp-section-header">
        <span class="lp-section-tag">Por que migrar?</span>
        <h2 class="lp-section-title">Controle Antigo vs UniControl</h2>
    </div>

    <table class="lp-compare-table">
        <thead>
            <tr>
                <th style="width: 40%;">Recurso / Capacidade</th>
                <th style="width: 30%;">Planilha ou Bloco de Papel</th>
                <th style="width: 30%; color: #60a5fa;">UniControl 2.0</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Bloqueio de Saldo Insuficiente</strong></td>
                <td><span class="lp-cross">❌ Não impede entrega sem saldo</span></td>
                <td><span class="lp-check">✔ Trava automática no servidor</span></td>
            </tr>
            <tr>
                <td><strong>Termo com Assinatura do Colaborador</strong></td>
                <td><span class="lp-cross">❌ Fichas impressas que se perdem</span></td>
                <td><span class="lp-check">✔ Protocolo digital com 1 clique</span></td>
            </tr>
            <tr>
                <td><strong>Alertas de Estoque Mínimo</strong></td>
                <td><span class="lp-cross">❌ Depende de conferência visual</span></td>
                <td><span class="lp-check">✔ Destaque visual em tempo real</span></td>
            </tr>
            <tr>
                <td><strong>Gestão Multiempresa</strong></td>
                <td><span class="lp-cross">❌ Dezenas de planilhas desorganizadas</span></td>
                <td><span class="lp-check">✔ Isolamento nativo em 1 sistema</span></td>
            </tr>
            <tr>
                <td><strong>Histórico de Auditoria</strong></td>
                <td><span class="lp-cross">❌ Qualquer um apaga ou edita</span></td>
                <td><span class="lp-check">✔ Rastreabilidade completa de usuários</span></td>
            </tr>
        </tbody>
    </table>
</section>

<!-- Rodapé -->
<footer class="lp-footer">
    <div style="margin-bottom: 12px; font-weight: 700; color: #fff;">
        UniControl — Sistema de Controle de Estoque de Uniformes
    </div>
    <div>
        Desenvolvido segundo a especificação oficial (<a href="spec.pdf" target="_blank" style="color:var(--lp-primary); text-decoration:none;">spec.pdf</a>). Código aberto e versionado no <a href="https://github.com/ryannesoares56-ux/UniControl" target="_blank" style="color:var(--lp-primary); text-decoration:none;">GitHub</a>.
    </div>
</footer>

<script>
// Script Interativo do Simulador de Economia
const inputColab = document.getElementById('inputColaboradores');
const inputKits  = document.getElementById('inputKits');
const inputCusto = document.getElementById('inputCusto');

const valColab   = document.getElementById('valColaboradores');
const valKits    = document.getElementById('valKits');
const valCusto   = document.getElementById('valCusto');

const dispEcon   = document.getElementById('displayEconomia');
const dispHoras  = document.getElementById('displayHoras');

function atualizarSimulador() {
    const colab = parseInt(inputColab.value, 10);
    const kits  = parseInt(inputKits.value, 10);
    const custo = parseInt(inputCusto.value, 10);

    valColab.textContent = colab + ' pessoas';
    valKits.textContent  = kits + (kits === 1 ? ' kit' : ' kits');
    valCusto.textContent = 'R$ ' + custo;

    // Gasto total anual em uniformes
    const gastoAnual = colab * kits * custo;
    // Estimativa conservadora de 20% de economia em perdas, compras duplicadas e extravios
    const economia = gastoAnual * 0.20;
    // Horas estimadas economizadas por mês no almoxarifado
    const horasMes = Math.round(colab * 0.2);

    dispEcon.textContent = 'R$ ' + economia.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    dispHoras.textContent = horasMes + ' horas';
}

inputColab.addEventListener('input', atualizarSimulador);
inputKits.addEventListener('input', atualizarSimulador);
inputCusto.addEventListener('input', atualizarSimulador);

// Inicializa no carregamento
atualizarSimulador();
</script>

</body>
</html>
