<?php
/**
 * app/views/packing/index.php
 * Fila de pedidos embalando (já separados pelo Picking).
 */
?>
<div class="wms-card">
    <div class="wms-card-header d-flex justify-content-between">
        <span>Pedidos aguardando Packing</span>
        <span class="badge badge-info-lg tabular-nums"><?php echo count($aEmbalar); ?></span>
    </div>
    <div class="p-3">
        <?php if (empty($aEmbalar)): ?>
            <div class="text-center py-5 text-secondary" style="color:#64748B">
                Nenhum pedido aguardando embalagem.
                <div class="small mt-1">Conclua a separação no <strong>Picking</strong> para o pedido chegar aqui.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-wms mb-0">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th class="text-center">ABC</th>
                            <th class="text-center">Itens</th>
                            <th class="text-center">SLA</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aEmbalar as $p): ?>
                        <tr>
                            <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                            <td><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                            <td class="text-center">
                                <span class="abc-tag abc-<?php echo strtolower(SecurityHelper::e($p['prioridade_abc'])); ?>"><?php echo SecurityHelper::e($p['prioridade_abc']); ?></span>
                            </td>
                            <td class="text-center tabular-nums"><?php echo (int) $p['itens_count']; ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo SecurityHelper::e($p['sla']['classe']); ?>">
                                    <?php echo (int) $p['sla']['minutos']; ?> min
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-outline-slate btn-sm text-decoration-none" href="<?php echo BASE_URL; ?>/packing/conferir/<?php echo (int) $p['id']; ?>">Embalar →</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>