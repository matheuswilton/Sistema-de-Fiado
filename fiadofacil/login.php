<?php
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['usuario_nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
    exit();
}
$erro = "";
$usuario_digitado = "";
require_once "conexao.php";
// Qual loja? Pelo endereco (login.php?loja=mercearia-do-ze ou /loja/mercearia-do-ze); sem endereco, a loja principal
$loja_publica = carregar_loja_por_slug($conn, $_GET['loja'] ?? ($_SESSION['loja_publica_slug'] ?? 'principal'));
if ($loja_publica) {
    $_SESSION['loja_publica_slug'] = $loja_publica['slug'];
}
if (isset($_GET['motivo']) && $_GET['motivo'] === 'bloqueado') { definir_flash("Sua sessão foi encerrada. Entre novamente.", "erro"); }
if (isset($_GET['motivo']) && $_GET['motivo'] === 'loja_bloqueada') { definir_flash("O acesso a esta loja está suspenso. Fale com o responsável pela loja.", "erro"); }
if (isset($_GET['motivo']) && $_GET['motivo'] === 'saiu') { definir_flash("Você saiu do sistema."); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $minutos_bloqueio = login_bloqueado();
    if (!$loja_publica) {
        $erro = "Loja não encontrada. Confira o endereço com a loja.";
    } elseif ($loja_publica['status'] !== 'ativo') {
        $erro = "O acesso a esta loja está suspenso. Fale com o responsável pela loja.";
    } elseif ($minutos_bloqueio > 0) {
        $erro = "Muitas tentativas erradas. Aguarde " . $minutos_bloqueio . " minuto(s) e tente de novo.";
    } else {
    $senha_digitada = $_POST['senha'] ?? '';
    // procura o usuario SOMENTE dentro da loja deste endereco
    $id_loja = (int)$loja_publica['id'];
    $stmt = $conn->prepare("SELECT id, nome, senha, nivel, status, cliente_id FROM usuarios WHERE usuario = ? AND comercio_id = ? AND nivel IN ('admin','comum','cliente')");
    $stmt->bind_param("si", $usuario_digitado, $id_loja);
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
            unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate'], $_SESSION['plataforma']);
            $_SESSION['comercio_id'] = $id_loja;
            $_SESSION['loja_slug'] = $loja_publica['slug'];
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
$titulo_pagina = $loja_publica && $loja_publica['slug'] !== 'principal' ? "Entrar · " . $loja_publica['nome'] : "Entrar";
$descricao_pagina = "Entre no FiadoFácil para anotar compras no fiado, registrar pagamentos e ver quem está devendo.";
$classe_container = "container-pequeno";
require "cabecalho.php";
?>
<h1>Entrar</h1>
<p class="subtitulo">Acesse com seu usuário e senha.</p>
<?php mostrar_flash(); ?>
<?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
<?php if (!$loja_publica): ?>
<div class="erro" role="alert">Loja não encontrada. Confira o endereço que a loja passou para você.</div>
<?php else: ?>
<form method="POST" action="<?php echo htmlspecialchars(endereco_login($loja_publica['slug'])); ?>">
    <?php campo_csrf(); ?>
    <label for="usuario">Usuário</label>
    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_digitado); ?>" autocomplete="username" autofocus required>
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autocomplete="current-password" required>
    <button type="submit" class="botao-total">Entrar</button>
</form>
<div class="barra-botoes"><a href="solicitar_acesso.php?loja=<?php echo urlencode($loja_publica['slug']); ?>" class="botao botao-secundario botao-total">Sou cliente e quero ver minha dívida</a></div>
<?php endif; ?>
<?php require "rodape.php"; ?>
