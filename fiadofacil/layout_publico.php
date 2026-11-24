<?php
// Molde das paginas publicas simples (Politica de Privacidade, pagina 404).
// Usa o mesmo visual da pagina de apresentacao: topo com o emblema, modo noturno e rodape.
require_once __DIR__ . "/funcoes.php";

function topo_publico($titulo, $descricao, $sem_indexar = false, $botao_topo = null) {
    $botao_topo = $botao_topo ?: ["texto" => "Entrar", "href" => "/login.php"];
    $titulo_html = htmlspecialchars($titulo);
    $descricao_html = htmlspecialchars($descricao);
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $titulo_html; ?> — FiadoFácil</title>
<meta name="description" content="<?php echo $descricao_html; ?>">
<?php if ($sem_indexar): ?><meta name="robots" content="noindex"><?php endif; ?>
<?php metas_compartilhamento($titulo . " — FiadoFácil", $descricao); ?>
<script>
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
<link rel="icon" type="image/svg+xml" href="<?php echo base_url(); ?>img/logo.svg?v=2">
<link rel="alternate icon" type="image/png" href="<?php echo base_url(); ?>img/icone-192.png?v=2">
<link rel="apple-touch-icon" href="<?php echo base_url(); ?>img/icone-180.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?php echo base_url(); ?>css/estilo.css?v=36">
<link rel="stylesheet" href="<?php echo base_url(); ?>css/inicio.css?v=21">
</head>
<body class="pagina-inicio">
<header class="inicio-topo">
    <div class="inicio-largura inicio-topo-conteudo">
        <a href="<?php echo base_url(); ?>index.php" class="inicio-marca"><?php echo logo(48); ?><span>FiadoFácil</span></a>
        <div class="inicio-topo-botoes">
            <button type="button" class="tema-botao inicio-tema" aria-label="Alternar modo noturno" title="Modo noturno"><?php echo icone('lua', 'icone-lua') . icone('sol', 'icone-sol'); ?></button>
            <a href="<?php echo htmlspecialchars($botao_topo['href']); ?>" class="botao"<?php if (!empty($botao_topo['confirmar'])) echo ' data-confirmar="' . htmlspecialchars($botao_topo['confirmar']) . '"'; ?>><?php echo htmlspecialchars($botao_topo['texto']); ?></a>
        </div>
    </div>
</header>
<main>
<?php
}

function rodape_publico() {
    ?>
</main>
<footer class="inicio-rodape">
    <div class="inicio-largura">
        <span class="rodape-marca"><?php echo logo(32); ?><strong>FiadoFácil</strong></span> — Sistema de controle de fiado · Projeto Integrador GTI / FPM · Goiânia/GO · <a href="<?php echo base_url(); ?>privacidade.php">Política de Privacidade</a>
    </div>
</footer>
<?php echo faixa_teste(); ?>
<script src="<?php echo base_url(); ?>js/script.js?v=26"></script>
</body>
</html>
<?php
}
