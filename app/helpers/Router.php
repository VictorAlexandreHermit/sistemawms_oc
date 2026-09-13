<?php
/**
 * app/helpers/Router.php
 * Roteamento simples: ?r=controller/action/parametros ou via REQUEST_URI.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class Router
{
    public static function dispatch(): void
    {
        $rota = self::obterRota();

        // Rota raiz: encaminha conforme autenticação
        if ($rota === '') {
            if (AuthHelper::logado()) {
                self::redirecionar(AuthHelper::usuario('perfil') === 'GESTOR' ? 'dashboard' : 'kanban');
            }
            self::redirecionar('login');
            return;
        }

        $segmentos = array_values(array_filter(explode('/', $rota), 'strlen'));
        $controllerNome = isset($segmentos[0]) ? ucfirst(strtolower($segmentos[0])) . 'Controller' : 'AuthController';
        $action = isset($segmentos[1]) ? 'action' . self::camelizar($segmentos[1]) : 'actionIndex';
        $params = array_slice($segmentos, 2);

        // Rotas de autenticação (prefixos amigáveis: /login, /logar, /logout)
        $aliasesAuth = [
            'login'  => ['AuthController', 'actionIndex'],
            'logar'  => ['AuthController', 'actionLogar'],
            'logout' => ['AuthController', 'actionLogout'],
        ];
        if (isset($aliasesAuth[strtolower($segmentos[0] ?? '')])) {
            [$controllerNome, $action] = $aliasesAuth[strtolower($segmentos[0] ?? '')];
            $params = array_slice($segmentos, 1);
        }

        $arquivo = BASE_DIR . '/app/controllers/' . $controllerNome . '.php';
        if (!is_file($arquivo) || !class_exists($controllerNome)) {
            http_response_code(404);
            self::render404();
            return;
        }

        $controller = new $controllerNome();

        if (!method_exists($controller, $action)) {
            http_response_code(404);
            self::render404();
            return;
        }

        $controller->$action(...$params);
    }

    public static function obterRota(): string
    {
        $rota = $_GET['r'] ?? '';

        if ($rota === '' && PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
            $uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
            $uri      = rawurldecode($uri);

            // Remove o prefixo base (ex.: /wms-agiliza)
            $prefixo = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
            if ($prefixo !== '' && strpos($uri, $prefixo) === 0) {
                $uri = substr($uri, strlen($prefixo));
            }

            // Remove o nome do script (index.php)
            $uri = preg_replace('#^/index\.php#', '', $uri);

            $rota = trim($uri, '/');
        }

        return trim($rota, '/');
    }

    public static function redirecionar(string $rota): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($rota, '/'));
        exit;
    }

    private static function camelizar(string $nome): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($nome))));
    }

    public static function url(string $rota): string
    {
        return BASE_URL . '/' . ltrim($rota, '/');
    }

    private static function render404(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>404</title></head>
              <body style="font-family:Sans-serif;background:#F8FAFC;display:flex;align-items:center;justify-content:center;height:100vh">
              <div style="text-align:center"><h1 style="color:#0F172A">404</h1><p style="color:#334155">Página não encontrada.</p>
              <a href="' . SecurityHelper::e(BASE_URL) . '/login" style="color:#0F172A">Voltar para o login</a></div></body></html>';
        exit;
    }
}