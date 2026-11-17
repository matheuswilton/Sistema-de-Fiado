<?php
// Pagina de apresentacao do FiadoFacil (aparece antes do login).
// Se a pessoa ja estiver logada, vai direto para o sistema.
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['usuario_nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
    exit();
}
$equipe = [
    ["Matheus Wilton", "Responsável pelo site: regras de negócio, desenvolvimento e banco de dados"],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FiadoFácil — Controle do fiado sem caderninho</title>
<meta name="description" content="Sistema web gratuito para pequenos comércios controlarem compras no fiado, pagamentos e dívidas de clientes, no computador e no celular.">
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
<link rel="icon" type="image/png" href="img/icone-192.png">
<link rel="apple-touch-icon" href="img/icone-180.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="css/estilo.css?v=8">
<link rel="stylesheet" href="css/inicio.css?v=3">
</head>
<body class="pagina-inicio">

<header class="inicio-topo">
    <div class="inicio-largura inicio-topo-conteudo">
        <a href="index.php" class="inicio-marca"><img src="img/icone-192.png" alt="" width="34" height="34"><span>FiadoFácil</span></a>
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
        <div class="inicio-largura inicio-hero-grade">
            <div class="revelar">
                <span class="inicio-selo">Projeto Integrador · GTI · FPM</span>
                <h1>O fiado da sua loja, organizado e <span class="destaque-texto">sem caderninho</span>.</h1>
                <p class="inicio-lead">O FiadoFácil anota as compras a prazo, controla o limite de cada cliente e mostra quem está devendo — no computador e no celular, de graça.</p>
                <div class="inicio-botoes">
                    <a href="login.php" class="botao botao-grande">Entrar no sistema <?php echo icone('seta'); ?></a>
                    <a href="solicitar_acesso.php" class="botao botao-secundario botao-grande">Sou cliente e quero ver minha dívida</a>
                </div>
            </div>
            <!-- "Tela" ilustrativa feita so com HTML e CSS -->
            <div class="inicio-mock revelar" aria-hidden="true">
                <div class="mock-barra"><span></span><span></span><span></span></div>
                <div class="mock-corpo">
                    <div class="mock-cartoes">
                        <div class="mock-cartao"><small>Total a receber</small><strong>R$ 1.240,00</strong></div>
                        <div class="mock-cartao mock-alerta"><small>Inadimplentes</small><strong>2</strong></div>
                    </div>
                    <div class="mock-linha"><span class="avatar avatar-cor-0">JS</span><b>João Silva</b><i class="selo selo-aviso">Devendo</i></div>
                    <div class="mock-linha"><span class="avatar avatar-cor-1">AL</span><b>Ana Lima</b><i class="selo selo-ok">Em dia</i></div>
                    <div class="mock-linha"><span class="avatar avatar-cor-2">CP</span><b>Carlos Pereira</b><i class="selo selo-alerta">Inadimplente</i></div>
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
                    <strong>84,2 mi</strong>
                    <p>de brasileiros estavam inadimplentes em setembro de 2026 — mais da metade dos adultos.</p>
                    <small>Fonte: Serasa, Mapa da Inadimplência</small>
                </div>
                <div class="numero-cartao revelar">
                    <strong>4 em 10</strong>
                    <p>microempreendedores individuais vendem fiado, muitas vezes controlando tudo no papel.</p>
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
                <h2>Tudo o que o caderninho não faz</h2>
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
                <span class="inicio-sobretitulo">Como funciona</span>
                <h2>Três passos no dia a dia da loja</h2>
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
                <h2>Feito por um aluno de Gestão da TI</h2>
                <p>Sou aluno do curso de Gestão da Tecnologia da Informação da FPM, em Goiânia/GO. O FiadoFácil é o meu Projeto Integrador: um sistema simples e gratuito para ajudar o pequeno comércio a trocar o caderninho por um controle seguro.</p>
            </div>
            <div class="grade-inicio grade-equipe">
                <?php foreach ($equipe as $integrante): ?>
                <div class="equipe-cartao revelar">
                    <?php echo avatar($integrante[0], 'avatar-equipe'); ?>
                    <h3><?php echo htmlspecialchars($integrante[0]); ?></h3>
                    <p><?php echo htmlspecialchars($integrante[1]); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Chamada final -->
    <section class="inicio-chamada">
        <div class="inicio-largura revelar">
            <h2>Pronto para deixar o caderninho de lado?</h2>
            <p>Entre com seu usuário ou peça acesso como cliente.</p>
            <div class="inicio-botoes">
                <a href="login.php" class="botao botao-grande botao-claro">Entrar no sistema <?php echo icone('seta'); ?></a>
                <a href="solicitar_acesso.php" class="botao botao-grande botao-contorno">Sou cliente</a>
            </div>
        </div>
    </section>
</main>

<footer class="inicio-rodape">
    <div class="inicio-largura">
        <strong>FiadoFácil</strong> — Sistema de controle de fiado · Projeto Integrador GTI / FPM · Goiânia/GO
    </div>
</footer>

<script src="js/script.js?v=5"></script>
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
    // topo ganha sombra ao rolar
    var topo = document.querySelector('.inicio-topo');
    window.addEventListener('scroll', function () { topo.classList.toggle('rolou', window.scrollY > 10); }, { passive: true });
})();
</script>
</body>
</html>
