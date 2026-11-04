-- Sistema de Fiado - banco de dados (funciona no XAMPP e na InfinityFree, sem stored procedures)
-- COMO USAR: no phpMyAdmin SELECIONE o banco, va em "Importar" e envie este arquivo.
-- Pode importar mais de uma vez: as tabelas so sao criadas se nao existirem e os dados de exemplo so entram se a tabela estiver vazia.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(200),
    limite_credito DECIMAL(10,2) DEFAULT 500.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin','comum','cliente') NOT NULL DEFAULT 'comum',
    status ENUM('pendente','aprovado','recusado','bloqueado') NOT NULL DEFAULT 'aprovado',
    cliente_id INT NULL,
    telefone_solicitado VARCHAR(20) NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao VARCHAR(200),
    valor DECIMAL(10,2) NOT NULL,
    data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET FOREIGN_KEY_CHECKS = 1;
-- DADOS DE EXEMPLO (so entram se a tabela estiver vazia)
-- admin / admin123 (administrador) e usuario / user123 (usuario comum). TROQUE AS SENHAS depois do primeiro login (menu "Minha senha").
INSERT INTO usuarios (nome, usuario, senha, nivel)
SELECT x.nome, x.usuario, x.senha, x.nivel FROM (
    SELECT 'Administrador' AS nome, 'admin' AS usuario, '$2y$10$MJxxI0F.bM2VFWJck879Vu0Hjgf9SUqQP3EKnVxW0uqApbfxhlO/S' AS senha, 'admin' AS nivel
    UNION ALL
    SELECT 'Atendente', 'usuario', '$2y$10$0HMHeAXinJ1McZeqFdMlTuNUpoM9n/jHCTstb0Kb/KVZJ.b9Q/dqy', 'comum'
) x WHERE NOT EXISTS (SELECT 1 FROM usuarios);
INSERT INTO clientes (nome, telefone, endereco, limite_credito)
SELECT x.nome, x.telefone, x.endereco, x.limite FROM (
    SELECT 'João Silva' AS nome, '(62) 99999-9999' AS telefone, 'Rua das Flores, 123' AS endereco, 500.00 AS limite
    UNION ALL SELECT 'Maria Souza', '(62) 98888-8888', 'Av. Goiás, 456', 300.00
    UNION ALL SELECT 'Carlos Pereira', '(62) 97777-7777', 'Rua 10, 89', 400.00
    UNION ALL SELECT 'Ana Lima', '(62) 96666-6666', 'Rua 5, 22', 200.00
) x WHERE NOT EXISTS (SELECT 1 FROM clientes);
INSERT INTO compras (cliente_id, descricao, valor, data_compra)
SELECT c.id, x.descricao, x.valor, NOW() - INTERVAL x.dias DAY FROM (
    SELECT '(62) 99999-9999' AS telefone, 'Arroz e feijão' AS descricao, 45.00 AS valor, 10 AS dias
    UNION ALL SELECT '(62) 99999-9999', 'Refrigerante', 15.00, 5
    UNION ALL SELECT '(62) 98888-8888', 'Pão e manteiga', 20.00, 3
    UNION ALL SELECT '(62) 97777-7777', 'Compra do mês', 180.00, 45
    UNION ALL SELECT '(62) 96666-6666', 'Frios', 60.00, 20
) x JOIN clientes c ON c.telefone = x.telefone WHERE NOT EXISTS (SELECT 1 FROM compras);
INSERT INTO pagamentos (cliente_id, valor, data_pagamento)
SELECT c.id, x.valor, NOW() - INTERVAL x.dias DAY FROM (
    SELECT '(62) 98888-8888' AS telefone, 10.00 AS valor, 1 AS dias
    UNION ALL SELECT '(62) 97777-7777', 50.00, 30
    UNION ALL SELECT '(62) 96666-6666', 60.00, 15
) x JOIN clientes c ON c.telefone = x.telefone WHERE NOT EXISTS (SELECT 1 FROM pagamentos);
