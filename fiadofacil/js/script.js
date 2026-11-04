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
