<?php
/**
 * app/views/configuracoes/index.php
 * SLAs por etapa do kanban (Gestor).
 */
?>
<div class="wms-card" style="max-width:760px">
    <div class="wms-card-header">Limites de tempo por etapa (SLA)</div>
    <div class="p-4">
        <form method="post" action="<?php echo BASE_URL; ?>/configuracoes/salvar-sla">
            <?php echo CsrfHelper::campo(); ?>
            <div class="table-responsive mb-3">
                <table class="table table-wms mb-0">
                    <thead>
                        <tr>
                            <th>Etapa</th>
                            <th>Descrição</th>
                            <th class="text-center" style="width:200px">Limite (horas)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $descricoes = [
                            'RECEBIDO'    => 'Da recepção até a conferência concluída',
                            'A_ARMAZENAR' => 'Do fim da conferência até a guarda física',
                            'A_SEPARAR'   => 'Do início do picking até a expedição',
                            'A_EXPEDIR'   => 'Da liberação até a baixa de entrega',
                        ];
                        ?>
                        <?php foreach ($slas as $sla): ?>
                        <?php
                        $minutosSla = (int) $sla['tempo_limite_minutos'];
                        $horasSla   = $minutosSla / 60;
                        $valorSla   = ($horasSla === (int) $horasSla)
                                      ? (string) (int) $horasSla
                                      : number_format($horasSla, 1, '.', '');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?php echo SecurityHelper::e($sla['etapa_kanban']); ?></td>
                            <td class="text-secondary"><?php echo SecurityHelper::e($descricoes[$sla['etapa_kanban']] ?? ''); ?></td>
                            <td>
                                <input class="form-control text-center tabular-nums" type="number" min="0.5" step="0.5"
                                       name="sla_<?php echo SecurityHelper::e($sla['etapa_kanban']); ?>"
                                       value="<?php echo SecurityHelper::e($valorSla); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-text text-secondary mb-3">Padrão recomendado: 2 horas por tarefa. Valores fracionados são aceitos (ex.: 1,5 h = 90 min).</div>
            <button type="submit" class="wms-btn-primary">Salvar Limites</button>
        </form>
    </div>
</div>