<?php
require_once "funcoes.php";
session_unset();
session_destroy();
header("Location: login.php?motivo=saiu");
exit();
