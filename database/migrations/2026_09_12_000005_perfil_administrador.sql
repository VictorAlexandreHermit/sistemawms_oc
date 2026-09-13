-- Migration 000005: Perfil mestre ADMINISTRADOR.
-- Amplia o ENUM de perfis e cria a conta de administração (senha padrão: admin123).

ALTER TABLE usuarios
    MODIFY COLUMN perfil ENUM('OPERADOR', 'GESTOR', 'ADMINISTRADOR') NOT NULL;

-- Conta mestre: vê todas as telas e pode criar/gerir logins.
INSERT INTO usuarios (matricula, senha_hash, nome_completo, perfil) VALUES
    ('ADMINISTRADOR', '$2y$10$juVts4pcSrbDTje7G5zXFOUWGyHLfAIJgV4DbLNZws9WT/GyYJDF.', 'Administrador Mestre', 'ADMINISTRADOR');