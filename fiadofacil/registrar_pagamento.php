<?php
require_once "verifica_login.php";
require_once "conexao.php";
$loja = loja_id();
$cliente_escolhido = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$valor_digitado = "";
$mensagem = "";
// veio do extrato do cliente? Entao volta para o extrato ao salvar ou cancelar
$voltar_cliente = (int)($_GET['voltar'] ?? ($_POST['voltar'] ?? 0));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $cliente_escolhido = (int)($_POST['cliente_id'] ?? 0);
    $valor_digitado = $_POST['valor'] ?? '';
    $valor = ler_valor($valor_digitado);
    if ($cliente_escolhido <= 0 || $valor <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
    } else {
        $resposta = registrar_pagamento($conn, $cliente_escolhido, $valor);
        if ($resposta[0]) {
            definir_flash($resposta[1]);
            header("Location: " . ($voltar_cliente ? "cliente.php?id=" . $cliente_escolhido : "registrar_pagamento.php"));
            exit();
        }
        $mensagem = $resposta[1];
    }
}
// so clientes que estao devendo aparecem na lista
$stmt_lista = $conn->prepare("
    SELECT id, nome, saldo_devedor FROM (
        SELECT c.id, c.nome,
               COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
               - COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.cliente_id = c.id), 0) AS saldo_devedor
        FROM clientes c WHERE c.comercio_id = ?
    ) t WHERE saldo_devedor > 0 ORDER BY nome
");
$stmt_lista->bind_param("i", $loja);
$stmt_lista->execute();
$clientes = $stmt_lista->get_result();
$stmt_lista->close();
$titulo_pagina = "Registrar pagamento";
$menu_ativo = "pagamento";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Registrar pagamento</h1>
<p class="subtitulo">Só aparecem clientes que estão devendo. O pagamento não pode ser maior que a dívida.</p>
<?php mostrar_flash(); ?>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<form method="POST" action="registrar_pagamento.php" data-resumo="pagamento">
    <?php campo_csrf(); ?>
    <?php if ($voltar_cliente): ?><input type="hidden" name="voltar" value="1"><?php endif; ?>
    <label for="selecao-cliente">Cliente *</label>
    <select id="selecao-cliente" name="cliente_id" required>
        <option value="">Selecione o cliente...</option>
        <?php $quantidade_devedores = 0; while ($cliente = $clientes->fetch_assoc()): $quantidade_devedores++; ?>
        <option value="<?php echo $cliente['id']; ?>" data-saldo="<?php echo number_format($cliente['saldo_devedor'], 2, '.', ''); ?>" data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>" <?php if ($cliente_escolhido === (int)$cliente['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($cliente['nome']); ?> — deve <?php echo dinheiro($cliente['saldo_devedor']); ?>
        </option>
        <?php endwhile; ?>
    </select>
    <?php if ($quantidade_devedores === 0): ?><div class="dica">Nenhum cliente com dívida em aberto no momento.</div><?php endif; ?>
    <label for="campo-valor">Valor pago (R$) *</label>
    <input type="text" id="campo-valor" name="valor" data-mascara="dinheiro" inputmode="numeric" placeholder="R$ 0,00" autocomplete="off" value="<?php echo htmlspecialchars($valor_digitado); ?>" required>
    <div class="barra-botoes">
        <button type="submit">Registrar pagamento</button>
        <button type="button" id="botao-quitar" class="botao-secundario">Quitar dívida inteira</button>
        <a href="<?php echo $voltar_cliente && $cliente_escolhido ? 'cliente.php?id=' . $cliente_escolhido : 'dashboard.php'; ?>" class="botao botao-secundario">Cancelar</a>
    </div>
</form>
</div>
<?php require "rodape.php"; ?>
