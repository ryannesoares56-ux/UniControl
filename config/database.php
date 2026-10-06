<?php
/**
 * UniControl - Configuração de Conexão com o Banco de Dados
 * Suporte nativo a MySQL (PDO) e fallback automático para SQLite local,
 * garantindo que o sistema funcione imediatamente em qualquer ambiente de apresentação.
 */

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'unicontrol_db');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}

/**
 * Retorna uma instância PDO configurada
 * 
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }

    $sqlitePath = __DIR__ . '/../database/unicontrol.sqlite';

    // 1. Se configurado explicitamente para sqlite, usa direto
    if (DB_DRIVER === 'sqlite') {
        $pdo = initSQLite($sqlitePath);
        return $pdo;
    }

    // 2. Tenta conectar ao MySQL
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Se MySQL falhar (ex: senha de root não informada no lab), usa SQLite como fallback imediato
        $pdo = initSQLite($sqlitePath);
        return $pdo;
    }
}

/**
 * Inicializa e popula o banco SQLite caso ainda não exista
 */
function initSQLite(string $sqlitePath): PDO {
    $isNew = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;
    
    $pdo = new PDO('sqlite:' . $sqlitePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($isNew) {
        setupSQLiteSchemaAndSeed($pdo);
    }

    return $pdo;
}

/**
 * Cria a estrutura e dados iniciais no SQLite para visualização imediata
 */
function setupSQLiteSchemaAndSeed(PDO $pdo): void {
    $schema = "
        CREATE TABLE IF NOT EXISTS empresas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            cnpj TEXT,
            status TEXT DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            empresa_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            senha TEXT NOT NULL,
            nivel TEXT NOT NULL DEFAULT 'operador',
            status TEXT DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS funcionarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            empresa_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            matricula TEXT NOT NULL,
            setor TEXT NOT NULL,
            cargo TEXT NOT NULL,
            status TEXT DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS uniformes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            empresa_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            categoria TEXT NOT NULL,
            descricao TEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS uniforme_variacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uniforme_id INTEGER NOT NULL,
            tamanho TEXT NOT NULL,
            cor TEXT NOT NULL,
            sku TEXT,
            estoque_minimo INTEGER NOT NULL DEFAULT 5,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uniforme_id) REFERENCES uniformes(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS estoque (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            variacao_id INTEGER NOT NULL UNIQUE,
            quantidade INTEGER NOT NULL DEFAULT 0,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (variacao_id) REFERENCES uniforme_variacoes(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS movimentacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            variacao_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            tipo TEXT NOT NULL,
            quantidade INTEGER NOT NULL,
            motivo TEXT,
            data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (variacao_id) REFERENCES uniforme_variacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        );

        CREATE TABLE IF NOT EXISTS entregas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            funcionario_id INTEGER NOT NULL,
            variacao_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL,
            observacao TEXT,
            data_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE,
            FOREIGN KEY (variacao_id) REFERENCES uniforme_variacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        );
    ";
    $pdo->exec($schema);

    // Carga inicial (Seed)
    $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $passOp    = password_hash('operador123', PASSWORD_DEFAULT);

    $seed = "
        INSERT INTO empresas (id, nome, cnpj, status) VALUES
        (1, 'UniControl Matriz - Indústria Alfa', '12.345.678/0001-90', 'ativo'),
        (2, 'Logística Beta Express', '98.765.432/0001-10', 'ativo');

        INSERT INTO usuarios (id, empresa_id, nome, email, senha, nivel, status) VALUES
        (1, 1, 'Administrador Geral', 'admin@unicontrol.com', '{$passAdmin}', 'administrador', 'ativo'),
        (2, 1, 'Operador de Estoque', 'operador@unicontrol.com', '{$passOp}', 'operador', 'ativo');

        INSERT INTO funcionarios (id, empresa_id, nome, matricula, setor, cargo, status) VALUES
        (1, 1, 'Carlos Alberto Souza', 'MAT-1001', 'Operações', 'Operador de Máquinas', 'ativo'),
        (2, 1, 'Mariana Costa', 'MAT-1002', 'Logística', 'Auxiliar de Almoxarifado', 'ativo'),
        (3, 1, 'João Pedro Santos', 'MAT-1003', 'Manutenção', 'Técnico Mecânico', 'ativo');

        INSERT INTO uniformes (id, empresa_id, nome, categoria, descricao) VALUES
        (1, 1, 'Camisa Polo Uniformizada', 'Camisas', 'Camisa polo em malha piquet com proteção UV e bordado no peito'),
        (2, 1, 'Calça Operacional Ripstop', 'Calças', 'Calça reforçada com costura tripla e 6 bolsos táticos'),
        (3, 1, 'Bota de Segurança Nobuck', 'Calçados', 'Botina com bico de composite e solado bidensidade antiderrapante');

        INSERT INTO uniforme_variacoes (id, uniforme_id, tamanho, cor, sku, estoque_minimo) VALUES
        (1, 1, 'M', 'Azul Marinho', 'POLO-AZM-M', 10),
        (2, 1, 'G', 'Azul Marinho', 'POLO-AZM-G', 10),
        (3, 1, 'GG', 'Azul Marinho', 'POLO-AZM-GG', 5),
        (4, 2, '42', 'Cinza Chumbo', 'CALC-CZ-42', 8),
        (5, 2, '44', 'Cinza Chumbo', 'CALC-CZ-44', 8),
        (6, 3, '41', 'Preto', 'BOT-PR-41', 4);

        INSERT INTO estoque (variacao_id, quantidade) VALUES
        (1, 15),
        (2, 4),
        (3, 12),
        (4, 2),
        (5, 10),
        (6, 3);

        INSERT INTO movimentacoes (variacao_id, usuario_id, tipo, quantidade, motivo) VALUES
        (1, 1, 'entrada', 20, 'Estoque inicial da remessa #001'),
        (2, 1, 'entrada', 10, 'Estoque inicial da remessa #001'),
        (3, 1, 'entrada', 15, 'Estoque inicial da remessa #001'),
        (4, 1, 'entrada', 10, 'Estoque inicial da remessa #001'),
        (5, 1, 'entrada', 12, 'Estoque inicial da remessa #001'),
        (6, 1, 'entrada', 5, 'Estoque inicial da remessa #001');

        INSERT INTO entregas (funcionario_id, variacao_id, usuario_id, quantidade, observacao) VALUES
        (1, 1, 2, 2, 'Kit inicial de admissão'),
        (1, 4, 2, 2, 'Kit inicial de admissão'),
        (2, 2, 2, 3, 'Reposição periódica de uniforme'),
        (3, 1, 2, 1, 'Troca por desgaste natural'),
        (3, 6, 2, 1, 'Entrega de EPI calçado');
    ";
    $pdo->exec($seed);
}
