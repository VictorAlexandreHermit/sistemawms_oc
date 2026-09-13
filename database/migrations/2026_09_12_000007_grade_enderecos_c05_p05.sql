-- database/migrations/2026_09_12_000007_grade_enderecos_c05_p05.sql
-- Grade completa de endereços: 5 corredores (C01..C05) x 5 prateleiras (P01..P05)
-- no galpão único G01. INSERT IGNORE preserva posições já existentes.
-- SQL mantido 100% ASCII: "ã" é montado via CHAR(195,163) para evitar mojibake
-- quando o arquivo for aplicado por cliente cujo terminal mude a codificação.

INSERT IGNORE INTO enderecos (corredor, galpao, prateleira, descricao, capacidade_maxima)
SELECT c.codigo, 'G01', p.codigo,
       CONCAT('Corredor ', 0 + SUBSTRING(c.codigo, 2),
              ' - Galp', CHAR(195), CHAR(163), 'o 1 - Prateleira ', 0 + SUBSTRING(p.codigo, 2)),
       1000
FROM (
    SELECT 'C01' AS codigo UNION ALL SELECT 'C02' UNION ALL
    SELECT 'C03'           UNION ALL SELECT 'C04' UNION ALL
    SELECT 'C05'
) c
CROSS JOIN (
    SELECT 'P01' AS codigo UNION ALL SELECT 'P02' UNION ALL
    SELECT 'P03'           UNION ALL SELECT 'P04' UNION ALL
    SELECT 'P05'
) p;