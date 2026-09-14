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
            <div class="wms-card-header">Entrada manual por código de barras</div>
            <div class="p-4">
                <p class="text-secondary" style="color:#475569">
                    Recebimento sem XML: digite ou bipe o (s) código (s) de barras, a quantidade
                    e a <strong>Curva ABC</strong> de prioridade (A = maior, C = menor). Códigos
                    novos são cadastrados automaticamente no <strong>Catálogo de Produtos</strong>
                    com um <strong>SKU interno</strong> gerado pelo sistema. A carga é liberada direto
                    para a <strong>Guarda (Putaway)</strong>.
                </p>

                <form method="post" action="<?php echo BASE_URL; ?>/recebimento/entrada-manual" autocomplete="off">
                    <?php echo CsrfHelper::campo(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="fornecedor">Fornecedor (opcional)</label>
                        <input class="form-control" type="text" id="fornecedor" name="fornecedor"
                               maxlength="150" placeholder="Ex.: Distribuidora Alfa">
                    </div>

                    <div id="manual-linhas">
                        <div class="manual-linha border rounded p-2 mb-2">
                            <div class="row g-2">
                                <div class="col-5">
                                    <input class="form-control" type="text" name="codigo_barras[]"
                                           placeholder="Código de barras" required autocomplete="off">
                                </div>
                                <div class="col-2">
                                    <input class="form-control" type="number" name="quantidade[]"
                                           value="1" min="1" required>
                                </div>
                                <div class="col-3">
                                    <select class="form-select" name="curva_abc[]" required>
                                        <option value="C" selected>Curva C</option>
                                        <option value="B">Curva B</option>
                                        <option value="A">Curva A</option>
                                    </select>
                                </div>
                                <div class="col-2 d-flex align-items-center">
                                    <button type="button" class="btn btn-outline-slate btn-sm btn-remove-linha w-100">Remover</button>
                                </div>
                            </div>
                            <div class="row g-2 mt-0">
                                <div class="col-12">
                                    <input class="form-control" type="text" name="descricao[]"
                                           placeholder="Descrição do produto (opcional — para o catálogo)"
                                           autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-slate btn-sm" id="btn-add-linha">Adicionar item</button>
                        <button type="submit" class="wms-btn-primary">Registrar entrada manual</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="wms-card mt-4">
            <div class="wms-card-header">Dica de operação</div>
            <div class="p-4">
                <ul class="mb-0 ps-3" style="color:#475569">
                    <li>Bipe <strong>1x por produto</strong> com o leitor USB (Enter automático) — o código da caixa/embalagem confirma o item inteiro.</li>
                    <li>Também é possível digitar o código manualmente no campo de leitura.</li>
                    <li>Produto novo: o sistema gera um <strong>SKU interno</strong> (ex.: <code>WM-000001</code>)
                        que vira o método de busca e endereçamento do item.</li>
                    <li>O código de barras do fornecedor fica salvo e reutilizado nas próximas entradas.</li>
                    <li>A <strong>Curva ABC</strong> define a prioridade da movimentação no galpão:
                        a carga assume a prioridade do item de maior classe (A).</li>
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
                                        <?php if (AuthHelper::ehAdministrador()): ?>
                                        <form method="post" action="<?php echo BASE_URL; ?>/kanban/excluir/<?php echo (int) $p['id']; ?>" class="d-inline"
                                              onsubmit="return confirm('Excluir definitivamente o processo da nota <?php echo SecurityHelper::e($p['numero_nota_xml']); ?>?')">
                                            <?php echo CsrfHelper::campo(); ?>
                                            <button class="btn btn-link btn-sm text-danger p-0 ms-1" title="Excluir processo (Administrador)" style="text-decoration:none">Excluir</button>
                                        </form>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var lista = document.getElementById('manual-linhas');
    var btnAdd = document.getElementById('btn-add-linha');
    function primeiraLinha() {
        return lista.querySelector('.manual-linha');
    }
    function removerEvento() {
        lista.querySelectorAll('.btn-remove-linha').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var linhas = lista.querySelectorAll('.manual-linha');
                if (linhas.length > 1) {
                    this.closest('.manual-linha').remove();
                } else {
                    var inputs = this.closest('.manual-linha').querySelectorAll('input');
                    inputs.forEach(function (i) { i.value = ''; });
                }
            });
        });
    }
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            var linha = primeiraLinha();
            var clone = linha.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) { i.value = i.name === 'quantidade[]' ? '1' : ''; });
            clone.querySelectorAll('select').forEach(function (s) { s.value = 'C'; });
            lista.appendChild(clone);
            removerEvento();
        });
    }
    removerEvento();
});
</script>