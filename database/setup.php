<?php
/**
 * UniControl - Script de Inicialização do Banco de Dados
 * Cria o banco e popula com dados iniciais (schema.sql + seed.sql).
 */

require_once __DIR__ . '/../config/database.php';

$isCli = (php_sapi_name() === 'cli');

function outputMsg($msg, $isCli) {
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo '<p>' . htmlspecialchars($msg) . '</p>';
    }
}

try {
    outputMsg("Conectando ao MySQL...", $isCli);
    
    // Conexão inicial sem banco selecionado para permitir CREATE DATABASE
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    outputMsg("Executando schema.sql...", $isCli);
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    outputMsg("Estrutura de tabelas criada com sucesso!", $isCli);

    outputMsg("Executando seed.sql...", $isCli);
    $seedSql = file_get_contents(__DIR__ . '/seed.sql');
    $pdo->exec($seedSql);
    outputMsg("Dados iniciais carregados com sucesso!", $isCli);

    $successMsg = "Banco de dados unicontrol_db pronto para uso!\n" .
                  "Login do Administrador: admin@unicontrol.com | Senha: admin123\n" .
                  "Login do Operador: operador@unicontrol.com | Senha: operador123";
    
    if ($isCli) {
        echo PHP_EOL . "=== SUCESSO ===" . PHP_EOL . $successMsg . PHP_EOL;
    } else {
        echo '<div style="font-family:sans-serif; background:#d4edda; color:#155724; padding:20px; border-radius:8px; margin:20px;">' .
             '<h3>Instalação Concluída com Sucesso!</h3>' .
             '<pre>' . htmlspecialchars($successMsg) . '</pre>' .
             '<p><a href="../login.php" style="display:inline-block; padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px;">Ir para a tela de Login</a></p>' .
             '</div>';
    }

} catch (PDOException $e) {
    $err = "Erro ao configurar o banco de dados: " . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $err . PHP_EOL);
        exit(1);
    } else {
        echo '<div style="font-family:sans-serif; background:#f8d7da; color:#721c24; padding:20px; border-radius:8px; margin:20px;">' .
             '<h3>Falha na Configuração</h3>' .
             '<p>' . htmlspecialchars($err) . '</p>' .
             '</div>';
    }
}
