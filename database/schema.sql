-- UniControl - Script de Criação do Banco de Dados
-- Em conformidade com a especificação (DER simples)

CREATE DATABASE IF NOT EXISTS `unicontrol_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unicontrol_db`;

-- 1. Empresas
CREATE TABLE IF NOT EXISTS `empresas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `cnpj` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('ativo', 'inativo') DEFAULT 'ativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id` INT NOT NULL,
    `nome` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `nivel` ENUM('administrador', 'operador') NOT NULL DEFAULT 'operador',
    `status` ENUM('ativo', 'inativo') DEFAULT 'ativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Funcionários
CREATE TABLE IF NOT EXISTS `funcionarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id` INT NOT NULL,
    `nome` VARCHAR(120) NOT NULL,
    `matricula` VARCHAR(50) NOT NULL,
    `setor` VARCHAR(80) NOT NULL,
    `cargo` VARCHAR(80) NOT NULL,
    `status` ENUM('ativo', 'inativo') DEFAULT 'ativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_funcionarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Uniformes (Catálogo de peças)
CREATE TABLE IF NOT EXISTS `uniformes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id` INT NOT NULL,
    `nome` VARCHAR(120) NOT NULL,
    `categoria` VARCHAR(60) NOT NULL,
    `descricao` TEXT DEFAULT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_uniformes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Variações do Uniforme (Tamanho/Cor/SKU)
CREATE TABLE IF NOT EXISTS `uniforme_variacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uniforme_id` INT NOT NULL,
    `tamanho` VARCHAR(20) NOT NULL,
    `cor` VARCHAR(40) NOT NULL,
    `sku` VARCHAR(50) DEFAULT NULL,
    `estoque_minimo` INT NOT NULL DEFAULT 5,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_variacoes_uniforme` FOREIGN KEY (`uniforme_id`) REFERENCES `uniformes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Estoque Atual (1 para 1 com variacao)
CREATE TABLE IF NOT EXISTS `estoque` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `variacao_id` INT NOT NULL UNIQUE,
    `quantidade` INT NOT NULL DEFAULT 0,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_estoque_variacao` FOREIGN KEY (`variacao_id`) REFERENCES `uniforme_variacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Movimentações de Estoque (Histórico de entrada/saída/devolução/baixa)
CREATE TABLE IF NOT EXISTS `movimentacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `variacao_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `tipo` ENUM('entrada', 'saida', 'devolucao', 'baixa') NOT NULL,
    `quantidade` INT NOT NULL,
    `motivo` VARCHAR(255) DEFAULT NULL,
    `data_movimentacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_movimentacoes_variacao` FOREIGN KEY (`variacao_id`) REFERENCES `uniforme_variacoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movimentacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Entregas a Funcionários
CREATE TABLE IF NOT EXISTS `entregas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `funcionario_id` INT NOT NULL,
    `variacao_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `quantidade` INT NOT NULL,
    `observacao` VARCHAR(255) DEFAULT NULL,
    `data_entrega` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_entregas_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_entregas_variacao` FOREIGN KEY (`variacao_id`) REFERENCES `uniforme_variacoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_entregas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
