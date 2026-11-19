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
    // motivo resumido, sem mostrar senha nem usuario
    $codigo = (int)$conn->connect_errno;
    $motivos = [
        1045 => "usuário ou senha do banco incorretos (confira \$banco_usuario e \$banco_senha)",
        1044 => "o usuário não tem permissão neste banco (confira \$banco_nome)",
        1049 => "o banco informado não existe (confira \$banco_nome)",
        2002 => "servidor do banco não encontrado ou fora do ar (confira \$banco_host)",
        2005 => "endereço do servidor do banco desconhecido (confira \$banco_host)",
        2006 => "o servidor do banco encerrou a conexão (pode estar fora do ar)",
        1040 => "o servidor do banco está com conexões demais; tente de novo em alguns minutos",
        1203 => "limite de conexões do usuário atingido; tente de novo em alguns minutos",
    ];
    $motivo = $motivos[$codigo] ?? "erro " . $codigo;
    die("Nao foi possivel conectar ao banco de dados. Motivo: " . $motivo . ". Confira os dados em config.local.php.");
}
unset($banco_host, $banco_usuario, $banco_senha, $banco_nome);
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '-03:00'");
// atualiza o banco para varias lojas, se ainda nao estiver atualizado
require_once __DIR__ . "/migracoes.php";
