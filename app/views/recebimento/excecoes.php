<?php
/**
 * app/views/recebimento/excecoes.php
 * Fila de exceções do Gestor (divergências entre físico e XML).
 */
?>
<div class="wms-card">
    <div class="wms-card-header">Divergências pendentes de aprovação</div>
    <div class="p-3">
        <?php if (empty($pendentes)): ?>
            <div class="text-center py-5 text-secondary" style="color:#64748B">
                Nenhuma divergência pendente. Tudo certo!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-wms mb-0">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Produto</th>
                            <th class="text-center">Qtd. XML</th>
                            <th class="text-center">Qtd. Física</th>
                            <th>Motivo</th>
                            <th class="text-end">Decisão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendentes as $d): ?>
                        <tr>
                            <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($d['numero_nota_xml']); ?></td>
                            <td><?php echo SecurityHelper::e($d['sku'] . ' — ' . $d['descricao']); ?></td>
                            <td class="text-center tabular-nums"><?php echo (int) $d['qtd_xml']; ?></td>
                            <td class="text-center tabular-nums fw-semibold"><?php echo (int) $d['qtd_fisica']; ?></td>
                            <td>
                                <span class="badge <?php echo (int) $d['qtd_fisica'] > (int) $d['qtd_xml'] ? 'badge-warning-lg' : 'badge-danger-lg'; ?>">
                                    <?php echo SecurityHelper::e($d['motivo']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <form method="post" action="<?php echo BASE_URL; ?>/recebimento/aprovar/<?php echo (int) $d['id']; ?>"
                                          onsubmit="return confirm('Aprovar esta divergência e consolidar a entrada?')">
                                        <?php echo CsrfHelper::campo(); ?>
                                        <button class="btn btn-outline-slate btn-sm" style="color:#15803D;border-color:#86EFAC">Aprovar Entrada</button>
                                    </form>
                                    <form method="post" action="<?php echo BASE_URL; ?>/recebimento/rejeitar/<?php echo (int) $d['id']; ?>"
                                          onsubmit="return confirm('Rejeitar o registro desta divergência?')">
                                        <?php echo CsrfHelper::campo(); ?>
                                        <button class="btn btn-outline-slate btn-sm" style="color:#B91C1C;border-color:#FECACA">Rejeitar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>