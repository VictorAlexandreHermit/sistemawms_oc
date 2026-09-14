-- 2026_09_14_000010_sku_sis_e_codigo_barras_nao_unico.sql
-- 1. Libera repetir o MESMO código de barras em produtos diferentes:
--    a movimentação passa a ser controlada pelo SKU interno (SIS-###),
--    que é único por produto ("código de barras interno à prova de erro").
--    Remove a UNIQUE KEY e mantém apenas o índice comum para busca.
ALTER TABLE produtos DROP INDEX codigo_barras;