<?php
/**
 * app/controllers/AuditoriaController.php
 * Correlação de contagens e acerto manual de saldo (com trilha de auditoria).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AuditoriaController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $termo = trim($_GET['q'] ?? '');
        ViewHelper::render('auditoria/index', [
            'titulo'    => 'Auditoria de Estoque',
            'subtitulo' => 'Acertos são gravados em trilha inalterável com motivo obrigatório.',
            'motivos'   => APP_CONFIG['motivos_ajuste'],
            'historico' => AuditoriaModel::historico($termo !== '' ? $termo : null),
            'termo'     => $termo,
            'enderecosOpcoes' => EnderecoModel::listarComOcupacao(),
            'enderecoSugerido' => EnderecoModel::enderecoSugerido(),
        ]);
    }

    public function actionAjustar(): void
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
        $novaQtd        = (int) ($_POST['nova_quantidade'] ?? -1);
        $motivo         = $_POST['motivo'] ?? '';
        $obs            = trim($_POST['observacoes'] ?? '');

        if ($codigoProduto === '' || $codigoEndereco === '') {
            ViewHelper::setFlash('erro', 'Informe o produto e o endereço do saldo a ajustar.');
            Router::redirecionar('auditoria');
        }
        if ($novaQtd < 0) {
            ViewHelper::setFlash('erro', 'A nova quantidade não pode ser negativa.');
            Router::redirecionar('auditoria');
        }
        if ($motivo === '' || !array_key_exists($motivo, APP_CONFIG['motivos_ajuste'])) {
            ViewHelper::setFlash('erro', 'Selecione um motivo de ajuste válido.');
            Router::redirecionar('auditoria');
        }

        $produto  = ProdutoModel::buscarPorCodigoBarras($codigoProduto);
        $endereco = EnderecoModel::buscarPorCodigo($codigoEndereco);

        if ($produto === null || $endereco === null) {
            ViewHelper::setFlash('erro', 'Produto ou endereço não localizados no cadastro.');
            Router::redirecionar('auditoria');
        }

        try {
            AuditoriaModel::ajustar(
                (int) $produto['id'],
                (int) $endereco['id'],
                $novaQtd,
                $motivo,
                $obs
            );
            ViewHelper::setFlash('sucesso', 'Ajuste aplicado e gravado na trilha de auditoria.');
        } catch (\Throwable $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
        }

        Router::redirecionar('auditoria');
    }

    public function actionExcluir(int $id): void
    {
        AuthHelper::requireLogin();
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        AuditoriaModel::excluir($id);
        ViewHelper::setFlash('sucesso', 'Registro de auditoria removido (soft delete).');
        Router::redirecionar('auditoria');
    }
}