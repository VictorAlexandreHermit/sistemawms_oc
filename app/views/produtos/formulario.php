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
                    <select class="form-select" name="unidade_medida">
                        <?php
                        $unidades = ['UN', 'CX', 'KG', 'G', 'L', 'ML', 'M', 'M2', 'M3', 'PC', 'RL'];
                        $unidadeAtual = strtoupper(trim($p['unidade_medida'] ?? 'UN'));
                        if (!in_array($unidadeAtual, $unidades, true)) {
                            $unidades[] = $unidadeAtual;
                        }
                        ?>
                        <?php foreach ($unidades as $u): ?>
                        <option value="<?php echo SecurityHelper::e($u); ?>" <?php echo $unidadeAtual === $u ? 'selected' : ''; ?>>
                            <?php echo SecurityHelper::e($u); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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

            <?php if ($p === null): ?>
            <hr class="my-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="fw-semibold mb-1">Cadastrar com estoque inicial <span class="text-secondary small">(opcional)</span></div>
                    <div class="form-text text-secondary mb-2">Informe a quantidade e a posição no galpão (CORREDOR + PRATELEIRA do galpão <?php echo SecurityHelper::e(GALPAO_UNICO); ?>). Se a posição ainda não existir, ela é criada automaticamente.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantidade</label>
                    <input class="form-control" type="number" name="quantidade_inicial" value="0" min="0" step="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Corredor</label>
                    <select class="form-select" name="corredor" id="corredor_novo_produto">
                        <option value="">— selecionar —</option>
                        <?php foreach ($corredores as $c): ?>
                        <option value="<?php echo SecurityHelper::e($c); ?>"><?php echo SecurityHelper::e($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prateleira</label>
                    <select class="form-select" name="prateleira" id="prateleira_novo_produto">
                        <option value="">— selecionar —</option>
                    </select>
                </div>
            </div>
            <script>
                (function () {
                    var mapa = <?php echo json_encode($diretorio); ?>;
                    var corredorSel = document.getElementById('corredor_novo_produto');
                    var prateleiraSel = document.getElementById('prateleira_novo_produto');
                    function montar() {
                        prateleiraSel.innerHTML = '<option value="">— selecionar —</option>';
                        if (!corredorSel.value) { return; }
                        mapa.forEach(function (pos) {
                            if (pos.corredor !== corredorSel.value) { return; }
                            var opt = document.createElement('option');
                            opt.value = pos.prateleira;
                            opt.textContent = pos.prateleira;
                            prateleiraSel.appendChild(opt);
                        });
                    }
                    corredorSel.addEventListener('change', montar);
                    montar();
                })();
            </script>
            <?php endif; ?>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="wms-btn-primary">Salvar</button>
                <a class="btn btn-outline-slate" href="<?php echo BASE_URL; ?>/produtos">Cancelar</a>
            </div>
        </form>
    </div>
</div>