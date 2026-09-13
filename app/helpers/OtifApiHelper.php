<?php
/**
 * app/helpers/OtifApiHelper.php
 * Disparo automático da pesquisa OTIF (WhatsApp via Evolution API / E-mail via Resend).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class OtifApiHelper
{
    /**
     * Monta o link de avaliação OTIF do pedido.
     */
    public static function linkDaAvaliacao(array $pedido): string
    {
        return BASE_URL . '/otif/avaliar?token=' . $pedido['token_otif'];
    }

    /**
     * Aciona o envio da mensagem ao cliente quando o pedido é marcado como ENTREGUE.
     *
     * @return bool True se o envio for considerado concluído (ENVIADO).
     */
    public static function disparar(array $pedido): bool
    {
        $pesquisa = PesquisaOtifModel::doPedido((int) $pedido['id']);
        if ($pesquisa === null) {
            return false;
        }

        $link = self::linkDaAvaliacao($pedido);
        $texto = "Olá {$pedido['cliente_nome']}! Seu pedido {$pedido['numero_nota_xml']} foi entregue. "
               . "Avalie a qualidade da nossa entrega em: {$link}";

        $config = APP_CONFIG['envio'];

        if (($config['modo'] ?? 'simulacao') === 'api') {
            return self::enviarViaApi($pedido, $texto, $pesquisa, $config);
        }

        // Modo simulação: registra como ENVIADO para validar o fluxo completo.
        PesquisaOtifModel::registrarStatusEnvio((int) $pesquisa['id'], 'ENVIADO');
        LogHelper::registrarSeguranca('OTIF_SIMULADO', 'Link de avaliação gerado (simulação): ' . $texto);
        return true;
    }

    private static function enviarViaApi(array $pedido, string $texto, array $pesquisa, array $config): bool
    {
        $enviado = false;

        try {
            $destination = trim($pedido['cliente_contato'] ?? '');

            if ($destination === '') {
                throw new RuntimeException('Contato do cliente ausente.');
            }

            // Prioriza WhatsApp (Evolution API) se configurado
            if (!empty($config['evolution_api']['url'])) {
                $payload = [
                    'number' => $destination,
                    'text'   => $texto,
                ];
                $url = rtrim($config['evolution_api']['url'], '/')
                     . '?instance=' . rawurlencode($config['evolution_api']['instance']);
                self::chamarPost($url, $payload, $config['evolution_api']['api_key'] ?? '');
                $enviado = true;
            } elseif (!empty($config['resend']['api_key'])) {
                // E-mail transacional via Resend
                $payload = [
                    'from'    => $config['resend']['from_email'],
                    'to'      => [$destination],
                    'subject' => 'Avalie a entrega do seu pedido ' . $pedido['numero_nota_xml'],
                    'text'    => $texto,
                ];
                self::chamarPost('https://api.resend.com/emails', $payload, $config['resend']['api_key'], true);
                $enviado = true;
            }

            if ($enviado) {
                PesquisaOtifModel::registrarStatusEnvio((int) $pesquisa['id'], 'ENVIADO');
            } else {
                // Nenhum provedor configurado: trata como falha para reprocessamento
                PesquisaOtifModel::registrarStatusEnvio((int) $pesquisa['id'], 'FALHA');
            }
        } catch (\Throwable $e) {
            LogHelper::registrarErro($e);
            PesquisaOtifModel::registrarStatusEnvio((int) $pesquisa['id'], 'FALHA');
            return false;
        }

        return $enviado;
    }

    private static function chamarPost(string $url, array $payload, string $apiKey = '', bool $bearer = false): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => array_merge(
                ['Content-Type: application/json'],
                $apiKey !== '' ? [($bearer ? 'Authorization: Bearer ' : 'apikey: ') . $apiKey] : []
            ),
        ]);
        $resposta = curl_exec($ch);
        $codigo   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($codigo >= 400) {
            throw new RuntimeException('Serviço externo respondeu HTTP ' . $codigo . ': ' . substr((string) $resposta, 0, 200));
        }
    }
}