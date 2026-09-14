<?php
/**
 * app/views/produtos/index.php
 * Listagem do catálogo de produtos com saldo disponível.
 */
?>
<div class="wms-card">
    <div class="wms-card-header d-flex justify-content-between align-items-center">
        <span>Produtos cadastrados</span>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" action="<?php echo BASE_URL; ?>/produtos" class="d-flex gap-2">
                <input class="form-control form-control-sm" type="text" name="q" value="<?php echo SecurityHelper::e($termo); ?>" placeholder="Buscar SKU/código/descrição…">
                <button class="btn btn-outline-slate btn-sm">Filtrar</button>
            </form>
            <a class="btn btn-dark btn-sm text-decoration-none" href="<?php echo BASE_URL; ?>/produtos/novo">+ Novo Produto</a>
        </div>
    </div>
    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-wms mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Código de barras</th>
                        <th>Descrição</th>
                        <th class="text-center">Un.</th>
                        <th class="text-center">Curva</th>
                        <th class="text-center">Saldo disp.</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($p['sku']); ?></td>
                        <td class="tabular-nums text-secondary"><?php echo SecurityHelper::e($p['codigo_barras']); ?></td>
                        <td><?php echo SecurityHelper::e($p['descricao']); ?></td>
                        <td class="text-center"><?php echo SecurityHelper::e($p['unidade_medida']); ?></td>
                        <td class="text-center">
                            <span class="abc-tag abc-<?php echo strtolower(SecurityHelper::e($p['curva_abc'])); ?>"><?php echo SecurityHelper::e($p['curva_abc']); ?></span>
                        </td>
                        <td class="text-center tabular-nums fw-semibold"><?php echo (int) $p['saldo_total']; ?></td>
                        <td class="text-end">
                            <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/produtos/editar/<?php echo (int) $p['id']; ?>">Editar</a>
                            <?php if (AuthHelper::ehAdministrador()): ?>
                            <form method="post" action="<?php echo BASE_URL; ?>/produtos/excluir/<?php echo (int) $p['id']; ?>" class="d-inline"
                                  onsubmit="return confirm('Excluir o produto <?php echo SecurityHelper::e($p['sku']); ?> de TODO o sistema (estoque, kanban, guarda e separação)?')">
                                <?php echo CsrfHelper::campo(); ?>
                                <button class="btn btn-link btn-sm text-danger p-0 ms-1">Excluir</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($produtos)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-secondary" style="color:#64748B">Nenhum produto encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>