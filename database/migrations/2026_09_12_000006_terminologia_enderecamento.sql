-- Migration 000006: Nova terminologia de endereçamento físico.
-- Rua -> Corredor | Prédio -> Galpão | Nível -> Prateleira.
-- Converte colunas, códigos existentes (Rxx->Cxx, Pxx->Gxx, Nxx->Pxx) e descrições.

ALTER TABLE enderecos
    CHANGE COLUMN rua      corredor    VARCHAR(10) NOT NULL,
    CHANGE COLUMN predio   galpao      VARCHAR(10) NOT NULL,
    CHANGE COLUMN nivel    prateleira  VARCHAR(10) NOT NULL;

-- Converte os códigos da nomenclatura antiga para a nova.
UPDATE enderecos SET corredor   = CONCAT('C', SUBSTRING(corredor, 2))   WHERE corredor   LIKE 'R%';
UPDATE enderecos SET galpao     = CONCAT('G', SUBSTRING(galpao, 2))     WHERE galpao     LIKE 'P%';
UPDATE enderecos SET prateleira = CONCAT('P', SUBSTRING(prateleira, 2)) WHERE prateleira LIKE 'N%';

-- Atualiza as descrições legíveis para a nova nomenclatura.
UPDATE enderecos SET descricao = REPLACE(REPLACE(descricao, 'Prédio', 'Galpão'), 'Nível', 'Prateleira');