<?php
/**
 * app/views/kanban/index.php
 * Quadro operacional: Recebimento/Guarda/Armazenado (inbound) e
 * Picking/Packing/Expedição/Rota (outbound). Curva ABC no topo e SLA por cor.
 */
$urlDoCard = [
    'RECEBIDO'    => 'recebimento/conferir/',
    'A_ARMAZENAR' => 'guarda/executar/',
    'ARMAZENADO'  => 'produtos',
    'A_SEPARAR'   => 'picking/conferir/',
    'A_EMBALAR'   => 'packing/conferir/',
    'A_EXPEDIR'   => 'expedicao',
    'EM_TRANSITO' => 'expedicao',
];
$rotulos = [
    'RECEBIDO'    => PedidoModel::rotuloStatus('RECEBIDO'),
    'A_ARMAZENAR' => PedidoModel::rotuloStatus('A_ARMAZENAR'),
    'ARMAZENADO'  => PedidoModel::rotuloStatus('ARMAZENADO'),
    'A_SEPARAR'   => PedidoModel::rotuloStatus('A_SEPARAR'),
    'A_EMBALAR'   => PedidoModel::rotuloStatus('A_EMBALAR'),
    'A_EXPEDIR'   => PedidoModel::rotuloStatus('A_EXPEDIR'),
    'EM_TRANSITO' => PedidoModel::rotuloStatus('EM_TRANSITO'),
];
?>
<div class="kanban">
    <?php foreach ($colunas as $etapa => $cards): ?>
    <?php $repouso = in_array($etapa, ['ARMAZENADO', 'EM_TRANSITO'], true); ?>
    <div class="kanban-col <?php echo $repouso ? 'kanban-col-repouso' : ''; ?>">
        <div class="kanban-col-header">
            <span><?php echo $rotulos[$etapa]; ?></span>
            <span class="badge badge-neutral-lg tabular-nums"><?php echo count($cards); ?></span>
        </div>
        <div class="kanban-col-body">
            <?php if (empty($cards)): ?>
                <div class="text-center p-4" style="color:#94A3B8;font-size:13px">Sem tarefas</div>
            <?php else: ?>
                <?php foreach ($cards as $c): ?>
                <?php
                $classeSla = $c['sla']['classe'];
                $acumulaRepouso = in_array($etapa, ['ARMAZENADO', 'EM_TRANSITO'], true);
                $linkCard = $etapa === 'ARMAZENADO' ? null : BASE_URL . '/' . $urlDoCard[$etapa] . (int) $c['id'];
                ?>
                <div class="kanban-card <?php echo SecurityHelper::e($classeSla); ?> <?php echo $acumulaRepouso ? 'kanban-card-repouso' : ''; ?>"
                     <?php echo $linkCard !== null ? 'data-url="' . SecurityHelper::e($linkCard) . '"' : ''; ?>>
                    <div class="card-top">
                        <span class="card-number"><?php echo $etapa === 'ARMAZENADO' ? 'Carga ' : 'Nota '; ?><?php echo SecurityHelper::e($c['numero_nota_xml']); ?></span>
                        <span class="d-flex align-items-center gap-2">
                            <?php if (AuthHelper::ehAdministrador()): ?>
                            <form method="post" action="<?php echo BASE_URL; ?>/kanban/excluir/<?php echo (int) $c['id']; ?>" class="kanban-excluir"
                                  onsubmit="return confirm('Excluir definitivamente o processo da nota <?php echo SecurityHelper::e($c['numero_nota_xml']); ?> (<?php echo SecurityHelper::e($c['cliente_nome']); ?>)? Ele sai do Kanban, Guarda, Picking, Packing e Expedição.')">
                                <?php echo CsrfHelper::campo(); ?>
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Excluir processo (Administrador)" style="text-decoration:none">✕</button>
                            </form>
                            <?php endif; ?>
                            <span class="abc-tag abc-<?php echo strtolower(SecurityHelper::e($c['prioridade_abc'])); ?>"><?php echo SecurityHelper::e($c['prioridade_abc']); ?></span>
                        </span>
                    </div>
                    <div class="card-client"><?php echo SecurityHelper::e($c['cliente_nome']); ?></div>
                    <div class="card-meta">
                        <span><?php echo (int) $c['itens_count']; ?> item(ns)</span>
                        <?php if ($etapa !== 'ARMAZENADO' && $etapa !== 'EM_TRANSITO'): ?>
                        <span class="timer-badge">
                            <?php
                            $h = floor($c['sla']['minutos'] / 60);
                            $m = $c['sla']['minutos'] % 60;
                            echo sprintf('%dh%02d', $h, $m);
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($etapa === 'ARMAZENADO'): ?>
                    <form method="post" action="<?php echo BASE_URL; ?>/pedidos/abrir-carga/<?php echo (int) $c['id']; ?>" class="kanban-abrir">
                        <?php echo CsrfHelper::campo(); ?>
                        <input type="text" name="destinatario" class="form-control form-control-sm" placeholder="Destinatário (cliente)" maxlength="100" required>
                        <button type="submit" class="btn btn-sm btn-primary mt-1 w-100">Abrir pedido → Picking</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($etapa !== 'ARMAZENADO' && $etapa !== 'EM_TRANSITO'): ?>
                    <div class="cronometro mt-1 text-secondary" data-inicio-ts="<?php
                        $ts = strtotime($c['ts_' . strtolower($etapa)] ?? date('Y-m-d H:i:s'));
                        echo (int) $ts;
                    ?>" style="font-size:12px"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>