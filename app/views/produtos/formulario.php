<?php
/**
 * app/views/produtos/formulario.php
 * Cadastro/edição de produto.
 */
$p = $produto; // null na criação
?>
<div class="wms-card" style="max-width:720px">
    <div class="wms-card-header">Dados do produto</div>
    <div class="p-4">
        <form method="post" action="<?php echo BASE_URL; ?>/produtos/salvar<?php echo $p ? '/' . (int) $p['id'] : ''; ?>" autocomplete="off">
            <?php echo CsrfHelper::campo(); ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">SKU *</label>
                    <input class="form-control" type="text" name="sku" value="<?php echo SecurityHelper::e($p['sku'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Código de barras *</label>
                    <input class="form-control" type="text" name="codigo_barras" value="<?php echo SecurityHelper::e($p['codigo_barras'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unidade de medida</label>
                    <input class="form-control" type="text" name="unidade_medida" value="<?php echo SecurityHelper::e($p['unidade_medida'] ?? 'UN'); ?>" maxlength="10">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <input class="form-control" type="text" name="descricao" value="<?php echo SecurityHelper::e($p['descricao'] ?? ''); ?>" required maxlength="255">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Curva ABC</label>
                    <select class="form-select" name="curva_abc">
                        <?php foreach (['A', 'B', 'C'] as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo (strtoupper($p['curva_abc'] ?? 'C') === $c) ? 'selected' : ''; ?>>
                                <?php echo $c; ?> — <?php echo $c === 'A' ? 'Alta demanda' : ($c === 'B' ? 'Média demanda' : 'Baixa demanda'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="wms-btn-primary">Salvar</button>
                <a class="btn btn-outline-slate" href="<?php echo BASE_URL; ?>/produtos">Cancelar</a>
            </div>
        </form>
    </div>
</div>