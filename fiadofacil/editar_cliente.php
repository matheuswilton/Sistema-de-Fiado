<?php
require_once "verifica_login.php";
require_once "conexao.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: listar_clientes.php");
    exit();
}
$stmt = $conn->prepare("SELECT nome, telefone, endereco, limite_credito FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cliente) {
    definir_flash("Cliente não encontrado.", "erro");
    header("Location: listar_clientes.php");
    exit();
}
$mensagem = "";
$nome = $cliente['nome'];
$telefone = $cliente['telefone'] ?? '';
$endereco = $cliente['endereco'] ?? '';
$limite_credito = (float)$cliente['limite_credito'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    // usuario comum nao altera o limite: continua o que esta no banco
    $novo_limite = eh_admin() ? round(floatval($_POST['limite_credito'] ?? 0), 2) : (float)$cliente['limite_credito'];
    if ($nome === "") {
        $mensagem = "O nome do cliente é obrigatório.";
    } elseif ($telefone !== "" && !telefone_valido($telefone)) {
        $mensagem = "Telefone inválido. Informe o DDD e o número, ex.: (62) 99999-9999.";
    } elseif ($novo_limite < 0) {
        $mensagem = "O limite de crédito não pode ser negativo.";
    } elseif ($novo_limite > 99999999.99) {
        $mensagem = "O limite de crédito informado é alto demais.";
    } elseif (telefone_ja_cadastrado($conn, $telefone, $id)) {
        $mensagem = "Já existe outro cliente cadastrado com este telefone.";
    } else {
        $telefone = formatar_telefone($telefone);
        $stmt = $conn->prepare("UPDATE clientes SET nome = ?, telefone = ?, endereco = ?, limite_credito = ? WHERE id = ?");
        $stmt->bind_param("sssdi", $nome, $telefone, $endereco, $novo_limite, $id);
        if ($stmt->execute()) {
            $stmt->close();
            definir_flash("Dados do cliente atualizados com sucesso!");
            header("Location: cliente.php?id=" . $id);
            exit();
        }
        $mensagem = "Erro ao atualizar o cliente.";
        $stmt->close();
    }
    $limite_credito = $novo_limite;
}
$titulo_pagina = "Editar cliente";
$menu_ativo = "clientes";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Editar cliente</h1>
<p class="subtitulo"><?php echo htmlspecialchars($cliente['nome']); ?></p>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<form method="POST" action="editar_cliente.php?id=<?php echo $id; ?>">
    <?php campo_csrf(); ?>
    <label for="nome">Nome completo *</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" required>
    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" data-mascara="telefone" placeholder="(62) 99999-9999" value="<?php echo htmlspecialchars($telefone); ?>">
    <label for="endereco">Endereço</label>
    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco); ?>" maxlength="200">
    <label for="limite_credito">Limite de crédito (R$)</label>
    <?php if (eh_admin()): ?>
        <input type="number" id="limite_credito" name="limite_credito" step="0.01" min="0" value="<?php echo $limite_credito; ?>">
    <?php else: ?>
        <input type="text" id="limite_credito" value="<?php echo dinheiro($limite_credito); ?> (somente o administrador altera)" disabled>
    <?php endif; ?>
    <div class="barra-botoes"><button type="submit">Salvar alterações</button><a href="cliente.php?id=<?php echo $id; ?>" class="botao botao-secundario">Cancelar</a></div>
</form>
</div>
<?php require "rodape.php"; ?>
