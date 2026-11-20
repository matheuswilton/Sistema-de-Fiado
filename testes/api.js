// Testes da API REST da loja (api.php): chave, recursos, codigos de resposta e isolamento.
const { chromium, B, ok, sql, entrar, SENHA, terminar } = require('./comum');
(async () => {
    const b = await chromium.launch();
    const ze = await entrar(b, 'mercearia-do-ze', 'ze', SENHA);
    await ze.goto(B + 'minha_loja.php');
    await Promise.all([ze.waitForNavigation(), ze.click('form:has(input[value=gerar_api]) button')]);
    const chave = (await ze.textContent('#chave-api')).trim();
    ok(/^ff_[0-9a-f]{48}$/.test(chave), 'Minha loja gera a chave da API');
    ok(!sql("SELECT COUNT(*) FROM comercios WHERE api_token_hash = '" + chave + "'").startsWith('1'), 'O banco guarda só o hash da chave');

    const chamar = (recurso, opcoes = {}) => fetch(B + 'api.php?recurso=' + recurso, { headers: { 'X-Api-Key': chave }, ...opcoes });
    let r = await chamar('resumo');
    let j = await r.json();
    ok(r.status === 200 && j.loja.nome === 'Mercearia do Zé' && typeof j.total_a_receber === 'number', 'resumo responde 200 com os totais');
    r = await chamar('clientes');
    j = await r.json();
    const daLoja = sql("SELECT COUNT(*) FROM clientes c JOIN comercios m ON m.id=c.comercio_id WHERE m.slug='mercearia-do-ze'");
    ok(r.status === 200 && String(j.total) === daLoja, 'clientes devolve só os clientes da loja');
    const outro = sql("SELECT c.id FROM clientes c JOIN comercios m ON m.id=c.comercio_id WHERE m.slug='padaria-pao-quente' LIMIT 1");
    r = await chamar('cliente&id=' + outro);
    ok(r.status === 404, 'Cliente de outra loja responde 404');
    r = await chamar('cliente&id=' + j.clientes[0].id);
    ok(r.status === 200 && Array.isArray((await r.json()).compras), 'Extrato de cliente da loja responde 200');
    r = await fetch(B + 'api.php?recurso=resumo');
    ok(r.status === 401, 'Sem chave responde 401');
    r = await chamar('resumo', { method: 'POST' });
    ok(r.status === 405, 'POST responde 405 (somente leitura)');
    r = await chamar('qualquer');
    ok(r.status === 400, 'Recurso desconhecido responde 400');

    await ze.goto(B + 'minha_loja.php');
    await ze.click('form:has(input[value=revogar_api]) button');
    await Promise.all([ze.waitForNavigation(), ze.click('.confirmar-caixa button[value=sim]')]);
    r = await chamar('resumo');
    ok(r.status === 401, 'Depois de desligar a API, a chave antiga para de funcionar');
    await terminar(b, 'API da loja');
})();
