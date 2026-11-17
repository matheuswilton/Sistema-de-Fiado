<?php
require_once "verifica_login.php";
require_once "conexao.php";
$por_pagina = 10;
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : "";
$like = "%" . $busca . "%";
$stmt_total = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE nome LIKE ? OR telefone LIKE ?");
$stmt_total->bind_param("ss", $like, $like);
$stmt_total->execute();
$stmt_total->bind_result($total);
$stmt_total->fetch();
$stmt_total->close();
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) { $pagina = 1; }
if ($pagina > $total_paginas) { $pagina = $total_paginas; }
$offset = ($pagina - 1) * $por_pagina;
$stmt = $conn->prepare("
    SELECT c.id, c.nome, c.telefone, c.limite_credito,
           COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
           - COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.cliente_id = c.id), 0) AS saldo_devedor
    FROM clientes c
    WHERE c.nome LIKE ? OR c.telefone LIKE ?
    ORDER BY c.nome
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ssii", $like, $like, $por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$titulo_pagina = "Clientes";
$menu_ativo = "clientes";
require "cabecalho.php";
?>
<div class="cabecalho-pagina">
    <div><h1>Clientes</h1><p class="subtitulo"><?php echo $total; ?> cliente(s)<?php if ($busca !== "") echo " encontrado(s) para “" . htmlspecialchars($busca) . "”"; ?>.</p></div>
    <a href="cadastro_cliente.php" class="botao">+ Cadastrar cliente</a>
</div>
<?php mostrar_flash(); ?>
<form method="GET" action="listar_clientes.php" class="form-busca">
    <input type="search" name="busca" placeholder="Buscar por nome ou telefone..." value="<?php echo htmlspecialchars($busca); ?>" aria-label="Buscar cliente">
    <button type="submit">Buscar</button>
    <?php if ($busca !== ""): ?><a href="listar_clientes.php" class="botao botao-secundario">Limpar</a><?php endif; ?>
</form>
<div class="tabela-rolagem">
<table>
    <tr><th>Nome</th><th>Telefone</th><th class="num">Limite</th><th class="num">Saldo devedor</th><th>Ações</th></tr>
    <?php $quantidade_linhas = 0; while ($linha = $resultado->fetch_assoc()): $quantidade_linhas++; ?>
    <tr>
        <td><a class="link-nome com-avatar" href="cliente.php?id=<?php echo $linha['id']; ?>" title="Ver extrato"><?php echo avatar($linha['nome']); ?><span><?php echo htmlspecialchars($linha['nome']); ?></span></a></td>
        <td><?php echo htmlspecialchars($linha['telefone'] ?? ''); ?></td>
        <td class="num"><?php echo dinheiro($linha['limite_credito']); ?></td>
        <td class="num"><?php echo $linha['saldo_devedor'] > 0 ? '<span class="alerta">' . dinheiro($linha['saldo_devedor']) . '</span>' : '<span class="ok">' . dinheiro(0) . '</span>'; ?></td>
        <td class="acoes">
            <a href="cliente.php?id=<?php echo $linha['id']; ?>" class="botao-icone botao-so-celular" title="Ver extrato">Extrato</a>
            <a href="editar_cliente.php?id=<?php echo $linha['id']; ?>" class="botao-icone" title="Editar cliente"><?php echo icone('editar'); ?><span>Editar</span></a>
            <?php if (eh_admin()): ?>
            <form method="POST" action="excluir_cliente.php" data-confirmar="Excluir o cliente <?php echo htmlspecialchars($linha['nome'], ENT_QUOTES); ?>?&#10;Todo o histórico de compras, pagamentos e o acesso dele serão apagados. Esta ação não pode ser desfeita.">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id" value="<?php echo $linha['id']; ?>">
                <button type="submit" class="botao-icone botao-icone-perigo" title="Excluir cliente" aria-label="Excluir cliente"><?php echo icone('lixeira'); ?><span class="texto-celular">Excluir</span></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if ($quantidade_linhas === 0): ?><tr><td colspan="5" class="vazio">
        <?php if ($busca === ""): ?>
            <strong>Nenhum cliente cadastrado ainda.</strong>
            <span>Cadastre o primeiro cliente para começar a anotar o fiado.</span>
            <a href="cadastro_cliente.php" class="botao">+ Cadastrar primeiro cliente</a>
        <?php else: ?>
            <strong>Nenhum cliente encontrado.</strong>
            <span>Confira o nome ou o telefone digitado.</span>
        <?php endif; ?>
    </td></tr><?php endif; ?>
</table>
</div>
<?php mostrar_paginacao($pagina, $total_paginas, ['busca' => $busca]); ?>
<?php $stmt->close(); require "rodape.php"; ?>
