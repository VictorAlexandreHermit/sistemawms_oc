-- Migration 000002: Seed de dados iniciais de operação.
-- Usuários padrão, SLAs, endereço virtual de quarentena, endereços exemplos e produtos.

-- Usuários padrão (Perfil Gestor e Operador)
INSERT INTO usuarios (matricula, senha_hash, nome_completo, perfil) VALUES
    ('GESTOR01', '$2y$10$4E//gJnBFNQVFD7vQkstre2Nz0YNyhGbq9txmntzvMqndGqfbttPG', 'Gestor Demonstração', 'GESTOR'),
    ('OPERADOR01', '$2y$10$CvZu7IpC0jheWzehcHtjJOlXSt63BYF1F4OTglZTmjbpPbX9jfp7u', 'Operador Demonstração', 'OPERADOR');

-- Configurações de SLA padrão (120 minutos por etapa do Kanban)
INSERT INTO configuracoes_sla (etapa_kanban, tempo_limite_minutos) VALUES
    ('RECEBIDO', 120),
    ('A_ARMAZENAR', 120),
    ('A_SEPARAR', 120),
    ('A_EXPEDIR', 120);

-- Endereço virtual de Quarentena
INSERT INTO enderecos (rua, predio, nivel, descricao, capacidade_maxima, quarantena) VALUES
    ('QTR', 'VIRT', '000', 'Endereço virtual de quarentena de avarias', 1000000, 1);

-- Endereços físicos de exemplo (galpão)
INSERT INTO enderecos (rua, predio, nivel, descricao, capacidade_maxima) VALUES
    ('R01', 'P01', 'N01', 'Corredor 1 - Prédio 1 - Nível 1', 1000),
    ('R01', 'P01', 'N02', 'Corredor 1 - Prédio 1 - Nível 2', 1000),
    ('R01', 'P02', 'N01', 'Corredor 1 - Prédio 2 - Nível 1', 1000),
    ('R02', 'P01', 'N01', 'Corredor 2 - Prédio 1 - Nível 1', 1000),
    ('R02', 'P01', 'N02', 'Corredor 2 - Prédio 1 - Nível 2', 1000),
    ('R02', 'P02', 'N01', 'Corredor 2 - Prédio 2 - Nível 1', 1000),
    ('R03', 'P01', 'N01', 'Corredor 3 - Prédio 1 - Nível 1', 1000),
    ('R03', 'P01', 'N02', 'Corredor 3 - Prédio 1 - Nível 2', 1000);

-- Produtos de exemplo
INSERT INTO produtos (sku, codigo_barras, descricao, unidade_medida, curva_abc) VALUES
    ('SKU-001', '7891000010011', 'Parafuso Inox 5/16 x 1" (cx 500)', 'CX', 'A'),
    ('SKU-002', '7891000020028', 'Arruela Lisa 3/8 Zincada (cx 1000)', 'CX', 'B'),
    ('SKU-003', '7891000030035', 'Porca Sextavada 3/8 (cx 200)', 'CX', 'A'),
    ('SKU-004', '7891000040042', 'Fita Isolante Preta 20m', 'UN', 'C'),
    ('SKU-005', '7891000050059', 'Lâmpada LED Bulbo 9W Branca', 'UN', 'A'),
    ('SKU-006', '7891000060066', 'Tomada 2P+T 10A Branca', 'UN', 'B'),
    ('SKU-007', '7891000070073', 'Disjuntor 16A Monofásico', 'UN', 'A'),
    ('SKU-008', '7891000080080', 'Cabo Flexível 2,5mm (rolo 100m)', 'RL', 'C');