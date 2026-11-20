// Testes da conta unica do cliente (CPF + senha): criar, confirmar pela loja, ver as dividas e as protecoes.
const { chromium, devices, B, SENHA, ok, sql, idCliente, terminar } = require('./comum');
const lucia = idCliente('mercearia-do-ze', 'Lúcia Santos');
async function staff(b,loja,u,s){ const p=await (await b.newContext({viewport:{width:1300,height:900}})).newPage(); await p.goto(B+'login.php?loja='+loja); await p.fill('#usuario',u); await p.fill('#senha',s); await Promise.all([p.waitForNavigation(), p.click('form button[type=submit]')]); return p; }
async function conta(b,cpf,s,mobile){ const c=await b.newContext(mobile?{...devices['iPhone 13']}:{viewport:{width:1300,height:900}}); const p=await c.newPage(); p.erros=[]; p.on('pageerror',e=>p.erros.push(e.message)); await p.goto(B+'conta_entrar.php'); await p.fill('#cpf',cpf); await p.fill('#senha',s); await Promise.all([p.waitForNavigation(), p.click('form button[type=submit]')]); return p; }
(async()=>{ const b=await chromium.launch();
 // deixa o banco como no comeco (permite rodar o teste mais de uma vez)
 sql("DELETE FROM contas_clientes WHERE cpf='39053344705'"); sql("UPDATE clientes SET cpf=NULL WHERE cpf='39053344705'");
 // 1) Joao (exemplo): ve Mercearia e Padaria, nao ve Acougue
 const j=await conta(b,'529.982.247-25',SENHA); await j.waitForTimeout(500);
 const tj=await j.textContent('body'); ok(j.url().endsWith('minhas_dividas.php'),'João entra com CPF e senha');
 ok(tj.includes('Mercearia do Zé') && tj.includes('Padaria Pão Quente'),'João vê Mercearia e Padaria'); ok(!tj.includes('Açougue Bom Corte') && !tj.includes('Lúcia'),'João não vê Açougue nem outros clientes');
 await j.click('summary:has-text("Pagar com Pix") >> nth=0'); await j.waitForTimeout(400); ok(await j.locator('.pix-qr svg').count()>=1,'QR Pix por loja');

 // senha errada
 const e=await conta(b,'529.982.247-25','errada123'); ok((await e.textContent('body')).includes('CPF ou senha inválidos'),'Senha errada recusada');
 // 2) loja coloca CPF da Lucia
 const ze=await staff(b,'mercearia-do-ze','ze',SENHA);
 await ze.goto(B+'editar_cliente.php?id='+lucia); await ze.fill('#cpf','123'); await Promise.all([ze.waitForNavigation(), ze.click('form button[type=submit]')]); ok((await ze.textContent('body')).includes('CPF inválido'),'CPF inválido recusado no cadastro');
 await ze.fill('#cpf','52998224725'); await Promise.all([ze.waitForNavigation(), ze.click('form button[type=submit]')]); ok((await ze.textContent('body')).includes('Já existe outro cliente com este CPF'),'CPF repetido na mesma loja recusado');
 await ze.fill('#cpf','390.533.447-05'); await Promise.all([ze.waitForNavigation(), ze.click('form button[type=submit]')]); ok(ze.url().includes('cliente.php?id='+lucia),'Loja salva o CPF da Lúcia');
 // 3) Lucia cria a conta
 const c=await (await b.newContext({viewport:{width:1300,height:900}})).newPage();
 async function criar(cpf,tel){ await c.goto(B+'conta_criar.php'); await c.fill('#nome','Lúcia Santos'); await c.fill('#cpf',cpf); await c.fill('#telefone',tel); await c.fill('#senha','lucia1234'); await c.fill('#confirmar_senha','lucia1234'); await c.waitForTimeout(3200); await Promise.all([c.waitForNavigation(), c.click('form button[type=submit]')]); return c.textContent('body'); }
 ok((await criar('111.444.777-35','(62) 98111-0002')).includes('Já existe uma conta'),'CPF que já tem conta recusado');
 ok((await criar('168.995.350-09','(62) 98111-0002')).includes('Nenhuma loja tem este CPF'),'CPF fora das lojas recusado');
 ok((await criar('390.533.447-05','(62) 90000-0000')).includes('telefone não confere'),'Telefone diferente do cadastro recusado');
 const t=await criar('390.533.447-05','(62) 98111-0002'); ok(c.url().endsWith('minhas_dividas.php') && t.includes('Aguardando a loja confirmar'),'Conta criada e aguardando a loja');
 ok(!t.includes('R$ 46,00'),'Antes de confirmar não mostra a dívida');
 // 4) Padaria nao ve o pedido; Mercearia confirma
 const ro=await staff(b,'padaria-pao-quente','rosa',SENHA); await ro.goto(B+'aprovar_acessos.php'); ok(!(await ro.textContent('body')).includes('Lúcia'),'Padaria não vê o pedido da Mercearia');
 await ze.goto(B+'dashboard.php'); ok((await ze.locator('.menu .contador').textContent()).trim()==='1','Contador do menu mostra o pedido');
 await ze.goto(B+'aprovar_acessos.php'); ok((await ze.textContent('body')).includes('confere'),'Telefone confere aparece');

 // tentativa de aprovar vinculo de outra loja (id do joao na padaria)
 await Promise.all([ze.waitForNavigation(), ze.click('button[value=aprovar_conta]')]);
 await c.reload(); const t2=await c.textContent('body'); ok(t2.includes('Mercearia do Zé') && t2.includes('R$ 46,00'),'Depois de confirmada, Lúcia vê a dívida');
 // 5) loja troca o CPF -> ligacao some
 await ze.goto(B+'editar_cliente.php?id='+lucia); await ze.fill('#cpf',''); await Promise.all([ze.waitForNavigation(), ze.click('form button[type=submit]')]);
 await c.reload(); ok(!(await c.textContent('body')).includes('R$ 46,00'),'CPF removido: conta deixa de ver a loja');
 // 6) celular
 const m=await conta(b,'111.444.777-35',SENHA,true); await m.waitForTimeout(500); ok((await m.textContent('body')).includes('Açougue Bom Corte'),'Maria vê o Açougue no celular');
 ok(await m.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'Sem rolagem lateral no celular');
 // 7) sair
 await Promise.all([j.waitForNavigation(), j.evaluate(()=>location.href='/logout.php')]); ok(j.url().includes('conta_entrar.php'),'Sair volta para a área do cliente');
 await j.goto(B+'minhas_dividas.php'); ok(j.url().includes('conta_entrar.php'),'Depois de sair não abre as dívidas');
 // 8) conta do cliente nao abre telas da loja
 const m2=await conta(b,'111.444.777-35',SENHA); await m2.goto(B+'dashboard.php'); ok(!m2.url().endsWith('dashboard.php'),'Conta de cliente não abre o painel da loja');
 // tela de loja
 const n=await (await b.newContext({viewport:{width:1300,height:900}})).newPage(); await n.goto(B+'login.php?trocar=1');
 console.log('erros JS:', j.erros.concat(m.erros)); await terminar(b, 'Conta do cliente por CPF'); })();
