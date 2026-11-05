<?php
// Use nas paginas so do administrador (ja inclui o verifica_login.php)
require_once "verifica_login.php";
if (!eh_admin()) {
    definir_flash("Acesso negado: apenas administradores podem acessar esta área.", "erro");
    header("Location: dashboard.php");
    exit();
}
