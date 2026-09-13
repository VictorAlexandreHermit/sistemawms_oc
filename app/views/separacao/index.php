<?php
/**
 * app/views/separacao/index.php
 * Fila de separação, expedição e histórico de entregues.
 */
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between">
                <span>A Separar</span>
                <span class="badge badge-warning-lg tabular-nums"><?php echo count($aSeparar); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($aSeparar)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Nenhuma ordem de separação aberta.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Cliente</th>
                                    <th class="text-center">ABC</th>
                                    <th>Ação</th>
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
                                    <td>
                                        <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/separacao/conferir/<?php echo (int) $p['id']; ?>">Conferir Picking</a>
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
                <span>A Expedir</span>
                <span class="badge badge-info-lg tabular-nums"><?php echo count($aExpedir); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($aExpedir)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Nenhum pedido aguardando baixa de expedição.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Cliente</th>
                                    <th class="text-center">SLA</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aExpedir as $p): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo SecurityHelper::e($p['sla']['classe']); ?>">
                                            <?php echo (int) $p['sla']['minutos']; ?> min
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" action="<?php echo BASE_URL; ?>/separacao/expedir/<?php echo (int) $p['id']; ?>"
                                              onsubmit="return confirm('Confirmar baixa de expedição (ENTREGUE) e disparar a pesquisa OTIF?')">
                                            <?php echo CsrfHelper::campo(); ?>
                                            <button class="btn btn-dark btn-sm">Entregar &amp; Disparar OTIF</button>
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

        <div class="wms-card mt-4">
            <div class="wms-card-header d-flex justify-content-between">
                <span>Últimos Entregues</span>
                <span class="badge badge-success-lg tabular-nums"><?php echo count($entregues); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($entregues)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Histórico vazio.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr><th>Nota</th><th>Cliente</th><th>Entregue em</th></tr>
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
    </div>
</div>