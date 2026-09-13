<?php
/**
 * app/views/guarda/index.php
 * Lista de cargas aguardando guarda física.
 */
?>
<?php if (empty($detalhes)): ?>
    <div class="wms-card p-5 text-center">
        <div style="color:#64748B">
            <h3 style="font-size:18px;font-weight:600;color:#0F172A">Nenhuma carga aguardando guarda</h3>
            <p class="mb-0">As cargas conferidas aparecem aqui para o direcionamento físico (Rua-Prédio-Nível).</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($detalhes as $d): $carga = $d['carga']; ?>
        <div class="col-lg-6">
            <div class="wms-card">
                <div class="wms-card-header d-flex justify-content-between">
                    <span>Nota <?php echo SecurityHelper::e($carga['numero_nota_xml']); ?></span>
                    <span class="badge badge-info-lg"><?php echo count($d['pendentes']); ?> pendente(s)</span>
                </div>
                <div class="p-4">
                    <p class="mb-1 fw-semibold"><?php echo SecurityHelper::e($carga['cliente_nome']); ?></p>
                    <p class="text-secondary mb-3" style="color:#64748B">
                        Produtos conferidos aguardando alocação física.
                    </p>

                    <ul class="list-unstyled mb-3 small">
                        <?php foreach ($d['pendentes'] as $item): ?>
                        <li class="py-1 border-bottom">
                            <strong class="tabular-nums"><?php echo SecurityHelper::e($item['sku']); ?></strong>
                            — <?php echo SecurityHelper::e(mb_strimwidth($item['descricao'], 0, 42, '…')); ?>
                            <span class="badge badge-neutral-lg ms-2 tabular-nums"><?php echo (int) $item['a_guardar']; ?> un.</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <a class="wms-btn-primary text-decoration-none" href="<?php echo BASE_URL; ?>/guarda/executar/<?php echo (int) $carga['id']; ?>">
                        Executar Guarda →
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>