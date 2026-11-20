<?php
require_once "verifica_login.php";
require_once "conexao.php";
$loja = loja_id();
$mensagem = "";
$nome = $telefone = $endereco = "";
$limite_credito = 500.00;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    // so o administrador define o limite; usuario comum usa o padrao de R$ 500
    $limite_credito = eh_admin() ? round(floatval($_POST['limite_credito'] ?? 500), 2) : 500.00;
    if ($nome === "") {
        $mensagem = "O nome do cliente é obrigatório.";
    } elseif ($telefone !== "" && !telefone_valido($telefone)) {
        $mensagem = "Telefone inválido. Informe o DDD e o número, ex.: (62) 99999-9999.";
    } elseif ($limite_credito < 0) {
        $mensagem = "O limite de crédito não pode ser negativo.";
    } elseif ($limite_credito > 99999999.99) {
        $mensagem = "O limite de crédito informado é alto demais.";
    } elseif (telefone_ja_cadastrado($conn, $telefone)) {
        $mensagem = "Já existe um cliente cadastrado com este telefone.";
    } else {
        $telefone = formatar_telefone($telefone);
        $stmt = $conn->prepare("INSERT INTO clientes (comercio_id, nome, telefone, endereco, limite_credito) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $loja, $nome, $telefone, $endereco, $limite_credito);
        if ($stmt->execute()) {
            $novo_id = $conn->insert_id;
            $stmt->close();
            definir_flash("Cliente cadastrado com sucesso! Agora você já pode registrar a primeira compra.");
            header("Location: cliente.php?id=" . $novo_id);
            exit();
        }
        $mensagem = "Erro ao cadastrar o cliente.";
        $stmt->close();
    }
}
$titulo_pagina = "Cadastrar cliente";
$menu_ativo = "clientes";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Cadastrar cliente</h1>
<p class="subtitulo">Só o nome é obrigatório.</p>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<form method="POST" action="cadastro_cliente.php">
    <?php campo_csrf(); ?>
    <div class="grade-form">
    <div class="campo campo-largo">
    <label for="nome">Nome completo *</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" autofocus required>
    </div>
    <div class="campo">
    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" data-mascara="telefone" placeholder="(62) 99999-9999" value="<?php echo htmlspecialchars($telefone); ?>">
    </div>
    <div class="campo">
    <label for="cep">CEP <span class="opcional">(preenche o endereço sozinho)</span></label>
    <input type="text" id="cep" inputmode="numeric" maxlength="9" placeholder="74000-000" data-cep="#endereco" autocomplete="postal-code">
    <div class="dica" id="cep-aviso" aria-live="polite"></div>
    </div>
    <div class="campo campo-largo campo-fim">
    <label for="endereco">Endereço</label>
    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco); ?>" maxlength="200">
    </div>
    <div class="campo">
    <label for="limite_credito">Limite de crédito (R$)</label>
    <?php if (eh_admin()): ?>
        <input type="number" id="limite_credito" name="limite_credito" step="0.01" min="0" value="<?php echo $limite_credito; ?>">
        <div class="dica">Quanto o cliente pode dever ao mesmo tempo.</div>
    <?php else: ?>
        <input type="text" id="limite_credito" value="R$ 500,00 (limite padrão — somente o administrador altera)" disabled>
    <?php endif; ?>
    </div>
    </div>
    <div class="barra-botoes"><button type="submit">Cadastrar</button><a href="listar_clientes.php" class="botao botao-secundario">Cancelar</a></div>
</form>
</div>
<?php require "rodape.php"; ?>
