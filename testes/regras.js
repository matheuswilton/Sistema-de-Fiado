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

    // pela tela: mascara de dinheiro, janela de confirmacao e volta para o extrato do cliente
    await ze.goto(B + 'registrar_pagamento.php?cliente_id=' + joao + '&voltar=1');
    await ze.type('#campo-valor', '1050');
    ok(await ze.inputValue('#campo-valor') === 'R$ 10,50', 'Campo de dinheiro formata sozinho (R$ 10,50)');
    await ze.click('form[data-resumo] button[type=submit]');
    const resumo = await ze.textContent('.confirmar-caixa');
    ok(resumo.includes('Confirmar pagamento?') && resumo.includes('R$ 10,50') && resumo.includes('Dívida depois'), 'Pagamento pede confirmação com o resumo');
    await Promise.all([ze.waitForNavigation(), ze.click('.confirmar-caixa button[value=sim]')]);
    ok(ze.url().includes('cliente.php?id=' + joao) && (await ze.textContent('body')).includes('registrado'), 'Depois do pagamento volta para o extrato do cliente');
    await ze.goto(B + 'registrar_compra.php?cliente_id=' + joao + '&voltar=1');
    await ze.type('#valor', '1050');
    await ze.click('form[data-resumo] button[type=submit]');
    await ze.click('.confirmar-caixa button[value=nao]');
    ok(ze.url().includes('registrar_compra.php'), 'Voltar na confirmação não grava a compra');
    await ze.click('form[data-resumo] button[type=submit]');
    await Promise.all([ze.waitForNavigation(), ze.click('.confirmar-caixa button[value=sim]')]);
    ok(ze.url().includes('cliente.php?id=' + joao) && (await ze.textContent('body')).includes('registrada'), 'Depois da compra volta para o extrato do cliente');
    await ze.goto(B + 'registrar_compra.php?cliente_id=' + joao + '&voltar=1');
    ok((await ze.getAttribute('a.botao-secundario:has-text("Cancelar")', 'href')) === 'cliente.php?id=' + joao, 'Cancelar volta para o extrato do cliente');

    // cobrancas
    await ze.goto(B + 'cobrancas.php');
    t = await ze.textContent('main');
    ok(t.includes('Em atraso') && t.includes('Lúcia Santos') && t.includes('Atrasado há'), 'Cobranças lista quem está em atraso');
    ok((await ze.locator('.cobranca a.botao-whatsapp').count()) > 0, 'Cobranças tem o botão de enviar no WhatsApp');
    ok((await ze.locator('.menu a[href="cobrancas.php"] .contador').count()) === 1, 'Menu mostra quantos clientes cobrar');

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

    // foto da loja (Minha loja): so aceita imagem de verdade e aparece no fundo da tela de entrar
    await ze.goto(B + 'minha_loja.php');
    await Promise.all([ze.waitForNavigation(), ze.setInputFiles('input[name=foto]', { name: 'nao-e-foto.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('isto nao e uma imagem') })]);
    ok((await ze.textContent('body')).includes('Envie uma foto em JPG'), 'Arquivo que não é imagem é recusado');
    const png = await ze.evaluate(() => { const c = document.createElement('canvas'); c.width = 1200; c.height = 700; const g = c.getContext('2d'); g.fillStyle = '#7c4a24'; g.fillRect(0, 0, 1200, 700); return c.toDataURL('image/png').split(',')[1]; });
    await Promise.all([ze.waitForNavigation(), ze.setInputFiles('input[name=foto]', { name: 'loja.png', mimeType: 'image/png', buffer: Buffer.from(png, 'base64') })]);
    ok((await ze.textContent('body')).includes('Foto da loja salva'), 'Foto da loja é salva');
    const visita = await (await b.newContext()).newPage();
    await visita.goto(B + 'login.php?loja=mercearia-do-ze');
    ok((await visita.locator('.marca-login.com-foto').count()) === 1, 'A foto aparece no fundo da tela de entrar da loja');
    const fotoCarregada = await visita.evaluate(async () => {
        await new Promise(r => setTimeout(r, 1000));
        return performance.getEntriesByType('resource').filter(e => e.name.includes('/uploads/lojas/')).map(e => e.responseStatus);
    });
    ok(fotoCarregada.length > 0 && fotoCarregada.every(s => s === 200), 'O navegador carrega a foto de verdade no fundo da tela de entrar');
    const fotoResp = await visita.request.get(new URL(await visita.evaluate(() => getComputedStyle(document.querySelector('.marca-login')).getPropertyValue('--foto-loja').match(/url\(['"]?([^'")]+)/)[1]), B).href);
    ok(fotoResp.status() === 200 && (fotoResp.headers()['content-type'] || '').includes('image/jpeg'), 'A foto foi regravada em JPEG');
    await visita.goto(B + 'login.php?loja=principal');
    ok((await visita.locator('.marca-login.com-foto').count()) === 0, 'A foto de uma loja não aparece em outra');
    await visita.goto(B + 'login.php?loja=padaria-pao-quente');
    ok((await visita.locator('.marca-login.com-foto').count()) === 1, 'As lojas de exemplo já começam com foto');
    await ze.goto(B + 'minha_loja.php');
    await ze.click('form:has(input[value=remover_foto]) button');
    await Promise.all([ze.waitForNavigation(), ze.click('.confirmar-caixa button[value=sim]')]);
    await visita.goto(B + 'login.php?loja=mercearia-do-ze');
    ok((await visita.locator('.marca-login.com-foto').count()) === 0, 'Foto removida: volta o fundo escuro');

    // formulario sem o codigo de seguranca (CSRF): volta para a mesma pagina do proprio site, nunca para outro
    const semToken = await ze.request.post(B + 'minha_senha.php?aba=1', { form: { senha_atual: 'x' }, maxRedirects: 0 });
    const destino = semToken.headers()['location'] || '';
    ok(semToken.status() === 302 && destino === 'minha_senha.php?aba=1', 'Formulário sem código de segurança volta para a mesma página do site');

    sql("DELETE FROM clientes WHERE nome='Cliente Teste Limite'");
    await terminar(b, 'Regras de negócio');
})();
