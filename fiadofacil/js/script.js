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
        // margem grande dos dois lados: ocupa 60% da largura no computador e 80% no celular
        var larguraPalco = palco.clientWidth * (window.innerWidth <= 640 ? 0.8 : 0.6);
        if (!larguraTexto || !larguraPalco) { return; }
        // ocupa a largura toda, mas sem empurrar o formulario de login para fora da tela
        var limiteAltura = (window.innerHeight - 448) / 1.09;
        var tamanho = Math.max(22, Math.min(440, limiteAltura, 100 * larguraPalco / larguraTexto));
        letreiro.style.fontSize = tamanho + 'px';
    }
    ajustar();
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(ajustar); }
    var espera;
    window.addEventListener('resize', function () { clearTimeout(espera); espera = setTimeout(ajustar, 80); });
    var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var faixa = document.querySelector('.marca-login');
    if (reduzir) { return; }
    // quando a letra termina de entrar, passa a reagir ao mouse
    letreiro.querySelectorAll('.letra').forEach(function (letra) {
        letra.addEventListener('animationend', function fim(e) {
            if (e.animationName === 'letra-entra') { letra.classList.add('pronta'); letra.removeEventListener('animationend', fim); }
        });
    });
    // inclinacao 3D acompanhando o mouse + cresce quando o mouse esta em cima
    var inclinaX = 0, inclinaY = 0, emCima = false;
    function aplicar() {
        letreiro.style.transform = 'perspective(1200px) rotateY(' + inclinaX + 'deg) rotateX(' + inclinaY + 'deg) scale(' + (emCima ? 1.12 : 1) + ')';
    }
    if (faixa && window.matchMedia('(hover: hover)').matches) {
        faixa.addEventListener('mousemove', function (e) {
            var caixa = faixa.getBoundingClientRect();
            inclinaX = ((e.clientX - caixa.left) / caixa.width - 0.5) * 12;
            inclinaY = -((e.clientY - caixa.top) / caixa.height - 0.5) * 10;
            aplicar();
        });
        faixa.addEventListener('mouseleave', function () { inclinaX = 0; inclinaY = 0; emCima = false; aplicar(); });
    }
    // ao passar por cima (ou tocar no celular): cresce e as letras pulam em onda
    function onda() {
        emCima = true; aplicar();
        letreiro.classList.remove('letras-pulando'); void letreiro.offsetWidth; letreiro.classList.add('letras-pulando');
    }
    letreiro.addEventListener('mouseenter', onda);
    letreiro.addEventListener('mouseleave', function () { emCima = false; aplicar(); });
    letreiro.addEventListener('touchstart', function () { onda(); setTimeout(function () { emCima = false; aplicar(); }, 700); }, { passive: true });
    letreiro.addEventListener('animationend', function (e) { if (e.animationName === 'pulinho' && e.target === letreiro.lastElementChild.lastElementChild) { letreiro.classList.remove('letras-pulando'); } }, true);
})();
// Frases de efeito que trocam sozinhas (tela de login)
(function () {
    var alvo = document.querySelector('.frase-atual[data-frases]');
    if (!alvo) { return; }
    var frases = alvo.getAttribute('data-frases').split('|');
    var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (frases.length < 2 || reduzir) { return; }
    var indice = 0;
    setInterval(function () {
        alvo.classList.add('trocando');
        setTimeout(function () {
            indice = (indice + 1) % frases.length;
            alvo.textContent = frases[indice];
            alvo.classList.remove('trocando');
        }, 450);
    }, 3600);
})();

// Janela de detalhes tecnicos (pagina de apresentacao): abre com zoom e fecha com o X, a tecla Esc ou clicando fora
(function () {
    document.querySelectorAll('[data-abrir]').forEach(function (botao) {
        var janela = document.getElementById(botao.getAttribute('data-abrir'));
        if (!janela || typeof janela.showModal !== 'function') { return; }
        function fechar() {
            janela.classList.add('fechando');
            setTimeout(function () { janela.classList.remove('fechando'); janela.close(); }, 200);
        }
        botao.addEventListener('click', function () { janela.showModal(); });
        janela.querySelectorAll('[data-fechar]').forEach(function (x) { x.addEventListener('click', fechar); });
        janela.addEventListener('click', function (e) { if (e.target === janela) { fechar(); } });
        janela.addEventListener('cancel', function (e) { e.preventDefault(); fechar(); });
    });

})();

// ---------- Integracoes: QR Code do Pix, botao copiar e CEP automatico (ViaCEP) ----------
(function () {
    // QR Code: a biblioteca (js/qrcode.js) so e carregada nas paginas que tem um Pix
    var areas = document.querySelectorAll('[data-qrcode]');
    function desenhar() {
        areas.forEach(function (area) {
            var qr = qrcode(0, 'M');
            qr.addData(area.getAttribute('data-qrcode'));
            qr.make();
            area.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 3, scalable: true });
        });
    }
    if (areas.length) {
        var s = document.createElement('script');
        s.src = 'js/qrcode.js?v=1';
        s.onload = desenhar;
        document.body.appendChild(s);
    }

    // Copiar um texto (codigo Pix, chave da API)
    document.querySelectorAll('[data-copiar]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var alvo = document.querySelector(botao.getAttribute('data-copiar'));
            if (!alvo) { return; }
            var texto = alvo.textContent.trim(), original = botao.innerHTML;
            function pronto() { botao.textContent = 'Copiado!'; setTimeout(function () { botao.innerHTML = original; }, 1800); }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(texto).then(pronto, function () { selecionar(alvo); });
            } else { selecionar(alvo); pronto(); }
        });
    });
    function selecionar(el) {
        var r = document.createRange(); r.selectNodeContents(el);
        var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(r);
        try { document.execCommand('copy'); } catch (e) {}
    }

    // CEP: ao digitar 8 numeros, consulta o ViaCEP (API publica e gratuita) pelo navegador e preenche o endereco
    document.querySelectorAll('[data-cep]').forEach(function (campo) {
        var destino = document.querySelector(campo.getAttribute('data-cep'));
        var aviso = document.getElementById('cep-aviso');
        var ultimo = '';
        function avisar(t) { if (aviso) { aviso.textContent = t; } }
        campo.addEventListener('input', function () {
            var n = campo.value.replace(/\D/g, '').slice(0, 8);
            campo.value = n.length > 5 ? n.slice(0, 5) + '-' + n.slice(5) : n;
            if (n.length !== 8 || n === ultimo || !destino) { return; }
            ultimo = n;
            avisar('Buscando o CEP...');
            fetch('https://viacep.com.br/ws/' + n + '/json/')
                .then(function (r) { if (!r.ok) { throw new Error(); } return r.json(); })
                .then(function (d) {
                    if (d.erro) { avisar('CEP não encontrado. Digite o endereço.'); return; }
                    var partes = [d.logradouro, d.bairro, d.localidade && d.uf ? d.localidade + '/' + d.uf : d.localidade].filter(Boolean);
                    destino.value = (d.logradouro ? d.logradouro + ', nº  - ' : '') + partes.slice(d.logradouro ? 1 : 0).join(' - ');
                    avisar('Endereço preenchido. Complete o número.');
                    destino.focus();
                    var pos = destino.value.indexOf('nº ');
                    if (pos >= 0) { destino.setSelectionRange(pos + 3, pos + 3); }
                })
                .catch(function () { avisar('Não deu para consultar o CEP agora. Digite o endereço.'); });
        });
    });
})();
