<?php
require_once "verifica_admin.php";
require_once "conexao.php";
$loja = loja_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'aprovar') {
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $stmt_cliente = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND comercio_id = ?");
        $stmt_cliente->bind_param("ii", $id_cliente, $loja);
        $stmt_cliente->execute();
        $stmt_cliente->store_result();
        $cliente_existe = $stmt_cliente->num_rows > 0;
        $stmt_cliente->close();
        if (!$cliente_existe) {
            definir_flash("Escolha a qual cliente esse acesso pertence antes de aprovar.", "erro");
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET status = 'aprovado', cliente_id = ? WHERE id = ? AND comercio_id = ? AND nivel = 'cliente' AND status = 'pendente'");
            $stmt->bind_param("iii", $id_cliente, $id_usuario, $loja);
            $stmt->execute();
            $stmt->close();
            definir_flash("Acesso aprovado com sucesso. O cliente já pode entrar.");
        }
    } elseif ($acao === 'recusar') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'recusado' WHERE id = ? AND comercio_id = ? AND nivel = 'cliente' AND status = 'pendente'");
        $stmt->bind_param("ii", $id_usuario, $loja);
        $stmt->execute();
        $stmt->close();
        definir_flash("Solicitação recusada.");
    }
    header("Location: aprovar_acessos.php");
    exit();
}
$lista_clientes = [];
$stmt = $conn->prepare("SELECT id, nome, telefone FROM clientes WHERE comercio_id = ? ORDER BY nome");
$stmt->bind_param("i", $loja);
$stmt->execute();
$resultado_clientes = $stmt->get_result();
$stmt->close();
while ($linha_cliente = $resultado_clientes->fetch_assoc()) { $lista_clientes[] = $linha_cliente; }
$stmt = $conn->prepare("SELECT id, nome, usuario, telefone_solicitado, criado_em FROM usuarios WHERE comercio_id = ? AND nivel = 'cliente' AND status = 'pendente' ORDER BY criado_em");
$stmt->bind_param("i", $loja);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
$titulo_pagina = "Aprovar acessos";
$menu_ativo = "aprovar";
require "cabecalho.php";
?>
<h1>Solicitações de acesso de clientes</h1>
<p class="subtitulo">Confira o telefone informado, escolha o cliente correto e aprove. O cliente passa a ver somente a própria dívida.</p>
<?php mostrar_flash(); ?>
<div class="tabela-rolagem"><table>
    <tr><th>Nome</th><th>Usuário</th><th>Telefone informado</th><th>Solicitado em</th><th>Cliente / Ação</th></tr>
    <?php $total_pendentes = 0; while ($linha = $resultado->fetch_assoc()): $total_pendentes++; $numeros_solicitado = so_numeros($linha['telefone_solicitado']); ?>
    <tr>
        <td><?php echo htmlspecialchars($linha['nome']); ?></td>
        <td><?php echo htmlspecialchars($linha['usuario']); ?></td>
        <td><?php echo htmlspecialchars($linha['telefone_solicitado'] ?? ''); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($linha['criado_em'])); ?></td>
        <td>
            <form method="POST" action="aprovar_acessos.php" class="acoes">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id_usuario" value="<?php echo $linha['id']; ?>">
                <select name="id_cliente" aria-label="Cliente">
                    <option value="">Escolha o cliente...</option>
                    <?php foreach ($lista_clientes as $cliente): $numeros_cliente = so_numeros($cliente['telefone'] ?? ''); ?>
                    <option value="<?php echo $cliente['id']; ?>" <?php if ($numeros_cliente !== '' && $numeros_cliente === $numeros_solicitado) echo 'selected'; ?>><?php echo htmlspecialchars($cliente['nome'] . ' — ' . ($cliente['telefone'] ?? '')); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="acao" value="aprovar" class="botao-pequeno botao-verde">Aprovar</button>
                <button type="submit" name="acao" value="recusar" class="botao-pequeno botao-perigo" data-confirmar="Recusar esta solicitação?">Recusar</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if ($total_pendentes === 0): ?><tr><td colspan="5" class="vazio">Nenhuma solicitação pendente.</td></tr><?php endif; ?>
</table></div>
<?php require "rodape.php"; ?>
