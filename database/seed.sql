-- UniControl - Dados Iniciais (Seed)
USE `unicontrol_db`;

-- Limpeza preliminar opcional
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `entregas`;
TRUNCATE TABLE `movimentacoes`;
TRUNCATE TABLE `estoque`;
TRUNCATE TABLE `uniforme_variacoes`;
TRUNCATE TABLE `uniformes`;
TRUNCATE TABLE `funcionarios`;
TRUNCATE TABLE `usuarios`;
TRUNCATE TABLE `empresas`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Empresas
INSERT INTO `empresas` (`id`, `nome`, `cnpj`, `status`) VALUES
(1, 'UniControl Matriz - Indústria Alfa', '12.345.678/0001-90', 'ativo'),
(2, 'Logística Beta Express', '98.765.432/0001-10', 'ativo');

-- 2. Usuários (Senhas: admin123 e operador123 com hash bcrypt)
INSERT INTO `usuarios` (`id`, `empresa_id`, `nome`, `email`, `senha`, `nivel`, `status`) VALUES
(1, 1, 'Administrador Geral', 'admin@unicontrol.com', '$2y$10$NlZAcd0zkGJyoaKjyPMrfuHgykjvS/cDi085dsKjBaCc/EK.3IB1y', 'administrador', 'ativo'),
(2, 1, 'Operador de Estoque', 'operador@unicontrol.com', '$2y$10$i4aPUC.C5Ea6EeWJKkt4Ce5LDKj1P3fOYKxnv08wqRbOlp9cXPmYu', 'operador', 'ativo');

-- 3. Funcionários
INSERT INTO `funcionarios` (`id`, `empresa_id`, `nome`, `matricula`, `setor`, `cargo`, `status`) VALUES
(1, 1, 'Carlos Alberto Souza', 'MAT-1001', 'Operações', 'Operador de Máquinas', 'ativo'),
(2, 1, 'Mariana Costa', 'MAT-1002', 'Logística', 'Auxiliar de Almoxarifado', 'ativo'),
(3, 1, 'João Pedro Santos', 'MAT-1003', 'Manutenção', 'Técnico Mecânico', 'ativo');

-- 4. Uniformes
INSERT INTO `uniformes` (`id`, `empresa_id`, `nome`, `categoria`, `descricao`) VALUES
(1, 1, 'Camisa Polo Uniformizada', 'Camisas', 'Camisa polo em malha piquet com proteção UV e bordado no peito'),
(2, 1, 'Calça Operacional Ripstop', 'Calças', 'Calça reforçada com costura tripla e 6 bolsos táticos'),
(3, 1, 'Bota de Segurança Nobuck', 'Calçados', 'Botina com bico de composite e solado bidensidade antiderrapante');

-- 5. Variações dos Uniformes
INSERT INTO `uniforme_variacoes` (`id`, `uniforme_id`, `tamanho`, `cor`, `sku`, `estoque_minimo`) VALUES
(1, 1, 'M', 'Azul Marinho', 'POLO-AZM-M', 10),
(2, 1, 'G', 'Azul Marinho', 'POLO-AZM-G', 10),
(3, 1, 'GG', 'Azul Marinho', 'POLO-AZM-GG', 5),
(4, 2, '42', 'Cinza Chumbo', 'CALC-CZ-42', 8),
(5, 2, '44', 'Cinza Chumbo', 'CALC-CZ-44', 8),
(6, 3, '41', 'Preto', 'BOT-PR-41', 4);

-- 6. Estoque Inicial
-- POLO-AZM-M: 15 (Normal)
-- POLO-AZM-G: 4 (Abaixo do mínimo: 4 <= 10 -> Alerta no Dashboard e destaque no estoque)
-- POLO-AZM-GG: 12 (Normal)
-- CALC-CZ-42: 2 (Abaixo do mínimo: 2 <= 8 -> Alerta)
-- CALC-CZ-44: 10 (Normal)
-- BOT-PR-41: 3 (Abaixo do mínimo: 3 <= 4 -> Alerta)
INSERT INTO `estoque` (`variacao_id`, `quantidade`) VALUES
(1, 15),
(2, 4),
(3, 12),
(4, 2),
(5, 10),
(6, 3);

-- 7. Histórico de Movimentações Iniciais
INSERT INTO `movimentacoes` (`variacao_id`, `usuario_id`, `tipo`, `quantidade`, `motivo`) VALUES
(1, 1, 'entrada', 20, 'Estoque inicial da remessa #001'),
(2, 1, 'entrada', 10, 'Estoque inicial da remessa #001'),
(3, 1, 'entrada', 15, 'Estoque inicial da remessa #001'),
(4, 1, 'entrada', 10, 'Estoque inicial da remessa #001'),
(5, 1, 'entrada', 12, 'Estoque inicial da remessa #001'),
(6, 1, 'entrada', 5, 'Estoque inicial da remessa #001');

-- 8. Entregas Realizadas no Mês
INSERT INTO `entregas` (`funcionario_id`, `variacao_id`, `usuario_id`, `quantidade`, `observacao`) VALUES
(1, 1, 2, 2, 'Kit inicial de admissão'),
(1, 4, 2, 2, 'Kit inicial de admissão'),
(2, 2, 2, 3, 'Reposição periódica de uniforme'),
(3, 1, 2, 1, 'Troca por desgaste natural'),
(3, 6, 2, 1, 'Entrega de EPI calçado');
