<?php
/**
 * app/views/recebimento/conferencia.php
 * Conferência Cega: leitura por bipagem USB/manual sem exibir o esperado.
 */
$totalBipado = 0;
foreach ($itens as $i) { if ((int) $i['quantidade_conferida'] >= (int) $i['quantidade_esperada']) { $totalBipado++; } }
?>
<div class="row g-4">

    <div class="col-12">
        <div class="wms-card p-4">
            <form method="post" action="<?php echo BASE_URL; ?>/recebimento/bipar/<?php echo (int) $pedido['id']; ?>" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>
                <label class="form-label" for="campo_bip">Leia o código — <strong>código de barras ou SKU</strong> (ex.: SIS-001) — 1 leitura por produto</label>
                <div class="input-group">
                    <input class="form-control bipador form-control-lg" type="text" id="campo_bip" name="codigo"
                           placeholder="Ex.: 7891000010011" autofocus required>
                    <button class="wms-btn-primary" type="submit">Bipar (Enter)</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge badge-dark-lg" style="padding:.45rem .8rem">
                    Produtos conferidos: <strong class="tabular-nums"><?php echo (int) $totalBipado; ?></strong>
                </span>
            </div>
            <form method="post" action="<?php echo BASE_URL; ?>/recebimento/finalizar/<?php echo (int) $pedido['id']; ?>"
                  onsubmit="return confirm('Finalizar a conferência? Itens divergentes irão para a fila do Gestor.')">
                <?php echo CsrfHelper::campo(); ?>
                <button type="submit" class="btn btn-outline-slate">Finalizar Conferência</button>
            </form>
        </div>

        <div class="wms-card">
            <div class="wms-card-header">Conferência cega — quantidades esperadas ocultas</div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Código de barras</th>
                                <th>Descrição</th>
                                <th class="text-center">Situação</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                            <?php $itemOk = (int) $item['quantidade_conferida'] >= (int) $item['quantidade_esperada']; ?>
                            <tr class="<?php echo $itemOk ? 'linha-ok' : ''; ?>">
                                <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($item['sku']); ?></td>
                                <td class="tabular-nums text-secondary"><?php echo SecurityHelper::e($item['codigo_barras']); ?></td>
                                <td><?php echo SecurityHelper::e($item['descricao']); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $itemOk ? 'badge-success-lg' : 'badge-neutral-lg'; ?> tabular-nums"><?php echo $itemOk ? 'CONFERIDO' : 'PENDENTE'; ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($itemOk): ?>
                                    <form method="post" action="<?php echo BASE_URL; ?>/recebimento/desfazer/<?php echo (int) $pedido['id']; ?>" class="d-inline"
                                          onsubmit="return confirm('Desfazer a conferência deste produto?')">
                                        <?php echo CsrfHelper::campo(); ?>
                                        <input type="hidden" name="codigo" value="<?php echo SecurityHelper::e($item['codigo_barras']); ?>">
                                        <button class="btn btn-sm btn-outline-slate" title="Desfazer a conferência deste produto">Desfazer</button>
                                    </form>
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
</div>