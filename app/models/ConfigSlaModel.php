<?php
/**
 * app/models/ConfigSlaModel.php
 * Limites de tempo (SLA) por etapa do Kanban.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ConfigSlaModel
{
    public static function todos(): array
    {
        $pdo = Database::conexao();
        return $pdo->query('SELECT * FROM configuracoes_sla ORDER BY FIELD(etapa_kanban, "RECEBIDO", "A_ARMAZENAR", "A_SEPARAR", "A_EXPEDIR")')->fetchAll();
    }

    public static function limiteDaEtapa(string $etapa): int
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT tempo_limite_minutos FROM configuracoes_sla WHERE etapa_kanban = :etapa LIMIT 1');
        $stmt->execute([':etapa' => $etapa]);
        $reg = $stmt->fetch();
        return $reg ? (int) $reg['tempo_limite_minutos'] : 120;
    }

    public static function atualizar(string $etapa, int $minutos, int $updatedBy): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_sla
                               SET tempo_limite_minutos = :minutos, updated_by = :user
                               WHERE etapa_kanban = :etapa');
        $stmt->execute([':minutos' => max(1, $minutos), ':user' => $updatedBy, ':etapa' => $etapa]);
    }
}