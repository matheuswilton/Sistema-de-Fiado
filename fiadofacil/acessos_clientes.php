<?php
require_once "verifica_admin.php";
require_once "conexao.php";
$loja = loja_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'bloquear') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ? AND comercio_id = ? AND nivel = 'cliente' AND status = 'aprovado'");
        $stmt->bind_param("ii", $id_usuario, $loja);
        $stmt->execute();
        $stmt->close();
        definir_flash("Acesso bloqueado. O cliente não consegue mais entrar.");
    } elseif ($acao === 'desbloquear') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'aprovado' WHERE id = ? AND comercio_id = ? AND nivel = 'cliente' AND status = 'bloqueado'");
        $stmt->bind_param("ii", $id_usuario, $loja);
        $stmt->execute();
        $stmt->close();
        definir_flash("Acesso desbloqueado.");
    } elseif ($acao === 'trocar_senha') {
        $nova_senha = $_POST['nova_senha'] ?? '';
        if (strlen($nova_senha) < 8) {
            definir_flash("A nova senha deve ter no mínimo 8 caracteres.", "erro");
        } else {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND comercio_id = ? AND nivel = 'cliente'");
            $stmt->bind_param("sii", $hash, $id_usuario, $loja);
            $stmt->execute();
            $stmt->close();
            definir_flash("Senha alterada com sucesso.");
        }
    }
    header("Location: acessos_clientes.php");
    exit();
}
$stmt = $conn->prepare("SELECT u.id, u.nome, u.usuario, u.status, c.nome AS nome_cliente FROM usuarios u LEFT JOIN clientes c ON c.id = u.cliente_id WHERE u.comercio_id = ? AND u.nivel = 'cliente' AND u.status IN ('aprovado','bloqueado') ORDER BY u.nome");
$stmt->bind_param("i", $loja);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
$titulo_pagina = "Acessos de clientes";
$menu_ativo = "acessos";
require "cabecalho.php";
?>
<h1>Acessos de clientes</h1>
<p class="subtitulo">Clientes que já podem ver a própria dívida. Aqui você bloqueia, desbloqueia e troca a senha.</p>
<?php mostrar_flash(); ?>
<div class="tabela-rolagem"><table>
    <tr><th>Usuário</th><th>Cliente vinculado</th><th>Situação</th><th>Ações</th></tr>
    <?php $total_acessos = 0; while ($linha = $resultado->fetch_assoc()): $total_acessos++; ?>
    <tr>
        <td><?php echo htmlspecialchars($linha['usuario']); ?></td>
        <td><?php echo htmlspecialchars($linha['nome_cliente'] ?? ''); ?></td>
        <td><?php echo $linha['status'] === 'bloqueado' ? '<span class="selo selo-alerta">Bloqueado</span>' : '<span class="selo selo-ok">Ativo</span>'; ?></td>
        <td>
            <form method="POST" action="acessos_clientes.php" class="acoes">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id_usuario" value="<?php echo $linha['id']; ?>">
                <input type="password" name="nova_senha" placeholder="Nova senha (mín. 8)" autocomplete="new-password" aria-label="Nova senha">
                <button type="submit" name="acao" value="trocar_senha" class="botao-pequeno">Trocar senha</button>
                <?php if ($linha['status'] === 'bloqueado'): ?>
                    <button type="submit" name="acao" value="desbloquear" class="botao-pequeno">Desbloquear</button>
                <?php else: ?>
                    <button type="submit" name="acao" value="bloquear" class="botao-pequeno botao-perigo" data-confirmar="Bloquear o acesso deste cliente?">Bloquear</button>
                <?php endif; ?>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if ($total_acessos === 0): ?><tr><td colspan="4" class="vazio">Nenhum cliente com acesso ainda.</td></tr><?php endif; ?>
</table></div>
<?php require "rodape.php"; ?>
