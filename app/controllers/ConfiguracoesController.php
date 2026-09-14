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
            'subtitulo' => 'Limites de tempo (SLA) em horas por etapa — padrão 2 horas — alimentam o alerta visual do Kanban.',
            'slas'      => ConfigSlaModel::todos(),
        ]);
    }

    public function actionSalvarSla(): void
    {
        AuthHelper::requireLogin();
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        $etapasPermitidas = ['RECEBIDO', 'A_ARMAZENAR', 'ARMAZENADO', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO'];
        foreach ($etapasPermitidas as $etapa) {
            $horas  = (float) str_replace(',', '.', $_POST['sla_' . $etapa] ?? '0');
            $minutos = (int) round($horas * 60);
            if ($minutos > 0) {
                ConfigSlaModel::atualizar($etapa, $minutos, (int) AuthHelper::usuario('id'));
            }
        }

        LogHelper::registrarSeguranca('SLA_ATUALIZADO', 'Limites de SLA atualizados pelo Gestor.');
        ViewHelper::setFlash('sucesso', 'Limites de SLA atualizados.');
        Router::redirecionar('configuracoes');
    }
}