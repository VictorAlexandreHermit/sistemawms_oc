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
    // (o botão de exclusão do Administrador NÃO pode disparar a navegação)
    // =============================================================
    document.querySelectorAll('.kanban-card[data-url]').forEach(function (card) {
        card.addEventListener('click', function () {
            window.location = card.getAttribute('data-url');
        });
    });

    document.querySelectorAll('.kanban-card form.kanban-excluir').forEach(function (form) {
        form.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    // =============================================================
    // Autocomplete do XML: informar pasta com arquivos prontos (dev)
    // =============================================================

    // =============================================================
    // Endereço físico em cascata (Corredor → Galpão → Prateleira)
    // Usado em Avarias e Auditoria. O servidor embute a lista de
    // endereços (com ocupação) e a sugestão pré-selecionada.
    // =============================================================
    function initEnderecoCascata() {
        document.querySelectorAll('[data-endereco-cascata]').forEach(function (wrap) {
            var enderecos = [];
            try {
                enderecos = JSON.parse(wrap.getAttribute('data-enderecos') || '[]');
            } catch (e) {
                return;
            }
            var selC = wrap.querySelector('[data-campo="corredor"]');
            var selG = wrap.querySelector('[data-campo="galpao"]');
            var selP = wrap.querySelector('[data-campo="prateleira"]');
            if (!selC || !selG || !selP) return;

            function valores(lista, chave) {
                var vistos = {};
                var saida = [];
                lista.forEach(function (item) {
                    if (!vistos[item[chave]]) {
                        vistos[item[chave]] = 1;
                        saida.push(item[chave]);
                    }
                });
                return saida.sort();
            }

            function preencher(sel, opcoes, valor) {
                sel.innerHTML = '';
                opcoes.forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o;
                    opt.textContent = o;
                    sel.appendChild(opt);
                });
                if (valor && Array.prototype.some.call(sel.options, function (o) { return o.value === valor; })) {
                    sel.value = valor;
                }
            }

            function preencherPrateleiras(corredor, galpao) {
                selP.innerHTML = '';
                enderecos.forEach(function (e) {
                    if (e.corredor !== corredor || e.galpao !== galpao) return;
                    var opt = document.createElement('option');
                    opt.value = e.prateleira;
                    opt.disabled = e.cheio == 1;
                    opt.textContent = e.prateleira + (e.cheio == 1 ? ' (cheio)' : '');
                    selP.appendChild(opt);
                });
                var sug = wrap.getAttribute('data-sugestao-prateleira');
                if (sug && Array.prototype.some.call(selP.options, function (o) { return o.value === sug && !o.disabled; })) {
                    selP.value = sug;
                }
            }

            function atualizarGalpao(corredor) {
                preencher(selG, valores(enderecos.filter(function (e) { return e.corredor === corredor; }), 'galpao'),
                          wrap.getAttribute('data-sugestao-galpao'));
                atualizarPrateleiras(corredor, selG.value);
            }

            function atualizarPrateleiras(corredor, galpao) {
                preencherPrateleiras(corredor, galpao);
            }

            selC.addEventListener('change', function () { atualizarGalpao(selC.value); });
            selG.addEventListener('change', function () { atualizarPrateleiras(selC.value, selG.value); });

            preencher(selC, valores(enderecos, 'corredor'), wrap.getAttribute('data-sugestao-corredor'));
            atualizarGalpao(selC.value);
        });
    }
    initEnderecoCascata();
})();