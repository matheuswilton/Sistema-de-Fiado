<?php
// Cabecalho comum das paginas. Antes de incluir, a pagina define: $titulo_pagina e $menu_ativo (opcional) e $classe_container (opcional)
if (!isset($titulo_pagina)) { $titulo_pagina = "Sistema"; }
if (!isset($menu_ativo)) { $menu_ativo = ""; }
if (!isset($classe_container)) { $classe_container = ""; }
$esta_logado = isset($_SESSION['usuario_id']);
$nivel_logado = $esta_logado ? $_SESSION['usuario_nivel'] : "";
$total_pendentes_menu = 0;
$total_cobrancas_menu = 0;
// clientes para cobrar hoje: vencem em ate 3 dias ou ja estao em atraso
if ($esta_logado && in_array($nivel_logado, ['admin', 'comum'], true) && isset($conn) && loja_id() > 0) {
    foreach (buscar_clientes_situacao($conn) as $c_menu) {
        $faltam_menu = dias_para_vencer($c_menu['saldo_devedor'], $c_menu['dias_devendo']);
        if ($faltam_menu !== null && $faltam_menu <= DIAS_AVISO_ANTES) { $total_cobrancas_menu++; }
    }
}
if ($esta_logado && $nivel_logado === 'admin' && isset($conn)) {
    $total_pendentes_menu = total_pedidos_pendentes($conn, loja_id());
}
// Loja mostrada nesta tela: a do usuario logado ou a do endereco de login
if (!isset($loja_atual) && $esta_logado && isset($conn) && loja_id() > 0) { $loja_atual = carregar_loja($conn, loja_id()); }
$loja_tela = $loja_atual ?? ($loja_publica ?? null);
$temas_lojas = ['mercearia' => '#14532d', 'padaria' => '#78350f', 'acougue' => '#4c0519'];
$tema_loja = ($loja_tela && isset($temas_lojas[$loja_tela['tema']])) ? $loja_tela['tema'] : '';
$loja_tem_nome_proprio = $loja_tela && $loja_tela['slug'] !== 'principal';
// Letreiro da tela de login: o nome da loja (ou FiadoFacil), com a ultima palavra em destaque
if ($loja_tem_nome_proprio) {
    $palavras = preg_split('/\s+/u', trim($loja_tela['nome']));
    $parte_destaque = array_pop($palavras);
    $parte_inicio = $palavras ? implode(' ', $palavras) . ' ' : '';
} else {
    $parte_inicio = 'Fiado';
    $parte_destaque = 'Fácil';
}
function letras_letreiro($texto, $inicio) {
    $html = '';
    foreach (preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY) as $i => $letra) {
        $html .= '<span class="letra" style="--i:' . ($inicio + $i) . '">' . ($letra === ' ' ? '&nbsp;' : htmlspecialchars($letra)) . '</span>';
    }
    return $html;
}
function link_menu($arquivo, $texto, $chave, $menu_ativo, $extra = "") {
    $classe = ($chave === $menu_ativo) ? ' class="ativo"' : '';
    echo '<a href="' . $arquivo . '"' . $classe . '><span class="menu-texto">' . $texto . '</span>' . $extra . '</a>';
}
function botao_tema($classe_extra = "") {
    echo '<button type="button" class="tema-botao ' . $classe_extra . '" aria-label="Alternar modo noturno" title="Modo noturno">'
        . icone('lua', 'icone-lua') . icone('sol', 'icone-sol') . '<span class="tema-texto tema-texto-claro">Modo noturno</span><span class="tema-texto tema-texto-escuro">Modo claro</span></button>';
}
$nomes_perfil = ['admin' => 'Administrador', 'comum' => 'Usuário comum', 'cliente' => 'Cliente'];
// Menu enxuto: varias paginas ficam dentro de um item so, e a pagina mostra abas para trocar entre elas
$grupos_menu = [
    'relatorios' => ['relatorio', 'ranking', 'resumo'],
    'equipe' => ['loja', 'usuarios', 'aprovar', 'acessos'],
    'conta' => ['senha'],
];
$grupo_ativo = $menu_ativo;
foreach ($grupos_menu as $grupo => $paginas_grupo) {
    if (in_array($menu_ativo, $paginas_grupo, true)) { $grupo_ativo = $grupo; }
}
$abas_grupo = [];
if ($esta_logado && $grupo_ativo === 'relatorios') {
    $abas_grupo = [['relatorio.php', 'Relatório', 'relatorio'], ['ranking.php', 'Ranking', 'ranking']];
    if ($nivel_logado === 'admin') { $abas_grupo[] = ['resumo.php', 'Resumo', 'resumo']; }
} elseif ($esta_logado && $grupo_ativo === 'equipe' && $nivel_logado === 'admin') {
    $abas_grupo = [['minha_loja.php', 'Minha loja', 'loja'], ['usuarios.php', 'Usuários', 'usuarios'],
        ['aprovar_acessos.php', 'Aprovar acessos', 'aprovar', $total_pendentes_menu], ['acessos_clientes.php', 'Acessos de clientes', 'acessos']];
}
?>
<!DOCTYPE html>
<html lang="pt-BR"<?php if ($tema_loja) echo ' data-loja="' . $tema_loja . '"'; ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($titulo_pagina); ?> — FiadoFácil</title>
<?php if (!$esta_logado): ?>
<meta name="description" content="<?php echo htmlspecialchars($descricao_pagina ?? 'FiadoFácil: controle de compras no fiado, pagamentos e dívidas de clientes, no computador e no celular.'); ?>">
<?php metas_compartilhamento($titulo_pagina . " — FiadoFácil", $descricao_pagina ?? "Controle do fiado sem caderninho."); ?>
<?php else: ?>
<meta name="robots" content="noindex">
<?php endif; ?>
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
<meta name="theme-color" content="<?php echo $tema_loja ? $temas_lojas[$tema_loja] : '#1e3a8a'; ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="FiadoFácil">
<link rel="manifest" href="manifest.json">
<link rel="icon" type="image/svg+xml" href="img/logo.svg?v=2">
<link rel="alternate icon" type="image/png" href="img/icone-192.png?v=2">
<link rel="apple-touch-icon" href="img/icone-180.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800<?php if (!$esta_logado) echo '&family=Baloo+2:wght@800'; ?><?php if (!$esta_logado) echo '&family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800'; ?>&display=swap">
<?php if (eh_ambiente_teste()): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="stylesheet" href="css/estilo.css?v=36">
</head>
<body<?php if ($esta_logado) echo ' class="com-menu"'; ?>>
<?php if ($esta_logado): ?>
<header class="topo">
    <div class="topo-conteudo">
        <a class="menu-loja" href="<?php echo $nivel_logado === 'cliente' ? 'minha_divida.php' : 'dashboard.php'; ?>">
            <?php if ($loja_tem_nome_proprio): ?>
            <span class="menu-loja-letra" aria-hidden="true"><?php echo htmlspecialchars(mb_strtoupper(mb_substr(trim($loja_tela['nome']), 0, 1))); ?></span>
            <span class="menu-loja-texto"><strong><?php echo htmlspecialchars($loja_tela['nome']); ?></strong><small>FiadoFácil</small></span>
            <?php else: ?>
            <?php echo logo(32, "marca-logo"); ?><span class="menu-loja-texto"><strong>FiadoFácil</strong><small>Controle do fiado</small></span>
            <?php endif; ?>
        </a>
        <button type="button" class="menu-botao" aria-label="Abrir ou fechar o menu">&#9776; Menu</button>
        <nav class="menu" aria-label="Menu principal">
            <div class="menu-lista">
            <?php if ($nivel_logado === 'cliente'): ?>
                <?php link_menu("minha_divida.php", "Minhas dívidas", "divida", $grupo_ativo); ?>
                <?php link_menu("minha_senha.php", "Minha conta", "conta", $grupo_ativo); ?>
            <?php else: ?>
                <details class="nova-venda">
                    <summary><?php echo icone('mais'); ?> Nova venda</summary>
                    <div class="nova-venda-opcoes">
                        <a href="registrar_compra.php"><strong>Registrar compra</strong><small>O cliente levou no fiado</small></a>
                        <a href="registrar_pagamento.php"><strong>Registrar pagamento</strong><small>O cliente pagou</small></a>
                    </div>
                </details>
                <?php link_menu("dashboard.php", "Início", "inicio", $grupo_ativo); ?>
                <?php link_menu("listar_clientes.php", "Clientes", "clientes", $grupo_ativo); ?>
                <?php link_menu("cobrancas.php", "Cobranças", "cobrancas", $grupo_ativo, $total_cobrancas_menu > 0 ? ' <span class="contador">' . $total_cobrancas_menu . '</span>' : ''); ?>
                <?php link_menu("relatorio.php", "Relatórios", "relatorios", $grupo_ativo); ?>
                <span class="menu-divisor" aria-hidden="true"></span>
                <?php if ($nivel_logado === 'admin'): ?>
                    <?php link_menu("minha_loja.php", "Loja e equipe", "equipe", $grupo_ativo, $total_pendentes_menu > 0 ? ' <span class="contador" title="Acessos esperando aprovação">' . $total_pendentes_menu . '</span>' : ''); ?>
                <?php endif; ?>
                <?php link_menu("minha_senha.php", "Minha conta", "conta", $grupo_ativo); ?>
            <?php endif; ?>
            </div>
            <div class="menu-rodape">
                <span class="menu-rodape-nome" title="<?php echo htmlspecialchars($nomes_perfil[$nivel_logado] ?? ''); ?>"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                <span aria-hidden="true">·</span>
                <a href="logout.php" class="menu-sair" data-confirmar="Deseja sair do sistema?">Sair</a>
                <?php botao_tema("tema-menu"); ?>
            </div>
        </nav>
    </div>
</header>
<?php else: ?>
<?php botao_tema("tema-flutuante"); ?>
<?php $foto_login = $loja_tela ? foto_da_loja($loja_tela['id']) : null; ?>
<div class="marca-login<?php echo $foto_login ? ' com-foto' : ''; ?>"<?php if ($foto_login) echo ' style="--foto-loja: url(\'' . htmlspecialchars($foto_login, ENT_QUOTES) . '\')"'; ?>>
    <div class="login-topo">
        <a href="index.php" class="marca-login-link" title="Voltar à página inicial"><?php echo logo(46, "logo-login"); ?><span>Conheça o FiadoFácil</span></a>
    </div>
    <div class="marca-login-conteudo">
        <div class="letreiro-palco">
            <div class="letreiro" role="img" aria-label="<?php echo htmlspecialchars($parte_inicio . $parte_destaque); ?>">
                <span class="letreiro-parte letreiro-fiado" aria-hidden="true"><?php echo letras_letreiro($parte_inicio, 0); ?></span><span class="letreiro-parte letreiro-facil" aria-hidden="true"><?php echo letras_letreiro($parte_destaque, mb_strlen($parte_inicio)); ?></span>
            </div>
        </div>
        <?php if ($loja_tem_nome_proprio): ?><p class="loja-por">Fiado organizado com <strong>FiadoFácil</strong></p><?php endif; ?>
        <p class="marca-frase" aria-live="polite"><span class="frase-atual">Controle de fiado</span></p>
    </div>
</div>
<?php endif; ?>
<?php if ($esta_logado && eh_plataforma() && $loja_tela): ?>
<div class="faixa-plataforma" role="status">Você está na loja <strong><?php echo htmlspecialchars($loja_tela['nome']); ?></strong> como dono da plataforma. <a href="painel.php?sair_da_loja=1">Voltar ao painel</a></div>
<?php endif; ?>
<?php echo faixa_teste(); ?>
<main class="container <?php echo $classe_container; ?>">
<?php if ($abas_grupo): ?>
<nav class="abas-secao nao-imprimir" aria-label="<?php echo $grupo_ativo === 'relatorios' ? 'Relatórios' : 'Loja e equipe'; ?>">
    <?php foreach ($abas_grupo as $aba): ?>
    <a href="<?php echo $aba[0]; ?>"<?php if ($aba[2] === $menu_ativo) echo ' class="ativo" aria-current="page"'; ?>><?php echo $aba[1]; ?><?php if (!empty($aba[3])) echo ' <span class="contador">' . (int)$aba[3] . '</span>'; ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
