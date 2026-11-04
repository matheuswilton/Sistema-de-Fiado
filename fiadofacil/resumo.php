<?php
// Resumo financeiro (somente administrador)
require_once "verifica_admin.php";
require_once "conexao.php";
$lista = buscar_clientes_situacao($conn);
$total_clientes = count($lista);
$quantidade_devendo = 0;
$quantidade_inadimplentes = 0;
$total_a_receber = 0;
$maiores_devedores = [];
foreach ($lista as $linha) {
    if ($linha['saldo_devedor'] > 0) {
        $quantidade_devendo++;
        $total_a_receber += $linha['saldo_devedor'];
        if (count($maiores_devedores) < 5) { $maiores_devedores[] = $linha; }
    }
    if ($linha['situacao'] === 'inadimplente') { $quantidade_inadimplentes++; }
}
$media_por_devedor = $quantidade_devendo > 0 ? $total_a_receber / $quantidade_devendo : 0;
$dados_compras = $conn->query("SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor), 0) AS total, COALESCE(AVG(valor), 0) AS media FROM compras")->fetch_assoc();
$dados_pagamentos = $conn->query("SELECT COALESCE(SUM(valor), 0) AS total FROM pagamentos")->fetch_assoc();
$titulo_pagina = "Resumo financeiro";
$menu_ativo = "resumo";
require "cabecalho.php";
?>
<h1>Resumo financeiro</h1>
<p class="subtitulo">Visão geral do fiado de toda a loja.</p>
<div class="cards">
    <div class="card"><span class="card-titulo">Clientes cadastrados</span><span class="card-valor"><?php echo $total_clientes; ?></span></div>
    <div class="card card-aviso"><span class="card-titulo">Clientes devendo</span><span class="card-valor"><?php echo $quantidade_devendo; ?></span></div>
    <div class="card card-alerta"><span class="card-titulo">Inadimplentes (+30 dias)</span><span class="card-valor"><?php echo $quantidade_inadimplentes; ?></span></div>
    <div class="card card-alerta"><span class="card-titulo">Total a receber</span><span class="card-valor"><?php echo dinheiro($total_a_receber); ?></span></div>
    <div class="card card-ok"><span class="card-titulo">Total já recebido</span><span class="card-valor"><?php echo dinheiro($dados_pagamentos['total']); ?></span></div>
    <div class="card"><span class="card-titulo">Total vendido no fiado</span><span class="card-valor"><?php echo dinheiro($dados_compras['total']); ?></span></div>
    <div class="card"><span class="card-titulo">Ticket médio por compra</span><span class="card-valor"><?php echo dinheiro($dados_compras['media']); ?></span></div>
    <div class="card"><span class="card-titulo">Dívida média por devedor</span><span class="card-valor"><?php echo dinheiro($media_por_devedor); ?></span></div>
</div>
<h2>Maiores devedores</h2>
<div class="tabela-rolagem"><table>
    <tr><th>Cliente</th><th class="num">Saldo devedor</th><th class="num">Dias em aberto</th></tr>
    <?php foreach ($maiores_devedores as $linha): ?>
    <tr><td><a href="cliente.php?id=<?php echo $linha['id']; ?>"><?php echo htmlspecialchars($linha['nome']); ?></a></td><td class="num"><?php echo dinheiro($linha['saldo_devedor']); ?></td><td class="num"><?php echo $linha['dias_devendo']; ?></td></tr>
    <?php endforeach; ?>
    <?php if (count($maiores_devedores) === 0): ?><tr><td colspan="3" class="vazio">Nenhum cliente devendo. 🎉</td></tr><?php endif; ?>
</table></div>
<div class="barra-botoes"><a href="relatorio.php" class="botao">Ver relatório detalhado</a></div>
<?php require "rodape.php"; ?>
