<?php
// Area do cliente: entra com CPF e a senha que ele mesmo criou, e ve as dividas de todas as lojas
require_once "funcoes.php";
if (!empty($_SESSION['conta_cliente_id'])) {
    header("Location: minhas_dividas.php");
    exit();
}
require_once "conexao.php";
$erro = "";
$cpf_digitado = "";
if (isset($_GET['motivo']) && $_GET['motivo'] === 'saiu') { definir_flash("Você saiu da sua conta."); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $cpf_digitado = so_numeros($_POST['cpf'] ?? '');
    $minutos_bloqueio = login_bloqueado();
    if ($minutos_bloqueio > 0) {
        $erro = "Muitas tentativas erradas. Aguarde " . $minutos_bloqueio . " minuto(s) e tente de novo.";
    } else {
        $stmt = $conn->prepare("SELECT id, nome, cpf, senha FROM contas_clientes WHERE cpf = ?");
        $stmt->bind_param("s", $cpf_digitado);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($conta && password_verify($_POST['senha'] ?? '', $conta['senha'])) {
            session_regenerate_id(true);
            // a conta do cliente nunca fica misturada com o login de funcionario
            unset($_SESSION['usuario_id'], $_SESSION['usuario_nome'], $_SESSION['usuario_nivel'], $_SESSION['comercio_id'],
                $_SESSION['loja_slug'], $_SESSION['cliente_id'], $_SESSION['plataforma'], $_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate']);
            $_SESSION['conta_cliente_id'] = (int)$conta['id'];
            $_SESSION['conta_cliente_nome'] = $conta['nome'];
            sincronizar_vinculos($conn, (int)$conta['id'], $conta['cpf']);
            header("Location: minhas_dividas.php");
            exit();
        }
        usleep(600000);
        registrar_falha_login();
        $erro = "CPF ou senha inválidos.";
    }
}
$titulo_pagina = "Área do cliente";
$descricao_pagina = "Entre com o seu CPF e veja quanto você deve em cada loja que usa o FiadoFácil.";
$classe_container = "container-pequeno";
require "cabecalho.php";
?>
<h1>Área do cliente</h1>
<p class="subtitulo">Entre com CPF e senha.</p>
<?php mostrar_flash(); ?>
<?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
<form method="POST" action="conta_entrar.php">
    <?php campo_csrf(); ?>
    <label for="cpf">CPF</label>
    <input type="text" id="cpf" name="cpf" data-mascara="cpf" inputmode="numeric" placeholder="000.000.000-00" value="<?php echo htmlspecialchars(formatar_cpf($cpf_digitado)); ?>" autocomplete="username" autofocus required>
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autocomplete="current-password" required>
    <button type="submit" class="botao-total">Entrar</button>
</form>
<div class="barra-botoes"><a href="conta_criar.php" class="botao botao-secundario botao-total">Ainda não tenho conta: criar agora</a></div>
<p class="trocar-loja">Trabalha numa loja? <a href="login.php">Entrar como funcionário</a></p>
<?php require "rodape.php"; ?>
