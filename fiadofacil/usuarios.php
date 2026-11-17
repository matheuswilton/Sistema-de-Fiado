<?php
// Gerenciamento de funcionarios - somente administrador
require_once "verifica_admin.php";
require_once "conexao.php";
$mensagem = "";
$nome = $login = "";
$nivel = "comum";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $acao = $_POST['acao'] ?? 'criar';
    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $login = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? 'comum';
        if ($nome === "" || $login === "") {
            $mensagem = "Nome e usuário são obrigatórios.";
        } elseif (!in_array($nivel, ['admin', 'comum'])) {
            $mensagem = "Nível de acesso inválido.";
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login)) {
            $mensagem = "O usuário deve ter de 3 a 50 caracteres: letras, números, ponto, traço ou sublinhado (sem espaços).";
        } elseif (strlen($senha) < 8) {
            $mensagem = "A senha deve ter no mínimo 8 caracteres.";
        } else {
            $stmt_duplicado = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt_duplicado->bind_param("s", $login);
            $stmt_duplicado->execute();
            $stmt_duplicado->store_result();
            $duplicado = $stmt_duplicado->num_rows > 0;
            $stmt_duplicado->close();
            if ($duplicado) {
                $mensagem = "Este nome de usuário já está em uso.";
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $login, $hash, $nivel);
                if ($stmt->execute()) {
                    $stmt->close();
                    definir_flash("Usuário cadastrado com sucesso!");
                    header("Location: usuarios.php");
                    exit();
                }
                $mensagem = "Erro ao cadastrar usuário.";
                $stmt->close();
            }
        }
    } else {
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        if ($id_usuario === (int)$_SESSION['usuario_id'] && $acao !== 'trocar_senha') {
            definir_flash("Você não pode bloquear a sua própria conta.", "erro");
        } elseif ($acao === 'bloquear') {
            $stmt = $conn->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ? AND nivel IN ('admin','comum')");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
            definir_flash("Usuário bloqueado. Ele não consegue mais entrar.");
        } elseif ($acao === 'desbloquear') {
            $stmt = $conn->prepare("UPDATE usuarios SET status = 'aprovado' WHERE id = ? AND nivel IN ('admin','comum')");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
            definir_flash("Usuário desbloqueado.");
        } elseif ($acao === 'trocar_senha') {
            $nova_senha = $_POST['nova_senha'] ?? '';
            if (strlen($nova_senha) < 8) {
                definir_flash("A nova senha deve ter no mínimo 8 caracteres.", "erro");
            } else {
                $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND nivel IN ('admin','comum')");
                $stmt->bind_param("si", $hash, $id_usuario);
                $stmt->execute();
                $stmt->close();
                definir_flash("Senha do usuário alterada com sucesso.");
            }
        }
        header("Location: usuarios.php");
        exit();
    }
}
$por_pagina = 10;
$total = (int)$conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel IN ('admin','comum')")->fetch_row()[0];
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) { $pagina = 1; }
if ($pagina > $total_paginas) { $pagina = $total_paginas; }
$offset = ($pagina - 1) * $por_pagina;
$stmt = $conn->prepare("SELECT id, nome, usuario, nivel, status, criado_em FROM usuarios WHERE nivel IN ('admin','comum') ORDER BY nome LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$titulo_pagina = "Usuários";
$menu_ativo = "usuarios";
require "cabecalho.php";
?>
<h1>Usuários do sistema</h1>
<p class="subtitulo">Funcionários que usam o sistema. Os acessos dos clientes ficam em “Acessos de clientes”.</p>
<?php mostrar_flash(); ?>
<?php if ($mensagem): ?><div class="erro" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<div class="painel">
<h2 style="margin-top:0">Novo usuário</h2>
<form method="POST" action="usuarios.php">
    <?php campo_csrf(); ?>
    <input type="hidden" name="acao" value="criar">
    <div class="duas-colunas">
        <div><label for="nome">Nome completo</label><input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" required></div>
        <div><label for="usuario">Usuário (login)</label><input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($login); ?>" autocomplete="off" required></div>
        <div><label for="senha">Senha (mínimo 8 caracteres)</label><input type="password" id="senha" name="senha" autocomplete="new-password" required></div>
        <div><label for="nivel">Perfil</label>
            <select id="nivel" name="nivel">
                <option value="comum" <?php if ($nivel === 'comum') echo 'selected'; ?>>Usuário comum</option>
                <option value="admin" <?php if ($nivel === 'admin') echo 'selected'; ?>>Administrador</option>
            </select></div>
    </div>
    <div class="barra-botoes"><button type="submit">Cadastrar usuário</button></div>
</form>
</div>
<div class="tabela-rolagem"><table>
    <tr><th>Nome</th><th>Usuário</th><th>Perfil</th><th>Situação</th><th>Criado em</th><th>Ações</th></tr>
    <?php while ($linha = $resultado->fetch_assoc()): $eh_eu = ((int)$linha['id'] === (int)$_SESSION['usuario_id']); ?>
    <tr>
        <td><?php echo htmlspecialchars($linha['nome']); ?><?php if ($eh_eu) echo ' <span class="selo selo-neutro">você</span>'; ?></td>
        <td><?php echo htmlspecialchars($linha['usuario']); ?></td>
        <td><?php echo $linha['nivel'] === 'admin' ? 'Administrador' : 'Usuário comum'; ?></td>
        <td><?php echo $linha['status'] === 'bloqueado' ? '<span class="selo selo-alerta">Bloqueado</span>' : '<span class="selo selo-ok">Ativo</span>'; ?></td>
        <td><?php echo date('d/m/Y', strtotime($linha['criado_em'])); ?></td>
        <td>
            <?php if ($eh_eu): ?>
                <a href="minha_senha.php" class="botao botao-pequeno botao-secundario">Trocar minha senha</a>
            <?php else: ?>
            <form method="POST" action="usuarios.php" class="acoes">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id_usuario" value="<?php echo $linha['id']; ?>">
                <input type="password" name="nova_senha" placeholder="Nova senha (mín. 8)" autocomplete="new-password" aria-label="Nova senha">
                <button type="submit" name="acao" value="trocar_senha" class="botao-pequeno">Trocar senha</button>
                <?php if ($linha['status'] === 'bloqueado'): ?>
                    <button type="submit" name="acao" value="desbloquear" class="botao-pequeno">Desbloquear</button>
                <?php else: ?>
                    <button type="submit" name="acao" value="bloquear" class="botao-pequeno botao-perigo" data-confirmar="Bloquear o acesso deste usuário?">Bloquear</button>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table></div>
<?php mostrar_paginacao($pagina, $total_paginas); ?>
<?php $stmt->close(); require "rodape.php"; ?>
