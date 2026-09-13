<?php
/**
 * app/helpers/DateHelper.php
 * Utilitários de datas, incluindo somatório de dias úteis para o OTIF.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class DateHelper
{
    /**
     * Soma N dias úteis (seg-sex) a uma data.
     */
    public static function adicionarDiasUteis(DateTime $data, int $diasUteis): DateTime
    {
        $data = clone $data;
        $adicionados = 0;
        while ($adicionados < $diasUteis) {
            $data->modify('+1 day');
            $diaSemana = (int) $data->format('N'); // 1=Seg ... 7=Dom
            if ($diaSemana <= 5) {
                $adicionados++;
            }
        }
        return $data;
    }

    /**
     * Diferenca em minutos entre duas datas.
     */
    public static function minutosEntre(DateTime $inicio, DateTime $fim): int
    {
        $diff = $fim->getTimestamp() - $inicio->getTimestamp();
        return max(0, (int) floor($diff / 60));
    }

    public static function agora(): DateTime
    {
        return new DateTime('now', new DateTimeZone(CFG_TIMEZONE));
    }

    /**
     * Exibe data e hora no fuso configurado (ex.: 12/09/2026 14:32).
     */
    public static function exibir(?string $dataHora): string
    {
        if ($dataHora === null || $dataHora === '') {
            return '';
        }
        try {
            $dt = new DateTime($dataHora, new DateTimeZone(CFG_TIMEZONE));
        } catch (\Throwable $e) {
            try {
                $dt = new DateTime($dataHora);
            } catch (\Throwable $e2) {
                return (string) $dataHora;
            }
        }
        return $dt->format('d/m/Y H:i');
    }

    /**
     * Exibe apenas a data (ex.: 12/09/2026).
     */
    public static function exibirData(?string $dataHora): string
    {
        if ($dataHora === null || $dataHora === '') {
            return '';
        }
        try {
            $dt = new DateTime($dataHora, new DateTimeZone(CFG_TIMEZONE));
        } catch (\Throwable $e) {
            return (string) $dataHora;
        }
        return $dt->format('d/m/Y');
    }
}