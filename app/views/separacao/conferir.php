<?php
/**
 * app/views/separacao/conferir.php
 * Estação de Picking & Packing com bipagem obrigatória.
 */
$pct = (float) $progresso['percentual'];
?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-semibold">Progresso da conferência</span>
        <span class="tabular-nums fw-semibold"><?php echo (int) $progresso['bipados']; ?>/<?php echo (int) $progresso['esperados']; ?> un. (<?php echo $pct; ?>%)</span>
    </div>
    <div class="progress-bar-atv" style="height:10px">
        <div style="width:<?php echo $pct; ?>%"></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="wms-card p-4">
            <div class="mb-3">
                <span class="badge badge-dark-lg">Pedido <?php echo SecurityHelper::e($pedido['numero_nota_xml']); ?></span>
            </div>

            <form method="post" action="<?php echo BASE_URL; ?>/separacao/bipar/<?php echo (int) $pedido['id']; ?>" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>
                <label class="form-label">Leia o código do item coletado</label>
                <input class="form-control bipador" type="text" name="codigo"
                       placeholder="Código de barras" autofocus required>
                <button type="submit" class="wms-btn-primary w-100 mt-3">Registrar Bipagem</button>
            </form>

            <?php if ($pedido['status_kanban'] === 'A_SEPARAR' && $progresso['bipados'] >= $progresso['esperados'] && $progresso['esperados'] > 0): ?>
                    <form method="post" action="<?php echo BASE_URL; ?>/separacao/concluir/<?php echo (int) $pedido['id']; ?>"
                          onsubmit="return confirm('Concluir embalagem e liberar para expedição?')">
                        <?php echo CsrfHelper::campo(); ?>
                        <button class="wms-btn-success w-100">Concluir Embalagem &amp; Liberar Expedição</button>
                    </form>
                <?php elseif ($pedido['status_kanban'] === 'A_EXPEDIR'): ?>
                    <div class="alert-info mt-4">
                        Pedido liberado para expedição — use o botão <strong>Entregar &amp; Disparar OTIF</strong> na fila de separação.
                    </div>
                <?php endif; ?>
                <a class="btn btn-outline-slate mt-4" href="<?php echo BASE_URL; ?>/separacao">Voltar à fila</a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header">Itens do pedido</div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Esperado</th>
                                <th class="text-center">Bipado</th>
                                <th>Localização (endereço &rarr; saldo)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                            <?php $bipado = (int) $item['quantidade_bipada_picking']; $esperado = (int) $item['quantidade_esperada']; $itemOk = $bipado >= $esperado; ?>
                            <tr class="<?php echo $itemOk ? 'linha-ok' : ''; ?>">
                                <td class="tabular-nums">
                                    <strong><?php echo SecurityHelper::e($item['sku']); ?></strong>
                                    <div class="text-secondary"><?php echo SecurityHelper::e(mb_strimwidth($item['descricao'], 0, 44, '…')); ?></div>
                                </td>
                                <td class="text-center tabular-nums"><?php echo $esperado; ?></td>
                                <td class="text-center tabular-nums fw-semibold"><?php echo $bipado; ?></td>
                                <td>
                                    <?php if (empty($item['localizacoes'])): ?>
                                        <span class="badge badge-warning-lg">Sem saldo disponível</span>
                                    <?php else: ?>
                                        <?php foreach ($item['localizacoes'] as $loc): ?>
                                            <span class="badge badge-neutral-lg me-1 tabular-nums"><?php echo SecurityHelper::e($loc['endereco_codigo']); ?> (<?php echo (int) $loc['saldo']; ?>)</span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($itemOk): ?>
                                        <span class="badge badge-success-lg">PICKING COMPLETO</span>
                                    <?php elseif ($bipado > 0): ?>
                                        <span class="badge badge-warning-lg">PARCIAL</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral-lg">AGUARDANDO</span>
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