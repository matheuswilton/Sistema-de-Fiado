<?php
// Cabecalho comum das paginas. Antes de incluir, a pagina define: $titulo_pagina e $menu_ativo (opcional) e $classe_container (opcional)
if (!isset($titulo_pagina)) { $titulo_pagina = "Sistema"; }
if (!isset($menu_ativo)) { $menu_ativo = ""; }
if (!isset($classe_container)) { $classe_container = ""; }
$esta_logado = isset($_SESSION['usuario_id']);
$nivel_logado = $esta_logado ? $_SESSION['usuario_nivel'] : "";
$total_pendentes_menu = 0;
if ($esta_logado && $nivel_logado === 'admin' && isset($conn)) {
    $resultado_pendentes = $conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'cliente' AND status = 'pendente'");
    if ($resultado_pendentes) { $total_pendentes_menu = (int)$resultado_pendentes->fetch_row()[0]; }
}
// Icones do menu: desenhos simples em uma cor so (seguem a cor do texto)
function icone($nome, $classe = "") {
    $desenhos = [
        'inicio'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'clientes'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'compra'     => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 2h2l2.7 12.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L22 7H5.1"/>',
        'pagamento'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
        'relatorio'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
        'ranking'    => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'resumo'     => '<path d="M21.2 15.9A10 10 0 1 1 8 2.8"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
        'usuarios'   => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M19 8v6"/><path d="M22 11h-6"/>',
        'aprovar'    => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="m16 11 2 2 4-4"/>',
        'acessos'    => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'senha'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'divida'     => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'sair'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'lua'        => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'sol'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M6.3 17.7l-1.4 1.4M19.1 4.9l-1.4 1.4"/>',
        'mais'       => '<path d="M12 5v14M5 12h14"/>',
    ];
    return '<svg class="icone ' . $classe . '" viewBox="0 0 24 24" aria-hidden="true">' . ($desenhos[$nome] ?? '') . '</svg>';
}
function link_menu($arquivo, $texto, $chave, $menu_ativo, $extra = "") {
    $classe = ($chave === $menu_ativo) ? ' class="ativo"' : '';
    echo '<a href="' . $arquivo . '"' . $classe . '>' . icone($chave) . '<span>' . $texto . '</span>' . $extra . '</a>';
}
function botao_tema($classe_extra = "") {
    echo '<button type="button" class="tema-botao ' . $classe_extra . '" aria-label="Alternar modo noturno" title="Modo noturno">'
        . icone('lua', 'icone-lua') . icone('sol', 'icone-sol') . '<span class="tema-texto tema-texto-claro">Modo noturno</span><span class="tema-texto tema-texto-escuro">Modo claro</span></button>';
}
$nomes_perfil = ['admin' => 'Administrador', 'comum' => 'Usuário comum', 'cliente' => 'Cliente'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($titulo_pagina); ?> — FiadoFácil</title>
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
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="FiadoFácil">
<link rel="manifest" href="manifest.json">
<link rel="icon" type="image/png" href="img/icone-192.png">
<link rel="apple-touch-icon" href="img/icone-180.png">
<link rel="stylesheet" href="css/estilo.css?v=4">
</head>
<body<?php if ($esta_logado) echo ' class="com-menu"'; ?>>
<?php if ($esta_logado): ?>
<header class="topo">
    <div class="topo-conteudo">
        <a class="marca" href="<?php echo $nivel_logado === 'cliente' ? 'minha_divida.php' : 'dashboard.php'; ?>">FiadoFácil</a>
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
                <?php if ($nivel_logado === 'admin'): ?>
                    <span class="menu-separador"></span>
                    <span class="menu-grupo">Administração</span>
                    <?php link_menu("resumo.php", "Resumo", "resumo", $menu_ativo); ?>
                    <?php link_menu("usuarios.php", "Usuários", "usuarios", $menu_ativo); ?>
                    <?php link_menu("aprovar_acessos.php", "Aprovar acessos", "aprovar", $menu_ativo, $total_pendentes_menu > 0 ? ' <span class="contador">' . $total_pendentes_menu . '</span>' : ''); ?>
                    <?php link_menu("acessos_clientes.php", "Acessos de clientes", "acessos", $menu_ativo); ?>
                <?php endif; ?>
                <span class="menu-separador"></span>
                <span class="menu-grupo">Conta</span>
                <?php link_menu("minha_senha.php", "Minha senha", "senha", $menu_ativo); ?>
            <?php endif; ?>
            <div class="menu-usuario">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                <span><?php echo $nomes_perfil[$nivel_logado] ?? ''; ?></span>
            </div>
            <a href="logout.php" class="menu-sair" data-confirmar="Deseja sair do sistema?"><?php echo icone('sair'); ?><span>Sair<span class="menu-sair-nome"> (<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>)</span></span></a>
        </nav>
        <div class="topo-botoes">
            <?php botao_tema(); ?>
            <button type="button" class="menu-botao" aria-label="Abrir ou fechar o menu">&#9776; Menu</button>
        </div>
    </div>
</header>
<?php else: ?>
<?php botao_tema("tema-flutuante"); ?>
<?php endif; ?>
<main class="container <?php echo $classe_container; ?>">
<?php if (!$esta_logado): ?>
    <div class="marca-grande">FiadoFácil</div>
<?php endif; ?>
