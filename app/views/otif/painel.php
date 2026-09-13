<?php
/**
 * app/views/otif/painel.php
 * Painel interno OTIF: mapa de envios, taxas e críticas.
 */
$t = $taxas;
?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">Avaliações recebidas</div>
            <div class="metric-value"><?php echo (int) $t['total']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">OTIF Global</div>
            <div class="metric-value" style="color:<?php echo $t['otif_percentual'] >= 95 ? 'var(--wms-success)' : 'var(--wms-danger)'; ?>"><?php echo $t['otif_percentual']; ?>%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">Prazo OK</div>
            <div class="metric-value" style="color:var(--wms-info)"><?php echo (int) $t['prazo_ok']; ?><span class="fs-6 text-secondary">/<?php echo (int) $t['total']; ?></span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">Sem avaria · Conformidade</div>
            <div class="metric-value"><?php echo (int) $t['avaria_ok']; ?> · <?php echo (int) $t['conformidade_ok']; ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between align-items-center">
                <span>Mapa de envios</span>
                <div class="d-flex gap-1">
                    <?php
                    $abas = ['TODAS' => '', 'PENDENTE' => 'PENDENTE', 'ENVIADO' => 'ENVIADO', 'FALHA' => 'FALHA'];
                    foreach ($abas as $rotulo => $valor):
                        $ativa = ($filtro === $valor);
                    ?>
                        <a class="badge-tab <?php echo $ativa ? 'ativa' : ''; ?>" href="<?php echo BASE_URL . '/otif/painel' . ($valor !== '' ? '?status=' . $valor : ''); ?>"><?php echo $rotulo; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Nota</th>
                                <th>Cliente</th>
                                <th class="text-center">Status envio</th>
                                <th class="text-center">Resposta</th>
                                <th class="text-center">Expira em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesquisas)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-secondary" style="color:#64748B">Nenhum envio neste filtro.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pesquisas as $po): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($po['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($po['cliente_nome']); ?></td>
                                    <td class="text-center">
                                        <?php
                                        $cores = ['ENVIADO' => 'badge-success-lg', 'PENDENTE' => 'badge-warning-lg', 'FALHA' => 'badge-danger-lg'];
                                        ?>
                                        <span class="badge <?php echo $cores[$po['status_envio']] ?? 'badge-neutral-lg'; ?>"><?php echo SecurityHelper::e($po['status_envio']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($po['respondido_em'] !== null): ?>
                                            <span class="badge badge-success-lg">Respondida em <?php echo SecurityHelper::e(DateHelper::exibirData($po['respondido_em'])); ?></span>
                                        <?php elseif (PesquisaOtifModel::expirada($po)): ?>
                                            <span class="badge badge-danger-lg">Expirada</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral-lg">Aguardando</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center tabular-nums text-secondary"><?php echo SecurityHelper::e(DateHelper::exibirData($po['data_expiracao'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="wms-card">
            <div class="wms-card-header">Avaliações críticas</div>
            <div class="p-3">
                <?php if (empty($criticas)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Nenhuma avaliação negativa registrada.</div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($criticas as $c): ?>
                        <li class="py-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong class="tabular-nums">Nota <?php echo SecurityHelper::e($c['numero_nota_xml']); ?></strong>
                                <span class="badge badge-danger-lg">Negativa</span>
                            </div>
                            <div class="small text-secondary">
                                <?php echo SecurityHelper::e($c['cliente_nome']); ?> · <?php echo SecurityHelper::e(DateHelper::exibirData($c['respondido_em'])); ?>
                            </div>
                            <div class="small mt-1">
                                <?php if ($c['prazo_cumprido'] === 'NAO'): ?><span class="badge badge-warning-lg me-1">Prazo não cumprido</span><?php endif; ?>
                                <?php if ($c['sem_avaria'] === 'NAO'): ?><span class="badge badge-warning-lg me-1">Avaria</span><?php endif; ?>
                                <?php if ($c['conformidade_itens'] === 'NAO'): ?><span class="badge badge-warning-lg me-1">Item ausente</span><?php endif; ?>
                            </div>
                            <?php if (!empty($c['observacoes'])): ?>
                                <div class="small mt-1" style="color:var(--wms-text-muted)">“<?php echo SecurityHelper::e(mb_strimwidth($c['observacoes'], 0, 120, '…')); ?>”</div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>