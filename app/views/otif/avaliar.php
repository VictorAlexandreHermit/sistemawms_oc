<?php
/**
 * app/views/otif/avaliar.php
 * Formulário público de avaliação OTIF (token).
 */
?>
<div class="d-flex align-items-center justify-content-center py-5" style="min-height:100vh;background:var(--wms-deep-slate)">
    <div class="card border-0 shadow" style="width:100%;max-width:560px;border-radius:var(--wms-radius)">
        <div class="card-body p-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:52px;height:52px;border-radius:var(--wms-radius);background:var(--wms-deep-slate);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px">📦</div>
                <div>
                    <h1 class="h5 mb-1" style="color:var(--wms-deep-slate)">Avalie sua entrega</h1>
                    <p class="mb-0 text-secondary" style="color:var(--wms-text-muted)">Pedido <strong><?php echo SecurityHelper::e($pesquisa['numero_nota_xml'] ?? ''); ?></strong> · leva menos de 1 minuto</p>
                </div>
            </div>

            <?php ViewHelper::flash(); ?>

            <form method="post" action="<?php echo BASE_URL; ?>/otif/responder" enctype="multipart/form-data">
                <?php echo CsrfHelper::campo(); ?>
                <input type="hidden" name="token" value="<?php echo SecurityHelper::e($pesquisa['token_acesso']); ?>">

                <?php
                $perguntas = [
                    'prazo_cumprido'     => 'A entrega aconteceu no prazo combinado?',
                    'sem_avaria'         => 'Os produtos chegaram sem avaria?',
                    'conformidade_itens' => 'Todos os itens do pedido estavam presentes?',
                ];
                foreach ($perguntas as $campo => $pergunta) {
                    echo '<div class="mb-4">';
                    echo '<p class="fw-semibold mb-2" style="font-size:15px">' . SecurityHelper::e($pergunta) . '</p>';
                    echo '<div class="d-flex gap-3">';
                    foreach (['SIM' => 'Sim', 'NAO' => 'Não'] as $valor => $rotulo) {
                        $id = $campo . '_' . $valor;
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="radio" name="' . SecurityHelper::e($campo) . '" id="' . $id . '" value="' . $valor . '" required>';
                        echo '<label class="form-check-label" for="' . $id . '">' . $rotulo . '</label>';
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
                ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Fotos (opcional — até 2)</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input class="form-control" type="file" name="foto_1" accept="image/jpeg,image/png">
                        </div>
                        <div class="col-6">
                            <input class="form-control" type="file" name="foto_2" accept="image/jpeg,image/png">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Observações (opcional)</label>
                    <textarea class="form-control" name="observacoes" rows="2" maxlength="2000" placeholder="Quer deixar um comentário?"></textarea>
                </div>

                <button type="submit" class="btn btn-dark w-100" style="border-radius:var(--wms-radius)">Enviar Avaliação</button>
            </form>
        </div>
    </div>
</div>