<?php
/**
 * app/controllers/ConfiguracoesController.php
 * Parâmetros operacionais do Gestor (SLA por etapa).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ConfiguracoesController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        ViewHelper::render('configuracoes/index', [
            'titulo'    => 'Configurações Operacionais',
            'subtitulo' => 'Limites de tempo (SLA) por etapa — alimentam o alerta visual do Kanban.',
            'slas'      => ConfigSlaModel::todos(),
        ]);
    }

    public function actionSalvarSla(): void
    {
        AuthHelper::requireLogin();
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        $etapasPermitidas = ['RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR'];
        foreach ($etapasPermitidas as $etapa) {
            $valor = (int) ($_POST['sla_' . $etapa] ?? 0);
            if ($valor > 0) {
                ConfigSlaModel::atualizar($etapa, $valor, (int) AuthHelper::usuario('id'));
            }
        }

        LogHelper::registrarSeguranca('SLA_ATUALIZADO', 'Limites de SLA atualizados pelo Gestor.');
        ViewHelper::setFlash('sucesso', 'Limites de SLA atualizados.');
        Router::redirecionar('configuracoes');
    }
}