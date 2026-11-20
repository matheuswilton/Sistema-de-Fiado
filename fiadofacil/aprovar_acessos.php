<?php
require_once "verifica_admin.php";
require_once "conexao.php";
$loja = loja_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    // contas por CPF: a loja confirma (ou recusa/desliga) a ligacao com o cadastro DELA
    $id_vinculo = (int)($_POST['id_vinculo'] ?? 0);
    $novos_status = ['aprovar_conta' => ['aprovado', 'pendente', "Conta confirmada. O cliente já vê a dívida desta loja."],
                     'recusar_conta' => ['recusado', 'pendente', "Ligação recusada."],
                     'desligar_conta' => ['recusado', 'aprovado', "Conta desligada. O cliente não vê mais a dívida desta loja."]];
    if (isset($novos_status[$acao])) {
        [$novo, $atual, $texto] = $novos_status[$acao];
        $stmt = $conn->prepare("UPDATE contas_vinculos SET status = ? WHERE id = ? AND comercio_id = ? AND status = ?");
        $stmt->bind_param("siis", $novo, $id_vinculo, $loja, $atual);
        $stmt->execute();
        $mudou = $stmt->affected_rows > 0;
        $stmt->close();
        definir_flash($mudou ? $texto : "Pedido não encontrado.", $mudou ? "mensagem" : "erro");
        header("Location: aprovar_acessos.php");
        exit();
    }
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
// ligacoes das contas por CPF com clientes desta loja
$stmt = $conn->prepare("SELECT v.id, v.status, v.criado_em, a.nome AS conta_nome, a.cpf, a.telefone AS conta_telefone, c.id AS cliente_id, c.nome AS cliente_nome, c.telefone AS cliente_telefone
    FROM contas_vinculos v JOIN contas_clientes a ON a.id = v.conta_id JOIN clientes c ON c.id = v.cliente_id AND c.comercio_id = v.comercio_id
    WHERE v.comercio_id = ? AND v.status IN ('pendente','aprovado') ORDER BY v.status DESC, v.criado_em");
$stmt->bind_param("i", $loja);
$stmt->execute();
$contas_cpf = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$contas_pendentes = array_filter($contas_cpf, function ($v) { return $v['status'] === 'pendente'; });
$contas_ligadas = array_filter($contas_cpf, function ($v) { return $v['status'] === 'aprovado'; });
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

<h2 class="titulo-secao">Contas de clientes por CPF</h2>
<p class="subtitulo">O cliente criou a conta com o CPF que está no cadastro da loja. Confira o nome e o telefone e confirme que é a mesma pessoa.</p>
<div class="tabela-rolagem"><table>
    <tr><th>Conta</th><th>CPF</th><th>Telefone da conta</th><th>Cadastro na loja</th><th>Ação</th></tr>
    <?php foreach ($contas_pendentes as $v): $confere = so_numeros($v['conta_telefone']) === so_numeros($v['cliente_telefone'] ?? ''); ?>
    <tr>
        <td><?php echo htmlspecialchars($v['conta_nome']); ?></td>
        <td><?php echo htmlspecialchars(mascarar_cpf($v['cpf'])); ?></td>
        <td><?php echo htmlspecialchars($v['conta_telefone']); ?> <?php echo $confere ? '<span class="selo selo-ok">confere</span>' : '<span class="selo selo-alerta">diferente</span>'; ?></td>
        <td><a href="cliente.php?id=<?php echo (int)$v['cliente_id']; ?>"><?php echo htmlspecialchars($v['cliente_nome']); ?></a> · <?php echo htmlspecialchars($v['cliente_telefone'] ?? ''); ?></td>
        <td>
            <form method="POST" action="aprovar_acessos.php" class="acoes">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id_vinculo" value="<?php echo (int)$v['id']; ?>">
                <button type="submit" name="acao" value="aprovar_conta" class="botao-pequeno botao-verde">Confirmar</button>
                <button type="submit" name="acao" value="recusar_conta" class="botao-pequeno botao-perigo" data-confirmar="Recusar esta ligação?">Recusar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$contas_pendentes): ?><tr><td colspan="5" class="vazio">Nenhuma conta aguardando confirmação.</td></tr><?php endif; ?>
</table></div>
<?php if ($contas_ligadas): ?>
<h3>Contas já confirmadas</h3>
<div class="tabela-rolagem"><table>
    <tr><th>Conta</th><th>CPF</th><th>Cadastro na loja</th><th>Ação</th></tr>
    <?php foreach ($contas_ligadas as $v): ?>
    <tr>
        <td><?php echo htmlspecialchars($v['conta_nome']); ?></td>
        <td><?php echo htmlspecialchars(mascarar_cpf($v['cpf'])); ?></td>
        <td><a href="cliente.php?id=<?php echo (int)$v['cliente_id']; ?>"><?php echo htmlspecialchars($v['cliente_nome']); ?></a></td>
        <td><form method="POST" action="aprovar_acessos.php"><?php campo_csrf(); ?><input type="hidden" name="id_vinculo" value="<?php echo (int)$v['id']; ?>"><button type="submit" name="acao" value="desligar_conta" class="botao-pequeno botao-secundario" data-confirmar="Desligar esta conta? O cliente deixa de ver a dívida desta loja.">Desligar</button></form></td>
    </tr>
    <?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require "rodape.php"; ?>
