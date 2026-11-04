<?php
// Extrato do cliente: dados, divida, compras e pagamentos
require_once "verifica_login.php";
require_once "conexao.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT id, nome, telefone, endereco, limite_credito, criado_em FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cliente) {
    definir_flash("Cliente não encontrado.", "erro");
    header("Location: listar_clientes.php");
    exit();
}
$saldo = saldo_do_cliente($conn, $id);
$limite = (float)$cliente['limite_credito'];
$disponivel = max(0, $limite - $saldo);
$lista_compras = [];
$stmt = $conn->prepare("SELECT descricao, valor, data_compra FROM compras WHERE cliente_id = ? ORDER BY data_compra DESC, id DESC LIMIT 100");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) { $lista_compras[] = $linha; }
$stmt->close();
$lista_pagamentos = [];
$stmt = $conn->prepare("SELECT valor, data_pagamento FROM pagamentos WHERE cliente_id = ? ORDER BY data_pagamento DESC, id DESC LIMIT 100");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) { $lista_pagamentos[] = $linha; }
$stmt->close();
$titulo_pagina = $cliente['nome'];
$menu_ativo = "clientes";
require "cabecalho.php";
?>
<div class="cabecalho-pagina">
    <div>
        <h1><?php echo htmlspecialchars($cliente['nome']); ?></h1>
        <p class="subtitulo"><?php echo htmlspecialchars($cliente['telefone'] ?: 'Sem telefone'); ?><?php if (!empty($cliente['endereco'])) echo " · " . htmlspecialchars($cliente['endereco']); ?> · cliente desde <?php echo date('d/m/Y', strtotime($cliente['criado_em'])); ?></p>
    </div>
    <div class="acoes nao-imprimir">
        <a href="registrar_compra.php?cliente_id=<?php echo $id; ?>" class="botao">Registrar compra</a>
        <?php if ($saldo > 0): ?><a href="registrar_pagamento.php?cliente_id=<?php echo $id; ?>" class="botao botao-verde">Registrar pagamento</a><?php endif; ?>
        <a href="editar_cliente.php?id=<?php echo $id; ?>" class="botao botao-secundario">Editar</a>
    </div>
</div>
<?php mostrar_flash(); ?>
<div class="cards">
    <div class="card <?php echo $saldo > 0 ? 'card-alerta' : 'card-ok'; ?>"><span class="card-titulo">Dívida atual</span><span class="card-valor"><?php echo dinheiro(max(0, $saldo)); ?></span></div>
    <div class="card"><span class="card-titulo">Limite de crédito</span><span class="card-valor"><?php echo dinheiro($limite); ?></span></div>
    <div class="card"><span class="card-titulo">Limite disponível</span><span class="card-valor"><?php echo dinheiro($disponivel); ?></span></div>
</div>
<div class="duas-colunas">
<div>
    <h2>Compras</h2>
    <div class="tabela-rolagem"><table>
        <tr><th>Data</th><th>Descrição</th><th class="num">Valor</th></tr>
        <?php foreach ($lista_compras as $compra): ?>
        <tr><td><?php echo date('d/m/Y', strtotime($compra['data_compra'])); ?></td><td><?php echo htmlspecialchars($compra['descricao'] ?? ''); ?></td><td class="num"><?php echo dinheiro($compra['valor']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($lista_compras) === 0): ?><tr><td colspan="3" class="vazio">Nenhuma compra registrada.</td></tr><?php endif; ?>
    </table></div>
</div>
<div>
    <h2>Pagamentos</h2>
    <div class="tabela-rolagem"><table>
        <tr><th>Data</th><th class="num">Valor</th></tr>
        <?php foreach ($lista_pagamentos as $pagamento): ?>
        <tr><td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td><td class="num"><?php echo dinheiro($pagamento['valor']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($lista_pagamentos) === 0): ?><tr><td colspan="2" class="vazio">Nenhum pagamento registrado.</td></tr><?php endif; ?>
    </table></div>
</div>
</div>
<?php if (count($lista_compras) >= 100 || count($lista_pagamentos) >= 100): ?><p class="dica">Mostrando os 100 registros mais recentes de cada lista.</p><?php endif; ?>
<div class="barra-botoes nao-imprimir"><a href="listar_clientes.php" class="botao botao-secundario">Voltar para clientes</a></div>
<?php require "rodape.php"; ?>
