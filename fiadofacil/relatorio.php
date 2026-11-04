<?php
// Relatorio de dividas com filtros por periodo e situacao
require_once "verifica_login.php";
require_once "conexao.php";
$erro = "";
$data_inicio = isset($_GET['data_ini']) ? trim($_GET['data_ini']) : "";
$data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : "";
$situacao = isset($_GET['situacao']) ? trim($_GET['situacao']) : "todos";
if (!in_array($situacao, ['todos', 'devendo', 'inadimplente', 'em_dia'])) { $situacao = "todos"; }
if ($data_inicio !== "" && !data_valida($data_inicio)) { $erro = "Data inicial inválida."; }
if ($data_fim !== "" && !data_valida($data_fim)) { $erro = "Data final inválida."; }
if ($erro === "" && $data_inicio !== "" && $data_fim !== "" && $data_inicio > $data_fim) { $erro = "A data inicial não pode ser maior que a data final."; }
$linhas = [];
if ($erro === "") { $linhas = buscar_clientes_situacao($conn, $data_inicio, $data_fim, $situacao); }
$total_clientes = count($linhas);
$soma_saldo = 0;
$soma_compras = 0;
$soma_pagamentos = 0;
foreach ($linhas as $linha) {
    $soma_saldo += max(0, $linha['saldo_devedor']);
    $soma_compras += $linha['compras_periodo'];
    $soma_pagamentos += $linha['pagamentos_periodo'];
}
$por_pagina = 10;
$total_paginas = max(1, (int)ceil($total_clientes / $por_pagina));
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) { $pagina = 1; }
if ($pagina > $total_paginas) { $pagina = $total_paginas; }
$linhas_da_pagina = array_slice($linhas, ($pagina - 1) * $por_pagina, $por_pagina);
$rotulos = ['em_dia' => 'Em dia', 'devendo' => 'Devendo', 'inadimplente' => 'Inadimplente'];
$classes = ['em_dia' => 'selo-ok', 'devendo' => 'selo-aviso', 'inadimplente' => 'selo-alerta'];
$titulo_pagina = "Relatório de dívidas";
$menu_ativo = "relatorio";
require "cabecalho.php";
?>
<div class="cabecalho-pagina">
    <div><h1>Relatório de dívidas</h1><p class="subtitulo">Inadimplente = dívida mais antiga com mais de 30 dias.</p></div>
    <button type="button" class="botao-secundario nao-imprimir" onclick="window.print()">Imprimir</button>
</div>
<form method="GET" action="relatorio.php" class="painel form-filtros nao-imprimir">
    <div><label for="data_ini">De</label><input type="date" id="data_ini" name="data_ini" value="<?php echo htmlspecialchars($data_inicio); ?>"></div>
    <div><label for="data_fim">Até</label><input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>"></div>
    <div><label for="situacao">Situação</label>
        <select id="situacao" name="situacao">
            <option value="todos" <?php if ($situacao === 'todos') echo 'selected'; ?>>Todas</option>
            <option value="devendo" <?php if ($situacao === 'devendo') echo 'selected'; ?>>Devendo</option>
            <option value="inadimplente" <?php if ($situacao === 'inadimplente') echo 'selected'; ?>>Inadimplente (+30 dias)</option>
            <option value="em_dia" <?php if ($situacao === 'em_dia') echo 'selected'; ?>>Em dia</option>
        </select></div>
    <div style="flex:0 0 auto"><button type="submit">Filtrar</button> <a href="relatorio.php" class="botao botao-secundario">Limpar</a></div>
</form>
<?php if ($erro): ?>
    <div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div>
<?php else: ?>
    <div class="cards">
        <div class="card"><span class="card-titulo">Clientes no relatório</span><span class="card-valor"><?php echo $total_clientes; ?></span></div>
        <div class="card card-alerta"><span class="card-titulo">Saldo devedor somado</span><span class="card-valor"><?php echo dinheiro($soma_saldo); ?></span></div>
        <?php if ($data_inicio !== "" || $data_fim !== ""): ?>
        <div class="card"><span class="card-titulo">Compras no período</span><span class="card-valor"><?php echo dinheiro($soma_compras); ?></span></div>
        <div class="card card-ok"><span class="card-titulo">Pagamentos no período</span><span class="card-valor"><?php echo dinheiro($soma_pagamentos); ?></span></div>
        <?php endif; ?>
    </div>
    <div class="tabela-rolagem"><table>
        <tr><th>Cliente</th><th class="num">Compras no período</th><th class="num">Pagamentos no período</th><th class="num">Saldo devedor</th><th class="num">Dias em aberto</th><th>Situação</th></tr>
        <?php foreach ($linhas_da_pagina as $linha): ?>
        <tr>
            <td><a href="cliente.php?id=<?php echo $linha['id']; ?>"><?php echo htmlspecialchars($linha['nome']); ?></a></td>
            <td class="num"><?php echo dinheiro($linha['compras_periodo']); ?></td>
            <td class="num"><?php echo dinheiro($linha['pagamentos_periodo']); ?></td>
            <td class="num"><?php echo dinheiro(max(0, $linha['saldo_devedor'])); ?></td>
            <td class="num"><?php echo $linha['dias_devendo']; ?></td>
            <td><span class="selo <?php echo $classes[$linha['situacao']]; ?>"><?php echo $rotulos[$linha['situacao']]; ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($total_clientes === 0): ?><tr><td colspan="6" class="vazio">Nenhum cliente encontrado com esses filtros.</td></tr><?php endif; ?>
    </table></div>
    <?php if ($data_inicio !== "" || $data_fim !== ""): ?><p class="dica">Compras e pagamentos somados só dentro do período escolhido. O saldo devedor considera todo o histórico.</p><?php endif; ?>
    <?php mostrar_paginacao($pagina, $total_paginas, ['data_ini' => $data_inicio, 'data_fim' => $data_fim, 'situacao' => $situacao]); ?>
<?php endif; ?>
<?php require "rodape.php"; ?>
