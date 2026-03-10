-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 09/03/2026 às 23:35
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `wave`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem_url` varchar(500) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `preco_promo` decimal(10,2) DEFAULT NULL,
  `estoque` int(11) NOT NULL DEFAULT 0,
  `estoque_min` int(11) DEFAULT 5,
  `categoria` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `imagem_url`, `preco`, `preco_promo`, `estoque`, `estoque_min`, `categoria`, `sku`, `ativo`, `destaque`, `criado_em`, `atualizado_em`) VALUES
(1, 'Notebook Dell', 'Notebook Dell Inspiron 15 - Processador i7', NULL, 3500.00, NULL, 10, 5, 'Eletrônicos', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 01:05:18'),
(2, 'Mouse Logitech', 'Mouse sem fio Logitech M170', NULL, 45.00, NULL, 50, 5, 'Periféricos', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 01:05:15'),
(3, 'Teclado Mecânico', 'Teclado Mecânico RGB com switches Cherry', NULL, 250.00, NULL, 25, 5, 'Periféricos', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 00:50:14'),
(4, 'Monitor LG 24\"', 'Monitor LG 24 polegadas Full HD', NULL, 800.00, NULL, 15, 5, 'Monitores', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 01:05:16'),
(5, 'Webcam HD', 'Webcam HD 1080p com microfone integrado', NULL, 150.00, NULL, 30, 5, 'Periféricos', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 00:50:14'),
(6, 'SSD 240GB', 'SSD Kingston 240GB', NULL, 180.00, NULL, 20, 5, 'Armazenamento', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 00:50:14'),
(7, 'Headset Gamer', 'Headset Gamer com som surround 7.1', NULL, 320.00, NULL, 12, 5, 'Audio', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 01:05:16'),
(8, 'Mousepad Grande', 'Mousepad grande resistente', NULL, 80.00, NULL, 40, 5, 'Acessórios', NULL, 1, 0, '2026-03-07 00:50:14', '2026-03-07 00:50:14'),
(9, 'KIT FACAS', 'afafafa', NULL, 12.90, NULL, 100, 5, 'Anéis', NULL, 1, 0, '2026-03-07 01:06:13', '2026-03-07 01:06:13'),
(10, 'Kit Facas', 'fafafa', NULL, 19.90, NULL, 19000, 5, 'Brincos', NULL, 1, 0, '2026-03-07 01:06:52', '2026-03-07 01:06:52'),
(11, 'Camera de Segurança', 'produto para quem quer ser uma diva', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTOrl05NfiX377uoWte5H-6oNI0ZAq3Fu3w8g&s', 19.90, 9.90, 100, 5, '0', 'WAV-010', 1, 0, '2026-03-07 01:22:44', '2026-03-07 01:22:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','usuario') DEFAULT 'usuario',
  `telefone` varchar(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel`, `telefone`, `criado_em`) VALUES
(1, 'Arthur Pereira', ' nexus.suporte.26@gmail.com', 'Nexus@2026', 'admin', NULL, '2026-03-04 18:28:12'),
(6, 'Admin Wave', 'admin@wave.com.br', '$2y$10$vUScbpbAXul8UTwxNx7w7.TIs1Ws.2WgtL4CrEkfN3BFkorh6Si5q', 'admin', NULL, '2026-03-06 18:39:21'),
(8, 'Luciano', 'fsacramento873@gmail.com', '$2y$10$vS3WhGHQKZlOmcJ5kQ5l3O/lKKWo5ajhbqTV3aAF0PgXvUqVutCha', 'usuario', '2197187660', '2026-03-07 00:52:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `numero_venda` varchar(50) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_venda` datetime DEFAULT current_timestamp(),
  `total_valor` decimal(10,2) DEFAULT 0.00,
  `total_itens` int(11) DEFAULT 0,
  `status` enum('pendente','confirmada','enviada','entregue','cancelada') DEFAULT 'confirmada',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `numero_venda`, `usuario_id`, `data_venda`, `total_valor`, `total_itens`, `status`, `observacoes`, `criado_em`) VALUES
(1, 'V-001', 2, '2026-03-07 10:30:00', 189.80, 2, 'entregue', NULL, '2026-03-07 18:47:02'),
(2, 'V-002', 3, '2026-03-07 14:15:00', 99.90, 1, 'entregue', NULL, '2026-03-07 18:47:02'),
(3, 'V-003', 2, '2026-03-06 09:45:00', 279.70, 3, 'confirmada', NULL, '2026-03-07 18:47:02'),
(4, 'V-004', 3, '2026-03-05 16:20:00', 149.85, 2, 'enviada', NULL, '2026-03-07 18:47:02'),
(5, 'V-005', 2, '2026-03-04 11:00:00', 89.90, 1, 'entregue', NULL, '2026-03-07 18:47:02'),
(6, 'V-006', 3, '2026-03-04 15:30:00', 259.80, 3, 'entregue', NULL, '2026-03-07 18:47:02'),
(7, 'V-007', 2, '2026-03-03 10:00:00', 179.80, 2, 'entregue', NULL, '2026-03-07 18:47:02'),
(8, 'V-008', 3, '2026-03-02 13:45:00', 399.70, 4, 'entregue', NULL, '2026-03-07 18:47:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas_itens`
--

CREATE TABLE `vendas_itens` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_venda` (`numero_venda`),
  ADD KEY `idx_vendas_usuario` (`usuario_id`),
  ADD KEY `idx_vendas_data` (`data_venda`),
  ADD KEY `idx_vendas_status` (`status`);

--
-- Índices de tabela `vendas_itens`
--
ALTER TABLE `vendas_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendas_itens_venda` (`venda_id`),
  ADD KEY `idx_vendas_itens_produto` (`produto_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `vendas_itens`
--
ALTER TABLE `vendas_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
