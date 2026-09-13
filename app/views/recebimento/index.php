<?php
/**
 * app/views/recebimento/index.php
 * Upload do XML da NF-e e cargas aguardando conferência.
 */
?>
<div class="row g-4">

    <div class="col-lg-5">
        <div class="wms-card">
            <div class="wms-card-header">Importar XML da NF-e</div>
            <div class="p-4">
                <p class="text-secondary" style="color:#475569">
                    Envie o XML da Nota Fiscal emitida pelo fornecedor. O sistema irá
                    registrar a carga e abrir a <strong>Conferência Cega</strong>.
                </p>

                <form method="post" action="<?php echo BASE_URL; ?>/recebimento/importar" enctype="multipart/form-data">
                    <?php echo CsrfHelper::campo(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="xml_nota">Arquivo XML (*.xml)</label>
                        <input class="form-control" type="file" id="xml_nota" name="xml_nota"
                               accept=".xml,text/xml" required>
                        <div class="form-text">Máximo 10MB.</div>
                    </div>

                    <button type="submit" class="wms-btn-primary">Importar e Iniciar Conferência</button>
                </form>
            </div>
        </div>

        <div class="wms-card mt-4">
            <div class="wms-card-header">Dica de operação</div>
            <div class="p-4">
                <ul class="mb-0 ps-3" style="color:#475569">
                    <li>Bipe cada item físico com o <strong>leitor USB</strong> (Enter automático).</li>
                    <li>Também é possível digitar o código manualmente no campo de leitura.</li>
                    <li>As quantidades esperadas do XML ficam <strong>ocultas</strong> durante a conferência.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="wms-card">
            <div class="wms-card-header">Cargas aguardando conferência cega</div>
            <div class="p-3">
                <?php if (empty($pendentes)): ?>
                    <div class="text-center py-5 text-secondary" style="color:#64748B">
                        Nenhuma carga em recebimento no momento.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Cliente / Destinatário</th>
                                    <th>Fornecedor</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendentes as $p): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($p['cliente_nome']); ?></td>
                                    <td><?php echo SecurityHelper::e($p['fornecedor_nome'] ?: '—'); ?></td>
                                    <td class="text-end">
                                        <a class="wms-btn-primary text-decoration-none d-inline-block" href="<?php echo BASE_URL; ?>/recebimento/conferir/<?php echo (int) $p['id']; ?>">Conferir</a>
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
</div>