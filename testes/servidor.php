<?php
// Roteador do servidor embutido do PHP (php -S) para os testes: imita o .htaccess da hospedagem.
// Uso: php -S 127.0.0.1:8080 -t fiadofacil testes/servidor.php
$raiz = realpath(__DIR__ . '/../fiadofacil');
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($caminho === '/' || is_file($raiz . $caminho)) {
    return false;
}
if (preg_match('#^/loja/([A-Za-z0-9-]+)/?$#i', $caminho, $partes)) {
    header("Location: /login.php?loja=" . $partes[1]);
    exit;
}
chdir($raiz);
require $raiz . '/404.php';
