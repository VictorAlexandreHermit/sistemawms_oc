<?php
/**
 * app/views/usuarios/index.php
 * Gestão de contas de acesso (exclusivo ADMINISTRADOR).
 */
function badgePerfil(string $perfil): string
{
    $cor = match ($perfil) {
        'ADMINISTRADOR' => 'badge-dark-lg',
        'GESTOR'        => 'badge-warning-lg',
        default         => 'badge-info-lg',
    };
    return '<span class="badge ' . $cor . '">' . SecurityHelper::e($perfil) . '</span>';
}
?>
<div class="row g-4">

    <div class="col-lg-4">
        <div class="wms-card">
            <div class="wms-card-header">Novo login</div>
            <div class="p-4">
                <form method="post" action="<?php echo BASE_URL; ?>/usuarios/salvar" autocomplete="off">
                    <?php echo CsrfHelper::campo(); ?>

                    <div class="mb-3">
                        <label class="form-label">Matrícula (login) *</label>
                        <input class="form-control" type="text" name="matricula" required maxlength="30"
                               placeholder="Ex.: GESTOR02" autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nome completo *</label>
                        <input class="form-control" type="text" name="nome_completo" required maxlength="120">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Perfil *</label>
                        <select class="form-select" name="perfil" required>
                            <?php foreach ($perfis as $p): ?>
                                <option value="<?php echo SecurityHelper::e($p); ?>"><?php echo SecurityHelper::e($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha inicial *</label>
                        <input class="form-control" type="password" name="senha" required minlength="6" autocomplete="new-password">
                        <div class="form-text" style="color:#64748B">Mínimo de 6 caracteres.</div>
                    </div>

                    <button type="submit" class="wms-btn-primary w-100">Criar login</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="wms-card">
            <div class="wms-card-header">Contas existentes</div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-wms mb-0">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nome</th>
                                <th>Perfil</th>
                                <th class="text-center">Status</th>
                                <th>Criado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-secondary" style="color:#64748B">Nenhuma conta cadastrada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><strong class="tabular-nums"><?php echo SecurityHelper::e($u['matricula']); ?></strong></td>
                                    <td><?php echo SecurityHelper::e($u['nome_completo']); ?></td>
                                    <td><?php echo badgePerfil($u['perfil']); ?></td>
                                    <td class="text-center">
                                        <?php if ($u['deleted_at'] !== null): ?>
                                            <span class="badge badge-neutral-lg">Excluído</span>
                                        <?php else: ?>
                                            <span class="badge <?php echo (int) $u['ativo'] === 1 ? 'badge-success-lg' : 'badge-danger-lg'; ?>">
                                                <?php echo (int) $u['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary" style="white-space:nowrap">
                                        <?php echo SecurityHelper::e(DateHelper::exibir($u['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($u['deleted_at'] !== null): ?>
                                            <span class="text-secondary small" style="color:#94A3B8">Conta removida</span>
                                        <?php else: ?>
                                        <div class="d-inline-flex flex-column gap-1 align-items-end">
                                            <form method="post"
                                                  action="<?php echo BASE_URL; ?>/usuarios/resetar-senha/<?php echo (int) $u['id']; ?>"
                                                  class="d-flex gap-1">
                                                <?php echo CsrfHelper::campo(); ?>
                                                <input class="form-control form-control-sm" type="text"
                                                       name="nova_senha" placeholder="Nova senha" minlength="6" required>
                                                <button class="btn btn-outline-slate btn-sm" title="Redefinir senha">Senha</button>
                                            </form>
                                            <?php if ((int) $u['id'] !== (int) $idAtual): ?>
                                                <?php if ((int) $u['ativo'] === 1): ?>
                                                <form method="post" action="<?php echo BASE_URL; ?>/usuarios/desativar/<?php echo (int) $u['id']; ?>"
                                                      onsubmit="return confirm('Desativar o acesso de <?php echo SecurityHelper::e($u['matricula']); ?>?')">
                                                    <?php echo CsrfHelper::campo(); ?>
                                                    <button class="btn btn-link btn-sm text-danger p-0">Desativar</button>
                                                </form>
                                                <?php else: ?>
                                                <form method="post" action="<?php echo BASE_URL; ?>/usuarios/ativar/<?php echo (int) $u['id']; ?>">
                                                    <?php echo CsrfHelper::campo(); ?>
                                                    <button class="btn btn-link btn-sm p-0" style="color:#15803D">Reativar</button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="post" action="<?php echo BASE_URL; ?>/usuarios/excluir/<?php echo (int) $u['id']; ?>"
                                                      onsubmit="return confirm('Excluir permanentemente a conta de <?php echo SecurityHelper::e($u['matricula']); ?>? Esta ação não pode ser desfeita.')">
                                                    <?php echo CsrfHelper::campo(); ?>
                                                    <button class="btn btn-link btn-sm p-0" style="color:#B91C1C">Excluir conta</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>