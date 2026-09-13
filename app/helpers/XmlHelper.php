<?php
/**
 * app/helpers/XmlHelper.php
 * Leitura e extração de dados do XML de NF-e para o recebimento.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class XmlHelper
{
    /**
     * Lê e valida o arquivo XML de NF-e enviado no upload.
     *
     * @param array $arquivo Elemento de $_FILES
     *
     * @return array Dados normalizados da nota
     * @throws RuntimeException Se o arquivo for inválido
     */
    public static function lerNFe(array $arquivo): array
    {
        if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no upload do arquivo XML.');
        }

        if ((int) $arquivo['size'] <= 0 || (int) $arquivo['size'] > 10485760) {
            throw new RuntimeException('Arquivo XML inválido ou maior que o limite permitido (10MB).');
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'xml') {
            throw new RuntimeException('Somente arquivos XML de NF-e são aceitos.');
        }

        $conteudo = @file_get_contents($arquivo['tmp_name']);
        if ($conteudo === false || trim($conteudo) === '') {
            throw new RuntimeException('Arquivo XML vazio ou ilegível.');
        }

        // Previne XXE (External Entity Injection) com processamento seguro
        $flags = LIBXML_NONET | LIBXML_NOCDATA;
        $xml = simplexml_load_string($conteudo, 'SimpleXMLElement', $flags);

        if ($xml === false) {
            throw new RuntimeException('O arquivo enviado não é um XML válido.');
        }

        // Identifica o nó da NFe (com ou sem envoltório <nfeProc>)
        $nfe = $xml->NFe ?? $xml->nfeProc->NFe ?? null;
        if ($nfe === null) {
            throw new RuntimeException('Estrutura de NF-e não encontrada no XML.');
        }

        $infNfe = $nfe->infNFe ?? $nfe->infNfe ?? $nfe->InfNFe ?? null;
        if ($infNfe === null) {
            throw new RuntimeException('Nó infNFe não localizado no XML da NF-e.');
        }

        $chave = '';
        $atributoId = (string) $infNfe['Id'];
        if (strpos($atributoId, 'NFe') === 0) {
            $chave = substr($atributoId, 3, 44);
        }
        if (strlen($chave) !== 44) {
            $chave = (string) ($infNfe->ide->cDV ?? '');
        }

        $itens = [];
        foreach ($infNfe->det as $det) {
            $prod = $det->prod;
            $codigoBarras = (string) ($prod->cBarra ?? '');
            if ($codigoBarras === '') {
                $codigoBarras = (string) ($prod->cEAN ?? $prod->cBarra ?? '');
            }
            $itens[] = [
                'sku'          => (string) ($prod->cProd ?? ''),
                'codigo_barras'=> $codigoBarras,
                'descricao'    => (string) ($prod->xProd ?? ''),
                'unidade'      => (string) ($prod->uCom ?? 'UN'),
                'quantidade'   => self::limparNumero($prod->qCom ?? '1'),
            ];
        }

        if (empty($itens)) {
            throw new RuntimeException('Nenhum item localizado na NF-e.');
        }

        return [
            'numero_nota'   => (string) ($infNfe->ide->nNF ?? '0'),
            'chave_nfe'     => $chave,
            'fornecedor'    => trim((string) ($infNfe->emit->xNome ?? '')),
            'cliente_nome'  => trim((string) ($infNfe->dest->xNome ?? '')),
            'cliente_contato' => trim((string) ($infNfe->dest->email ?? '')),
            'valor_total'   => (float) self::limparNumero($infNfe->total->ICMSTot->vNF ?? 0),
            'itens'         => $itens,
        ];
    }

    private static function limparNumero($valor): string
    {
        $v = (string) $valor;
        $v = trim(str_replace(',', '.', $v));
        return $v;
    }
}