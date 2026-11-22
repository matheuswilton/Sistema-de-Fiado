<?php
require_once "verifica_login.php";
require_once "conexao.php";
$loja = loja_id();
$cliente_escolhido = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$descricao = "";
$valor_digitado = "";
$mensagem = "";
// veio do extrato do cliente? Entao volta para o extrato ao salvar ou cancelar
$voltar_cliente = (int)($_GET['voltar'] ?? ($_POST['voltar'] ?? 0));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $cliente_escolhido = (int)($_POST['cliente_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_digitado = $_POST['valor'] ?? '';
    $valor = ler_valor($valor_digitado);
    if ($cliente_escolhido <= 0 || $valor <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
    } elseif (strlen($descricao) > 200) {
        $mensagem = "A descrição pode ter no máximo 200 caracteres.";
    } else {
        $resposta = registrar_compra($conn, $cliente_escolhido, $descricao, $valor);
        if ($resposta[0]) {
            definir_flash($resposta[1]);
            header("Location: " . ($voltar_cliente ? "cliente.php?id=" . $cliente_escolhido : "registrar_compra.php"));
            exit();
        }
        $mensagem = $resposta[1];
    }
}
$stmt_lista = $conn->prepare("
    SELECT c.id, c.nome, c.limite_credito,
           COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
           - COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.cliente_id = c.id), 0) AS saldo_devedor
    FROM clientes c WHERE c.comercio_id = ? ORDER BY c.nome
");
$stmt_lista->bind_param("i", $loja);
$stmt_lista->execute();
$clientes = $stmt_lista->get_result();
$stmt_lista->close();
$titulo_pagina = "Registrar compra";
$menu_ativo = "compra";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Registrar compra no fiado</h1>
<p class="subtitulo">Compras acima do limite do cliente são recusadas.</p>
<?php mostrar_flash(); ?>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<form method="POST" action="registrar_compra.php" data-resumo="compra">
    <?php campo_csrf(); ?>
    <?php if ($voltar_cliente): ?><input type="hidden" name="voltar" value="1"><?php endif; ?>
    <label for="cliente_id">Cliente *</label>
    <select id="cliente_id" name="cliente_id" required>
        <option value="">Selecione o cliente...</option>
        <?php while ($cliente = $clientes->fetch_assoc()): $disponivel = max(0, $cliente['limite_credito'] - $cliente['saldo_devedor']); ?>
        <option value="<?php echo $cliente['id']; ?>" data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>" data-saldo="<?php echo number_format(max(0, $cliente['saldo_devedor']), 2, '.', ''); ?>" data-disponivel="<?php echo number_format($disponivel, 2, '.', ''); ?>" <?php if ($cliente_escolhido === (int)$cliente['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($cliente['nome']); ?> — disponível <?php echo dinheiro($disponivel); ?>
        </option>
        <?php endwhile; ?>
    </select>
    <label for="descricao">Descrição</label>
    <input type="text" id="descricao" name="descricao" placeholder="Ex.: Arroz e feijão" maxlength="200" value="<?php echo htmlspecialchars($descricao); ?>">
    <label for="valor">Valor (R$) *</label>
    <input type="text" id="valor" name="valor" data-mascara="dinheiro" inputmode="numeric" placeholder="R$ 0,00" autocomplete="off" value="<?php echo htmlspecialchars($valor_digitado); ?>" required>
    <div class="barra-botoes"><button type="submit" class="botao-compra">Registrar compra</button><a href="<?php echo $voltar_cliente && $cliente_escolhido ? 'cliente.php?id=' . $cliente_escolhido : 'dashboard.php'; ?>" class="botao botao-secundario">Cancelar</a></div>
</form>
</div>
<?php require "rodape.php"; ?>
