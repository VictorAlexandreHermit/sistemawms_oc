<?php
/**
 * app/helpers/UploadHelper.php
 * Validação e salvamento seguro de arquivos anexados (fotos).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class UploadHelper
{
    /**
     * Salva uma imagem validada e retorna o caminho relativo (ex.: avarias/hash.png).
     *
     * @param array  $arquivo       Elemento de $_FILES
     * @param string $subpasta      'avarias' ou 'otif'
     * @param int    $maxBytes      Tamanho máximo permitido
     * @param array  $mimePermitidos Tipos MIME aceitos
     *
     * @throws RuntimeException Em caso de falha de validação
     */
    public static function salvarImagem(array $arquivo, string $subpasta, int $maxBytes = 5242880, array $mimePermitidos = ['image/jpeg', 'image/png']): string
    {
        if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nenhum arquivo válido enviado.');
        }

        $tamanho = (int) $arquivo['size'];
        if ($tamanho <= 0 || $tamanho > $maxBytes) {
            throw new RuntimeException('Tamanho do arquivo excede o limite permitido (5MB).');
        }

        $mime = mime_content_type($arquivo['tmp_name']);
        if (!in_array($mime, $mimePermitidos, true)) {
            throw new RuntimeException('Tipo de arquivo não permitido. Apenas imagens JPG/PNG são aceitas.');
        }

        $extensao = $mime === 'image/png' ? 'png' : 'jpg';
        $destino  = BASE_DIR . '/uploads/' . $subpasta;
        if (!is_dir($destino)) {
            @mkdir($destino, 0755, true);
        }

        $nomeHash = SecurityHelper::gerarNomeHash(file_get_contents($arquivo['tmp_name']), $extensao);
        $caminho  = $destino . '/' . $nomeHash;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminho)) {
            throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
        }

        return $subpasta . '/' . $nomeHash;
    }
}