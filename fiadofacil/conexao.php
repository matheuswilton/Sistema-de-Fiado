<?php
// Os dados do banco ficam em config.local.php (esse arquivo nao vai para o GitHub).
if (!file_exists(__DIR__ . "/config.local.php")) {
    http_response_code(500);
    die("Falta o arquivo config.local.php. Copie o config.local.example.php, renomeie para config.local.php e preencha com os dados do banco.");
}
require __DIR__ . "/config.local.php";
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($banco_host, $banco_usuario, $banco_senha, $banco_nome);
if ($conn->connect_error) {
    error_log("Falha na conexao MySQL: " . $conn->connect_error);
    http_response_code(500);
    die("Nao foi possivel conectar ao banco de dados. Confira os dados em config.local.php.");
}
unset($banco_host, $banco_usuario, $banco_senha, $banco_nome);
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '-03:00'");
