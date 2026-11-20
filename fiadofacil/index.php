<?php
// Pagina de apresentacao do FiadoFacil (aparece antes do login).
// Se a pessoa ja estiver logada, vai direto para o sistema.
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['usuario_nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FiadoFácil — Controle do fiado sem caderninho</title>
<meta name="description" content="Sistema web gratuito para pequenos comércios controlarem compras no fiado, pagamentos e dívidas de clientes, no computador e no celular.">
<?php metas_compartilhamento("FiadoFácil — Controle do fiado sem caderninho", "Quem anota, recebe. Sistema gratuito para o comércio de bairro controlar o fiado, no computador e no celular."); ?>
<script>
// aplica o tema (claro/noturno) antes de desenhar a pagina, para nao piscar
(function () {
    var tema = null;
    try { tema = localStorage.getItem('tema'); } catch (e) {}
    if (tema !== 'claro' && tema !== 'escuro') {
        tema = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'escuro' : 'claro';
    }
    document.documentElement.setAttribute('data-tema', tema);
})();
</script>
<meta name="theme-color" content="#1e3a8a">
<link rel="manifest" href="manifest.json">
<link rel="icon" type="image/svg+xml" href="img/logo.svg?v=2">
<link rel="alternate icon" type="image/png" href="img/icone-192.png?v=2">
<link rel="apple-touch-icon" href="img/icone-180.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Caveat:wght@500;700&display=swap">
<link rel="stylesheet" href="css/estilo.css?v=30">
<link rel="stylesheet" href="css/inicio.css?v=21">
<link rel="stylesheet" href="css/cinema.css?v=1">
</head>
<body class="pagina-inicio">
<div class="progresso-rolagem" aria-hidden="true"></div>

<header class="inicio-topo">
    <div class="inicio-largura inicio-topo-conteudo">
        <a href="index.php" class="inicio-marca"><?php echo logo(48); ?><span>FiadoFácil</span></a>
        <nav class="inicio-nav" aria-label="Seções da página">
            <a href="#problema">O problema</a>
            <a href="#funcoes">Funcionalidades</a>
            <a href="#como">Como funciona</a>
            <a href="#equipe">Quem fez</a>
        </nav>
        <div class="inicio-topo-botoes">
            <button type="button" class="tema-botao inicio-tema" aria-label="Alternar modo noturno" title="Modo noturno"><?php echo icone('lua', 'icone-lua') . icone('sol', 'icone-sol'); ?></button>
            <a href="login.php" class="botao">Entrar</a>
        </div>
    </div>
</header>

<main>
    <!-- Abertura -->
    <section class="inicio-hero">
        <span class="bolha bolha-1" aria-hidden="true"></span><span class="bolha bolha-2" aria-hidden="true"></span>
        <div class="inicio-largura inicio-hero-grade">
            <div class="revelar">
                <span class="inicio-selo">Projeto Integrador · GTI · FPM</span>
                <h1><span class="h1-linha">Quem anota, recebe.</span> O fiado da sua loja <span class="destaque-texto">sem caderninho</span>.</h1>
                <p class="inicio-lead">Anote cada compra a prazo, trave o limite de cada cliente e saiba na hora quem está devendo. No computador e no celular, de graça.</p>
                <div class="inicio-botoes">
                    <a href="login.php" class="botao botao-grande">Entrar no sistema <?php echo icone('seta'); ?></a>
                    <a href="conta_entrar.php" class="botao botao-secundario botao-grande">Sou cliente e quero ver minhas dívidas</a>
                </div>
            </div>
            <!-- "Tela" ilustrativa feita so com HTML e CSS -->
            <div class="inicio-mock revelar" aria-hidden="true">
                <div class="mock-barra"><span></span><span></span><span></span></div>
                <div class="mock-corpo">
                    <div class="mock-cartoes">
                        <div class="mock-cartao"><small>Total a receber</small><strong class="conta" data-valor="1240" data-dinheiro="1">R$ 1.240,00</strong></div>
                        <div class="mock-cartao mock-alerta"><small>Inadimplentes</small><strong>2</strong></div>
                    </div>
                    <div class="mock-linha"><span class="avatar avatar-cor-0">JS</span><b>João Silva</b><i class="selo selo-aviso">Devendo</i></div>
                    <div class="mock-linha"><span class="avatar avatar-cor-1">AL</span><b>Ana Lima</b><i class="selo selo-ok">Em dia</i></div>
                    <div class="mock-linha"><span class="avatar avatar-cor-2">CP</span><b>Carlos Pereira</b><i class="selo selo-alerta">Inadimplente</i></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Faixa de frases de efeito correndo -->
    <div class="faixa-frases" role="marquee" aria-label="Quem anota, recebe. Adeus, caderninho. Fiado organizado, cliente fiel. Cada real anotado é um real recebido. Seu caderninho agora cabe no bolso. Vender a prazo sem perder o sono. Confiança se constrói. Controle também.">
        <div class="faixa-frases-trilho" aria-hidden="true"><div class="faixa-frases-grupo"><span>Quem anota, recebe</span><i aria-hidden="true">✓</i><span>Adeus, caderninho</span><i aria-hidden="true">✓</i><span>Fiado organizado, cliente fiel</span><i aria-hidden="true">✓</i><span>Cada real anotado é um real recebido</span><i aria-hidden="true">✓</i><span>Seu caderninho agora cabe no bolso</span><i aria-hidden="true">✓</i><span>Vender a prazo sem perder o sono</span><i aria-hidden="true">✓</i><span>Confiança se constrói. Controle também</span><i aria-hidden="true">✓</i></div><div class="faixa-frases-grupo"><span>Quem anota, recebe</span><i aria-hidden="true">✓</i><span>Adeus, caderninho</span><i aria-hidden="true">✓</i><span>Fiado organizado, cliente fiel</span><i aria-hidden="true">✓</i><span>Cada real anotado é um real recebido</span><i aria-hidden="true">✓</i><span>Seu caderninho agora cabe no bolso</span><i aria-hidden="true">✓</i><span>Vender a prazo sem perder o sono</span><i aria-hidden="true">✓</i><span>Confiança se constrói. Controle também</span><i aria-hidden="true">✓</i></div></div>
    </div>


    <!-- Cena de cinema: o caderninho vira o FiadoFacil conforme a pessoa rola a pagina -->
    <section class="cinema" aria-label="Do caderninho ao celular">
        <div class="cinema-palco">
            <div class="cinema-legendas">
                <p class="legenda" data-cena="1"><small>01</small>Todo fiado começava assim.</p>
                <p class="legenda" data-cena="2"><small>02</small>Até a página acabar, a conta não fechar e alguém esquecer de pagar.</p>
                <p class="legenda" data-cena="3"><small>03</small>Então o caderninho virou sistema.</p>
                <p class="legenda" data-cena="4"><small>04</small>Cada compra respeita o limite. Cada atraso aparece sozinho.</p>
            </div>
            <div class="cinema-cena" aria-hidden="true">
                <div class="caderno">
                    <div class="caderno-espiral"></div>
                    <div class="caderno-titulo">Fiado — Novembro</div>
                    <div class="caderno-linha"><span class="cl-nome">João Silva</span><span class="cl-item">Arroz, feijão</span><span class="cl-valor">45,00</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div><div class="caderno-linha"><span class="cl-nome">Maria Souza</span><span class="cl-item">Pão e leite</span><span class="cl-valor">20,00</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div><div class="caderno-linha"><span class="cl-nome">Carlos P.</span><span class="cl-item">Compra do mês</span><span class="cl-valor">180,00</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div><div class="caderno-linha"><span class="cl-nome">Ana Lima</span><span class="cl-item">Frios</span><span class="cl-valor">60,00</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div><div class="caderno-linha"><span class="cl-nome">Seu Zé</span><span class="cl-item">Refrigerante</span><span class="cl-valor">15,00</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div><div class="caderno-linha"><span class="cl-nome">Dona Cida</span><span class="cl-item">???</span><span class="cl-valor">—</span><svg class="rasura" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M2 12c40-9 70 8 110-2s80 9 120-1 50 6 66-3"/></svg></div>
                    <span class="mancha-cafe"></span>
                    <span class="interrogacao i1">?</span><span class="interrogacao i2">?!</span>
                    <div class="caderno-soma">Total: <s>R$ 320</s> <s>R$ 302</s> R$ ???</div>
                </div>
                <div class="celular">
                    <div class="celular-tela">
                        <div class="app-topo">🧺 Mercearia do Zé</div>
                        <div class="app-total"><small>Total a receber</small><strong data-total="1240">R$ 0,00</strong></div>
                        <div class="app-limite"><small>Uso do limite · Carlos</small><span class="app-limite-trilho"><span class="app-limite-barra"></span></span></div>
                        <div class="app-item"><span class="avatar avatar-cor-0">JS</span><span class="app-nome">João Silva</span><b>R$ 45,00</b><i class="selo selo-aviso">Devendo</i></div><div class="app-item"><span class="avatar avatar-cor-1">MS</span><span class="app-nome">Maria Souza</span><b>R$ 20,00</b><i class="selo selo-aviso">Devendo</i></div><div class="app-item"><span class="avatar avatar-cor-2">CP</span><span class="app-nome">Carlos Pereira</span><b>R$ 180,00</b><i class="selo selo-alerta">Inadimplente</i></div><div class="app-item"><span class="avatar avatar-cor-3">AL</span><span class="app-nome">Ana Lima</span><b>R$ 0,00</b><i class="selo selo-ok">Em dia</i></div>
                        <div class="app-aviso">⛔ Compra bloqueada: passou do limite</div>
                        <div class="app-recebido">✓ Pagamento de R$ 60,00 registrado</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- O problema -->
    <section id="problema" class="inicio-secao">
        <div class="inicio-largura">
            <div class="inicio-titulo revelar">
                <span class="inicio-sobretitulo">Por que criei o FiadoFácil</span>
                <h2>“Anota aí que eu pago sexta.”</h2>
                <p>O fiado é tradição no comércio de bairro, mas quase sempre fica num caderno: some, não soma sozinho, não avisa quem está atrasado e gera discussão no balcão.</p>
            </div>
            <div class="grade-inicio grade-numeros">
                <div class="numero-cartao revelar">
                    <strong class="conta" data-valor="84.2" data-casas="1" data-sufixo=" mi">84,2 mi</strong>
                    <p>Brasileiros estavam inadimplentes em setembro de 2026 — mais da metade dos adultos.</p>
                    <small>Fonte: Serasa, Mapa da Inadimplência</small>
                </div>
                <div class="numero-cartao revelar">
                    <strong class="conta" data-valor="4" data-sufixo=" em 10">4 em 10</strong>
                    <p>Microempreendedores individuais vendem fiado, muitas vezes controlando tudo no papel.</p>
                    <small>Fonte: Sebrae</small>
                </div>
                <div class="numero-cartao revelar">
                    <strong><?php echo icone('caderno'); ?></strong>
                    <p>Caderno não tem cópia, não calcula a dívida e não mostra quem passou do limite.</p>
                    <small>O problema que o FiadoFácil resolve</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Funcionalidades -->
    <section id="funcoes" class="inicio-secao inicio-secao-alt">
        <div class="inicio-largura">
            <div class="inicio-titulo revelar">
                <span class="inicio-sobretitulo">Funcionalidades</span>
                <h2>Tudo o que o caderninho nunca fez por você</h2>
            </div>
            <div class="grade-inicio grade-funcoes">
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('compra'); ?></span><h3>Compra com limite</h3><p>Cada cliente tem um limite de crédito. Se a compra passar dele, o sistema bloqueia.</p></div>
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('pagamento'); ?></span><h3>Pagamentos</h3><p>Pagamento parcial ou "quitar tudo". A dívida é sempre calculada, nunca digitada.</p></div>
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('atencao'); ?></span><h3>Quem está atrasado</h3><p>Situação automática: em dia, devendo ou inadimplente (mais de 30 dias).</p></div>
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('relatorio'); ?></span><h3>Relatórios e ranking</h3><p>Dívidas por período, pronto para imprimir, e ranking de confiabilidade.</p></div>
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('clientes'); ?></span><h3>Cliente consulta</h3><p>Com login próprio, o cliente vê só a própria dívida. Acabam as discussões.</p></div>
                <div class="funcao-cartao revelar"><span class="funcao-icone"><?php echo icone('celular'); ?></span><h3>Computador e celular</h3><p>Funciona no navegador, com modo noturno, e pode virar app na tela inicial.</p></div>
            </div>
        </div>
    </section>

    <!-- Como funciona -->
    <section id="como" class="inicio-secao">
        <div class="inicio-largura">
            <div class="inicio-titulo revelar">
                <span class="inicio-sobretitulo">Como funciona · três passos no dia a dia da loja</span>
                <h2>Anotou. Controlou. Recebeu.</h2>
            </div>
            <ol class="passos-inicio">
                <li class="revelar"><span>1</span><h3>Cadastre o cliente</h3><p>Nome, telefone e o limite de crédito que você confia a ele.</p></li>
                <li class="revelar"><span>2</span><h3>Anote compras e pagamentos</h3><p>No balcão, pelo celular, em poucos segundos.</p></li>
                <li class="revelar"><span>3</span><h3>Acompanhe quem deve</h3><p>Painel, relatório e ranking mostram quem cobrar e em quem confiar.</p></li>
            </ol>
        </div>
    </section>

    <!-- Quem fez -->
    <section id="equipe" class="inicio-secao inicio-secao-alt">
        <div class="inicio-largura">
            <div class="inicio-titulo revelar">
                <span class="inicio-sobretitulo">Quem fez</span>
                <h2>Quem está por trás do FiadoFácil</h2>
                <p>Sou aluno do curso de Gestão da Tecnologia da Informação da FPM, em Goiânia/GO. O FiadoFácil é o meu Projeto Integrador: um sistema simples e gratuito para ajudar o pequeno comércio a trocar o caderninho por um controle seguro.</p>
            </div>
            <div class="perfil-autor revelar">
                <div class="perfil-topo">
                    <img src="img/matheus.jpg" class="foto-autor" alt="Foto de Matheus Wilton" width="120" height="120">
                    <div>
                        <h3>Matheus Wilton</h3>
                        <p class="perfil-cargo">Idealizador e desenvolvedor do FiadoFácil</p>
                        <p class="perfil-local">Gestão da Tecnologia da Informação · FPM · Goiânia/GO</p>
                    </div>
                </div>
                <p class="perfil-texto">Fui responsável por todo o projeto, do papel ao site no ar: entendi como o fiado funciona no comércio de bairro, transformei isso em regras de negócio, modelei o banco de dados, programei o sistema e cuidei da segurança e da publicação.</p>
                <p class="perfil-frase">“Criei o FiadoFácil para que o comerciante de bairro tenha o mesmo controle que uma grande loja, sem perder a confiança no cliente.”</p>
                <button type="button" class="botao-detalhes" data-abrir="detalhes-tecnicos"><?php echo icone('relatorio'); ?><span>Ver detalhes técnicos</span><small>Para quem é da área</small></button>
            </div>
            <dialog id="detalhes-tecnicos" class="janela-tecnica" aria-labelledby="t-detalhes">
                <div class="janela-conteudo">
                    <button type="button" class="janela-fechar" data-fechar aria-label="Fechar">&times;</button>
                    <span class="inicio-sobretitulo">Detalhes técnicos</span>
                    <h3 id="t-detalhes">Por dentro do FiadoFácil</h3>
                    <p class="janela-intro">Como o sistema foi construído, para quem é da área de tecnologia.</p>
                    <ul class="perfil-lista">
                        <li><?php echo icone('relatorio'); ?><span><strong>Regras de negócio</strong>Limite de crédito por cliente, situação automática (em dia, devendo, inadimplente), pagamentos que quitam as compras mais antigas e score de confiabilidade.</span></li>
                        <li><?php echo icone('resumo'); ?><span><strong>Banco de dados</strong>Modelagem MySQL com 4 tabelas relacionadas, dívida sempre calculada e transações com trava para nenhuma compra estourar o limite.</span></li>
                        <li><?php echo icone('celular'); ?><span><strong>Desenvolvimento</strong>PHP, HTML, CSS e JavaScript; layout para computador e celular, modo noturno e página de apresentação.</span></li>
                        <li><?php echo icone('escudo'); ?><span><strong>Segurança e publicação</strong>Senhas com hash, proteção contra SQL Injection e CSRF, perfis de acesso e publicação automática do GitHub para a InfinityFree.</span></li>
                    </ul>
                    <div class="perfil-tags"><span>PHP</span><span>MySQL</span><span>JavaScript</span><span>HTML e CSS</span><span>Git e GitHub</span><span>UML</span></div>
                </div>
            </dialog>
            </div>
        </div>
    </section>

    <!-- Chamada final -->
    <section class="inicio-chamada">
        <div class="inicio-largura revelar">
            <h2>Fiado sem dor de cabeça começa agora.</h2>
            <p>Adeus, caderninho. Entre com seu usuário ou peça acesso como cliente.</p>
            <div class="inicio-botoes">
                <a href="login.php" class="botao botao-grande botao-claro">Entrar no sistema <?php echo icone('seta'); ?></a>
                <a href="conta_entrar.php" class="botao botao-grande botao-contorno">Sou cliente</a>
            </div>
        </div>
    </section>
</main>

<footer class="inicio-rodape">
    <div class="inicio-largura">
        <span class="rodape-marca"><?php echo logo(32); ?><strong>FiadoFácil</strong></span> — Sistema de controle de fiado · Projeto Integrador GTI / FPM · Goiânia/GO · <a href="privacidade.php">Política de Privacidade</a>
    </div>
</footer>

<script src="js/script.js?v=24"></script>
<script src="js/vendor/gsap.min.js?v=3.12.5"></script>
<script src="js/vendor/ScrollTrigger.min.js?v=3.12.5"></script>
<script src="js/cinema.js?v=1"></script>
<script>
// Os blocos da pagina aparecem suavemente quando entram na tela
(function () {
    var blocos = document.querySelectorAll('.revelar');
    if (!('IntersectionObserver' in window) || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
        blocos.forEach(function (b) { b.classList.add('visivel'); });
        return;
    }
    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (entrada.isIntersecting) { entrada.target.classList.add('visivel'); observador.unobserve(entrada.target); }
        });
    }, { threshold: 0.12 });
    blocos.forEach(function (b, i) { b.style.setProperty('--atraso', (i % 3) * 90 + 'ms'); observador.observe(b); });
    // topo ganha sombra ao rolar e a barrinha do topo mostra quanto da pagina ja foi lido
    var topo = document.querySelector('.inicio-topo');
    var progresso = document.querySelector('.progresso-rolagem');
    function aoRolar() {
        topo.classList.toggle('rolou', window.scrollY > 10);
        var total = document.documentElement.scrollHeight - window.innerHeight;
        progresso.style.transform = 'scaleX(' + (total > 0 ? window.scrollY / total : 0) + ')';
    }
    window.addEventListener('scroll', aoRolar, { passive: true });
    aoRolar();

    // numeros contam do zero quando aparecem na tela
    function contar(el) {
        var alvo = parseFloat(el.getAttribute('data-valor'));
        var casas = parseInt(el.getAttribute('data-casas') || '0', 10);
        var sufixo = el.getAttribute('data-sufixo') || '';
        var dinheiro = el.hasAttribute('data-dinheiro');
        var final = el.textContent, inicio = null;
        function formatar(v) {
            var n = v.toLocaleString('pt-BR', { minimumFractionDigits: dinheiro ? 2 : casas, maximumFractionDigits: dinheiro ? 2 : casas });
            return (dinheiro ? 'R$ ' : '') + n + sufixo;
        }
        function passo(agora) {
            if (inicio === null) { inicio = agora; }
            var t = Math.min(1, (agora - inicio) / 1400);
            el.textContent = formatar(alvo * (1 - Math.pow(1 - t, 3)));
            if (t < 1) { requestAnimationFrame(passo); } else { el.textContent = final; }
        }
        requestAnimationFrame(passo);
    }
    var observaNumeros = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (e) { if (e.isIntersecting) { contar(e.target); observaNumeros.unobserve(e.target); } });
    }, { threshold: 0.6 });
    document.querySelectorAll('.conta').forEach(function (el) { observaNumeros.observe(el); });

    // a "tela" da abertura se inclina acompanhando o mouse
    var mock = document.querySelector('.inicio-mock');
    if (mock && window.matchMedia('(hover: hover)').matches) {
        mock.addEventListener('mousemove', function (e) {
            var caixa = mock.getBoundingClientRect();
            var x = (e.clientX - caixa.left) / caixa.width - 0.5;
            var y = (e.clientY - caixa.top) / caixa.height - 0.5;
            mock.style.transform = 'perspective(900px) rotateY(' + (x * 10) + 'deg) rotateX(' + (-y * 10) + 'deg) translateY(-4px)';
        });
        mock.addEventListener('mouseleave', function () { mock.style.transform = ''; });
    }
})();
</script>
</body>
</html>
