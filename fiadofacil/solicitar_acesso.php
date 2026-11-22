<?php
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once "conexao.php";
// o pedido de acesso e sempre para a loja do endereco que o cliente abriu
$loja_publica = carregar_loja_por_slug($conn, $_GET['loja'] ?? ($_SESSION['loja_publica_slug'] ?? 'principal'));
if ($loja_publica) {
    $_SESSION['loja_publica_slug'] = $loja_publica['slug'];
}
$mensagem = "";
$tipo = "mensagem";
$nome = $telefone = $usuario_digitado = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $eh_robo = parece_robo('solicitar_acesso');
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    if (!$loja_publica || $loja_publica['status'] !== 'ativo') {
        $mensagem = "Loja não encontrada ou com acesso suspenso. Confira o endereço com a loja.";
        $tipo = "erro";
    } elseif ($eh_robo) {
        $mensagem = "Não foi possível enviar. Confira os dados e envie de novo.";
        $tipo = "erro";
    } elseif ($nome === "" || $telefone === "" || $usuario_digitado === "") {
        $mensagem = "Preencha nome, telefone e usuário.";
        $tipo = "erro";
    } elseif (!telefone_valido($telefone)) {
        $mensagem = "Informe o telefone com DDD, exatamente como está cadastrado na loja.";
        $tipo = "erro";
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario_digitado)) {
        $mensagem = "O usuário deve ter de 3 a 50 caracteres: letras, números, ponto, traço ou sublinhado (sem espaços).";
        $tipo = "erro";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha deve ter no mínimo 8 caracteres.";
        $tipo = "erro";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = "A confirmação da senha não confere.";
        $tipo = "erro";
    } else {
        $telefone = formatar_telefone($telefone);
        $id_loja = (int)$loja_publica['id'];
        $stmt_duplicado = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND comercio_id = ?");
        $stmt_duplicado->bind_param("si", $usuario_digitado, $id_loja);
        $stmt_duplicado->execute();
        $stmt_duplicado->store_result();
        $usuario_existe = $stmt_duplicado->num_rows > 0;
        $stmt_duplicado->close();
        $numeros_telefone = so_numeros($telefone);
        $stmt_pendente = $conn->prepare("SELECT id FROM usuarios WHERE comercio_id = ? AND nivel = 'cliente' AND status = 'pendente' AND " . sql_telefone_numeros("telefone_solicitado") . " = ?");
        $stmt_pendente->bind_param("is", $id_loja, $numeros_telefone);
        $stmt_pendente->execute();
        $stmt_pendente->store_result();
        $ja_tem_pedido = $stmt_pendente->num_rows > 0;
        $stmt_pendente->close();
        if ($usuario_existe) {
            $mensagem = "Este nome de usuário já está em uso. Escolha outro.";
            $tipo = "erro";
        } elseif ($ja_tem_pedido) {
            $mensagem = "Já existe uma solicitação pendente com este telefone. Aguarde o administrador aprovar.";
            $tipo = "erro";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (comercio_id, nome, usuario, senha, nivel, status, telefone_solicitado) VALUES (?, ?, ?, ?, 'cliente', 'pendente', ?)");
            $stmt->bind_param("issss", $id_loja, $nome, $usuario_digitado, $hash, $telefone);
            if ($stmt->execute()) {
                $mensagem = "Solicitação enviada! Quando o administrador aprovar, você poderá entrar com o usuário e a senha que criou.";
                $nome = $telefone = $usuario_digitado = "";
            } else {
                $mensagem = "Erro ao enviar a solicitação. Tente novamente.";
                $tipo = "erro";
            }
            $stmt->close();
        }
    }
}
$titulo_pagina = "Solicitar acesso";
$descricao_pagina = "Cliente da loja: peça seu acesso ao FiadoFácil para acompanhar a sua dívida pelo celular.";
$classe_container = "container-pequeno";
require "cabecalho.php";
?>
<h1>Solicitar acesso de cliente</h1>
<p class="subtitulo">Para ver a sua dívida nesta loja.</p>
<?php if ($mensagem): ?><div class="<?php echo $tipo; ?>" role="alert"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<form method="POST" action="solicitar_acesso.php<?php echo $loja_publica ? "?loja=" . urlencode($loja_publica['slug']) : ""; ?>">
    <?php campo_csrf(); campo_antispam('solicitar_acesso'); ?>
    <label for="nome">Nome completo</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" required>
    <label for="telefone">Telefone cadastrado na loja</label>
    <input type="text" id="telefone" name="telefone" data-mascara="telefone" placeholder="(62) 99999-9999" value="<?php echo htmlspecialchars($telefone); ?>" required>
    <label for="usuario">Usuário (login)</label>
    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_digitado); ?>" autocomplete="username" required>
    <label for="senha">Senha (mínimo 8 caracteres)</label>
    <input type="password" id="senha" name="senha" autocomplete="new-password" required>
    <label for="confirmar_senha">Confirmar senha</label>
    <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" required>
    <p class="dica aviso-privacidade">Ao enviar, você concorda com o uso do seu nome e telefone para a loja liberar o seu acesso, conforme a <a href="privacidade.php" target="_blank">Política de Privacidade</a>.</p>
    <button type="submit" class="botao-total">Enviar solicitação</button>
</form>
<div class="barra-botoes"><a href="<?php echo htmlspecialchars(endereco_login($loja_publica['slug'] ?? null)); ?>" class="botao botao-secundario botao-total">Voltar ao login</a></div>
<?php require "rodape.php"; ?>
