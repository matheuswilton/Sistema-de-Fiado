<?php
require_once "funcoes.php";
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['usuario_nivel'] === 'cliente' ? "minha_divida.php" : "dashboard.php"));
    exit();
}
$erro = "";
$usuario_digitado = "";
require_once "conexao.php";
// Qual loja? Pelo endereco (login.php?loja=mercearia-do-ze ou /loja/mercearia-do-ze), pela ultima usada,
// ou a pessoa digita o nome da loja (passo "Qual e a sua loja?")
$aviso_loja = "";
$lojas_encontradas = [];
if (isset($_GET['trocar'])) {
    unset($_SESSION['loja_publica_slug']);
}
$procura = trim((string)($_GET['procurar'] ?? ''));
if ($procura !== '') {
    $procura = mb_substr($procura, 0, 60);
    $slug_procurado = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $procura)), '-'));
    $parecido = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $procura) . '%';
    $stmt = $conn->prepare("SELECT slug, nome FROM comercios WHERE status = 'ativo' AND (slug = ? OR nome LIKE ?) ORDER BY nome LIMIT 10");
    $stmt->bind_param("ss", $slug_procurado, $parecido);
    $stmt->execute();
    $lojas_encontradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (count($lojas_encontradas) === 1) {
        header("Location: " . endereco_login($lojas_encontradas[0]['slug']));
        exit();
    }
    if (!$lojas_encontradas) {
        $aviso_loja = "Não encontramos nenhuma loja com \"" . $procura . "\". Confira o nome com a loja.";
    }
}
$slug_pedido = isset($_GET['trocar']) ? null : ($_GET['loja'] ?? ($_SESSION['loja_publica_slug'] ?? null));
$loja_publica = $slug_pedido !== null ? carregar_loja_por_slug($conn, $slug_pedido) : null;
if ($loja_publica) {
    $_SESSION['loja_publica_slug'] = $loja_publica['slug'];
} elseif ($slug_pedido !== null && isset($_GET['loja'])) {
    $aviso_loja = "Não encontramos a loja desse endereço. Digite o nome da loja.";
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
            unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate'], $_SESSION['plataforma'], $_SESSION['conta_cliente_id'], $_SESSION['conta_cliente_nome']);
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
<?php if (!$loja_publica): ?>
<h1>Entrar</h1>
<p class="subtitulo">Digite o nome da loja.</p>
<?php mostrar_flash(); ?>
<?php if ($aviso_loja): ?><div class="erro" role="alert"><?php echo htmlspecialchars($aviso_loja); ?></div><?php endif; ?>
<form method="GET" action="login.php" class="form-loja">
    <label for="procurar">Nome da loja</label>
    <input type="text" id="procurar" name="procurar" value="<?php echo htmlspecialchars($procura); ?>" placeholder="Ex.: Mercearia do Zé" maxlength="60" autocomplete="organization" autofocus required>
    <button type="submit" class="botao-total">Continuar</button>
</form>
<?php if (count($lojas_encontradas) > 1): ?>
<div class="lista-lojas" role="list" aria-label="Lojas encontradas">
    <p class="dica">Escolha a loja:</p>
    <?php foreach ($lojas_encontradas as $encontrada): ?>
    <a role="listitem" class="loja-opcao" href="<?php echo htmlspecialchars(endereco_login($encontrada['slug'])); ?>"><?php echo icone('loja'); ?><span><?php echo htmlspecialchars($encontrada['nome']); ?></span><?php echo icone('seta'); ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="separador-ou"><span>ou</span></div>
<a href="conta_entrar.php" class="botao botao-secundario botao-total">Sou cliente: ver minhas dívidas em todas as lojas</a>
<?php else: ?>
<h1>Entrar</h1>
<p class="subtitulo">Acesse com seu usuário e senha.</p>
<?php mostrar_flash(); ?>
<?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
<form method="POST" action="<?php echo htmlspecialchars(endereco_login($loja_publica['slug'])); ?>">
    <?php campo_csrf(); ?>
    <label for="usuario">Usuário</label>
    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_digitado); ?>" autocomplete="username" autofocus required>
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autocomplete="current-password" required>
    <button type="submit" class="botao-total">Entrar</button>
</form>
<div class="barra-botoes"><a href="conta_entrar.php" class="botao botao-secundario botao-total">Sou cliente: ver minhas dívidas</a></div>
<p class="trocar-loja">Não é a <?php echo htmlspecialchars($loja_publica['nome']); ?>? <a href="login.php?trocar=1">Trocar de loja</a></p>
<?php endif; ?>
<?php require "rodape.php"; ?>
