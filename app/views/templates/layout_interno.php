<?php
/**
 * app/views/templates/layout_interno.php
 * Layout fixo: Sidebar Deep Slate (260px) + Conteúdo fluid.
 * Variáveis: $conteudo, $titulo, $subtitulo, $rotaAtiva, $usuario (perfil/nome)
 */
$perfil = AuthHelper::perfil();
$usuarioNome = AuthHelper::usuario('nome_completo') ?? '';
$excecoesPendentes = AuthHelper::ehGestor() ? DivergenciaModel::contarPendentes() : 0;
$rotaBase = explode('/', $rotaAtiva)[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo SecurityHelper::e($titulo ?? 'WMS Agiliza'); ?> · WMS Agiliza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app.css?v=20260913">
</head>
<body>
<div class="wms-layout">

    <aside class="wms-sidebar">
        <div class="brand">
            <img class="brand-logo" src="<?php echo BASE_URL; ?>/assets/img/wms_branco.png" alt="WMS Agiliza logo">
            <span class="brand-text">WMS AGILIZA</span>
        </div>

        <nav class="wms-nav">
        <div class="nav-section">Operação</div>

        <?php if (AuthHelper::ehGestor()): ?>
        <a class="nav-item <?php echo $rotaBase === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a>
        <?php endif; ?>

        <a class="nav-item <?php echo $rotaBase === 'kanban' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/kanban">Kanban</a>

        <?php if (AuthHelper::ehGestor()): ?>
        <a class="nav-item <?php echo $rotaBase === 'configuracoes' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/configuracoes">SLAs do Kanban</a>
        <?php endif; ?>

        <?php if (AuthHelper::ehAdministrador()): ?>
        <a class="nav-item <?php echo $rotaBase === 'usuarios' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/usuarios">Usuários e Contas</a>
        <?php endif; ?>

        <a class="nav-item <?php echo $rotaBase === 'otif' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/otif/painel">Relatórios OTIF</a>

        <div class="nav-section">Recebimento</div>

        <a class="nav-item <?php echo $rotaBase === 'recebimento' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/recebimento">Recebimento</a>

        <?php if (AuthHelper::ehGestor()): ?>
        <a class="nav-item <?php echo $rotaBase === 'recebimento' && (stripos($rotaAtiva, 'excecoes') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/recebimento/excecoes">
            Exceções de Recebimento
            <?php if ($excecoesPendentes > 0): ?>
                <span class="badge rounded-pill bg-danger ms-auto"><?php echo $excecoesPendentes; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <div class="nav-section">Transporte</div>

        <a class="nav-item <?php echo $rotaBase === 'guarda' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/guarda">Guarda (Putaway)</a>

        <div class="nav-section">Picking & Packing</div>

        <a class="nav-item <?php echo $rotaBase === 'separacao' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/separacao">Separação e Embalagem</a>

        <div class="nav-section">Geral</div>

        <a class="nav-item <?php echo $rotaBase === 'produtos' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produtos">Produtos</a>

        <?php if (AuthHelper::ehGestor()): ?>
        <a class="nav-item <?php echo $rotaBase === 'enderecos' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/enderecos">Endereços Físicos</a>
        <?php endif; ?>

        <a class="nav-item <?php echo $rotaBase === 'avarias' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/avarias">Avarias e Quarentena</a>

        <a class="nav-item <?php echo $rotaBase === 'auditoria' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/auditoria">Auditoria de Estoque</a>

        <a class="nav-item nav-item-sair" href="<?php echo BASE_URL; ?>/logout">↪ Sair da operação</a>
        </nav>

        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="dot <?php echo AuthHelper::ehGestor() ? 'dot-warning' : 'dot-info'; ?>"></span>
                <?php echo SecurityHelper::e($usuarioNome); ?>
                <span class="d-block text-capitalize" style="color:#64748B"><?php echo strtolower(SecurityHelper::e($perfil)); ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>/logout" style="color:#EDF5E1;text-decoration:none;font-weight:700">Sair da operação →</a>
        </div>
    </aside>

    <main class="wms-content">
        <div class="print-brand">
            <img src="<?php echo BASE_URL; ?>/assets/img/wms_preto.png" alt="WMS Agiliza logo">
        </div>
        <?php if (!empty($titulo)): ?>
            <h1 class="page-title"><?php echo SecurityHelper::e($titulo); ?></h1>
            <?php if (!empty($subtitulo)): ?>
                <div class="page-subtitle"><?php echo SecurityHelper::e($subtitulo); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php ViewHelper::flash(); ?>

        <?php echo $conteudo; ?>
    </main>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js?v=20260913"></script>
</body>
</html>