<?php
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['usuario_nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
    exit();
}
$erro = "";
$usuario_digitado = "";
if (isset($_GET['motivo']) && $_GET['motivo'] === 'bloqueado') { definir_flash("Sua sessão foi encerrada. Entre novamente.", "erro"); }
if (isset($_GET['motivo']) && $_GET['motivo'] === 'saiu') { definir_flash("Você saiu do sistema."); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $minutos_bloqueio = login_bloqueado();
    if ($minutos_bloqueio > 0) {
        $erro = "Muitas tentativas erradas. Aguarde " . $minutos_bloqueio . " minuto(s) e tente de novo.";
    } else {
    require_once "conexao.php";
    $senha_digitada = $_POST['senha'] ?? '';
    $stmt = $conn->prepare("SELECT id, nome, senha, nivel, status, cliente_id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario_digitado);
    $stmt->execute();
    $linha_usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($linha_usuario && password_verify($senha_digitada, $linha_usuario['senha'])) {
        if ($linha_usuario['status'] === 'pendente') {
            $erro = "Sua solicitação de acesso ainda está aguardando a aprovação do administrador.";
        } elseif ($linha_usuario['status'] === 'recusado') {
            $erro = "Sua solicitação de acesso foi recusada pelo administrador.";
        } elseif ($linha_usuario['status'] === 'bloqueado') {
            $erro = "Seu acesso foi bloqueado pelo administrador.";
        } else {
            session_regenerate_id(true);
            unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate']);
            $_SESSION['usuario_id'] = $linha_usuario['id'];
            $_SESSION['usuario_nome'] = $linha_usuario['nome'];
            $_SESSION['usuario_nivel'] = $linha_usuario['nivel'];
            $_SESSION['cliente_id'] = $linha_usuario['cliente_id'];
            header("Location: " . ($linha_usuario['nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
            exit();
        }
    } else {
        usleep(600000);
        registrar_falha_login();
        $erro = "Usuário ou senha inválidos.";
    }
    }
}
$titulo_pagina = "Entrar";
$descricao_pagina = "Entre no FiadoFácil para anotar compras no fiado, registrar pagamentos e ver quem está devendo.";
$classe_container = "container-pequeno";
require "cabecalho.php";
?>
<h1>Entrar</h1>
<p class="subtitulo">Acesse com seu usuário e senha.</p>
<?php mostrar_flash(); ?>
<?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
<form method="POST" action="login.php">
    <?php campo_csrf(); ?>
    <label for="usuario">Usuário</label>
    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_digitado); ?>" autocomplete="username" autofocus required>
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autocomplete="current-password" required>
    <button type="submit" class="botao-total">Entrar</button>
</form>
<div class="barra-botoes"><a href="solicitar_acesso.php" class="botao botao-secundario botao-total">Sou cliente e quero ver minha dívida</a></div>
<?php require "rodape.php"; ?>
