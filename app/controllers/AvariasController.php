<?php
/**
 * app/controllers/AvariasController.php
 * Registro de avarias com foto e gestão da Quarentena.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AvariasController
{
    private const ETAPAS = ['RECEBIMENTO', 'ARMAZENAGEM', 'SEPARACAO', 'EMBALAGEM', 'EXPEDICAO', 'DEVOLUCAO', 'OUTROS'];

    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        ViewHelper::render('avarias/index', [
            'titulo'    => 'Controle de Avarias e Quarentena',
            'subtitulo' => 'Ao registrar, o saldo é transferido automaticamente para o endereço virtual de Quarentena.',
            'etapas'    => self::ETAPAS,
            'motivos'   => APP_CONFIG['avarias_motivos'] ?? ['AVARIADO', 'EMBALAGEM DETERIORADA', 'VENCI. PRÓXIMO', 'QUEBRA'],
            'registros' => AvariaModel::listar(),
            'saldoQtr'  => AvariaModel::saldoEmQuarentena(),
            'fornecedores' => AvariaModel::ocorrenciasPorFornecedor(),
            'enderecosOpcoes' => EnderecoModel::listarComOcupacao(),
            'enderecoSugerido' => EnderecoModel::enderecoSugerido(),
        ]);
    }

    public function actionRegistrar(): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $codigoProduto  = trim($_POST['produto'] ?? '');
        $corredor       = strtoupper(trim($_POST['corredor'] ?? ''));
        $galpao         = strtoupper(trim($_POST['galpao'] ?? ''));
        $prateleira     = strtoupper(trim($_POST['prateleira'] ?? ''));
        $codigoEndereco = ($corredor !== '' && $galpao !== '' && $prateleira !== '')
            ? $corredor . '-' . $galpao . '-' . $prateleira
            : '';
        $quantidade     = (int) ($_POST['quantidade'] ?? 0);
        $motivo         = trim($_POST['motivo'] ?? '');
        $etapa          = $_POST['etapa'] ?? 'OUTROS';

        $arquivoFoto = $_FILES['foto'] ?? null;

        if ($codigoProduto === '' || $codigoEndereco === '') {
            ViewHelper::setFlash('erro', 'Informe o produto e o endereço onde a avaria foi encontrada.');
            Router::redirecionar('avarias');
        }

        $produto  = ProdutoModel::buscarPorSku($codigoProduto);
        $endereco = EnderecoModel::buscarPorCodigo($codigoEndereco);

        if ($produto === null || $endereco === null) {
            ViewHelper::setFlash('erro', 'Produto (bipa apenas o SKU, ex.: SIS-001) ou endereço não localizados no cadastro.');
            Router::redirecionar('avarias');
        }
        if ($quantidade <= 0) {
            ViewHelper::setFlash('erro', 'A quantidade avariada deve ser maior que zero.');
            Router::redirecionar('avarias');
        }
        if (!in_array($etapa, self::ETAPAS, true)) {
            $etapa = 'OUTROS';
        }

        try {
            AvariaModel::registrarComFoto([
                'produto_id' => $produto['id'],
                'endereco_id'=> $endereco['id'],
                'quantidade' => $quantidade,
                'motivo'     => $motivo !== '' ? $motivo : 'Avaria identificada',
                'etapa'      => $etapa,
            ], $arquivoFoto);
            ViewHelper::setFlash('sucesso', 'Avaria registrada e saldo enviado para a Quarentena.');
        } catch (\Throwable $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
        }

        Router::redirecionar('avarias');
    }
}