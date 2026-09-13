<?php
/**
 * app/models/UsuarioModel.php
 * Contas e credenciais de operadores e gestores.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class UsuarioModel
{
    public static function buscarPorMatricula(string $matricula): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM usuarios
                WHERE matricula = :matricula AND deleted_at IS NULL
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':matricula' => $matricula]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function autenticar(string $matricula, string $senha): ?array
    {
        $usuario = self::buscarPorMatricula($matricula);
        if ($usuario === null) {
            return null;
        }
        if ((int) $usuario['ativo'] !== 1) {
            return null;
        }
        if (!password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }
        return $usuario;
    }

    public static function listar($somenteAtivos = true): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT id, matricula, nome_completo, perfil, ativo, deleted_at, created_at
                FROM usuarios';
        if ($somenteAtivos) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        $sql .= ' ORDER BY nome_completo';
        return $pdo->query($sql)->fetchAll();
    }

    public static function verificarContaAtiva(int $usuarioId): bool
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT ativo FROM usuarios WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $usuarioId]);
        $reg = $stmt->fetch();
        return $reg !== false && (int) $reg['ativo'] === 1;
    }
}