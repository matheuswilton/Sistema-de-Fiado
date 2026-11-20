<?php
require_once "funcoes.php";
// cliente com conta por CPF volta para a area do cliente
if (!empty($_SESSION['conta_cliente_id']) && empty($_SESSION['usuario_id'])) {
    session_unset();
    session_destroy();
    header("Location: conta_entrar.php?motivo=saiu");
    exit();
}
encerrar_sessao("saiu");
