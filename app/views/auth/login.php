<?php
/**
 * app/views/auth/login.php
 * Tela de login por matrícula e senha.
 */
?>
<div class="login-page">
    <div style="width:100%;max-width:460px">

        <div class="wms-card p-4 shadow-sm">
            <div class="login-logo d-flex flex-column align-items-center justify-content-center mb-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/wms_color.png" alt="WMS Agiliza logo" class="login-logo-img">
                <span class="login-logo-text">WMS AGILIZA</span>
            </div>

            <p class="text-secondary mb-4" style="color:#64748B">
                Acesse com seu login e senha para iniciar a operação:
            </p>

            <form method="post" action="<?php echo BASE_URL; ?>/logar" autocomplete="off">
                <?php echo CsrfHelper::campo(); ?>

                <div class="mb-3">
                    <label class="form-label" for="login">Login</label>
                    <input class="form-control" type="text" id="login" name="login"
                           placeholder="" required autofocus autocomplete="username">
                </div>

                <div class="mb-4">
                    <label class="form-label" for="senha">Senha</label>
                    <input class="form-control" type="password" id="senha" name="senha"
                           placeholder="" required autocomplete="current-password">
                </div>

                <button type="submit" class="wms-btn-primary w-100">Entrar na Operação</button>
            </form>
        </div>
    </div>
</div>