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
    echo '<a href="' . $arquivo . '"' . $classe . ' data-dica="' . htmlspecialchars(strip_tags($texto)) . '">' . icone($chave) . '<span class="menu-texto">' . $texto . '</span>' . $extra . '</a>';
}
function botao_tema($classe_extra = "") {
    echo '<button type="button" class="tema-botao ' . $classe_extra . '" aria-label="Alternar modo noturno" title="Modo noturno">'
        . icone('lua', 'icone-lua') . icone('sol', 'icone-sol') . '<span class="tema-texto tema-texto-claro">Modo noturno</span><span class="tema-texto tema-texto-escuro">Modo claro</span></button>';
}
$nomes_perfil = ['admin' => 'Administrador', 'comum' => 'Usuário comum', 'cliente' => 'Cliente'];
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
    try { if (localStorage.getItem('menu') === 'recolhido') { document.documentElement.setAttribute('data-menu', 'recolhido'); } } catch (e) {}
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700<?php if (!$esta_logado) echo '&family=Baloo+2:wght@800'; ?><?php if ($tema_loja === 'acougue') echo '&family=Caveat:wght@600;700'; ?>&display=swap">
<?php if (defined('AMBIENTE_TESTE') && AMBIENTE_TESTE): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="stylesheet" href="css/estilo.css?v=29">
<?php if ($tema_loja): ?><link rel="stylesheet" href="css/lojas.css?v=1"><?php endif; ?>
</head>
<body<?php if ($esta_logado) echo ' class="com-menu"'; ?>>
<?php if ($esta_logado): ?>
<header class="topo">
    <?php if ($tema_loja === 'mercearia'): ?><div class="toldo toldo-menu" aria-hidden="true"></div><?php endif; ?>
    <div class="topo-conteudo">
        <div class="marca-linha">
            <a class="marca" href="<?php echo $nivel_logado === 'cliente' ? 'minha_divida.php' : 'dashboard.php'; ?>"><?php echo logo(40, "marca-logo"); ?><span class="marca-texto">FiadoFácil</span></a>
            <?php botao_tema("tema-compacto"); ?>
            <button type="button" class="recolher-botao" aria-label="Recolher ou expandir o menu" title="Recolher menu"><?php echo icone('recolher'); ?></button>
        </div>
        <?php if ($loja_tem_nome_proprio): ?><div class="loja-nome" title="<?php echo htmlspecialchars($loja_tela['nome']); ?>"><?php echo desenho_loja($tema_loja) ?: icone('inicio'); ?><span><?php echo htmlspecialchars($loja_tela['nome']); ?></span></div><?php endif; ?>
        <nav class="menu" aria-label="Menu principal">
            <?php if ($nivel_logado === 'cliente'): ?>
                <?php link_menu("minha_divida.php", "Minha dívida", "divida", $menu_ativo); ?>
                <?php link_menu("minha_senha.php", "Minha senha", "senha", $menu_ativo); ?>
            <?php else: ?>
                <?php link_menu("dashboard.php", "Início", "inicio", $menu_ativo); ?>
                <?php link_menu("listar_clientes.php", "Clientes", "clientes", $menu_ativo); ?>
                <?php link_menu("registrar_compra.php", "Nova compra", "compra", $menu_ativo); ?>
                <?php link_menu("registrar_pagamento.php", "Pagamento", "pagamento", $menu_ativo); ?>
                <?php link_menu("relatorio.php", "Relatório", "relatorio", $menu_ativo); ?>
                <?php link_menu("ranking.php", "Ranking", "ranking", $menu_ativo); ?>
                <?php link_menu("cobrancas.php", "Cobranças", "cobrancas", $menu_ativo, $total_cobrancas_menu > 0 ? ' <span class="contador">' . $total_cobrancas_menu . '</span>' : ''); ?>
                <?php if ($nivel_logado === 'admin'): ?>
                    <span class="menu-separador"></span>
                    <span class="menu-grupo">Administração</span>
                    <?php link_menu("resumo.php", "Resumo", "resumo", $menu_ativo); ?>
                    <?php link_menu("usuarios.php", "Usuários", "usuarios", $menu_ativo); ?>
                    <?php link_menu("aprovar_acessos.php", "Aprovar acessos", "aprovar", $menu_ativo, $total_pendentes_menu > 0 ? ' <span class="contador">' . $total_pendentes_menu . '</span>' : ''); ?>
                    <?php link_menu("acessos_clientes.php", "Acessos de clientes", "acessos", $menu_ativo); ?>
                    <?php link_menu("minha_loja.php", "Minha loja", "loja", $menu_ativo); ?>
                <?php endif; ?>
                <span class="menu-separador"></span>
                <span class="menu-grupo">Conta</span>
                <?php link_menu("minha_senha.php", "Minha senha", "senha", $menu_ativo); ?>
            <?php endif; ?>
            <div class="menu-usuario">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                <span><?php echo $nomes_perfil[$nivel_logado] ?? ''; ?></span>
            </div>
            <a href="logout.php" class="menu-sair" data-dica="Sair" data-confirmar="Deseja sair do sistema?"><?php echo icone('sair'); ?><span class="menu-texto">Sair<span class="menu-sair-nome"> (<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>)</span></span></a>
        </nav>
        <div class="topo-botoes">
            <?php botao_tema(); ?>
            <button type="button" class="menu-botao" aria-label="Abrir ou fechar o menu">&#9776; Menu</button>
        </div>
    </div>
</header>
<?php else: ?>
<?php botao_tema("tema-flutuante"); ?>
<div class="marca-login">
    <?php echo cena_loja($tema_loja); ?>
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
        <p class="marca-frase" aria-live="polite"><span class="frase-atual" data-frases="Controle do fiado sem caderninho.|Quem anota, recebe.|Adeus, caderninho. Olá, controle.|Cada real anotado é um real recebido.|Fiado organizado, cliente fiel.|Seu caderninho agora cabe no bolso.">Controle do fiado sem caderninho.</span></p>
    </div>
</div>
<?php endif; ?>
<?php if ($esta_logado && eh_plataforma() && $loja_tela): ?>
<div class="faixa-plataforma" role="status">Você está na loja <strong><?php echo htmlspecialchars($loja_tela['nome']); ?></strong> como dono da plataforma. <a href="painel.php?sair_da_loja=1">Voltar ao painel</a></div>
<?php endif; ?>
<?php if (defined('AMBIENTE_TESTE') && AMBIENTE_TESTE): ?><div class="faixa-teste" role="note">🧪 <strong>Site de teste</strong>: os dados aqui são de exemplo e podem ser apagados. <a href="https://fiadofacil.free.je">Ir para o site oficial</a></div><?php endif; ?>
<?php if ($tema_loja): ?><template id="desenho-da-loja"><?php echo desenho_loja($tema_loja); ?></template><?php endif; ?>
<main class="container <?php echo $classe_container; ?>">
