<?php
/**
 * app/views/otif/invalido.php
 * Estado inválido/expirado/respondido do link OTIF.
 */
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:var(--wms-deep-slate)">
    <div class="card border-0 shadow" style="width:100%;max-width:460px;border-radius:var(--wms-radius)">
        <div class="card-body p-5 text-center">
            <div class="mb-3" style="font-size:40px">📦</div>
            <h1 class="h5 mb-2" style="color:var(--wms-deep-slate)"><?php echo SecurityHelper::e($titulo ?? 'Avaliação indisponível'); ?></h1>
            <p class="text-secondary mb-4" style="color:var(--wms-text-muted)">
                <?php echo SecurityHelper::e($mensagem ?? 'Verifique o link recebido ou entre em contato com a central de relacionamento.'); ?>
            </p>
            <a class="btn btn-dark" href="#" onclick="window.close();return false;">Fechar página</a>
        </div>
    </div>
</div>