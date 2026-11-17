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
    try { if (localStorage.getItem('menu') === 'recolhido') { document.documentElement.setAttribute('data-menu', 'recolhido'); } } catch (e) {}
})();
</script>
<meta name="theme-color" content="#1e3a8a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="FiadoFácil">
<link rel="manifest" href="manifest.json">
<link rel="icon" type="image/png" href="img/icone-192.png">
<link rel="apple-touch-icon" href="img/icone-180.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="css/estilo.css?v=10">
</head>
<body<?php if ($esta_logado) echo ' class="com-menu"'; ?>>
<?php if ($esta_logado): ?>
<header class="topo">
    <div class="topo-conteudo">
        <div class="marca-linha">
            <a class="marca" href="<?php echo $nivel_logado === 'cliente' ? 'minha_divida.php' : 'dashboard.php'; ?>"><span class="marca-curta">F$</span><span class="marca-texto">FiadoFácil</span></a>
            <button type="button" class="recolher-botao" aria-label="Recolher ou expandir o menu" title="Recolher menu"><?php echo icone('recolher'); ?></button>
        </div>
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
    <a href="index.php" class="marca-login-link" title="Voltar à página inicial">
        <img src="img/icone-192.png" alt="" width="64" height="64">
        <div class="marca-grande">FiadoFácil</div>
    </a>
    <p class="marca-frase">Controle do fiado sem caderninho</p>
    <a href="index.php" class="voltar-inicio">&larr; Conheça o FiadoFácil</a>
</div>
<?php endif; ?>
<main class="container <?php echo $classe_container; ?>">
