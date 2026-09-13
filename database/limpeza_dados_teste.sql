-- =============================================================
-- Limpeza total dos DADOS DE TESTE / DEMONSTRAÇÃO do WMS Agiliza
-- Executar no phpMyAdmin da hospedagem (aba SQL) OU no localhost.
--
-- IMPORTANTE: este script NÃO apaga:
--   * usuarios       (contas de acesso reais criadas pelo Gestor)
--   * enderecos      (layout físico do galpão + Quarentena)
--   * configuracoes_sla (SLAs do Kanban)
--   * schema_migrations (controle de versão do banco)
--
-- Resultado: site "como se nunca tivesse sido usado".
-- Quem desejar remover também contas de login, use o menu
-- Usuários e Contas (a nova opção "Excluir") na interface.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Respostas e envios de avaliação OTIF
DELETE FROM pesquisas_otif;

-- Divergências de conferência do recebimento
DELETE FROM divergencias_recebimento;

-- Avarias e quarentena
DELETE FROM avarias;

-- Itens e pedidos/cargas (inclusive o histórico de entregues)
DELETE FROM pedido_itens;
DELETE FROM pedidos;

-- Saldos de estoque (zera a ocupação do Dashboard)
DELETE FROM estoque_saldos;

-- Logs de movimentação/auditoria
DELETE FROM logs_auditoria_estoque;

-- Logs de aplicação e segurança
DELETE FROM logs_erro;
DELETE FROM logs_seguranca;

-- Catálogo de produtos de demonstração (ex.: SKU-001 "parafuso inox")
DELETE FROM produtos;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Verificação final (deve retornar 0 em todas as linhas)
-- =============================================================
SELECT 'produtos' AS tabela, COUNT(*) AS registros FROM produtos
UNION ALL SELECT 'estoque_saldos', COUNT(*) FROM estoque_saldos
UNION ALL SELECT 'pedidos', COUNT(*) FROM pedidos
UNION ALL SELECT 'pedido_itens', COUNT(*) FROM pedido_itens
UNION ALL SELECT 'avarias', COUNT(*) FROM avarias
UNION ALL SELECT 'pesquisas_otif', COUNT(*) FROM pesquisas_otif
UNION ALL SELECT 'divergencias_recebimento', COUNT(*) FROM divergencias_recebimento
UNION ALL SELECT 'logs_erro', COUNT(*) FROM logs_erro
UNION ALL SELECT 'logs_seguranca', COUNT(*) FROM logs_seguranca
UNION ALL SELECT 'logs_auditoria_estoque', COUNT(*) FROM logs_auditoria_estoque
ORDER BY tabela;