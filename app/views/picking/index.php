<?php
/**
 * app/views/picking/index.php
 * Fila de pedidos de venda aguardando separação.
 */
?>
<div class="wms-card">
    <div class="wms-card-header d-flex justify-content-between">
        <span>Pedidos aguardando Picking</span>
        <span class="badge badge-warning-lg tabular-nums"><?php echo count($aSeparar); ?></span>
    </div>
    <div class="p-3">
        <?php if (empty($aSeparar)): ?>
            <div class="text-center py-5 text-secondary" style="color:#64748B">
                Nenhum pedido na fila de separação.
                <div class="small mt-1">Abra um pedido de venda na aba <strong>Pedidos</strong> para ele chegar aqui.</div>
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
                        <?php foreach ($aSeparar as $p): ?>
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
                                <a class="btn btn-outline-slate btn-sm text-decoration-none" href="<?php echo BASE_URL; ?>/picking/conferir/<?php echo (int) $p['id']; ?>">Separar →</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>