<?php
/**
 * app/views/enderecos/formulario.php
 * Cadastro/edição de endereço.
 */
$e = $endereco; // null na criação
?>
<div class="wms-card" style="max-width:640px">
    <div class="wms-card-header">Dados do endereço</div>
    <div class="p-4">
        <form method="post" action="<?php echo BASE_URL; ?>/enderecos/salvar<?php echo $e ? '/' . (int) $e['id'] : ''; ?>" autocomplete="off">
            <?php echo CsrfHelper::campo(); ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Corredor *</label>
                    <input class="form-control" type="text" name="corredor" value="<?php echo SecurityHelper::e($e['corredor'] ?? ''); ?>" maxlength="5" required
                           placeholder="C01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Galpão *</label>
                    <input class="form-control" type="text" name="galpao" value="<?php echo SecurityHelper::e($e['galpao'] ?? ''); ?>" maxlength="5" required
                           placeholder="G01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prateleira *</label>
                    <input class="form-control" type="text" name="prateleira" value="<?php echo SecurityHelper::e($e['prateleira'] ?? ''); ?>" maxlength="5" required
                           placeholder="P01">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <input class="form-control" type="text" name="descricao" value="<?php echo SecurityHelper::e($e['descricao'] ?? ''); ?>" placeholder="Ex.: Corredor A — paletes baixos">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Capacidade máxima (un.)</label>
                    <input class="form-control" type="number" name="capacidade_maxima" value="<?php echo (int) ($e['capacidade_maxima'] ?? 1000); ?>" min="1" required>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="wms-btn-primary">Salvar</button>
                <a class="btn btn-outline-slate" href="<?php echo BASE_URL; ?>/enderecos">Cancelar</a>
            </div>
        </form>
    </div>
</div>