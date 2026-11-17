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
