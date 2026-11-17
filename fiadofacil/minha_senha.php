<?php
// Qualquer usuario logado (funcionario ou cliente) troca a propria senha aqui
require_once "funcoes.php";
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require_once "conexao.php";
$id_usuario = (int)$_SESSION['usuario_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$linha || !password_verify($senha_atual, $linha['senha'])) {
        definir_flash("A senha atual está incorreta.", "erro");
    } elseif (strlen($nova_senha) < 8) {
        definir_flash("A nova senha deve ter no mínimo 8 caracteres.", "erro");
    } elseif ($nova_senha !== $confirmar_senha) {
        definir_flash("A confirmação da nova senha não confere.", "erro");
    } elseif ($nova_senha === $senha_atual) {
        definir_flash("A nova senha precisa ser diferente da atual.", "erro");
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id_usuario);
        $stmt->execute();
        $stmt->close();
        definir_flash("Senha alterada com sucesso!");
    }
    header("Location: minha_senha.php");
    exit();
}
$titulo_pagina = "Minha senha";
$menu_ativo = "senha";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Minha senha</h1>
<p class="subtitulo">Escolha uma senha com pelo menos 8 caracteres.</p>
<?php mostrar_flash(); ?>
<div class="painel">
<form method="POST" action="minha_senha.php">
    <?php campo_csrf(); ?>
    <label for="senha_atual">Senha atual</label>
    <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
    <label for="nova_senha">Nova senha</label>
    <input type="password" id="nova_senha" name="nova_senha" autocomplete="new-password" required>
    <label for="confirmar_senha">Confirmar nova senha</label>
    <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" required>
    <button type="submit" class="botao-total">Salvar nova senha</button>
</form>
</div>
<?php require "rodape.php"; ?>
