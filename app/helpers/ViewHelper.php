<?php
/**
 * app/helpers/ViewHelper.php
 * Renderização das views PHP com suporte a layouts.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ViewHelper
{
    /**
     * Renderiza uma view, envolvendo-a em um layout.
     *
     * @param string      $view    Caminho relativo dentro de app/views (ex.: 'dashboard/index')
     * @param array       $dados   Variáveis disponíveis na view
     * @param string|null $layout  Nome do layout ('interno' ou 'externo'). Default: interno.
     */
    public static function render(string $view, array $dados = [], ?string $layout = 'interno'): void
    {
        extract($dados, EXTR_SKIP);

        // Rota atual para destacar o item ativo no menu
        $rotaAtiva = Router::obterRota();

        $arquivo = BASE_DIR . '/app/views/' . $view . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        ob_start();
        include $arquivo;
        $conteudo = ob_get_clean();

        if ($layout === null) {
            echo $conteudo;
            return;
        }

        $layoutArquivo = BASE_DIR . '/app/views/templates/layout_' . $layout . '.php';
        if (!is_file($layoutArquivo)) {
            throw new RuntimeException('Layout não encontrado: ' . $layout);
        }
        include $layoutArquivo;
    }

    /**
     * Monta e exibe notificações (flash) armazenadas em sessão.
     */
    public static function flash(): void
    {
        if (!empty($_SESSION['flash_sucesso'])) {
            echo '<div class="alert alert-success border-0 shadow-sm">' . SecurityHelper::e($_SESSION['flash_sucesso']) . '</div>';
            unset($_SESSION['flash_sucesso']);
        }
        if (!empty($_SESSION['flash_erro'])) {
            echo '<div class="alert alert-danger border-0 shadow-sm">' . SecurityHelper::e($_SESSION['flash_erro']) . '</div>';
            unset($_SESSION['flash_erro']);
        }
        if (!empty($_SESSION['flash_aviso'])) {
            echo '<div class="alert alert-warning border-0 shadow-sm">' . SecurityHelper::e($_SESSION['flash_aviso']) . '</div>';
            unset($_SESSION['flash_aviso']);
        }
    }

    public static function setFlash(string $tipo, string $mensagem): void
    {
        $_SESSION['flash_' . $tipo] = $mensagem;
    }
}