<?php
/**
 * app/views/expedicao/index.php
 * Expedição: gerar nota (em trânsito), rota animada com contagem regressiva
 * de 15s e confirmação automática de entrega ao destinatário.
 */
?>
<div class="row g-4">

    <div class="col-lg-6">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between">
                <span>A Expedir (embalados)</span>
                <span class="badge badge-dark-lg tabular-nums"><?php echo count($aExpedir); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($aExpedir)): ?>
                    <div class="text-center py-5 text-secondary" style="color:#64748B">
                        Nenhum pedido embalado aguardando saída.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th class="text-center">Itens</th>
                                    <th class="text-center">SLA</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aExpedir as $p): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                                    <td class="text-center tabular-nums"><?php echo (int) $p['itens_count']; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo SecurityHelper::e($p['sla']['classe']); ?>">
                                            <?php echo (int) $p['sla']['minutos']; ?> min
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="<?php echo BASE_URL; ?>/expedicao/expedir/<?php echo (int) $p['id']; ?>"
                                              onsubmit="return confirm('Gerar a nota e colocar o pedido <?php echo SecurityHelper::e($p['numero_nota_xml']); ?> em trânsito?')">
                                            <?php echo CsrfHelper::campo(); ?>
                                            <button class="btn btn-dark btn-sm">Gerar Nota e Expedir</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between">
                <span>Mercadoria em Trânsito</span>
                <span class="badge badge-info-lg tabular-nums"><?php echo count($emTransito); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($emTransito)): ?>
                    <div class="text-center py-5 text-secondary" style="color:#64748B">
                        Nenhum veículo em rota. Use "Gerar Nota e Expedir" ao lado.
                    </div>
                <?php else: ?>
                    <?php foreach ($emTransito as $p): ?>
                    <div class="rota-card border rounded p-3 mb-3" data-entregar-s="15">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <span class="fw-semibold tabular-nums">🚚 <?php echo SecurityHelper::e($p['numero_nota_xml']); ?> — <?php echo SecurityHelper::e($p['cliente_nome']); ?></span>
                            <span class="badge badge-info-lg rota-timer">Entregar em 15s</span>
                        </div>
                        <div class="rota-pista">
                            <div class="rota-magico">🏭</div>
                            <div class="rota-caminhao">🚚</div>
                            <div class="rota-gps">📍</div>
                        </div>
                        <div class="form-text mb-2" style="color:#64748B">Galpão → destinatário · entrega confirmada automaticamente ao fim da contagem (15 segundos).</div>
                        <form method="post" action="<?php echo BASE_URL; ?>/expedicao/confirmar-entrega/<?php echo (int) $p['id']; ?>">
                            <?php echo CsrfHelper::campo(); ?>
                            <button type="submit" class="btn btn-success btn-sm w-100">Confirmar Entrega (manual)</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="wms-card mt-4">
    <div class="wms-card-header d-flex justify-content-between">
        <span>Últimos entregues</span>
        <span class="badge badge-success-lg tabular-nums"><?php echo count($entregues); ?></span>
    </div>
    <div class="p-3">
        <?php if (empty($entregues)): ?>
            <div class="text-center py-4 text-secondary" style="color:#64748B">Histórico vazio.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-wms mb-0">
                    <thead>
                        <tr><th>Pedido</th><th>Cliente</th><th>Entregue em</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregues as $p): ?>
                        <tr>
                            <td class="tabular-nums"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                            <td><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                            <td class="tabular-nums"><?php echo SecurityHelper::e(DateHelper::exibir($p['ts_entregue'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>