<?php
/**
 * app/views/templates/campos_endereco_cascata.php
 * Campo de endereço físico em 3 selects em cascata (CORREDOR / GALPÃO / PRATELEIRA),
 * com a sugestão já pré-selecionada no servidor (funciona mesmo sem JavaScript).
 * Quando o usuário troca o corredor ou o galpão, o app.js (initEnderecoCascata)
 * atualiza a cascata e aplica novamente a sugestão do sistema.
 *
 * Variáveis esperadas (já carregadas pela view):
 *   $enderecosOpcoes   (array)      EnderecoModel::listarComOcupacao()
 *   $enderecoSugerido   (array|null) EnderecoModel::enderecoSugerido()
 * Opcionais:
 *   $campoEnderecoRotulo (string) Texto do rótulo.
 *   $campoEnderecoId     (string) Id extra para o select de corredor (foco de bipagem).
 *   $campoEnderecoDica   (string) Observação abaixo dos selects.
 */
$enderecosOpcoes  = $enderecosOpcoes ?? [];
$enderecoSugerido = $enderecoSugerido ?? null;

$rotuloEndereco = $campoEnderecoRotulo ?? 'Endereço físico (CORREDOR / GALPÃO / PRATELEIRA)';
$idEndereco     = $campoEnderecoId ?? '';
$dicaEndereco   = $campoEnderecoDica ?? '';

$corredores = [];
$galpoesPorCorredor = [];
$prateleirasPorChave = [];
foreach ($enderecosOpcoes as $e) {
    $c = $e['corredor'];
    if (!in_array($c, $corredores, true)) {
        $corredores[] = $c;
    }
    $galpoesPorCorredor[$c][$e['galpao']] = true;
    $prateleirasPorChave[$c . '|' . $e['galpao']][] = $e;
}

$selCorredor = $enderecoSugerido ? $enderecoSugerido['corredor'] : ($corredores[0] ?? '');
if (!in_array($selCorredor, $corredores, true)) {
    $selCorredor = $corredores[0] ?? '';
}

$gals = $galpoesPorCorredor[$selCorredor] ?? [];
$galSugerido = $enderecoSugerido ? $enderecoSugerido['galpao'] : '';
$selGalpao = isset($gals[$galSugerido]) ? $galSugerido : (count($gals) ? array_keys($gals)[0] : '');

$prats = $prateleirasPorChave[$selCorredor . '|' . $selGalpao] ?? [];
$prePrateleira = ($enderecoSugerido !== null && $enderecoSugerido['galpao'] === $selGalpao)
    ? $enderecoSugerido['prateleira']
    : '';
?>
<div class="mb-3" data-endereco-cascata
     data-enderecos="<?php echo SecurityHelper::e(json_encode($enderecosOpcoes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"
     data-sugestao-corredor="<?php echo SecurityHelper::e($enderecoSugerido['corredor'] ?? ''); ?>"
     data-sugestao-galpao="<?php echo SecurityHelper::e($enderecoSugerido['galpao'] ?? ''); ?>"
     data-sugestao-prateleira="<?php echo SecurityHelper::e($enderecoSugerido['prateleira'] ?? ''); ?>">
    <label class="form-label"><?php echo SecurityHelper::e($rotuloEndereco); ?></label>
    <div class="row g-2">
        <div class="col-4">
            <select class="form-select" name="corredor" data-campo="corredor" required
                    <?php echo $idEndereco !== '' ? 'id="' . SecurityHelper::e($idEndereco) . '"' : ''; ?>>
                <?php foreach ($corredores as $c): ?>
                    <option value="<?php echo SecurityHelper::e($c); ?>"<?php echo $c === $selCorredor ? ' selected' : ''; ?>>
                        <?php echo SecurityHelper::e($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <select class="form-select" name="galpao" data-campo="galpao" required>
                <?php foreach ($gals as $g => $ignorado): ?>
                    <option value="<?php echo SecurityHelper::e($g); ?>"<?php echo $g === $selGalpao ? ' selected' : ''; ?>>
                        <?php echo SecurityHelper::e($g); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <select class="form-select" name="prateleira" data-campo="prateleira" required>
                <?php foreach ($prats as $p): ?>
                    <option value="<?php echo SecurityHelper::e($p['prateleira']); ?>"
                            <?php echo (int) $p['cheio'] === 1 ? 'disabled' : ''; ?><?php echo $p['prateleira'] === $prePrateleira ? ' selected' : ''; ?>>
                        <?php echo SecurityHelper::e($p['prateleira'] . ((int) $p['cheio'] === 1 ? ' (cheio)' : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-text">
        <?php echo SecurityHelper::e($dicaEndereco !== '' ? $dicaEndereco : 'Pré-selecionado em ordem crescente (posições cheias ficam em "(cheio)").'); ?>
        <?php if (!empty($enderecoSugerido['corredor'])): ?>
            <span class="badge badge-success-lg" style="margin-left:.25rem">Sugestão: <?php echo SecurityHelper::e(EnderecoModel::formato($enderecoSugerido)); ?></span>
        <?php else: ?>
            <span>Nenhum endereço físico com espaço livre no momento.</span>
        <?php endif; ?>
    </div>
</div>