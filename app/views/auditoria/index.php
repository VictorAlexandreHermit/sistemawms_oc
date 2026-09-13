<?php
/**
 * app/views/auditoria/index.php
 * Auditoria de estoque: ajuste manual + trilha inalterável.
 */
?>
<div class="row g-4">

    <div class="col-lg-4">
        <div class="wms-card p-4">
            <h3 class="h5 mb-3">Ajustar saldo em endereço</h3>
            <form method="post" action="<?php echo BASE_URL; ?>/auditoria/ajustar" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>

                <div class="mb-3">
                    <label class="form-label">Produto (código de barras ou SKU)</label>
                    <input class="form-control" type="text" name="produto" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Endereço (RUA-PREDIO-NIVEL)</label>
                    <input class="form-control" type="text" name="endereco" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nova quantidade contada</label>
                    <input class="form-control" type="number" name="nova_quantidade" min="0" value="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Motivo (obrigatório)</label>
                    <select class="form-select" name="motivo" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($motivos as $codigo => $texto): ?>
                            <option value="<?php echo SecurityHelper::e($codigo); ?>"><?php echo SecurityHelper::e($texto); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="2" maxlength="500"></textarea>
                </div>

                <button type="submit" class="wms-btn-danger w-100"
                        onclick="return confirm('Confirmar o ajuste de saldo? Ele ficará gravado na trilha de auditoria.')">
                    Aplicar Ajuste
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between align-items-center">
                <span>Trilha de auditoria</span>
                <form method="get" action="<?php echo BASE_URL; ?>/auditoria" class="d-flex gap-2">
                    <input class="form-control form-control-sm" type="text" name="q" value="<?php echo SecurityHelper::e($termo); ?>" placeholder="Buscar produto/endereço…">
                    <button class="btn btn-outline-slate btn-sm">Filtrar</button>
                </form>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Endereço</th>
                                <th class="text-center">Anterior</th>
                                <th class="text-center">Nova</th>
                                <th>Motivo</th>
                                <th>Operador</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-secondary" style="color:#64748B">Nenhum ajuste registrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historico as $l): ?>
                                <tr>
                                    <td class="tabular-nums text-secondary" style="white-space:nowrap"><?php echo SecurityHelper::e(DateHelper::exibir($l['created_at'])); ?></td>
                                    <td><strong class="tabular-nums"><?php echo SecurityHelper::e($l['sku']); ?></strong></td>
                                    <td class="tabular-nums"><?php echo SecurityHelper::e(EnderecoModel::formato($l)); ?></td>
                                    <td class="text-center tabular-nums"><?php echo (int) $l['quantidade_anterior']; ?></td>
                                    <td class="text-center tabular-nums fw-semibold"><?php echo (int) $l['quantidade_nova']; ?></td>
                                    <td>
                                        <span class="badge <?php echo (int) $l['quantidade_nova'] > (int) $l['quantidade_anterior'] ? 'badge-success-lg' : 'badge-warning-lg'; ?>">
                                            <?php echo SecurityHelper::e($motivos[$l['motivo_codigo']] ?? $l['motivo_codigo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo SecurityHelper::e(explode(' ', $l['operador_nome'])[0]); ?></td>
                                    <td class="text-end">
                                        <?php if (AuthHelper::perfil() === 'GESTOR'): ?>
                                        <form method="post" action="<?php echo BASE_URL; ?>/auditoria/excluir/<?php echo (int) $l['id']; ?>"
                                              onsubmit="return confirm('Remover este registro da trilha?')">
                                            <?php echo CsrfHelper::campo(); ?>
                                            <button class="btn btn-link btn-sm text-danger p-0">Excluir</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>