// Testes de isolamento entre lojas: nenhuma loja ve, altera ou apaga dados de outra (pela URL ou pelo formulario).
const { chromium, B, SENHA, ok, sql, idCliente, idUsuario, entrar, postar, terminar } = require('./comum');
const cida = idCliente('padaria-pao-quente', 'Dona Cida');
const mariaCliente = idCliente('acougue-bom-corte', 'Maria Oliveira');
const antonio = idCliente('acougue-bom-corte', 'Seu Antônio');
const paula = idCliente('acougue-bom-corte', 'Paula Reis');
const rosaId = idUsuario('padaria-pao-quente', 'rosa');
const mariaId = idUsuario('acougue-bom-corte', 'maria');
const joaoPadariaId = idUsuario('padaria-pao-quente', 'joao');
(async()=>{ const b=await chromium.launch();
 // 1) cada loja ve so os proprios clientes
 const ze=await entrar(b,'mercearia-do-ze','ze',SENHA); ok(ze.url().endsWith('dashboard.php'),'Zé entra na Mercearia');
 await ze.goto(B+'listar_clientes.php'); let t=await ze.textContent('main');
 ok(t.includes('Lúcia Santos') && !t.includes('Dona Cida') && !t.includes('Maria Oliveira') && !t.includes('Ana Lima'),'Mercearia lista só os próprios clientes');
 await ze.goto(B+'relatorio.php'); t=await ze.textContent('main'); ok(!t.includes('Dona Cida') && !t.includes('Seu Antônio'),'Relatório da Mercearia sem clientes de outras lojas');
 await ze.goto(B+'ranking.php'); t=await ze.textContent('main'); ok(!t.includes('Dona Cida'),'Ranking sem clientes de outras lojas');
 await ze.goto(B+'resumo.php'); t=await ze.textContent('main'); ok(!t.includes('Dona Cida'),'Resumo sem clientes de outras lojas');
 await ze.goto(B+'usuarios.php'); t=await ze.textContent('main'); ok(t.includes('ana.caixa') && !t.includes('rosa') && !t.includes('carlos.dono') && !t.includes('admin '),'Usuários: só funcionários da Mercearia');
 await ze.goto(B+'acessos_clientes.php'); t=await ze.textContent('main'); ok(!t.includes('maria') && !t.includes('Maria Oliveira'),'Acessos de clientes só da Mercearia');
 await ze.goto(B+'registrar_compra.php'); t=await ze.textContent('select#cliente_id'); ok(!t.includes('Dona Cida') && !t.includes('Maria Oliveira'),'Lista de compra só com clientes da Mercearia');
 // 2) tentativas de acessar dados de outra loja trocando o id
 await ze.goto(B+'cliente.php?id='+cida); ok(ze.url().includes('listar_clientes') && !(await ze.textContent('body')).includes('Dona Cida'),'Extrato de cliente da Padaria bloqueado');
 await ze.goto(B+'editar_cliente.php?id='+mariaCliente); ok(!(await ze.textContent('body')).includes('Maria Oliveira'),'Editar cliente do Açougue bloqueado');
 t=await postar(ze,'registrar_compra.php',{cliente_id:cida,descricao:'teste invasao',valor:'10'}); ok(t.includes('não encontrado') || t.includes('Cliente não'),'Compra para cliente da Padaria recusada');
 t=await postar(ze,'registrar_pagamento.php',{cliente_id:antonio,valor:'10'}); ok(!t.includes('registrado'),'Pagamento para cliente do Açougue recusado');
 t=await postar(ze,'excluir_cliente.php',{id:paula}); ok(t.includes('não encontrado'),'Excluir cliente do Açougue recusado');
 await postar(ze,'usuarios.php',{acao:'bloquear',id_usuario:rosaId});
 await postar(ze,'acessos_clientes.php',{acao:'bloquear',id_usuario:mariaId});
 await postar(ze,'acessos_clientes.php',{acao:'trocar_senha',id_usuario:joaoPadariaId,nova_senha:'invasao123'});
 // 3) mesmo nome de usuario em lojas diferentes
 const joaoP=await entrar(b,'padaria-pao-quente','joao',SENHA); t=await joaoP.textContent('main');
 ok(joaoP.url().endsWith('minha_divida.php') && t.includes('Pão francês') && !t.includes('Arroz'),'joao na Padaria vê só as compras da Padaria');
 const joaoM=await entrar(b,'mercearia-do-ze','joao',SENHA); t=await joaoM.textContent('main');
 ok(t.includes('Arroz') && !t.includes('Pão francês'),'joao na Mercearia vê só as compras da Mercearia');
 // 4) usuario de uma loja nao entra pelo endereco de outra
 const errado=await entrar(b,'padaria-pao-quente','ze',SENHA); ok(errado.url().includes('login.php') && (await errado.textContent('body')).includes('inválidos'),'Zé não entra pelo endereço da Padaria');
 // as tentativas de invasao acima nao mudaram nada
 const rosa=await entrar(b,'padaria-pao-quente','rosa',SENHA); ok(rosa.url().endsWith('dashboard.php'),'Rosa continua entrando (bloqueio vindo da Mercearia não funcionou)');
 const maria=await entrar(b,'acougue-bom-corte','maria',SENHA); ok(maria.url().endsWith('minha_divida.php'),'maria continua entrando no Açougue');
 const joaoP2=await entrar(b,'padaria-pao-quente','joao',SENHA); ok(joaoP2.url().endsWith('minha_divida.php'),'Senha do joao da Padaria não foi trocada pela Mercearia');
 await rosa.goto(B+'listar_clientes.php'); ok((await rosa.textContent('main')).includes('Dona Cida'),'Cliente da Padaria não foi excluído');
 // 5) loja principal (dados antigos) continua igual
 // conta por CPF: a Mercearia tenta confirmar a ligacao do joao com a PADARIA (id de outra loja)
 {
   const vid=sql("SELECT v.id FROM contas_vinculos v JOIN comercios m ON m.id=v.comercio_id WHERE m.slug='padaria-pao-quente' LIMIT 1");
   sql(`UPDATE contas_vinculos SET status='pendente' WHERE id=${vid}`);
   await postar(ze,'aprovar_acessos.php',{acao:'aprovar_conta', id_vinculo:vid});
   ok(sql(`SELECT status FROM contas_vinculos WHERE id=${vid}`)==='pendente','Mercearia não confirma conta ligada à Padaria');
   await postar(ze,'aprovar_acessos.php',{acao:'desligar_conta', id_vinculo:vid});
   ok(sql(`SELECT status FROM contas_vinculos WHERE id=${vid}`)==='pendente','Mercearia não desliga conta da Padaria');
   sql(`UPDATE contas_vinculos SET status='aprovado' WHERE id=${vid}`); }
 const adm=await entrar(b,'principal','admin',SENHA); await adm.goto(B+'listar_clientes.php'); t=await adm.textContent('main'); ok(t.includes('Ana Lima') && !t.includes('Lúcia'),'Loja principal: admin antigo vê os clientes de antes');
 // 6) funcionario comum nao abre area de administrador
 const ana=await entrar(b,'mercearia-do-ze','ana.caixa',SENHA); await ana.goto(B+'usuarios.php'); ok(ana.url().endsWith('dashboard.php'),'Funcionária não abre a tela de usuários');
 // 7) painel da plataforma
 const c=await b.newContext({viewport:{width:1366,height:900}}); const pl=await c.newPage(); pl.erros=[]; pl.on('pageerror',e=>pl.erros.push(e.message));
 await pl.goto(B+'painel.php'); await pl.fill('#usuario','dono.plataforma'); await pl.fill('#senha',SENHA); await Promise.all([pl.waitForNavigation(), pl.click('form button[type=submit]')]);
 t=await pl.textContent('main'); ok(t.includes('Mercearia do Zé') && t.includes('Padaria Pão Quente') && t.includes('Açougue Bom Corte') && t.includes('Loja principal'),'Painel lista as 4 lojas');
 // entra no Acougue como admin
 const cartao=pl.locator('.loja-cartao',{hasText:'Açougue Bom Corte'}); await Promise.all([pl.waitForNavigation(), cartao.locator('button[value=entrar]').click()]);
 t=await pl.textContent('body'); ok(pl.url().endsWith('dashboard.php') && t.includes('Açougue Bom Corte') && t.includes('dono da plataforma'),'Painel entra no Açougue como administrador');
 await pl.goto(B+'listar_clientes.php'); t=await pl.textContent('main'); ok(t.includes('Maria Oliveira') && !t.includes('Lúcia'),'Dentro do Açougue vê só os clientes do Açougue');
 await pl.goto(B+'painel.php?sair_da_loja=1'); ok(pl.url().endsWith('painel.php'),'Volta ao painel');
 // bloqueia a Padaria
 const cp=pl.locator('.loja-cartao',{hasText:'Padaria Pão Quente'}); await cp.locator('button[value=bloquear]').click(); await Promise.all([pl.waitForNavigation(), pl.click('.confirmar-caixa button[value=sim]')]);
 const rosa2=await entrar(b,'padaria-pao-quente','rosa',SENHA); ok(rosa2.url().includes('login.php') && (await rosa2.textContent('body')).includes('suspenso'),'Loja bloqueada: Rosa não entra');
 await rosa.goto(B+'dashboard.php'); ok(rosa.url().includes('login.php'),'Loja bloqueada: sessão aberta da Rosa cai na hora');
 await pl.goto(B+'painel.php'); const cp2=pl.locator('.loja-cartao',{hasText:'Padaria Pão Quente'}); await Promise.all([pl.waitForNavigation(), cp2.locator('button[value=desbloquear]').click()]);
 const rosa3=await entrar(b,'padaria-pao-quente','rosa',SENHA); ok(rosa3.url().endsWith('dashboard.php'),'Loja desbloqueada: Rosa entra de novo');
 // cria loja nova
 await pl.goto(B+'painel.php'); await pl.fill('#nome','Lanchonete da Praça'); await pl.fill('#admin_nome','Teste'); await pl.fill('#admin_usuario','lanche.adm'); await pl.fill('#admin_senha','Lanche12345');
 await Promise.all([pl.waitForNavigation(), pl.click('.nova-loja button[type=submit]')]); t=await pl.textContent('main'); ok(t.includes('/loja/lanchonete-da-praca'),'Painel cria loja nova com endereço automático');
 const lan=await entrar(b,'lanchonete-da-praca','lanche.adm','Lanche12345'); await lan.goto(B+'listar_clientes.php'); ok((await lan.textContent('main')).includes('Nenhum cliente'),'Loja nova começa vazia');
 // usuario de loja nao abre o painel
 await ze.goto(B+'painel.php'); ok(!ze.url().endsWith('painel.php') || !(await ze.textContent('body')).includes('Todas as lojas'),'Usuário de loja não abre o painel');
 const errosJS=[ze,rosa,adm,ana,pl].flatMap(p=>p.erros||[]); console.log('erros JS:', errosJS.length?errosJS:'nenhum');
 await terminar(b, 'Isolamento entre lojas'); })();
