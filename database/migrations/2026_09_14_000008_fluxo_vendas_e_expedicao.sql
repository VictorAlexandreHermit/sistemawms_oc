-- database/migrations/2026_09_14_000008_fluxo_vendas_e_expedicao.sql
-- Desacopla o fluxo de VENDAS (outbound) do Recebimento (inbound):
--   * pedidos ganham o tipo RECEBIMENTO|VENDA;
--   * novos estagios de kanban: ARMAZENADO (repouso apos a guarda),
--     A_EMBALAR (packing) e EM_TRANSITO (rota de entrega);
--   * timestamps para os novos estagios;
--   * SLAs iniciais para as novas etapas do pipeline.

ALTER TABLE pedidos
    ADD COLUMN tipo ENUM('RECEBIMENTO', 'VENDA') NOT NULL DEFAULT 'RECEBIMENTO' AFTER chave_nfe,
    MODIFY COLUMN status_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'ARMAZENADO', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO', 'ENTREGUE') NOT NULL DEFAULT 'RECEBIDO',
    ADD COLUMN ts_armazenado TIMESTAMP NULL AFTER ts_a_armazenar,
    ADD COLUMN ts_a_embalar TIMESTAMP NULL AFTER ts_a_separar,
    ADD COLUMN ts_em_transito TIMESTAMP NULL AFTER ts_a_expedir;

ALTER TABLE configuracoes_sla
    MODIFY COLUMN etapa_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'ARMAZENADO', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO') NOT NULL;

INSERT INTO configuracoes_sla (etapa_kanban, tempo_limite_minutos, updated_by)
SELECT e.etapa, 120, NULL
FROM (
    SELECT 'ARMAZENADO' AS etapa UNION ALL SELECT 'A_EMBALAR' UNION ALL SELECT 'EM_TRANSITO'
) e
WHERE NOT EXISTS (
    SELECT 1 FROM configuracoes_sla c WHERE c.etapa_kanban = e.etapa
);