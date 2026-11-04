<?php
require_once "verifica_login.php";
require_once "conexao.php";
$cliente_escolhido = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$descricao = "";
$valor_digitado = "";
$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $cliente_escolhido = (int)($_POST['cliente_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_digitado = $_POST['valor'] ?? '';
    $valor = round(floatval($valor_digitado), 2);
    if ($cliente_escolhido <= 0 || $valor <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
    } elseif (strlen($descricao) > 200) {
        $mensagem = "A descrição pode ter no máximo 200 caracteres.";
    } else {
        $resposta = registrar_compra($conn, $cliente_escolhido, $descricao, $valor);
        if ($resposta[0]) {
            definir_flash($resposta[1]);
            header("Location: registrar_compra.php");
            exit();
        }
        $mensagem = $resposta[1];
    }
}
$clientes = $conn->query("
    SELECT c.id, c.nome, c.limite_credito,
           COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
           - COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.cliente_id = c.id), 0) AS saldo_devedor
    FROM clientes c ORDER BY c.nome
");
$titulo_pagina = "Registrar compra";
$menu_ativo = "compra";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Registrar compra no fiado</h1>
<p class="subtitulo">A compra só é aceita se não passar do limite de crédito do cliente.</p>
<?php mostrar_flash(); ?>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<form method="POST" action="registrar_compra.php">
    <?php campo_csrf(); ?>
    <label for="cliente_id">Cliente *</label>
    <select id="cliente_id" name="cliente_id" required>
        <option value="">Selecione o cliente...</option>
        <?php while ($cliente = $clientes->fetch_assoc()): $disponivel = max(0, $cliente['limite_credito'] - $cliente['saldo_devedor']); ?>
        <option value="<?php echo $cliente['id']; ?>" <?php if ($cliente_escolhido === (int)$cliente['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($cliente['nome']); ?> — disponível <?php echo dinheiro($disponivel); ?>
        </option>
        <?php endwhile; ?>
    </select>
    <label for="descricao">Descrição</label>
    <input type="text" id="descricao" name="descricao" placeholder="Ex.: Arroz e feijão" maxlength="200" value="<?php echo htmlspecialchars($descricao); ?>">
    <label for="valor">Valor (R$) *</label>
    <input type="number" id="valor" name="valor" placeholder="0,00" step="0.01" min="0.01" max="99999999.99" value="<?php echo htmlspecialchars($valor_digitado); ?>" required>
    <div class="barra-botoes"><button type="submit">Registrar compra</button><a href="dashboard.php" class="botao botao-secundario">Cancelar</a></div>
</form>
</div>
<?php require "rodape.php"; ?>
