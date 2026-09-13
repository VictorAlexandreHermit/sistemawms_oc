<?php
/**
 * app/views/templates/acesso_negado.php
 * Página 403 exibida fora do layout principal.
 */
?>
<div class="login-page">
    <div style="width:100%;max-width:420px;text-align:center">
        <h1 style="font-size:56px;font-weight:700;color:#0F172A;margin-bottom:.5rem">403</h1>
        <h2 style="font-size:20px;font-weight:600;margin-bottom:1rem">Acesso negado</h2>
        <p style="color:#64748B;margin-bottom:1.5rem">
            Você não possui permissão para acessar esta área do sistema.<br>
            Este ocorrência foi registrada no log de segurança.
        </p>
        <a class="wms-btn-primary text-decoration-none" href="<?php echo BASE_URL; ?>/kanban">Voltar para a operação</a>
    </div>
</div>