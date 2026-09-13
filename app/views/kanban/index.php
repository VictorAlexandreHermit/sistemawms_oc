<?php
/**
 * app/views/kanban/index.php
 * Painel Kanban com 4 colunas, Curva ABC no topo e alertas de SLA.
 */
$urlDoCard = [
    'RECEBIDO'    => 'recebimento/conferir/',
    'A_ARMAZENAR' => 'guarda/executar/',
    'A_SEPARAR'   => 'separacao/conferir/',
    'A_EXPEDIR'   => 'separacao/conferir/',
];
$rotulos = [
    'RECEBIDO'    => 'Recebido',
    'A_ARMAZENAR' => 'A Armazenar',
    'A_SEPARAR'   => 'A Separar',
    'A_EXPEDIR'   => 'A Expedir',
];
?>
<div class="kanban">
    <?php foreach ($colunas as $etapa => $cards): ?>
    <div class="kanban-col">
        <div class="kanban-col-header">
            <span><?php echo $rotulos[$etapa]; ?></span>
            <span class="badge badge-neutral-lg tabular-nums"><?php echo count($cards); ?></span>
        </div>
        <div class="kanban-col-body">
            <?php if (empty($cards)): ?>
                <div class="text-center p-4" style="color:#94A3B8;font-size:13px">Sem tarefas</div>
            <?php else: ?>
                <?php foreach ($cards as $c): ?>
                <div class="kanban-card <?php echo SecurityHelper::e($c['sla']['classe']); ?>" data-url="<?php echo BASE_URL . '/' . $urlDoCard[$etapa] . (int) $c['id']; ?>">
                    <div class="card-top">
                        <span class="card-number">Nota <?php echo SecurityHelper::e($c['numero_nota_xml']); ?></span>
                        <span class="abc-tag abc-<?php echo strtolower(SecurityHelper::e($c['prioridade_abc'])); ?>"><?php echo SecurityHelper::e($c['prioridade_abc']); ?></span>
                    </div>
                    <div class="card-client"><?php echo SecurityHelper::e($c['cliente_nome']); ?></div>
                    <div class="card-meta">
                        <span><?php echo (int) $c['itens_count']; ?> item(ns)</span>
                        <span class="timer-badge">
                            <?php
                            $h = floor($c['sla']['minutos'] / 60);
                            $m = $c['sla']['minutos'] % 60;
                            echo sprintf('%dh%02d', $h, $m);
                            ?>
                        </span>
                    </div>
                    <div class="cronometro mt-1 text-secondary" data-inicio-ts="<?php
                        $ts = strtotime($c['ts_' . strtolower($etapa)] ?? date('Y-m-d H:i:s'));
                        echo (int) $ts;
                    ?>" style="font-size:12px"></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>