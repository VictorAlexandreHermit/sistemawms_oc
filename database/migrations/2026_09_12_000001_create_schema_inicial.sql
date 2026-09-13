-- Migration 000001: Criação do schema inicial do WMS Agiliza
-- 14 tabelas principais + índices de desempenho.

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    perfil ENUM('OPERADOR', 'GESTOR') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_matricula (matricula),
    INDEX idx_perfil (perfil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    codigo_barras VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    unidade_medida VARCHAR(10) NOT NULL DEFAULT 'UN',
    curva_abc ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_sku (sku),
    INDEX idx_codigo_barras (codigo_barras),
    INDEX idx_curva_abc (curva_abc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rua VARCHAR(10) NOT NULL,
    predio VARCHAR(10) NOT NULL,
    nivel VARCHAR(10) NOT NULL,
    descricao VARCHAR(100) NULL,
    capacidade_maxima INT NOT NULL DEFAULT 1000,
    quarantena TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_endereco_fisico (rua, predio, nivel),
    INDEX idx_rua_predio_nivel (rua, predio, nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE estoque_saldos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    lote VARCHAR(50) NULL,
    data_validade DATE NULL,
    status_saldo ENUM('DISPONIVEL', 'QUARENTENA') NOT NULL DEFAULT 'DISPONIVEL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    CONSTRAINT chk_quantidade_positiva CHECK (quantidade >= 0),
    INDEX idx_produto_endereco (produto_id, endereco_id),
    INDEX idx_status_saldo (status_saldo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_nota_xml VARCHAR(50) NOT NULL,
    chave_nfe VARCHAR(44) NOT NULL UNIQUE,
    fornecedor_nome VARCHAR(150) NULL,
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_contato VARCHAR(100) NULL,
    status_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR', 'ENTREGUE') NOT NULL DEFAULT 'RECEBIDO',
    prioridade_abc ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    token_otif VARCHAR(64) NOT NULL UNIQUE,
    ts_recebido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ts_a_armazenar TIMESTAMP NULL,
    ts_a_separar TIMESTAMP NULL,
    ts_a_expedir TIMESTAMP NULL,
    ts_entregue TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_kanban (status_kanban),
    INDEX idx_prioridade_abc (prioridade_abc),
    INDEX idx_token_otif (token_otif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade_esperada INT NOT NULL,
    quantidade_conferida INT NOT NULL DEFAULT 0,
    quantidade_bipada_picking INT NOT NULL DEFAULT 0,
    status_conferencia ENUM('PENDENTE', 'CONFERENCIA_EM_ANDAMENTO', 'CONFERIDO', 'DIVERGENTE', 'PICKING_PARCIAL', 'PICKING_COMPLETO') NOT NULL DEFAULT 'PENDENTE',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    INDEX idx_pedido_produto (pedido_id, produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE divergencias_recebimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    qtd_xml INT NOT NULL,
    qtd_fisica INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    status_aprovacao ENUM('PENDENTE', 'APROVADO', 'REJEITADO') NOT NULL DEFAULT 'PENDENTE',
    tratado_por INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (tratado_por) REFERENCES usuarios(id),
    INDEX idx_status_aprovacao (status_aprovacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE avarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    pedido_id INT NULL,
    quantidade INT NOT NULL,
    foto_url VARCHAR(255) NULL,
    motivo VARCHAR(255) NOT NULL,
    operador_id INT NOT NULL,
    etapa ENUM('RECEBIMENTO', 'SEPARACAO', 'OUTROS') NOT NULL DEFAULT 'OUTROS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE logs_auditoria_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    quantidade_anterior INT NOT NULL,
    quantidade_nova INT NOT NULL,
    motivo_codigo VARCHAR(50) NOT NULL,
    observacoes VARCHAR(500) NULL,
    operador_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id),
    INDEX idx_auditoria_produto (produto_id),
    INDEX idx_auditoria_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pesquisas_otif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL UNIQUE,
    token_acesso VARCHAR(64) NOT NULL UNIQUE,
    prazo_cumprido ENUM('SIM', 'NAO') NULL,
    sem_avaria ENUM('SIM', 'NAO') NULL,
    conformidade_itens ENUM('SIM', 'NAO') NULL,
    foto_1_url VARCHAR(255) NULL,
    foto_2_url VARCHAR(255) NULL,
    observacoes TEXT NULL,
    data_envio DATETIME NULL,
    data_expiracao DATETIME NOT NULL,
    respondido_em DATETIME NULL,
    status_envio ENUM('PENDENTE', 'ENVIADO', 'FALHA') DEFAULT 'PENDENTE',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_token (token_acesso),
    INDEX idx_data_expiracao (data_expiracao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE configuracoes_sla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etapa_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR') NOT NULL UNIQUE,
    tempo_limite_minutos INT NOT NULL DEFAULT 120,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE logs_erro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    linha INT NOT NULL,
    trace TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE logs_seguranca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(100) NOT NULL,
    ip_origem VARCHAR(45) NOT NULL,
    usuario_id INT NULL,
    detalhes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;