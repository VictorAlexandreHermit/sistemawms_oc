<?php
/**
 * app/views/dashboard/index.php
 * Dashboard Executivo — 9 indicadores operacionais.
 */
$cap = (int) $ocupacao['cap'];
$usado = (int) $ocupacao['usado'];
$pctOcup = $cap > 0 ? round(($usado / $cap) * 100, 1) : 0;
?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">1 · Ocupação do estoque</div>
            <div class="metric-value"><?php echo $pctOcup; ?>%</div>
            <div class="metric-sub"><?php echo $usado; ?> de <?php echo $cap; ?> un. em posições ativas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">2 · Taxa OTIF acumulada (mês)</div>
            <div class="metric-value" style="color:<?php echo $taxasMes['otif'] >= 95 ? 'var(--wms-success)' : 'var(--wms-danger)'; ?>"><?php echo $taxasMes['otif']; ?>%</div>
            <div class="metric-sub"><?php echo $taxasMes['perfeitas']; ?> perfeitas de <?php echo $taxasMes['total']; ?> avaliadas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">5 · Acuracidade de estoque</div>
            <div class="metric-value" style="color:var(--wms-success)"><?php echo $acuracidade; ?>%</div>
            <div class="metric-sub">Conferências sem divergência</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="wms-stat">
            <div class="metric-label">9 · Produtos movimentados hoje</div>
            <div class="metric-value"><?php echo $movimentados; ?></div>
            <div class="metric-sub">SKUs com saldo alterado no dia</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">

    <div class="col-lg-6">
        <div class="wms-card">
            <div class="wms-card-header">3 · Gargalos de lead time por etapa</div>
            <div class="p-4">
                <?php
                if (max(array_column($gargalos, 'qtd')) < 1 && max(array_column($gargalos, 'media')) < 1) {
                    echo '<div class="text-center py-3 text-secondary" style="color:#64748B">Sem pedidos em processamento.</div>';
                } else {
                    foreach ($gargalos as $etapa => $g):
                        $pct = $g['limite'] > 0 ? min(100, round(($g['media'] / $g['limite']) * 100)) : 0;
                        $cor = $pct >= 100 ? 'var(--wms-danger)' : ($pct >= 80 ? 'var(--wms-warning)' : 'var(--wms-success)');
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold"><?php echo SecurityHelper::e($etapa); ?></span>
                            <span class="tabular-nums">
                                <?php echo (int) $g['qtd']; ?> pedido(s) ·
                                média <?php echo (int) $g['media']; ?> min / limite <?php echo (int) $g['limite']; ?> min
                            </span>
                        </div>
                        <div class="progress-bar-atv" style="height:10px">
                            <div style="width:<?php echo $pct; ?>%;background:<?php echo $cor; ?>"></div>
                        </div>
                    </div>
                <?php
                    endforeach;
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="wms-card">
            <div class="wms-card-header">4 · Avarias por fornecedor</div>
            <div class="p-4">
                <?php if (empty($avarias)): ?>
                    <div class="text-center py-3 text-secondary" style="color:#64748B">Nenhuma avaria registrada.</div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($avarias as $a): ?>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><?php echo SecurityHelper::e($a['fornecedor']); ?></span>
                            <span class="badge badge-danger-lg tabular-nums"><?php echo (int) $a['ocorrencias']; ?> ocorr. · <?php echo (int) $a['total_avariado']; ?> un.</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="wms-card">
            <div class="wms-card-header">6 · Pedidos atrasados (SLA)</div>
            <div class="p-3">
                <?php if (empty($atrasados)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Nenhum pedido em atraso.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <tbody>
                                <?php foreach ($atrasados as $p): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold">Nota <?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                                    <td class="text-secondary"><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                                    <td class="text-end">
                                        <span class="badge badge-danger-lg tabular-nums"><?php echo (int) $p['sla']['minutos']; ?> min em <?php echo SecurityHelper::e($p['status_kanban']); ?></span>
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

    <div class="col-lg-4">
        <div class="wms-card">
            <div class="wms-card-header">7 · Curva ABC</div>
            <div class="p-4">
                <?php
                $pedidosTotais = array_sum(array_column($abc, 'pedidos'));
                foreach (['A', 'B', 'C'] as $curva):
                    $reg = null;
                    foreach ($abc as $r) { if ($r['prioridade_abc'] === $curva) { $reg = $r; break; } }
                    $ped = $reg ? (int) $reg['pedidos'] : 0;
                    $itens = $reg ? (int) $reg['itens'] : 0;
                    $pct = $pedidosTotais > 0 ? round(($ped / $pedidosTotais) * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="abc-tag abc-<?php echo strtolower($curva); ?>"><?php echo $curva; ?></span>
                        <span class="tabular-nums text-secondary"><?php echo $ped; ?> pedido(s) · <?php echo $itens; ?> item(ns)</span>
                    </div>
                    <div class="progress-bar-atv" style="height:10px">
                        <div style="width:<?php echo $pct; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="wms-card">
            <div class="wms-card-header">8 · Endereçamento de cargas</div>
            <div class="p-4">
                <div class="d-flex align-items-end justify-content-between mb-3">
                    <div>
                        <div class="metric-label">Aguardando endereçamento</div>
                        <div class="metric-value"><?php echo (int) $enderecamento['aguardando']; ?> carga(s)</div>
                    </div>
                    <span class="badge badge-neutral-lg tabular-nums"><?php echo (int) $enderecamento['itens']; ?> item(ns)</span>
                </div>
                <div class="text-secondary small" style="color:#64748B">
                    Cargas endereçadas hoje: <strong><?php echo (int) $enderecamento['hoje']; ?></strong> · <a href="<?php echo BASE_URL; ?>/guarda">Ir para a Guarda</a>
                </div>
            </div>
        </div>
    </div>
</div>