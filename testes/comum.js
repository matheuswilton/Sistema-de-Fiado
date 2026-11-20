// Funcoes usadas por todos os testes de navegador.
// O site de testes roda em http://127.0.0.1:8080 com um banco SO de testes (nunca o banco do site no ar).
const { chromium, devices } = require('playwright');
const { execSync } = require('child_process');

const B = process.env.ENDERECO_TESTE || 'http://127.0.0.1:8080/';
// senha de todas as contas no banco de testes (definida por testes/preparar.php)
const SENHA = process.env.SENHA_TESTE || 'teste-ci-2026';
const MYSQL = process.env.MYSQL_CMD || 'mysql -ufiado -pfiado sistema_fiado';

let falhas = 0;
function ok(condicao, mensagem) {
    console.log((condicao ? 'OK    ' : 'FALHA ') + mensagem);
    if (!condicao) { falhas++; }
}
// consulta direta no banco de testes (para conferir o que foi gravado)
// id de um cliente ou de um usuario pelo nome e pela loja (os numeros mudam de um banco para outro)
function idCliente(loja, nome) {
    return sql(`SELECT c.id FROM clientes c JOIN comercios m ON m.id = c.comercio_id WHERE m.slug = '${loja}' AND c.nome = '${nome}'`);
}
function idUsuario(loja, usuario) {
    return sql(`SELECT u.id FROM usuarios u JOIN comercios m ON m.id = u.comercio_id WHERE m.slug = '${loja}' AND u.usuario = '${usuario}'`);
}
function sql(consulta) {
    return execSync(`${MYSQL} --default-character-set=utf8mb4 -N -e "${consulta}"`).toString().trim();
}
// entra no login de uma loja com usuario e senha
async function entrar(navegador, loja, usuario, senha) {
    const contexto = await navegador.newContext({ viewport: { width: 1366, height: 800 } });
    const p = await contexto.newPage();
    p.erros = [];
    p.on('pageerror', e => p.erros.push(e.message));
    await p.goto(B + 'login.php' + (loja ? '?loja=' + loja : ''));
    await p.fill('#usuario', usuario);
    await p.fill('#senha', senha);
    await Promise.all([p.waitForNavigation(), p.click('form button[type=submit]')]);
    return p;
}
// envia um formulario POST com o token CSRF da sessao (simula alguem mandando dados "na mao")
async function postar(p, pagina, campos) {
    await p.goto(B + pagina);
    if (!(await p.$('input[name=csrf]'))) { await p.goto(B + 'minha_senha.php'); }
    const token = await p.$eval('input[name=csrf]', e => e.value);
    await Promise.all([
        p.waitForNavigation(),
        p.evaluate(([pagina, campos, token]) => {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = pagina; campos.csrf = token;
            for (const k in campos) { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = campos[k]; f.appendChild(i); }
            document.body.appendChild(f); f.submit();
        }, [pagina, campos, token]),
    ]);
    await p.waitForLoadState('load');
    return (await p.textContent('body')).replace(/\s+/g, ' ');
}
async function terminar(navegador, nome) {
    await navegador.close();
    console.log(falhas ? `${nome}: ${falhas} FALHA(S)` : `${nome}: todos os testes passaram`);
    process.exitCode = falhas ? 1 : 0;
}
module.exports = { chromium, devices, B, SENHA, ok, sql, idCliente, idUsuario, entrar, postar, terminar };
