<?php
// Painel do DONO DA PLATAFORMA: ve todas as lojas, cria, bloqueia e entra em qualquer loja como administrador.
require_once "funcoes.php";
require_once "conexao.php";

$temas_disponiveis = ['padrao' => 'Azul FiadoFácil', 'mercearia' => 'Verde mercearia', 'padaria' => 'Caramelo padaria', 'acougue' => 'Vinho açougue'];
$erro = "";

// endereco amigavel a partir do nome: "Mercearia do Zé" -> "mercearia-do-ze"
function gerar_slug($texto) {
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e','í'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim(substr($texto, 0, 60), '-');
}

// confere, a cada acesso, se o dono da plataforma continua liberado
function plataforma_valida($conn) {
    if (!eh_plataforma() || !isset($_SESSION['usuario_id'])) { return false; }
    $stmt = $conn->prepare("SELECT status, nivel FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $linha && $linha['status'] === 'aprovado' && $linha['nivel'] === 'plataforma';
}

if (isset($_GET['motivo']) && $_GET['motivo'] === 'saiu') { definir_flash("Você saiu do painel."); }

// ---------- entrar no painel ----------
if (!eh_plataforma()) {
    if (isset($_SESSION['usuario_id'])) {
        // alguem de uma loja tentando abrir o painel: volta para o sistema da loja
        header("Location: dashboard.php");
        exit();
    }
    $usuario_digitado = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $usuario_digitado = trim($_POST['usuario'] ?? '');
        $minutos = login_bloqueado();
        if ($minutos > 0) {
            $erro = "Muitas tentativas erradas. Aguarde " . $minutos . " minuto(s).";
        } else {
            $stmt = $conn->prepare("SELECT id, nome, senha, status FROM usuarios WHERE usuario = ? AND nivel = 'plataforma' AND comercio_id IS NULL");
            $stmt->bind_param("s", $usuario_digitado);
            $stmt->execute();
            $linha = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($linha && $linha['status'] === 'aprovado' && password_verify($_POST['senha'] ?? '', $linha['senha'])) {
                session_regenerate_id(true);
                unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate'], $_SESSION['loja_slug']);
                $_SESSION['usuario_id'] = $linha['id'];
                $_SESSION['usuario_nome'] = $linha['nome'];
                $_SESSION['usuario_nivel'] = 'plataforma';
                $_SESSION['plataforma'] = true;
                $_SESSION['comercio_id'] = 0;
                header("Location: painel.php");
                exit();
            }
            usleep(600000);
            registrar_falha_login();
            $erro = "Usuário ou senha inválidos.";
        }
    }
    painel_topo("Painel da plataforma", false);
    ?>
    <section class="painel-login">
        <h1>Painel da plataforma</h1>
        <p class="subtitulo">Acesso exclusivo do dono do FiadoFácil.</p>
        <?php mostrar_flash(); ?>
        <?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
        <form method="POST" action="painel.php">
            <?php campo_csrf(); ?>
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_digitado); ?>" autocomplete="username" autofocus required>
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" autocomplete="current-password" required>
            <button type="submit" class="botao-total">Entrar no painel</button>
        </form>
    </section>
    <?php
    painel_rodape();
    exit();
}

if (!plataforma_valida($conn)) {
    encerrar_sessao("bloqueado");
}

// ---------- sair da loja e voltar ao painel ----------
if (isset($_GET['sair_da_loja'])) {
    $_SESSION['comercio_id'] = 0;
    $_SESSION['usuario_nivel'] = 'plataforma';
    unset($_SESSION['loja_slug']);
    header("Location: painel.php");
    exit();
}

// ---------- acoes ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $acao = $_POST['acao'] ?? '';
    $id_loja = (int)($_POST['id_loja'] ?? 0);
    if ($acao === 'entrar') {
        $loja = carregar_loja($conn, $id_loja);
        if ($loja) {
            $_SESSION['comercio_id'] = (int)$loja['id'];
            $_SESSION['loja_slug'] = $loja['slug'];
            $_SESSION['usuario_nivel'] = 'admin';
            header("Location: dashboard.php");
            exit();
        }
        definir_flash("Loja não encontrada.", "erro");
    } elseif ($acao === 'bloquear' || $acao === 'desbloquear') {
        $novo_status = $acao === 'bloquear' ? 'bloqueado' : 'ativo';
        $stmt = $conn->prepare("UPDATE comercios SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_status, $id_loja);
        $stmt->execute();
        $stmt->close();
        definir_flash($acao === 'bloquear' ? "Loja bloqueada: ninguém dela consegue entrar até você desbloquear." : "Loja desbloqueada.");
    } elseif ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $slug = gerar_slug($_POST['slug'] ?? '') ?: gerar_slug($nome);
        $tema = array_key_exists($_POST['tema'] ?? '', $temas_disponiveis) ? $_POST['tema'] : 'padrao';
        $admin_nome = trim($_POST['admin_nome'] ?? '');
        $admin_usuario = trim($_POST['admin_usuario'] ?? '');
        $admin_senha = $_POST['admin_senha'] ?? '';
        if ($nome === '' || $admin_nome === '' || $admin_usuario === '') {
            definir_flash("Preencha o nome da loja e os dados do administrador.", "erro");
        } elseif (strlen($slug) < 2) {
            definir_flash("Informe um endereço válido (letras, números e traço).", "erro");
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $admin_usuario)) {
            definir_flash("O usuário do administrador deve ter de 3 a 50 caracteres, sem espaços.", "erro");
        } elseif (strlen($admin_senha) < 8) {
            definir_flash("A senha do administrador deve ter no mínimo 8 caracteres.", "erro");
        } elseif (carregar_loja_por_slug($conn, $slug)) {
            definir_flash("Já existe uma loja com o endereço /loja/" . $slug . ". Escolha outro.", "erro");
        } else {
            $conn->begin_transaction();
            $stmt = $conn->prepare("INSERT INTO comercios (nome, slug, tema) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $slug, $tema);
            $criou = $stmt->execute();
            $novo_id = $conn->insert_id;
            $stmt->close();
            if ($criou) {
                $hash = password_hash($admin_senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (comercio_id, nome, usuario, senha, nivel, status) VALUES (?, ?, ?, ?, 'admin', 'aprovado')");
                $stmt->bind_param("isss", $novo_id, $admin_nome, $admin_usuario, $hash);
                $criou = $stmt->execute();
                $stmt->close();
            }
            if ($criou) {
                $conn->commit();
                definir_flash("Loja criada! Endereço: /loja/" . $slug . " · administrador: " . $admin_usuario);
            } else {
                $conn->rollback();
                definir_flash("Não foi possível criar a loja. Tente novamente.", "erro");
            }
        }
    }
    header("Location: painel.php");
    exit();
}

// ---------- dados do painel ----------
$lojas = $conn->query("
    SELECT m.id, m.nome, m.slug, m.tema, m.status, m.criado_em,
        (SELECT COUNT(*) FROM clientes c WHERE c.comercio_id = m.id) AS clientes,
        (SELECT COUNT(*) FROM usuarios u WHERE u.comercio_id = m.id AND u.nivel IN ('admin','comum')) AS funcionarios,
        (SELECT COUNT(*) FROM usuarios u WHERE u.comercio_id = m.id AND u.nivel = 'cliente' AND u.status = 'aprovado') AS clientes_com_acesso,
        (SELECT COALESCE(SUM(co.valor), 0) FROM compras co JOIN clientes c ON c.id = co.cliente_id WHERE c.comercio_id = m.id)
        - (SELECT COALESCE(SUM(p.valor), 0) FROM pagamentos p JOIN clientes c ON c.id = p.cliente_id WHERE c.comercio_id = m.id) AS a_receber
    FROM comercios m
    ORDER BY m.id
")->fetch_all(MYSQLI_ASSOC);
$total_clientes = array_sum(array_column($lojas, 'clientes'));
$total_receber = array_sum(array_column($lojas, 'a_receber'));
$lojas_ativas = count(array_filter($lojas, function ($l) { return $l['status'] === 'ativo'; }));

painel_topo("Painel da plataforma", true);
?>
<div class="cabecalho-pagina">
    <div><h1>Painel da plataforma</h1><p class="subtitulo">Todas as lojas que usam o FiadoFácil. Entre em qualquer uma como administrador.</p></div>
</div>
<?php mostrar_flash(); ?>
<div class="cards">
    <div class="card card-com-icone"><span class="card-icone"><?php echo icone('inicio'); ?></span><div><span class="card-titulo">Lojas</span><span class="card-valor"><?php echo count($lojas); ?></span></div></div>
    <div class="card card-ok card-com-icone"><span class="card-icone"><?php echo icone('escudo'); ?></span><div><span class="card-titulo">Lojas ativas</span><span class="card-valor"><?php echo $lojas_ativas; ?></span></div></div>
    <div class="card card-com-icone"><span class="card-icone"><?php echo icone('clientes'); ?></span><div><span class="card-titulo">Clientes em todas as lojas</span><span class="card-valor"><?php echo $total_clientes; ?></span></div></div>
    <div class="card card-ok card-com-icone"><span class="card-icone"><?php echo icone('pagamento'); ?></span><div><span class="card-titulo">A receber (todas as lojas)</span><span class="card-valor"><?php echo dinheiro($total_receber); ?></span></div></div>
</div>

<div class="grade-lojas">
<?php foreach ($lojas as $loja): $endereco = "loja/" . $loja['slug']; ?>
    <article class="loja-cartao tema-<?php echo htmlspecialchars($loja['tema']); ?>">
        <div class="loja-faixa"><span><?php echo htmlspecialchars($loja['nome']); ?></span>
            <span class="selo <?php echo $loja['status'] === 'ativo' ? 'selo-ok' : 'selo-alerta'; ?>"><?php echo $loja['status'] === 'ativo' ? 'Ativa' : 'Bloqueada'; ?></span></div>
        <dl class="loja-numeros">
            <div><dt><?php echo (int)$loja['clientes']; ?></dt><dd>clientes</dd></div>
            <div><dt><?php echo (int)$loja['funcionarios']; ?></dt><dd>funcionários</dd></div>
            <div><dt><?php echo (int)$loja['clientes_com_acesso']; ?></dt><dd>com acesso</dd></div>
            <div><dt><?php echo dinheiro($loja['a_receber']); ?></dt><dd>a receber</dd></div>
        </dl>
        <p class="loja-endereco">Endereço: <a href="<?php echo htmlspecialchars(endereco_login($loja['slug'])); ?>" target="_blank">/<?php echo htmlspecialchars($endereco); ?></a></p>
        <div class="acoes">
            <form method="POST" action="painel.php"><?php campo_csrf(); ?><input type="hidden" name="id_loja" value="<?php echo (int)$loja['id']; ?>"><button type="submit" name="acao" value="entrar" class="botao-pequeno">Entrar como administrador</button></form>
            <?php if ($loja['status'] === 'ativo'): ?>
            <form method="POST" action="painel.php" data-confirmar="Bloquear a loja <?php echo htmlspecialchars($loja['nome'], ENT_QUOTES); ?>? Ninguém dela conseguirá entrar."><?php campo_csrf(); ?><input type="hidden" name="id_loja" value="<?php echo (int)$loja['id']; ?>"><button type="submit" name="acao" value="bloquear" class="botao-pequeno botao-secundario">Bloquear</button></form>
            <?php else: ?>
            <form method="POST" action="painel.php"><?php campo_csrf(); ?><input type="hidden" name="id_loja" value="<?php echo (int)$loja['id']; ?>"><button type="submit" name="acao" value="desbloquear" class="botao-pequeno botao-verde">Desbloquear</button></form>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</div>

<section class="painel nova-loja">
    <h2>Criar nova loja</h2>
    <form method="POST" action="painel.php" class="grade-form">
        <?php campo_csrf(); ?>
        <input type="hidden" name="acao" value="criar">
        <div class="campo"><label for="nome">Nome da loja *</label><input type="text" id="nome" name="nome" maxlength="100" required placeholder="Ex.: Lanchonete da Praça"></div>
        <div class="campo"><label for="slug">Endereço (opcional)</label><input type="text" id="slug" name="slug" maxlength="60" placeholder="lanchonete-da-praca"><div class="dica">Fica /loja/endereco. Em branco, é criado a partir do nome.</div></div>
        <div class="campo"><label for="tema">Visual</label><select id="tema" name="tema"><?php foreach ($temas_disponiveis as $chave => $rotulo): ?><option value="<?php echo $chave; ?>"><?php echo htmlspecialchars($rotulo); ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="admin_nome">Nome do administrador *</label><input type="text" id="admin_nome" name="admin_nome" maxlength="100" required></div>
        <div class="campo"><label for="admin_usuario">Usuário do administrador *</label><input type="text" id="admin_usuario" name="admin_usuario" maxlength="50" required></div>
        <div class="campo"><label for="admin_senha">Senha do administrador *</label><input type="password" id="admin_senha" name="admin_senha" minlength="8" required autocomplete="new-password"></div>
        <div class="campo campo-largo"><button type="submit">Criar loja</button></div>
    </form>
</section>
<?php
painel_rodape();

function painel_topo($titulo, $logado) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo htmlspecialchars($titulo); ?> — FiadoFácil</title>
<script>
(function () {
    var tema = null;
    try { tema = localStorage.getItem('tema'); } catch (e) {}
    if (tema !== 'claro' && tema !== 'escuro') { tema = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'escuro' : 'claro'; }
    document.documentElement.setAttribute('data-tema', tema);
})();
</script>
<link rel="icon" type="image/svg+xml" href="img/logo.svg?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="css/estilo.css?v=29">
</head>
<body class="pagina-painel">
<header class="painel-topo">
    <a href="painel.php" class="marca"><?php echo logo(38); ?><span>FiadoFácil <small>Plataforma</small></span></a>
    <div class="painel-topo-botoes">
        <?php botao_tema_painel(); ?>
        <?php if ($logado): ?><a href="logout.php" class="botao botao-secundario" data-confirmar="Sair do painel?">Sair</a><?php endif; ?>
    </div>
</header>
<main class="container painel-conteudo">
    <?php
}

function botao_tema_painel() {
    echo '<button type="button" class="tema-botao" aria-label="Alternar modo noturno" title="Modo noturno">' . icone('lua', 'icone-lua') . icone('sol', 'icone-sol') . '</button>';
}

function painel_rodape() {
    ?>
</main>
<script src="js/script.js?v=23"></script>
</body>
</html>
    <?php
}
