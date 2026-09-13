<?php
/**
 * app/views/otif/resposta.php
 * Detalhe individual de uma avaliação OTIF respondida pelo cliente.
 */
$p = $pesquisa;
$respostas = [
    'Prazo cumprido'     => $p['prazo_cumprido'],
    'Sem avaria'         => $p['sem_avaria'],
    'Itens em conformidade' => $p['conformidade_itens'],
];
$temStatus = $p['respondido_em'] !== null;
?>
<div class="row g-4">

    <div class="col-lg-7">
        <div class="wms-card">
            <div class="wms-card-header">Resposta do cliente</div>
            <div class="p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase" style="color:#94A3B8">Nota fiscal</div>
                        <strong class="tabular-nums"><?php echo SecurityHelper::e($p['numero_nota_xml']); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase" style="color:#94A3B8">Cliente</div>
                        <strong><?php echo SecurityHelper::e($p['cliente_nome']); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase" style="color:#94A3B8">Contato</div>
                        <span class="tabular-nums"><?php echo $p['cliente_contato'] ? SecurityHelper::e($p['cliente_contato']) : 'não informado'; ?></span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small text-uppercase" style="color:#94A3B8">Entregue em</div>
                        <span class="tabular-nums"><?php echo $p['ts_entregue'] ? SecurityHelper::e(DateHelper::exibir($p['ts_entregue'])) : '—'; ?></span>
                    </div>
                </div>

                <table class="table table-wms mb-3">
                    <tbody>
                        <?php foreach ($respostas as $rotulo => $valor): ?>
                        <tr>
                            <td class="text-secondary"><?php echo SecurityHelper::e($rotulo); ?></td>
                            <td class="text-end">
                                <?php if ($valor === 'SIM'): ?>
                                    <span class="badge badge-success-lg">Sim</span>
                                <?php elseif ($valor === 'NAO'): ?>
                                    <span class="badge badge-danger-lg">Não</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral-lg">Sem resposta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!$temStatus): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-0">Avaliação ainda não respondida pelo cliente.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-secondary small text-uppercase" style="color:#94A3B8">Enviada em</div>
                            <div class="tabular-nums"><?php echo $p['data_envio'] ? SecurityHelper::e(DateHelper::exibir($p['data_envio'])) : '—'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary small text-uppercase" style="color:#94A3B8">Respondida em</div>
                            <div class="tabular-nums"><?php echo SecurityHelper::e(DateHelper::exibir($p['respondido_em'])); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <div class="text-secondary small text-uppercase mb-1" style="color:#94A3B8">Observações do cliente</div>
                    <p class="mb-0" style="color:#334155">
                        <?php echo $p['observacoes'] ? SecurityHelper::e($p['observacoes']) : '<span class="text-secondary">Sem observações.</span>'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="wms-card">
            <div class="wms-card-header">Fotos enviadas</div>
            <div class="p-3">
                <?php
                $fotos = array_filter([$p['foto_1_url'], $p['foto_2_url']]);
                if (empty($fotos)): ?>
                    <div class="text-center py-4 text-secondary" style="color:#64748B">Nenhuma foto anexada.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($fotos as $foto): ?>
                        <a href="<?php echo BASE_URL . '/' . ltrim(SecurityHelper::e($foto), '/'); ?>" target="_blank">
                            <img src="<?php echo BASE_URL . '/' . ltrim(SecurityHelper::e($foto), '/'); ?>" alt="Foto da avaliação" class="img-fluid rounded border w-100">
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (AuthHelper::ehGestor()): ?>
        <div class="wms-card mt-4">
            <div class="wms-card-header">Reenviar avaliação (WhatsApp)</div>
            <div class="p-3">
                <form method="post" action="<?php echo BASE_URL; ?>/otif/reenviar/<?php echo (int) $p['id']; ?>">
                    <?php echo CsrfHelper::campo(); ?>
                    <div class="mb-2">
                        <label class="form-label">Número do cliente (WhatsApp)</label>
                        <input class="form-control form-control-sm" type="text" name="contato"
                               value="<?php echo SecurityHelper::e($p['cliente_contato'] ?? ''); ?>"
                               placeholder="DDD + número do WhatsApp do cliente" autocomplete="off">
                        <div class="form-text">Sem número padrão: informe o contato do cliente a cada reenvio.</div>
                    </div>
                    <button class="btn btn-dark btn-sm w-100" type="submit">Disparar avaliação novamente</button>
                </form>
                <div class="small mt-2" style="color:#64748B">
                    O envio real depende do canal configurado em <code>config/config.php</code> (modo
                    <code>api</code> + Evolution API). No modo <code>simulacao</code> apenas o status é marcado.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-3 text-center">
            <a class="btn btn-outline-slate btn-sm" href="<?php echo BASE_URL; ?>/otif/painel">← Voltar ao painel OTIF</a>
        </div>
    </div>

</div>