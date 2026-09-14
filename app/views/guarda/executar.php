<?php
/**
 * app/views/guarda/executar.php
 * Instrução de guarda: bipe o produto e o endereço físico de destino.
 */
?>
<div class="row g-4">

    <div class="col-lg-5">
        <div class="wms-card p-4">
            <div class="mb-3">
                <span class="badge badge-dark-lg">Nota <?php echo SecurityHelper::e($pedido['numero_nota_xml']); ?></span>
                <span class="badge badge-neutral-lg ms-1"><?php echo count($pendentes); ?> item(ns) a guardar</span>
            </div>

            <form method="post" action="<?php echo BASE_URL; ?>/guarda/confirmar/<?php echo (int) $pedido['id']; ?>" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>

                <div class="mb-3">
                    <label class="form-label" for="produto">1. Bipe o SKU do produto</label>
                    <input class="form-control bipador" type="text" id="produto" name="produto"
                           placeholder="SKU do produto (ex.: SIS-001)" data-proximo="endereco" autofocus required>
                </div>

                <?php
                $campoEnderecoRotulo = '2. Corredor, galpão e prateleira de armazenamento';
                $campoEnderecoId     = 'endereco';
                $campoEnderecoDica   = 'Ao trocar corredor ou galpão, as prateleiras são atualizadas automaticamente.';
                include BASE_DIR . '/app/views/templates/campos_endereco_cascata.php';
                ?>

                <button type="submit" class="wms-btn-primary w-100">Confirmar Alocação Física</button>
            </form>

            <p class="text-secondary mt-3 mb-0" style="color:#64748B;font-size:13px">
                Dica: aqui o sistema pesquisa APENAS pelo SKU (ex.: SIS-001). Se o leitor USB bipar o produto,
                ele envia <em>Enter</em> automaticamente e o foco vai para o corredor. Selecione corredor,
                galpão e prateleira onde está armazenando
                (a sugestão do sistema já vem pré-selecionada em ordem crescente).
            </p>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="wms-card">
            <div class="wms-card-header">Instruções geradas pelo sistema</div>
            <div class="p-3">
                <?php if (empty($pendentes)): ?>
                    <div class="text-center py-5 text-secondary" style="color:#64748B">Todos os itens já foram guardados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Direcionar</th>
                                    <th class="text-center">Já guardado</th>
                                    <th>Sugestão de endereço</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendentes as $item): ?>
                                <tr>
                                    <td class="tabular-nums">
                                        <strong><?php echo SecurityHelper::e($item['sku']); ?></strong>
                                        <div class="text-secondary"><?php echo SecurityHelper::e(mb_strimwidth($item['descricao'], 0, 46, '…')); ?></div>
                                    </td>
                                    <td class="text-center tabular-nums fw-semibold"><?php echo (int) $item['a_guardar']; ?></td>
                                    <td class="text-center tabular-nums text-secondary"><?php echo (int) $item['quantidade_guardada']; ?></td>
                                    <td class="tabular-nums">
                                        <?php if (!empty($item['endereco_sugerido'])): ?>
                                            <span class="badge badge-success-lg"><?php echo SecurityHelper::e(EnderecoModel::formato($item['endereco_sugerido'])); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning-lg">Sem posição livre</span>
                                        <?php endif; ?>
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