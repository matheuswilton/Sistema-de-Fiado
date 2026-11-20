<?php
require_once "verifica_login.php";
require_once "conexao.php";
$loja = loja_id();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: listar_clientes.php");
    exit();
}
$stmt = $conn->prepare("SELECT nome, telefone, cpf, endereco, limite_credito FROM clientes WHERE id = ? AND comercio_id = ?");
$stmt->bind_param("ii", $id, $loja);
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
$cpf = $cliente['cpf'] ?? '';
$limite_credito = (float)$cliente['limite_credito'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cpf = so_numeros($_POST['cpf'] ?? '');
    // usuario comum nao altera o limite: continua o que esta no banco
    $novo_limite = eh_admin() ? round(floatval($_POST['limite_credito'] ?? 0), 2) : (float)$cliente['limite_credito'];
    if ($nome === "") {
        $mensagem = "O nome do cliente é obrigatório.";
    } elseif ($telefone !== "" && !telefone_valido($telefone)) {
        $mensagem = "Telefone inválido. Informe o DDD e o número, ex.: (62) 99999-9999.";
    } elseif ($cpf !== "" && !cpf_valido($cpf)) {
        $mensagem = "CPF inválido. Confira os números (ou deixe em branco).";
    } elseif ($cpf !== "" && cpf_ja_cadastrado($conn, $cpf, $id)) {
        $mensagem = "Já existe outro cliente com este CPF nesta loja.";
    } elseif ($novo_limite < 0) {
        $mensagem = "O limite de crédito não pode ser negativo.";
    } elseif ($novo_limite > 99999999.99) {
        $mensagem = "O limite de crédito informado é alto demais.";
    } elseif (telefone_ja_cadastrado($conn, $telefone, $id)) {
        $mensagem = "Já existe outro cliente cadastrado com este telefone.";
    } else {
        $telefone = formatar_telefone($telefone);
        $cpf_banco = $cpf !== "" ? $cpf : null;
        $stmt = $conn->prepare("UPDATE clientes SET nome = ?, telefone = ?, cpf = ?, endereco = ?, limite_credito = ? WHERE id = ? AND comercio_id = ?");
        $stmt->bind_param("ssssdii", $nome, $telefone, $cpf_banco, $endereco, $novo_limite, $id, $loja);
        if ($stmt->execute()) {
            $stmt->close();
            // CPF trocado: a conta ligada ao CPF antigo deixa de ver este cadastro
            if ($cpf !== (string)($cliente['cpf'] ?? '')) {
                $stmt = $conn->prepare("DELETE FROM contas_vinculos WHERE cliente_id = ? AND comercio_id = ?");
                $stmt->bind_param("ii", $id, $loja);
                $stmt->execute();
                $stmt->close();
            }
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
    <div class="grade-form">
    <div class="campo campo-largo">
    <label for="nome">Nome completo *</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" required>
    </div>
    <div class="campo">
    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" data-mascara="telefone" placeholder="(62) 99999-9999" value="<?php echo htmlspecialchars($telefone); ?>">
    </div>
    <div class="campo">
    <label for="cpf">CPF <span class="opcional">(para o cliente ver o fiado pela conta dele)</span></label>
    <input type="text" id="cpf" name="cpf" data-mascara="cpf" inputmode="numeric" placeholder="000.000.000-00" value="<?php echo htmlspecialchars(formatar_cpf($cpf)); ?>">
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
    <?php else: ?>
        <input type="text" id="limite_credito" value="<?php echo dinheiro($limite_credito); ?> (somente o administrador altera)" disabled>
    <?php endif; ?>
    </div>
    </div>
    <div class="barra-botoes"><button type="submit">Salvar alterações</button><a href="cliente.php?id=<?php echo $id; ?>" class="botao botao-secundario">Cancelar</a></div>
</form>
</div>
<?php require "rodape.php"; ?>
