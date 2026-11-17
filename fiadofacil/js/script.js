// Menu do celular
var botaoMenu = document.querySelector('.menu-botao');
if (botaoMenu) {
    botaoMenu.addEventListener('click', function () {
        document.body.classList.toggle('menu-aberto');
    });
}
// Confirmacao: qualquer formulario, botao ou link com data-confirmar pergunta antes
document.querySelectorAll('[data-confirmar]').forEach(function (elemento) {
    var evento = elemento.tagName === 'FORM' ? 'submit' : 'click';
    elemento.addEventListener(evento, function (e) {
        if (!confirm(elemento.getAttribute('data-confirmar'))) {
            e.preventDefault();
        }
    });
});
// Mascara de telefone: (62) 99999-9999
document.querySelectorAll('input[data-mascara="telefone"]').forEach(function (campo) {
    campo.addEventListener('input', function () {
        var numeros = campo.value.replace(/\D/g, '').slice(0, 11);
        var texto = numeros;
        if (numeros.length > 10) {
            texto = '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 7) + '-' + numeros.slice(7);
        } else if (numeros.length > 6) {
            texto = '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 6) + '-' + numeros.slice(6);
        } else if (numeros.length > 2) {
            texto = '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2);
        }
        campo.value = texto;
    });
});
// Pagamento: botao "Quitar dívida" preenche o valor com a dívida do cliente escolhido
var selecaoCliente = document.getElementById('selecao-cliente');
var botaoQuitar = document.getElementById('botao-quitar');
var campoValor = document.getElementById('campo-valor');
if (selecaoCliente && botaoQuitar && campoValor) {
    botaoQuitar.addEventListener('click', function () {
        var opcao = selecaoCliente.options[selecaoCliente.selectedIndex];
        if (opcao && opcao.getAttribute('data-saldo')) {
            campoValor.value = opcao.getAttribute('data-saldo');
        }
    });
}
// Celular: cada linha das tabelas vira um cartao. Aqui cada celula recebe o nome da sua coluna
// (data-label), que o CSS mostra ao lado do valor quando a tela e pequena.
document.querySelectorAll('table').forEach(function (tabela) {
    var linhaTitulos = tabela.querySelector('tr');
    if (!linhaTitulos || !linhaTitulos.querySelector('th')) {
        return;
    }
    var titulos = [];
    linhaTitulos.querySelectorAll('th').forEach(function (th) {
        titulos.push(th.textContent.trim());
    });
    tabela.classList.add('tabela-cartoes');
    tabela.querySelectorAll('tr').forEach(function (linha) {
        if (linha === linhaTitulos) {
            return;
        }
        linha.querySelectorAll('td').forEach(function (celula, indice) {
            if (!celula.hasAttribute('colspan') && titulos[indice]) {
                celula.setAttribute('data-label', titulos[indice]);
            }
        });
    });
});
// Modo noturno: o botao troca entre claro e escuro e guarda a escolha neste aparelho
document.querySelectorAll('.tema-botao').forEach(function (botao) {
    botao.addEventListener('click', function () {
        var raiz = document.documentElement;
        var novoTema = raiz.getAttribute('data-tema') === 'escuro' ? 'claro' : 'escuro';
        raiz.setAttribute('data-tema', novoTema);
        try { localStorage.setItem('tema', novoTema); } catch (e) {}
        var corBarra = document.querySelector('meta[name="theme-color"]');
        if (corBarra) { corBarra.setAttribute('content', novoTema === 'escuro' ? '#0f172a' : '#1e3a8a'); }
    });
});
// ajusta a cor da barra do celular ao abrir a pagina
(function () {
    var corBarra = document.querySelector('meta[name="theme-color"]');
    if (corBarra && document.documentElement.getAttribute('data-tema') === 'escuro') {
        corBarra.setAttribute('content', '#0f172a');
    }
})();
// Mensagens (sucesso, erro, aviso): adiciona um botao "x" para fechar
document.querySelectorAll('.mensagem, .erro, .aviso-caixa').forEach(function (caixa) {
    var botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'fechar-msg';
    botao.setAttribute('aria-label', 'Fechar mensagem');
    botao.innerHTML = '&times;';
    botao.addEventListener('click', function () { caixa.classList.add('saindo'); setTimeout(function () { caixa.remove(); }, 250); });
    caixa.appendChild(botao);
});
// Menu lateral: botao para recolher (so os icones) ou expandir. A escolha fica salva neste aparelho.
var botaoRecolher = document.querySelector('.recolher-botao');
if (botaoRecolher) {
    botaoRecolher.addEventListener('click', function () {
        var raiz = document.documentElement;
        var recolhido = raiz.getAttribute('data-menu') === 'recolhido';
        if (recolhido) { raiz.removeAttribute('data-menu'); } else { raiz.setAttribute('data-menu', 'recolhido'); }
        botaoRecolher.title = recolhido ? 'Recolher menu' : 'Expandir menu';
        try { localStorage.setItem('menu', recolhido ? 'aberto' : 'recolhido'); } catch (e) {}
    });
}
// Quem pediu "reduzir movimento" no aparelho nao recebe animacoes
var semAnimacao = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!semAnimacao) {
    // Entrada em cascata: cartoes, atalhos e linhas das tabelas aparecem um depois do outro
    document.querySelectorAll('.cards, .atalhos, .acoes-rapidas, .paineis-inicio, .lista-inicio').forEach(function (grupo) {
        Array.prototype.forEach.call(grupo.children, function (item, indice) {
            item.style.setProperty('--i', indice);
            item.classList.add('entrada');
        });
    });
    document.querySelectorAll('table').forEach(function (tabela) {
        tabela.querySelectorAll('tr').forEach(function (linha, indice) {
            if (indice > 0 && indice < 25) { linha.style.setProperty('--i', indice); linha.classList.add('entrada'); }
        });
    });
    // Numeros dos cartoes "contam" do zero ate o valor
    document.querySelectorAll('.card-valor').forEach(function (campo) {
        var texto = campo.textContent.trim();
        var dinheiro = texto.indexOf('R$') === 0;
        var numero = parseFloat(texto.replace(/[^\d,]/g, '').replace(',', '.'));
        if (isNaN(numero) || numero === 0) { return; }
        var inicio = null, duracao = 800;
        function formatar(v) {
            return dinheiro ? 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : Math.round(v).toString();
        }
        function passo(agora) {
            if (inicio === null) { inicio = agora; }
            var t = Math.min(1, (agora - inicio) / duracao);
            var suave = 1 - Math.pow(1 - t, 3);
            campo.textContent = formatar(numero * suave);
            if (t < 1) { requestAnimationFrame(passo); } else { campo.textContent = texto; }
        }
        campo.textContent = formatar(0);
        requestAnimationFrame(passo);
    });
    // Efeito de onda ao clicar em botoes
    document.querySelectorAll('.botao, button[type="submit"], .botao-icone, .tema-botao:not(.tema-flutuante), .recolher-botao').forEach(function (botao) {
        botao.classList.add('tem-onda');
        botao.addEventListener('pointerdown', function (e) {
            var caixa = botao.getBoundingClientRect();
            var tamanho = Math.max(caixa.width, caixa.height);
            var onda = document.createElement('span');
            onda.className = 'onda';
            onda.style.width = onda.style.height = tamanho + 'px';
            onda.style.left = (e.clientX - caixa.left - tamanho / 2) + 'px';
            onda.style.top = (e.clientY - caixa.top - tamanho / 2) + 'px';
            botao.appendChild(onda);
            setTimeout(function () { onda.remove(); }, 600);
        });
    });
    // Troca de pagina com uma saida rapida e suave
    document.querySelectorAll('a[href]').forEach(function (link) {
        var destino = link.getAttribute('href');
        if (!destino || destino.charAt(0) === '#' || link.target === '_blank' || link.hasAttribute('data-confirmar') || /^(https?:|mailto:|tel:)/.test(destino)) { return; }
        link.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) { return; }
            e.preventDefault();
            document.body.classList.add('saindo-pagina');
            setTimeout(function () { window.location.href = link.href; }, 140);
        });
    });
    window.addEventListener('pageshow', function () { document.body.classList.remove('saindo-pagina'); });
    // Troca de tema com transicao de cores
    document.querySelectorAll('.tema-botao').forEach(function (botao) {
        botao.addEventListener('click', function () {
            document.documentElement.classList.add('trocando-tema');
            setTimeout(function () { document.documentElement.classList.remove('trocando-tema'); }, 400);
        });
    });
}
// Comemoracao: quando a mensagem diz que a divida foi quitada, caem confetes
(function () {
    var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.mensagem').forEach(function (caixa) {
        if (!/quitada/i.test(caixa.textContent)) { return; }
        caixa.classList.add('comemora');
        if (reduzir) { return; }
        var cores = ['#16a34a', '#2563eb', '#f59e0b', '#ea580c', '#a855f7', '#06b6d4'];
        for (var i = 0; i < 70; i++) {
            var confete = document.createElement('span');
            confete.className = 'confete';
            confete.style.left = (Math.random() * 100) + 'vw';
            confete.style.background = cores[i % cores.length];
            confete.style.setProperty('--desvio', (Math.random() * 160 - 80) + 'px');
            confete.style.setProperty('--giro', (Math.random() * 720 + 180) + 'deg');
            confete.style.setProperty('--duracao', (Math.random() * 1.4 + 2) + 's');
            confete.style.animationDelay = (Math.random() * 0.5) + 's';
            document.body.appendChild(confete);
            setTimeout(function (c) { return function () { c.remove(); }; }(confete), 4500);
        }
    });
})();
// Tela de login: o letreiro "FiadoFacil" ocupa a largura toda e se inclina com o mouse
(function () {
    var letreiro = document.querySelector('.letreiro');
    if (!letreiro) { return; }
    var palco = letreiro.parentElement;
    function ajustar() {
        letreiro.style.fontSize = '100px';
        var larguraTexto = letreiro.offsetWidth;
        var larguraPalco = palco.clientWidth * 0.95;
        if (!larguraTexto || !larguraPalco) { return; }
        // ocupa a largura toda, mas sem empurrar o formulario de login para fora da tela
        var limiteAltura = (window.innerHeight - 448) / 1.09;
        var tamanho = Math.max(48, Math.min(440, limiteAltura, 100 * larguraPalco / larguraTexto));
        letreiro.style.fontSize = tamanho + 'px';
    }
    ajustar();
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(ajustar); }
    var espera;
    window.addEventListener('resize', function () { clearTimeout(espera); espera = setTimeout(ajustar, 80); });
    var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var faixa = document.querySelector('.marca-login');
    if (faixa && !reduzir && window.matchMedia('(hover: hover)').matches) {
        faixa.addEventListener('mousemove', function (e) {
            var caixa = faixa.getBoundingClientRect();
            var x = (e.clientX - caixa.left) / caixa.width - 0.5;
            var y = (e.clientY - caixa.top) / caixa.height - 0.5;
            letreiro.style.transform = 'perspective(1200px) rotateY(' + (x * 6) + 'deg) rotateX(' + (-y * 5) + 'deg)';
        });
        faixa.addEventListener('mouseleave', function () { letreiro.style.transform = ''; });
    }
})();
