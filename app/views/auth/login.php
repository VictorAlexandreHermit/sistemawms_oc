<?php
/**
 * app/views/auth/login.php
 * Tela de login por matrícula e senha.
 */
$erroLogin = $erroLogin ?? null;
$erroSenha = $erroSenha ?? null;
$login     = $login ?? '';
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
                    <input class="form-control form-control-uppercase <?php echo $erroLogin !== null ? 'is-invalid' : ''; ?>" type="text" id="login" name="login"
                           placeholder="" required autofocus autocomplete="username"
                           oninput="this.value = this.value.toUpperCase()"
                           value="<?php echo SecurityHelper::e($login ?? ''); ?>">
                    <?php if ($erroLogin !== null): ?>
                        <div class="campo-erro"><?php echo SecurityHelper::e($erroLogin); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="senha">Senha</label>
                    <div class="input-group input-group-senha">
                        <input class="form-control <?php echo $erroSenha !== null ? 'is-invalid' : ''; ?>" type="password" id="senha" name="senha"
                               placeholder="" required autocomplete="current-password">
                        <button type="button" class="btn btn-toggle-senha" data-alvo="senha"
                                aria-label="Mostrar ou ocultar senha" tabindex="-1">
                            <svg class="icone-olho" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icone-olho-cortado" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <?php if ($erroSenha !== null): ?>
                        <div class="campo-erro"><?php echo SecurityHelper::e($erroSenha); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="wms-btn-primary w-100">Entrar na Operação</button>
            </form>
        </div>
    </div>
</div>

<div class="login-versao">v<?php echo SecurityHelper::e(APP_VERSION); ?> · WMS AGILIZA</div>

<script>
(function () {
    'use strict';
    document.querySelectorAll('.btn-toggle-senha[data-alvo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-alvo'));
            if (!input) return;
            var mostrar = !btn.classList.contains('senha-visivel');
            input.type = mostrar ? 'text' : 'password';
            btn.classList.toggle('senha-visivel', mostrar);
            input.focus();
        });
    });
})();
</script>