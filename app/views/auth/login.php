<?php
/**
 * app/views/auth/login.php
 * Tela de login por matrícula e senha.
 */
?>
<div class="login-page">
    <div style="width:100%;max-width:400px">

        <div class="wms-card p-4 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="dot" style="width:12px;height:12px;background:#2563EB"></span>
                <h1 style="font-size:22px;font-weight:700;margin:0;letter-spacing:-.01em">WMS Agiliza</h1>
            </div>

            <p class="text-secondary mb-4" style="color:#475569">
                Acesse com sua matrícula e senha para iniciar a operação.
            </p>

            <form method="post" action="<?php echo BASE_URL; ?>/logar" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>

                <div class="mb-3">
                    <label class="form-label" for="matricula">Matrícula</label>
                    <input class="form-control" type="text" id="matricula" name="matricula"
                           placeholder="Ex.: GESTOR01" required autofocus autocomplete="username">
                </div>

                <div class="mb-4">
                    <label class="form-label" for="senha">Senha</label>
                    <input class="form-control" type="password" id="senha" name="senha"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="wms-btn-primary w-100">Entrar na Operação</button>
            </form>
        </div>

        <p class="text-center mt-3 mb-0" style="color:#94A3B8;font-size:12px">
            Contas de demonstração: <strong>ADMINISTRADOR</strong> / <strong>GESTOR01</strong> / <strong>OPERADOR01</strong>
        </p>
    </div>
</div>