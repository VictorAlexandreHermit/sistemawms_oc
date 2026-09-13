<?php
/**
 * app/views/enderecos/index.php
 * Mapa de ocupação dos endereços do galpão.
 */
?>
<div class="wms-card">
    <div class="wms-card-header d-flex justify-content-between align-items-center">
        <span>Posições do galpão</span>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" action="<?php echo BASE_URL; ?>/enderecos" class="d-flex gap-2">
                <input class="form-control form-control-sm" type="text" name="q" value="<?php echo SecurityHelper::e($termo); ?>" placeholder="Buscar CORREDOR/GALPAO/PRATELEIRA…">
                <button class="btn btn-outline-slate btn-sm">Filtrar</button>
            </form>
            <a class="btn btn-dark btn-sm text-decoration-none" href="<?php echo BASE_URL; ?>/enderecos/novo">+ Novo Endereço</a>
        </div>
    </div>
    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-wms mb-0">
                <thead>
                    <tr>
                        <th>Endereço</th>
                        <th>Descrição</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Capacidade</th>
                        <th style="min-width:220px">Ocupação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enderecos as $e): ?>
                    <tr>
                        <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e(EnderecoModel::formato($e)); ?></td>
                        <td><?php echo SecurityHelper::e($e['descricao']); ?></td>
                        <td class="text-center">
                            <?php if ((int) $e['quarantena'] === 1): ?>
                                <span class="badge badge-warning-lg">Quarentena</span>
                            <?php else: ?>
                                <span class="badge badge-neutral-lg">Endereço físico</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center tabular-nums"><?php echo (int) $e['capacidade_maxima']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress-bar-atv flex-grow-1" style="height:8px">
                                    <div style="width:<?php echo (int) $e['percentual']; ?>%"
                                         class="<?php echo $e['percentual'] >= 90 ? 'bg-danger' : ($e['percentual'] >= 70 ? 'bg-warning' : ''); ?>"></div>
                                </div>
                                <span class="small tabular-nums text-secondary"><?php echo (int) $e['total_ocupado']; ?>/<?php echo (int) $e['capacidade_maxima']; ?></span>
                            </div>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/enderecos/editar/<?php echo (int) $e['id']; ?>">Editar</a>
                            <?php if ((int) $e['quarantena'] !== 1): ?>
                            <form method="post" action="<?php echo BASE_URL; ?>/enderecos/excluir/<?php echo (int) $e['id']; ?>" class="d-inline"
                                  onsubmit="return confirm('Excluir o endereço <?php echo SecurityHelper::e(EnderecoModel::formato($e)); ?>?')">
                                <?php echo CsrfHelper::campo(); ?>
                                <button class="btn btn-link btn-sm text-danger p-0 ms-1">Excluir</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($enderecos)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-secondary" style="color:#64748B">Nenhum endereço encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>