<?php
// Somente administrador e somente por POST (botao "Excluir" da lista de clientes)
require_once "verifica_admin.php";
require_once "conexao.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_clientes.php");
    exit();
}
verificar_csrf();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id > 0) {
    $saldo = saldo_do_cliente($conn, $id);
    if ($saldo > 0) {
        // regra: nao exclui cliente que ainda tem divida em aberto
        definir_flash("Não é possível excluir: o cliente ainda deve " . dinheiro($saldo) . ".", "erro");
    } else {
        // compras, pagamentos e o acesso do cliente sao apagados pelo ON DELETE CASCADE
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $apagou = $stmt->affected_rows > 0;
        $stmt->close();
        if ($apagou) { definir_flash("Cliente excluído com sucesso."); } else { definir_flash("Cliente não encontrado.", "erro"); }
    }
}
header("Location: listar_clientes.php");
exit();
