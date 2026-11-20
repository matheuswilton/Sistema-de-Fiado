<?php
// Prepara o banco de TESTES: roda as migracoes (cria lojas, contas e tabelas novas)
// e coloca a mesma senha de teste em todas as contas, para os testes nao usarem as senhas reais.
// Uso: php testes/preparar.php   (depois de importar fiadofacil/sql/banco.sql no banco de testes)
define('SEM_SESSAO', true);
require __DIR__ . '/../fiadofacil/funcoes.php';
require __DIR__ . '/../fiadofacil/conexao.php';
if (($_SESSION['versao_banco'] ?? 0) !== VERSAO_BANCO) {
    fwrite(STDERR, "As migracoes do banco falharam.\n");
    exit(1);
}
$senha = getenv('SENHA_TESTE') ?: 'teste-ci-2026';
$hash = password_hash($senha, PASSWORD_DEFAULT);
foreach (['usuarios', 'contas_clientes'] as $tabela) {
    $stmt = $conn->prepare("UPDATE $tabela SET senha = ?");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    echo "Senha de teste aplicada em $tabela: " . $stmt->affected_rows . " conta(s)\n";
    $stmt->close();
}
echo "Banco de testes pronto (versao " . VERSAO_BANCO . ").\n";
