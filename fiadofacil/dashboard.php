<?php
require_once "verifica_login.php";
require_once "conexao.php";
// aviso se o usuario ainda esta com a senha de exemplo
$senha_de_exemplo = false;
$stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$linha_senha = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($linha_senha && (password_verify('admin123', $linha_senha['senha']) || password_verify('user123', $linha_senha['senha']))) {
    $senha_de_exemplo = true;
}
$lista_clientes = buscar_clientes_situacao($conn);
$quantidade_clientes = count($lista_clientes);
$quantidade_devendo = 0;
$quantidade_inadimplentes = 0;
$total_a_receber = 0;
foreach ($lista_clientes as $linha_cliente) {
    if ($linha_cliente['saldo_devedor'] > 0) {
        $quantidade_devendo++;
        $total_a_receber += $linha_cliente['saldo_devedor'];
    }
    if ($linha_cliente['situacao'] === 'inadimplente') { $quantidade_inadimplentes++; }
}
$total_pendentes = 0;
if (eh_admin()) {
    $total_pendentes = (int)$conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'cliente' AND status = 'pendente'")->fetch_row()[0];
}
$titulo_pagina = "Início";
$menu_ativo = "inicio";
require "cabecalho.php";
?>
<h1>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
<p class="subtitulo">Perfil: <strong><?php echo eh_admin() ? "Administrador" : "Usuário comum"; ?></strong>. Veja o resumo do fiado e o que fazer agora.</p>
<?php mostrar_flash(); ?>
<?php if ($senha_de_exemplo): ?>
<div class="aviso-caixa">Você ainda está usando a senha de exemplo do sistema. Por segurança, <a href="minha_senha.php">troque a sua senha agora</a>.</div>
<?php endif; ?>
<?php if ($total_pendentes > 0): ?>
<div class="aviso-caixa"><strong><?php echo $total_pendentes; ?></strong> solicitação(ões) de acesso de cliente aguardando você. <a href="aprovar_acessos.php">Ver solicitações</a></div>
<?php endif; ?>
<div class="cards">
    <div class="card card-com-icone"><span class="card-icone"><?php echo icone('clientes'); ?></span><div><span class="card-titulo">Clientes cadastrados</span><span class="card-valor"><?php echo $quantidade_clientes; ?></span></div></div>
    <div class="card card-aviso card-com-icone"><span class="card-icone"><?php echo icone('relogio'); ?></span><div><span class="card-titulo">Clientes devendo</span><span class="card-valor"><?php echo $quantidade_devendo; ?></span></div></div>
    <div class="card card-alerta card-com-icone"><span class="card-icone"><?php echo icone('atencao'); ?></span><div><span class="card-titulo">Inadimplentes (+30 dias)</span><span class="card-valor"><?php echo $quantidade_inadimplentes; ?></span></div></div>
    <?php if (eh_admin()): ?>
    <div class="card card-ok card-com-icone"><span class="card-icone"><?php echo icone('pagamento'); ?></span><div><span class="card-titulo">Total a receber</span><span class="card-valor"><?php echo dinheiro($total_a_receber); ?></span></div></div>
    <?php endif; ?>
</div>
<div class="acoes-rapidas">
    <a class="botao botao-compra" href="registrar_compra.php"><?php echo icone('compra'); ?>Registrar compra</a>
    <a class="botao botao-verde" href="registrar_pagamento.php"><?php echo icone('pagamento'); ?>Registrar pagamento</a>
    <a class="botao botao-secundario" href="cadastro_cliente.php"><?php echo icone('mais'); ?>Cadastrar cliente</a>
</div>
<h2 class="atalhos-titulo">Atalhos</h2>
<div class="atalhos">
    <a class="atalho" href="registrar_compra.php"><strong>Registrar compra (fiado)</strong><span>Anotar uma compra no fiado de um cliente.</span></a>
    <a class="atalho" href="registrar_pagamento.php"><strong>Registrar pagamento</strong><span>Receber um pagamento e abater da dívida.</span></a>
    <a class="atalho" href="listar_clientes.php"><strong>Clientes</strong><span>Buscar, ver extrato, editar ou excluir clientes.</span></a>
    <a class="atalho" href="cadastro_cliente.php"><strong>Cadastrar cliente</strong><span>Adicionar um novo cliente ao fiado.</span></a>
    <a class="atalho" href="relatorio.php"><strong>Relatório de dívidas</strong><span>Filtrar por período e situação. Dá para imprimir.</span></a>
    <a class="atalho" href="ranking.php"><strong>Ranking de confiabilidade</strong><span>Quais clientes pagam melhor.</span></a>
    <?php if (eh_admin()): ?>
    <a class="atalho" href="resumo.php"><strong>Resumo financeiro</strong><span>Totais, médias e maiores devedores.</span></a>
    <a class="atalho" href="usuarios.php"><strong>Usuários</strong><span>Criar funcionários e redefinir senhas.</span></a>
    <a class="atalho" href="aprovar_acessos.php"><strong>Aprovar acessos de clientes<?php if ($total_pendentes > 0) echo " (" . $total_pendentes . ")"; ?></strong><span>Liberar o cliente para ver a própria dívida.</span></a>
    <a class="atalho" href="acessos_clientes.php"><strong>Acessos de clientes</strong><span>Bloquear, desbloquear e trocar senha.</span></a>
    <?php endif; ?>
</div>
<?php require "rodape.php"; ?>
