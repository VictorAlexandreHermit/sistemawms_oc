<?php
/**
 * app/views/pedidos/index.php
 * Simulação de Pedido de Venda + fila de pedidos abertos.
 */
$statusRotulos = [
    'A_SEPARAR'   => 'No Picking (A Separar)',
    'A_EMBALAR'   => 'No Packing (A Embalar)',
    'A_EXPEDIR'   => 'A Expedir',
    'EM_TRANSITO' => 'Em Trânsito',
    'ENTREGUE'    => 'Entregue',
];
$statusClasse = [
    'A_SEPARAR'   => 'badge-warning-lg',
    'A_EMBALAR'   => 'badge-info-lg',
    'A_EXPEDIR'   => 'badge-dark-lg',
    'EM_TRANSITO' => 'badge-info-lg',
    'ENTREGUE'    => 'badge-success-lg',
];
$ativos = [];
$entregues = [];
foreach ($vendas as $v) {
    if ($v['status_kanban'] === 'ENTREGUE') {
        $entregues[] = $v;
    } else {
        $ativos[] = $v;
    }
}
?>
<div class="row g-4">

    <div class="col-lg-5">
        <div class="wms-card">
            <div class="wms-card-header">Simular Pedido de Venda</div>
            <div class="p-4">
                <p class="text-secondary" style="color:#475569">
                    Abra um pedido do cliente a partir do estoque <strong>armazenado</strong>.
                    Só depois disso a mercadoria entra no fluxo de <strong>Picking</strong>
                    (e o estoque só será baixado ao concluir a separação).
                </p>

                <form method="post" action="<?php echo BASE_URL; ?>/pedidos/criar" autocomplete="off">
                    <?php echo CsrfHelper::campo(); ?>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label" for="cliente">Cliente</label>
                            <input class="form-control" type="text" id="cliente" name="cliente"
                                   maxlength="100" placeholder="Nome do cliente/destinatário" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label" for="contato">Contato (opcional)</label>
                            <input class="form-control" type="text" id="contato" name="contato"
                                   maxlength="100" placeholder="Telefone/e-mail">
                        </div>
                    </div>

                    <div id="pedido-linhas">
                        <div class="pedido-linha border rounded p-2 mb-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-7">
                                    <select class="form-select" name="produto[]" required>
                                        <option value="">Selecione o produto…</option>
                                        <?php foreach ($produtos as $p): ?>
                                        <option value="<?php echo (int) $p['id']; ?>">
                                            <?php echo SecurityHelper::e($p['sku']); ?> — <?php echo SecurityHelper::e(mb_strimwidth($p['descricao'], 0, 34, '…')); ?> (saldo <?php echo (int) $p['saldo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input class="form-control" type="number" name="quantidade[]" value="1" min="1" required>
                                </div>
                                <div class="col-2 d-flex align-items-center">
                                    <button type="button" class="btn btn-outline-slate btn-sm btn-remove-pedido w-100">Remover</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-slate btn-sm" id="btn-add-pedido">Adicionar item</button>
                    </div>

                    <?php if (empty($produtos)): ?>
                    <div class="alert alert-warning border-0 shadow-sm">
                        Nenhum produto com estoque disponível. Receba e faça a guarda de uma mercadoria antes.
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="wms-btn-primary w-100" <?php echo empty($produtos) ? 'disabled' : ''; ?>>
                        Abrir Pedido para o Picking
                    </button>
                </form>
            </div>
        </div>

        <div class="wms-card mt-4">
            <div class="wms-card-header">Como funciona</div>
            <div class="p-4">
                <ul class="mb-0 ps-3" style="color:#475569">
                    <li>O pedido de venda nasce em <strong>A Separar</strong> na fila do <strong>Picking</strong>.</li>
                    <li>No Picking, a separação <strong>baixa o estoque</strong> automaticamente (acuracidade do dashboard).</li>
                    <li>Só produtos com saldo <strong>Disponível</strong> podem entrar no pedido.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="wms-card">
            <div class="wms-card-header d-flex justify-content-between">
                <span>Pedidos de venda</span>
                <span class="badge badge-dark-lg tabular-nums"><?php echo count($ativos); ?> em operação</span>
            </div>
            <div class="p-3">
                <?php if (empty($ativos)): ?>
                    <div class="text-center py-5 text-secondary" style="color:#64748B">
                        Nenhum pedido de venda em operação.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th class="text-center">ABC</th>
                                    <th>Status</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ativos as $v): ?>
                                <tr>
                                    <td class="tabular-nums fw-semibold"><?php echo SecurityHelper::e($v['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($v['cliente_nome']); ?></td>
                                    <td class="text-center">
                                        <span class="abc-tag abc-<?php echo strtolower(SecurityHelper::e($v['prioridade_abc'])); ?>"><?php echo SecurityHelper::e($v['prioridade_abc']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClasse[$v['status_kanban']] ?? 'badge-neutral-lg'; ?>"><?php echo $statusRotulos[$v['status_kanban']] ?? SecurityHelper::e($v['status_kanban']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($v['status_kanban'] === 'A_SEPARAR'): ?>
                                        <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/picking/conferir/<?php echo (int) $v['id']; ?>">Separar</a>
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1" data-cancelar="<?php echo (int) $v['id']; ?>" data-pedido="<?php echo SecurityHelper::e($v['numero_nota_xml']); ?>" style="text-decoration:none">Cancelar</button>
                                        <form method="post" action="<?php echo BASE_URL; ?>/pedidos/cancelar/<?php echo (int) $v['id']; ?>" class="form-cancelar-pedido d-none">
                                            <?php echo CsrfHelper::campo(); ?>
                                        </form>
                                        <?php elseif ($v['status_kanban'] === 'A_EMBALAR'): ?>
                                        <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/packing/conferir/<?php echo (int) $v['id']; ?>">Embalar</a>
                                        <?php elseif ($v['status_kanban'] === 'A_EXPEDIR' || $v['status_kanban'] === 'EM_TRANSITO'): ?>
                                        <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/expedicao">Expedir</a>
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

        <div class="wms-card mt-4">
            <div class="wms-card-header d-flex justify-content-between">
                <span>Últimos entregues</span>
                <span class="badge badge-success-lg tabular-nums"><?php echo count($entregues); ?></span>
            </div>
            <div class="p-3">
                <?php if (empty($entregues)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Histórico vazio.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-wms mb-0">
                            <thead>
                                <tr><th>Pedido</th><th>Cliente</th><th>Entregue em</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entregues as $v): ?>
                                <tr>
                                    <td class="tabular-nums"><?php echo SecurityHelper::e($v['numero_nota_xml']); ?></td>
                                    <td><?php echo SecurityHelper::e($v['cliente_nome']); ?></td>
                                    <td class="tabular-nums"><?php echo SecurityHelper::e(DateHelper::exibir($v['ts_entregue'])); ?></td>
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
    function primeiraLinha() {
        return document.querySelector('#pedido-linhas .pedido-linha');
    }
    function removerEventoPedido() {
        document.querySelectorAll('.btn-remove-pedido').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var linhas = document.querySelectorAll('#pedido-linhas .pedido-linha');
                if (linhas.length > 1) {
                    this.closest('.pedido-linha').remove();
                } else {
                    var sel = this.closest('.pedido-linha').querySelector('select');
                    var qtd = this.closest('.pedido-linha').querySelector('input[type=number]');
                    if (sel) sel.value = '';
                    if (qtd) qtd.value = '1';
                }
            });
        });
    }
    var btnAdd = document.getElementById('btn-add-pedido');
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            var linha = primeiraLinha();
            if (!linha) return;
            var clone = linha.cloneNode(true);
            clone.querySelectorAll('select').forEach(function (s) { s.value = ''; });
            clone.querySelectorAll('input[type=number]').forEach(function (i) { i.value = '1'; });
            var lista = document.getElementById('pedido-linhas');
            lista.appendChild(clone);
            removerEventoPedido();
        });
    }
    removerEventoPedido();

    document.querySelectorAll('[data-cancelar]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var pedido = this.getAttribute('data-pedido');
            if (!window.confirm('Cancelar o pedido ' + pedido + '? O pedido ainda não teve estoque baixado.')) return;
            var id = this.getAttribute('data-cancelar');
            var form = document.querySelector('.form-cancelar-pedido[action*="/pedidos/cancelar/' + id + '"]');
            if (form) form.submit();
        });
    });
});
</script>