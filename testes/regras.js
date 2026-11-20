// Testes das regras de negocio: limite de credito, pagamento, exclusao e permissoes.
const { chromium, B, SENHA, ok, sql, entrar, postar, terminar } = require('./comum');
(async () => {
    const b = await chromium.launch();
    const ze = await entrar(b, 'mercearia-do-ze', 'ze', SENHA);
    ok(ze.url().endsWith('dashboard.php'), 'Administrador entra na loja');
    const id = (nome) => sql(`SELECT c.id FROM clientes c JOIN comercios m ON m.id=c.comercio_id WHERE m.slug='mercearia-do-ze' AND c.nome='${nome}'`);
    const lucia = id('Lúcia Santos'), joao = id('João Pereira'), roberto = id('Roberto Alves');
    const compras = () => Number(sql(`SELECT COUNT(*) FROM compras WHERE cliente_id=${lucia}`));

    // compra
    const antes = compras();
    let t = await postar(ze, 'registrar_compra.php', { cliente_id: lucia, descricao: 'teste', valor: '999' });
    ok(t.includes('Limite de crédito excedido') && compras() === antes, 'Compra acima do limite é recusada e nada é gravado');
    t = await postar(ze, 'registrar_compra.php', { cliente_id: lucia, descricao: 'teste', valor: '0' });
    ok(t.includes('valor válido'), 'Compra de valor zero é recusada');
    t = await postar(ze, 'registrar_compra.php', { cliente_id: lucia, descricao: 'teste automatico', valor: '1.50' });
    ok(t.includes('registrada') && compras() === antes + 1, 'Compra dentro do limite é registrada');

    // pagamento
    t = await postar(ze, 'registrar_pagamento.php', { cliente_id: joao, valor: '9999' });
    ok(t.includes('maior que a dívida'), 'Pagamento maior que a dívida é recusado');
    t = await postar(ze, 'registrar_pagamento.php', { cliente_id: roberto, valor: '10' });
    ok(t.includes('não possui dívida'), 'Pagamento de quem não deve é recusado');
    t = await postar(ze, 'registrar_pagamento.php', { cliente_id: lucia, valor: '1.50' });
    ok(t.includes('registrado'), 'Pagamento parcial é registrado');

    // exclusao
    t = await postar(ze, 'excluir_cliente.php', { id: joao });
    ok(t.includes('Não é possível excluir') && id('João Pereira') !== '', 'Cliente com dívida não pode ser excluído');

    // situacao e ranking
    await ze.goto(B + 'relatorio.php?situacao=inadimplente');
    t = await ze.textContent('main');
    ok(t.includes('Lúcia Santos') && !t.includes('Roberto Alves'), 'Relatório filtra os inadimplentes (mais de 30 dias)');
    await ze.goto(B + 'ranking.php');
    ok((await ze.textContent('main')).includes('/100'), 'Ranking mostra o score de 0 a 100');

    // atendente
    const ana = await entrar(b, 'mercearia-do-ze', 'ana.caixa', SENHA);
    t = await postar(ana, 'cadastro_cliente.php', { nome: 'Cliente Teste Limite', telefone: '', limite_credito: '9999' });
    ok(sql("SELECT limite_credito FROM clientes WHERE nome='Cliente Teste Limite'") === '500.00', 'Atendente cadastra sempre com o limite padrão de R$ 500');
    await postar(ana, 'excluir_cliente.php', { id: roberto });
    ok(id('Roberto Alves') !== '', 'Atendente não exclui cliente');
    await ana.goto(B + 'resumo.php');
    ok(ana.url().endsWith('dashboard.php'), 'Atendente não abre o resumo financeiro');
    await ana.goto(B + 'minha_loja.php');
    ok(ana.url().endsWith('dashboard.php'), 'Atendente não abre Minha loja');

    // entrada: escolher a loja e endereco /loja com maiusculas
    const p = await (await b.newContext()).newPage();
    await p.goto(B + 'login.php?trocar=1');
    await p.fill('#procurar', 'acougue');
    await Promise.all([p.waitForNavigation(), p.click('form.form-loja button')]);
    ok(p.url().includes('loja=acougue-bom-corte'), '"Qual é a sua loja?" acha a loja mesmo sem acento');
    await p.goto(B + 'loja/Mercearia-do-Ze');
    ok((await p.textContent('body')).includes('Mercearia do Zé'), 'Endereço /loja com letras maiúsculas funciona');
    const r = await p.goto(B + 'pagina-que-nao-existe');
    ok(r.status() === 404, 'Endereço inexistente mostra a página 404');

    sql("DELETE FROM clientes WHERE nome='Cliente Teste Limite'");
    await terminar(b, 'Regras de negócio');
})();
