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
    echo '<a href="' . $arquivo . '"' . $classe . '>' . $texto . $extra . '</a>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($titulo_pagina); ?> — FiadoFácil</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php if ($esta_logado): ?>
<header class="topo">
    <div class="topo-conteudo">
        <a class="marca" href="dashboard.php">FiadoFácil</a>
        <button type="button" class="menu-botao" aria-label="Abrir ou fechar o menu">&#9776; Menu</button>
        <nav class="menu" aria-label="Menu principal">
            <?php if ($nivel_logado === 'cliente'): ?>
                <?php link_menu("minha_divida.php", "Minha dívida", "minha_divida", $menu_ativo); ?>
                <?php link_menu("minha_senha.php", "Minha senha", "minha_senha", $menu_ativo); ?>
            <?php else: ?>
                <?php link_menu("dashboard.php", "Início", "inicio", $menu_ativo); ?>
                <?php link_menu("listar_clientes.php", "Clientes", "clientes", $menu_ativo); ?>
                <?php link_menu("registrar_compra.php", "Nova compra", "compra", $menu_ativo); ?>
                <?php link_menu("registrar_pagamento.php", "Pagamento", "pagamento", $menu_ativo); ?>
                <?php link_menu("relatorio.php", "Relatório", "relatorio", $menu_ativo); ?>
                <?php link_menu("ranking.php", "Ranking", "ranking", $menu_ativo); ?>
                <?php if ($nivel_logado === 'admin'): ?>
                    <span class="menu-separador"></span>
                    <?php link_menu("resumo.php", "Resumo", "resumo", $menu_ativo); ?>
                    <?php link_menu("usuarios.php", "Usuários", "usuarios", $menu_ativo); ?>
                    <?php link_menu("aprovar_acessos.php", "Aprovar acessos", "aprovar", $menu_ativo, $total_pendentes_menu > 0 ? ' <span class="contador">' . $total_pendentes_menu . '</span>' : ''); ?>
                    <?php link_menu("acessos_clientes.php", "Acessos de clientes", "acessos", $menu_ativo); ?>
                <?php endif; ?>
                <span class="menu-separador"></span>
                <?php link_menu("minha_senha.php", "Minha senha", "minha_senha", $menu_ativo); ?>
            <?php endif; ?>
            <a href="logout.php" class="menu-sair" data-confirmar="Deseja sair do sistema?">Sair (<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>)</a>
        </nav>
    </div>
</header>
<?php endif; ?>
<main class="container <?php echo $classe_container; ?>">
<?php if (!$esta_logado): ?>
    <div class="marca-grande">FiadoFácil</div>
<?php endif; ?>
