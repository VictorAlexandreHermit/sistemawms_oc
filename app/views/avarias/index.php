<?php
/**
 * app/views/avarias/index.php
 * Registro de avarias com foto + painel de quarentena.
 */
?>
<div class="row g-4">

    <div class="col-lg-4">
        <div class="wms-card p-4">
            <h3 class="h5 mb-3">Registrar avaria</h3>
            <form method="post" action="<?php echo BASE_URL; ?>/avarias/registrar" enctype="multipart/form-data" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>

                <div class="mb-3">
                    <label class="form-label">Produto (apenas o SKU, ex.: SIS-001)</label>
                    <input class="form-control bipador" type="text" name="produto" placeholder="SKU (ex.: SIS-001)" required autofocus>
                </div>

                <?php
                $campoEnderecoRotulo = 'Endereço físico de origem (CORREDOR / GALPÃO / PRATELEIRA)';
                include BASE_DIR . '/app/views/templates/campos_endereco_cascata.php';
                ?>

                <div class="mb-3">
                    <label class="form-label">Quantidade avariada</label>
                    <input class="form-control" type="number" name="quantidade" min="1" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label">Motivo</label>
                        <select class="form-select" name="motivo">
                            <?php foreach ($motivos as $m): ?>
                                <option value="<?php echo SecurityHelper::e($m); ?>"><?php echo SecurityHelper::e($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Etapa</label>
                        <select class="form-select" name="etapa">
                            <?php foreach ($etapas as $e): ?>
                                <option value="<?php echo SecurityHelper::e($e); ?>"><?php echo SecurityHelper::e(ucfirst(strtolower($e))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto (JPG/PNG até 5MB)</label>
                    <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png">
                </div>

                <button type="submit" class="wms-btn-danger w-100">Registrar Avaria</button>
            </form>
        </div>

        <div class="wms-card p-4 mt-4">
            <h3 class="h5 mb-3">Quarentena</h3>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="metric-label">Saldo em Quarentena</div>
                    <div class="metric-value"><?php echo (int) $saldoQtr; ?> un.</div>
                </div>
                <span class="badge badge-warning-lg">QTR-VIRT-000</span>
            </div>
            <ul class="list-unstyled mt-3 mb-0">
                <?php if (empty($fornecedores)): ?>
                    <li class="text-secondary" style="color:#64748B">Sem ocorrências registradas.</li>
                <?php else: ?>
                    <?php foreach ($fornecedores as $f): ?>
                        <li class="d-flex justify-content-between py-1 border-bottom small">
                            <span><?php echo SecurityHelper::e($f['fornecedor']); ?></span>
                            <strong class="tabular-nums"><?php echo (int) $f['ocorrencias']; ?> × <?php echo (int) $f['total_avariado']; ?> un.</strong>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between">
                <span>Histórico de avarias</span>
                <span class="badge badge-neutral-lg tabular-nums"><?php echo count($registros); ?></span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th class="text-center">Qtd.</th>
                                <th>Endereço</th>
                                <th>Motivo</th>
                                <th>Etapa</th>
                                <th>Operador</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $a): ?>
                            <tr>
                                <td class="tabular-nums text-secondary" style="white-space:nowrap"><?php echo SecurityHelper::e(DateHelper::exibir($a['created_at'])); ?></td>
                                <td>
                                    <strong class="tabular-nums"><?php echo SecurityHelper::e($a['sku']); ?></strong>
                                    <div class="text-secondary"><?php echo SecurityHelper::e(mb_strimwidth($a['descricao'], 0, 36, '…')); ?></div>
                                </td>
                                <td class="text-center tabular-nums fw-semibold"><?php echo (int) $a['quantidade']; ?></td>
                                <td class="tabular-nums"><?php echo SecurityHelper::e(EnderecoModel::formato($a)); ?></td>
                                <td><?php echo SecurityHelper::e($a['motivo']); ?></td>
                                <td><span class="badge badge-neutral-lg"><?php echo SecurityHelper::e($a['etapa']); ?></span></td>
                                <td><?php echo SecurityHelper::e(explode(' ', $a['operador_nome'])[0]); ?></td>
                                <td>
                                    <?php if (!empty($a['foto_url'])): ?>
                                        <a href="<?php echo BASE_URL . '/uploads/' . SecurityHelper::e($a['foto_url']); ?>" target="_blank" class="btn btn-outline-slate btn-sm">Ver</a>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary" style="color:#64748B">Nenhuma avaria registrada.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>