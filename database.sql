-- =====================================================
-- NAMPULA FODD - Banco de Dados MySQL
-- iFood de Nampula e Nacala
-- =====================================================

CREATE DATABASE IF NOT EXISTS nampula_fodd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nampula_fodd;

-- -----------------------------------------------------
-- Tabela: usuarios
-- Tipos: cliente, restaurante, admin
-- -----------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    senha VARCHAR(255) NOT NULL,          -- hash com password_hash()
    tipo ENUM('cliente', 'restaurante', 'admin') NOT NULL DEFAULT 'cliente',
    saldo_carteira DECIMAL(10,2) DEFAULT 0.00,  -- E-Wallet do cliente
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: restaurantes
-- -----------------------------------------------------
CREATE TABLE restaurantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,               -- dono do restaurante
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    cidade ENUM('Nampula', 'Nacala-Porto', 'Nacala-a-Velha') NOT NULL,
    tipo_comida VARCHAR(100),              -- ex: Moçambicana, Pizza, Fast Food
    endereco VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    foto VARCHAR(255) DEFAULT 'default-rest.jpg',
    aberto TINYINT(1) DEFAULT 1,           -- 1 = aberto, 0 = fechado
    saldo_a_receber DECIMAL(10,2) DEFAULT 0.00,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: produtos (cardápio)
-- -----------------------------------------------------
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(80) DEFAULT 'Geral', -- ex: Entradas, Pratos, Bebidas
    foto VARCHAR(255) DEFAULT 'default-prato.jpg',
    prato_do_dia TINYINT(1) DEFAULT 0,     -- 1 = sim
    desconto_prato_dia DECIMAL(5,2) DEFAULT 0.00, -- % de desconto
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: pedidos
-- -----------------------------------------------------
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    restaurante_id INT NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    minha_comissao_10 DECIMAL(10,2) NOT NULL,   -- 10% da plataforma
    valor_restaurante_90 DECIMAL(10,2) NOT NULL, -- 90% para o restaurante
    status ENUM('pendente', 'aceito', 'recusado', 'em_preparo', 'entregue', 'cancelado') DEFAULT 'pendente',
    forma_pagamento ENUM('carteira', 'entrega') DEFAULT 'entrega',
    status_saque ENUM('nao_solicitado', 'solicitado', 'pago') DEFAULT 'nao_solicitado',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: itens_pedido (detalhes do carrinho)
-- -----------------------------------------------------
CREATE TABLE itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: comentarios
-- Só pode comentar depois de comprar (pedido entregue)
-- -----------------------------------------------------
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    restaurante_id INT NOT NULL,
    pedido_id INT NOT NULL,                -- garante que comprou
    nota TINYINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    texto TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: carteira (histórico de movimentos)
-- -----------------------------------------------------
CREATE TABLE carteira (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('deposito', 'pagamento', 'comissao', 'saque') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(255),
    pedido_id INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Dados iniciais de demonstração
-- -----------------------------------------------------

-- Admin (senha: admin123)
INSERT INTO usuarios (nome, email, telefone, senha, tipo, saldo_carteira) VALUES
('Admin Nampula Fodd', 'admin@nampulafodd.co.mz', '840000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00);

-- Cliente de teste (senha: 123456)
INSERT INTO usuarios (nome, email, telefone, senha, tipo, saldo_carteira) VALUES
('João Cliente', 'cliente@teste.com', '841111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 500.00);

-- Dono de restaurante (senha: 123456)
INSERT INTO usuarios (nome, email, telefone, senha, tipo) VALUES
('Maria Restaurante', 'maria@restaurante.com', '842222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restaurante');

-- Restaurante de exemplo
INSERT INTO restaurantes (usuario_id, nome, descricao, cidade, tipo_comida, endereco, latitude, longitude, foto, aberto) VALUES
(3, 'Churrasqueira Nampula', 'Os melhores grelhados da cidade', 'Nampula', 'Moçambicana', 'Av. Eduardo Mondlane, Nampula', -15.11670000, 39.26670000, 'default-rest.jpg', 1);

-- Produtos de exemplo
INSERT INTO produtos (restaurante_id, nome, descricao, preco, categoria, prato_do_dia, desconto_prato_dia) VALUES
(1, 'Frango Grelhado Completo', 'Frango inteiro grelhado com batata e salada', 450.00, 'Pratos Principais', 1, 10.00),
(1, 'Matapa com Camarão', 'Matapa tradicional com camarão fresco', 380.00, 'Pratos Principais', 0, 0),
(1, 'Sumo de Manga', 'Sumo natural de manga', 80.00, 'Bebidas', 0, 0);

-- Nota: A senha hash acima é o hash de "password". 
-- Para produção use password_hash('sua_senha', PASSWORD_DEFAULT)
-- No código PHP de login usaremos password_verify().
