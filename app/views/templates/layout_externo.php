<?php
/**
 * app/views/templates/layout_externo.php
 * Layout "limpo" para telas públicas (login, OTIF, acesso negado).
 * Variáveis: $conteudo.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SecurityHelper::e($titulo ?? 'WMS Agiliza'); ?> · WMS Agiliza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app.css?v=20260918">
</head>
<body>
    <?php echo $conteudo; ?>
</body>
</html>