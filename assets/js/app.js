/*
 * WMS Agiliza — Scripts de operação (bipagem USB, cronômetros, kanban)
 */
(function () {
    'use strict';

    // =============================================================
    // Bipagem via leitor USB: ao pressionar Enter no campo de leitura,
    // dispara o submit do formulário pai imediatamente.
    // =============================================================
    var bipadores = document.querySelectorAll('.bipador');

    bipadores.forEach(function (input) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var valor = input.value.trim();
                if (valor === '') return;
                var proximo = input.getAttribute('data-proximo');
                if (proximo) {
                    var alvo = document.getElementById(proximo);
                    if (alvo) { alvo.focus(); return; }
                }
                var form = input.closest('form');
                var acao = input.getAttribute('data-acao');
                if (acao) {
                    var oculto = document.createElement('input');
                    oculto.type = 'hidden';
                    oculto.name = 'acao_bipagem';
                    oculto.value = acao;
                    input.form.appendChild(oculto);
                }
                if (form) form.submit();
            }
        });
    });

    // Auto-foco assistivo APENAS quando a tela tem um único campo de bipagem.
    // Nunca rouba o foco quando o usuário clica em outro campo, botão ou link,
    // para não travar formulários com múltiplos campos (ex.: Avarias).
    if (bipadores.length === 1) {
        document.addEventListener('click', function (event) {
            var alvo = event.target;
            var tag = (alvo && alvo.tagName || '').toLowerCase();
            if (['input', 'select', 'textarea', 'button', 'a', 'label'].indexOf(tag) !== -1) return;
            bipadores[0].focus();
        });
    }

    // =============================================================
    // Cronômetros dos cards do Kanban (tempo decorrido na etapa)
    // =============================================================
    function formatarSegundos(total) {
        if (isNaN(total) || total < 0) total = 0;
        var h = Math.floor(total / 3600);
        var m = Math.floor((total % 3600) / 60);
        var s = total % 60;
        function p(n) { return n < 10 ? '0' + n : '' + n; }
        return p(h) + ':' + p(m) + ':' + p(s);
    }

    document.querySelectorAll('.cronometro').forEach(function (el) {
        var inicio = parseInt(el.getAttribute('data-inicio-ts'), 10);
        if (isNaN(inicio)) inicio = Math.floor(Date.now() / 1000);
        function tick() {
            el.textContent = formatarSegundos(Math.floor(Date.now() / 1000) - inicio);
        }
        tick();
        setInterval(tick, 1000);
    });

    // =============================================================
    // Cards do Kanban: clique navega para a página de ação
    // =============================================================
    document.querySelectorAll('.kanban-card[data-url]').forEach(function (card) {
        card.addEventListener('click', function () {
            window.location = card.getAttribute('data-url');
        });
    });

    // =============================================================
    // Autocomplete do XML: informar pasta com arquivos prontos (dev)
    // =============================================================
})();