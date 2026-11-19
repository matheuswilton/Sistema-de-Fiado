<?php
// Use no inicio das paginas dos funcionarios (admin e comum). Clientes vao para a pagina "Minha divida".
require_once "funcoes.php";
if (!isset($_SESSION['usuario_id']) || loja_id() === 0) {
    header("Location: " . (isset($_SESSION['plataforma']) ? "painel.php" : endereco_login($_SESSION['loja_slug'] ?? null)));
    exit();
}
if ($_SESSION['usuario_nivel'] === 'cliente') {
    header("Location: minha_divida.php");
    exit();
}
require_once "conexao.php";
// confere no banco, a cada pagina, se o usuario e a loja continuam liberados (se alguem for bloqueado, a sessao cai na hora)
$stmt_sessao = $conn->prepare("SELECT status, nivel, comercio_id FROM usuarios WHERE id = ?");
$stmt_sessao->bind_param("i", $_SESSION['usuario_id']);
$stmt_sessao->execute();
$dados_sessao = $stmt_sessao->get_result()->fetch_assoc();
$stmt_sessao->close();
if (eh_plataforma()) {
    // dono da plataforma visitando uma loja pelo painel: age como administrador daquela loja
    if (!$dados_sessao || $dados_sessao['status'] !== 'aprovado' || $dados_sessao['nivel'] !== 'plataforma') {
        encerrar_sessao("bloqueado");
    }
    $_SESSION['usuario_nivel'] = 'admin';
} else {
    if (!$dados_sessao || $dados_sessao['status'] !== 'aprovado' || !in_array($dados_sessao['nivel'], ['admin', 'comum'], true)
        || (int)$dados_sessao['comercio_id'] !== loja_id()) {
        encerrar_sessao("bloqueado");
    }
    $_SESSION['usuario_nivel'] = $dados_sessao['nivel'];
}
$loja_atual = carregar_loja($conn, loja_id());
if (!$loja_atual || ($loja_atual['status'] !== 'ativo' && !eh_plataforma())) {
    encerrar_sessao("loja_bloqueada");
}
