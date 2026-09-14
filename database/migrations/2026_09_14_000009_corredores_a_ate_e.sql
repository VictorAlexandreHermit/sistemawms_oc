-- database/migrations/2026_09_14_000009_corredores_a_ate_e.sql
-- Renomeia corredores de C01-C05 para A-E, mantendo galpão único G01
-- e prateleiras P01-P05. O log de auditoria referencia endereco_id (FK),
-- então a nova nomenclatura já é refletida automaticamente nas consultas.

UPDATE enderecos SET corredor = 'A' WHERE corredor = 'C01';
UPDATE enderecos SET corredor = 'B' WHERE corredor = 'C02';
UPDATE enderecos SET corredor = 'C' WHERE corredor = 'C03';
UPDATE enderecos SET corredor = 'D' WHERE corredor = 'C04';
UPDATE enderecos SET corredor = 'E' WHERE corredor = 'C05';

UPDATE enderecos
SET descricao = CONCAT('Corredor ', corredor, ' - Galpão ', galpao, ' - Prateleira ', prateleira)
WHERE corredor IN ('A','B','C','D','E') AND galpao = 'G01';