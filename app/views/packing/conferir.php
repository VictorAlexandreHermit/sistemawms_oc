<?php
/**
 * app/views/packing/conferir.php
 * Embalagem e verificação dos itens separados pelo Picking.
 */
$pct = (float) $progresso['percentual'];
?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-semibold">Itens separados (Picking concluído)</span>
        <span class="tabular-nums fw-semibold"><?php echo (int) $progresso['bipados']; ?>/<?php echo (int) $progresso['esperados']; ?> (<?php echo $pct; ?>%)</span>
    </div>
    <div class="progress-bar-atv" style="height:10px">
        <div style="width:<?php echo $pct; ?>%"></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header">Itens a embalar e verificar</div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Quant.</th>
                                <th class="text-center">Separado</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                            <?php $esperado = (int) $item['quantidade_esperada']; $bipado = (int) $item['quantidade_bipada_picking']; ?>
                            <tr class="linha-ok">
                                <td class="tabular-nums">
                                    <strong><?php echo SecurityHelper::e($item['sku']); ?></strong>
                                    <div class="text-secondary"><?php echo SecurityHelper::e(mb_strimwidth($item['descricao'], 0, 44, '…')); ?></div>
                                </td>
                                <td class="text-center tabular-nums"><?php echo $esperado; ?></td>
                                <td class="text-center tabular-nums fw-semibold"><?php echo $bipado; ?></td>
                                <td>
                                    <?php if ($bipado >= $esperado): ?>
                                        <span class="badge badge-success-lg">SEPARADO &amp; VERIFICADO</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning-lg">PENDENTE NO PICKING</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="wms-card p-4">
            <div class="mb-3">
                <span class="badge badge-dark-lg">Pedido <?php echo SecurityHelper::e($pedido['numero_nota_xml']); ?></span>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="packing-verificado">
                <label class="form-check-label" for="packing-verificado">
                    Confirme que todos os itens foram <strong>embalados</strong> e <strong>verificados</strong>.
                </label>
            </div>

            <form method="post" action="<?php echo BASE_URL; ?>/packing/concluir/<?php echo (int) $pedido['id']; ?>"
                  onsubmit="return confirm('Concluir a embalagem e liberar o pedido para a Expedição?')">
                <?php echo CsrfHelper::campo(); ?>
                <button id="btn-concluir-packing" type="submit" class="wms-btn-success w-100" disabled>
                    Concluir Embalagem &amp; Liberar Expedição
                </button>
            </form>

            <a class="btn btn-outline-slate mt-4" href="<?php echo BASE_URL; ?>/packing">Voltar à fila</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var check = document.getElementById('packing-verificado');
    var btn = document.getElementById('btn-concluir-packing');
    if (check && btn) {
        check.addEventListener('change', function () {
            btn.disabled = !check.checked;
        });
    }
});
</script>