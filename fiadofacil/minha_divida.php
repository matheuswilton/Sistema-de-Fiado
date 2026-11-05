<?php
// Tela do cliente (somente leitura): mostra apenas a divida dele
require_once "funcoes.php";
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['usuario_nivel'] !== 'cliente') {
    header("Location: dashboard.php");
    exit();
}
require_once "conexao.php";
$id_usuario = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT status, cliente_id FROM usuarios WHERE id = ? AND nivel = 'cliente'");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$dados_usuario || $dados_usuario['status'] !== 'aprovado' || !$dados_usuario['cliente_id']) {
    session_unset();
    session_destroy();
    header("Location: login.php?motivo=bloqueado");
    exit();
}
$id_cliente = (int)$dados_usuario['cliente_id'];
$stmt = $conn->prepare("SELECT nome, limite_credito FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cliente) {
    session_unset();
    session_destroy();
    header("Location: login.php?motivo=bloqueado");
    exit();
}
$total_compras = 0;
$lista_compras = [];
$stmt = $conn->prepare("SELECT descricao, valor, data_compra FROM compras WHERE cliente_id = ? ORDER BY data_compra DESC, id DESC");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) { $total_compras += $linha['valor']; $lista_compras[] = $linha; }
$stmt->close();
$total_pagamentos = 0;
$lista_pagamentos = [];
$stmt = $conn->prepare("SELECT valor, data_pagamento FROM pagamentos WHERE cliente_id = ? ORDER BY data_pagamento DESC, id DESC");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) { $total_pagamentos += $linha['valor']; $lista_pagamentos[] = $linha; }
$stmt->close();
$saldo_devedor = round($total_compras - $total_pagamentos, 2);
$limite_disponivel = max(0, $cliente['limite_credito'] - $saldo_devedor);
$titulo_pagina = "Minha dívida";
$menu_ativo = "minha_divida";
require "cabecalho.php";
?>
<h1>Olá, <?php echo htmlspecialchars($cliente['nome']); ?>!</h1>
<p class="subtitulo">Aqui você acompanha suas compras no fiado e seus pagamentos.</p>
<?php mostrar_flash(); ?>
<div class="cards">
    <div class="card <?php echo $saldo_devedor > 0 ? 'card-alerta' : 'card-ok'; ?>"><span class="card-titulo">Quanto você deve</span><span class="card-valor"><?php echo dinheiro(max(0, $saldo_devedor)); ?></span></div>
    <div class="card"><span class="card-titulo">Limite de crédito</span><span class="card-valor"><?php echo dinheiro($cliente['limite_credito']); ?></span></div>
    <div class="card"><span class="card-titulo">Limite disponível</span><span class="card-valor"><?php echo dinheiro($limite_disponivel); ?></span></div>
</div>
<div class="duas-colunas">
<div>
    <h2>Minhas compras</h2>
    <div class="tabela-rolagem"><table>
        <tr><th>Data</th><th>Descrição</th><th class="num">Valor</th></tr>
        <?php foreach ($lista_compras as $compra): ?>
        <tr><td><?php echo date('d/m/Y', strtotime($compra['data_compra'])); ?></td><td><?php echo htmlspecialchars($compra['descricao'] ?? ''); ?></td><td class="num"><?php echo dinheiro($compra['valor']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($lista_compras) === 0): ?><tr><td colspan="3" class="vazio">Nenhuma compra registrada.</td></tr><?php endif; ?>
    </table></div>
</div>
<div>
    <h2>Meus pagamentos</h2>
    <div class="tabela-rolagem"><table>
        <tr><th>Data</th><th class="num">Valor</th></tr>
        <?php foreach ($lista_pagamentos as $pagamento): ?>
        <tr><td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td><td class="num"><?php echo dinheiro($pagamento['valor']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($lista_pagamentos) === 0): ?><tr><td colspan="2" class="vazio">Nenhum pagamento registrado.</td></tr><?php endif; ?>
    </table></div>
</div>
</div>
<?php require "rodape.php"; ?>
