<?php
// Pagina inicial: se estiver logado vai para o menu, senao vai para o login
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
