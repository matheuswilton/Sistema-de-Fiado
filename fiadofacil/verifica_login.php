<?php
// Use no inicio das paginas dos funcionarios (admin e comum). Clientes vao para a pagina "Minha divida".
require_once "funcoes.php";
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['usuario_nivel'] === 'cliente') {
    header("Location: minha_divida.php");
    exit();
}
require_once "conexao.php";
// confere no banco se o usuario ainda existe e continua liberado (se o admin bloquear, a sessao cai na hora)
$stmt_sessao = $conn->prepare("SELECT status, nivel FROM usuarios WHERE id = ?");
$stmt_sessao->bind_param("i", $_SESSION['usuario_id']);
$stmt_sessao->execute();
$dados_sessao = $stmt_sessao->get_result()->fetch_assoc();
$stmt_sessao->close();
if (!$dados_sessao || $dados_sessao['status'] !== 'aprovado' || $dados_sessao['nivel'] === 'cliente') {
    session_unset();
    session_destroy();
    header("Location: login.php?motivo=bloqueado");
    exit();
}
$_SESSION['usuario_nivel'] = $dados_sessao['nivel'];
