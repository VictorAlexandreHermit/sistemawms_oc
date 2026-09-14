<?php
/**
 * app/models/EnderecoModel.php
 * Estrutura física do galpão (Corredor - Galpão - Prateleira).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class EnderecoModel
{
    public static function buscarPorId(int $id, $incluirExcluidos = false): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM enderecos WHERE id = :id';
        if (!$incluirExcluidos) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function buscarPorCodigo(string $codigo): ?array
    {
        $code = strtoupper(trim($codigo));
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM enderecos
                WHERE deleted_at IS NULL
                  AND (UPPER(CONCAT(corredor, "-", galpao, "-", prateleira)) = :codigo OR UPPER(CONCAT(corredor, galpao, prateleira)) = :codigo2)
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $code,
            ':codigo2' => str_replace('-', '', $code),
        ]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function quarentena(): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM enderecos WHERE quarantena = 1 AND deleted_at IS NULL LIMIT 1');
        $stmt->execute();
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    /**
     * Endereços físicos (não-quarentena) com o total de unidades ocupadas,
     * ordenados em ordem crescente (corredor, galpão, prateleira).
     * Cada linha traz também a flag "cheio" (usado >= capacidade_maxima),
     * usada pelos selects em cascata de Avarias/Auditoria.
     */
    public static function listarComOcupacao(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT e.id, e.corredor, e.galpao, e.prateleira, e.descricao, e.capacidade_maxima,
                       COALESCE(SUM(CASE WHEN es.quantidade > 0 AND es.status_saldo = "DISPONIVEL" THEN es.quantidade END), 0) AS usado
                FROM enderecos e
                LEFT JOIN estoque_saldos es ON es.endereco_id = e.id
                WHERE e.deleted_at IS NULL AND e.quarantena = 0
                GROUP BY e.id
                ORDER BY e.corredor ASC, e.galpao ASC, e.prateleira ASC';
        $enderecos = $pdo->query($sql)->fetchAll();
        foreach ($enderecos as &$e) {
            $e['cheio'] = (int) $e['usado'] >= (int) $e['capacidade_maxima'] ? 1 : 0;
        }
        unset($e);
        return $enderecos;
    }

    /**
     * Primeiro endereço físico com espaço em ordem crescente (ex.: A-G01-P01).
     * Quando o corredor A está cheio, o próximo livre (ex.: B-G01-P01) é sugerido.
     */
    public static function enderecoSugerido(): ?array
    {
        foreach (self::listarComOcupacao() as $e) {
            if ((int) $e['cheio'] === 0) {
                return $e;
            }
        }
        return null;
    }

    public static function listar(?string $termo = null, $incluirExcluidos = false): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT e.*,
                       (SELECT COUNT(*) FROM estoque_saldos es WHERE es.endereco_id = e.id AND es.quantidade > 0) AS posicoes_ocupadas
                FROM enderecos e WHERE 1=1';
        if (!$incluirExcluidos) {
            $sql .= ' AND e.deleted_at IS NULL';
        }
        if ($termo !== null && trim($termo) !== '') {
            $sql .= ' AND (e.corredor LIKE :t OR e.galpao LIKE :t OR e.prateleira LIKE :t OR CONCAT(e.corredor,\'-\',e.galpao,\'-\',e.prateleira) LIKE :t)';
        }
        $sql .= ' ORDER BY e.quarantena ASC, e.corredor ASC, e.galpao ASC, e.prateleira ASC';
        $stmt = $pdo->prepare($sql);
        if ($termo !== null && trim($termo) !== '') {
            $stmt->execute([':t' => '%' . $termo . '%']);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public static function salvar(array $dados, ?int $id = null): int
    {
        $pdo = Database::conexao();
        $dados['corredor']    = strtoupper(trim($dados['corredor']));
        $dados['galpao']      = GALPAO_UNICO; // Galpão único do sistema
        $dados['prateleira']  = strtoupper(trim($dados['prateleira']));

        if ($id === null) {
            // Reaproveita posição eventualmente soft-deletada: o índice único
            // uk_endereco_fisico considera também linhas com deleted_at preenchido.
            $stmt = $pdo->prepare('SELECT id FROM enderecos
                                   WHERE corredor = :c AND galpao = :g AND prateleira = :p
                                     AND quarantena != 1 LIMIT 1');
            $stmt->execute([
                ':c' => $dados['corredor'],
                ':g' => GALPAO_UNICO,
                ':p' => $dados['prateleira'],
            ]);
            $reuso = $stmt->fetch();
            if ($reuso) {
                $stmt = $pdo->prepare('UPDATE enderecos
                                       SET descricao = :descricao, capacidade_maxima = :capacidade, deleted_at = NULL
                                       WHERE id = :id');
                $stmt->execute([
                    ':descricao'  => $dados['descricao'] ?? '',
                    ':capacidade' => (int) ($dados['capacidade_maxima'] ?? 1000),
                    ':id'         => (int) $reuso['id'],
                ]);
                return (int) $reuso['id'];
            }

            $sql = 'INSERT INTO enderecos (corredor, galpao, prateleira, descricao, capacidade_maxima)
                    VALUES (:corredor, :galpao, :prateleira, :descricao, :capacidade)';
        } else {
            $sql = 'UPDATE enderecos
                    SET corredor = :corredor, galpao = :galpao, prateleira = :prateleira,
                        descricao = :descricao, capacidade_maxima = :capacidade
                    WHERE id = :id AND deleted_at IS NULL';
        }
        $stmt = $pdo->prepare($sql);
        $params = [
            ':corredor'    => $dados['corredor'],
            ':galpao'      => $dados['galpao'],
            ':prateleira'  => $dados['prateleira'],
            ':descricao'   => $dados['descricao'] ?? '',
            ':capacidade'  => (int) ($dados['capacidade_maxima'] ?? 1000),
        ];
        if ($id !== null) {
            $params[':id'] = $id;
        }
        $stmt->execute($params);
        return $id !== null ? $id : (int) $pdo->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE enderecos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
    }

    public static function formato(array $endereco): string
    {
        return $endereco['corredor'] . '-' . $endereco['galpao'] . '-' . $endereco['prateleira'];
    }

    /**
     * Corredores e prateleiras existentes (para seleção no cadastro de produto).
     * Retorna linhas não-quarantena ordenadas por corredor/prateleira.
     */
    public static function diretorio(array $termos = ['corredor', 'prateleira']): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT e.corredor, e.prateleira, e.galpao
                FROM enderecos e
                WHERE e.quarantena != 1 AND e.deleted_at IS NULL
                ORDER BY e.corredor ASC, e.prateleira ASC';
        $res = $pdo->query($sql)->fetchAll();
        return array_map(function (array $linha) use ($termos) {
            $ret = [];
            foreach ($termos as $t) {
                $ret[$t] = $linha[$t];
            }
            return $ret;
        }, $res);
    }

    /**
     * Localiza ou cria uma posição física por corredor+prateleira (galpão único).
     * Usado pelo cadastro de produto para registrar o saldo inicial.
     */
    public static function obterOuCriarPorCorredorPrateleira(string $corredor, string $prateleira): array
    {
        $corredor = strtoupper(trim($corredor));
        $prateleira = strtoupper(trim($prateleira));

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM enderecos
                               WHERE corredor = :c AND galpao = :g AND prateleira = :p
                                 AND quarantena != 1 AND deleted_at IS NULL
                               LIMIT 1');
        $stmt->execute([':c' => $corredor, ':g' => GALPAO_UNICO, ':p' => $prateleira]);
        $existente = $stmt->fetch();
        if ($existente) {
            return $existente;
        }

        $id = self::salvar([
            'corredor'   => $corredor,
            'prateleira' => $prateleira,
            'descricao'  => 'Posição criada automaticamente no cadastro de produto',
            'capacidade_maxima' => 1000,
        ]);
        return self::buscarPorId($id);
    }
}