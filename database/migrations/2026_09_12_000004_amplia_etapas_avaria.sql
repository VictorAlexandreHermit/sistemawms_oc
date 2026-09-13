-- Migration 000004: Amplia as etapas de avaria aceitas pelo ENUM de avarias
-- (sincroniza com as etapas do AvariasController: RECEBIMENTO, ARMAZENAGEM,
-- SEPARACAO, EMBALAGEM, EXPEDICAO, DEVOLUCAO, OUTROS).

ALTER TABLE avarias
    MODIFY COLUMN etapa ENUM('RECEBIMENTO', 'ARMAZENAGEM', 'SEPARACAO', 'EMBALAGEM', 'EXPEDICAO', 'DEVOLUCAO', 'OUTROS')
        NOT NULL DEFAULT 'OUTROS';