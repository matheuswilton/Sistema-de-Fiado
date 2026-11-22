<?php
// Cliente cria a propria conta com CPF e senha.
// Seguranca: so cria se alguma loja ja tiver este CPF com o MESMO telefone no cadastro,
// e mesmo assim cada loja precisa confirmar a ligacao antes de o cliente ver a divida dela.
require_once "funcoes.php";
if (!empty($_SESSION['conta_cliente_id'])) {
    header("Location: minhas_dividas.php");
    exit();
}
require_once "conexao.php";
$erro = "";
$nome = $cpf = $telefone = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nome = trim($_POST['nome'] ?? '');
    $cpf = so_numeros($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';
    if (parece_robo('conta')) {
        $erro = "Não foi possível enviar. Espere alguns segundos e tente de novo.";
    } elseif ($nome === '' || mb_strlen($nome) > 100) {
        $erro = "Informe o seu nome.";
    } elseif (!cpf_valido($cpf)) {
        $erro = "CPF inválido. Confira os números.";
    } elseif (!telefone_valido($telefone)) {
        $erro = "Telefone inválido. Informe o DDD e o número, ex.: (62) 99999-9999.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter no mínimo 8 caracteres.";
    } elseif ($senha !== $confirmar) {
        $erro = "A confirmação da senha não confere.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM contas_clientes WHERE cpf = ?");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $ja_tem_conta = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        // telefones cadastrados nas lojas para este CPF
        $stmt = $conn->prepare("SELECT c.telefone FROM clientes c JOIN comercios m ON m.id = c.comercio_id WHERE c.cpf = ? AND m.status = 'ativo'");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $telefones_lojas = array_map(function ($l) { return so_numeros($l[0] ?? ''); }, $stmt->get_result()->fetch_all());
        $stmt->close();
        if ($ja_tem_conta) {
            $erro = "Já existe uma conta com este CPF. Entre com a sua senha.";
        } elseif (!$telefones_lojas) {
            $erro = "Nenhuma loja tem este CPF no cadastro ainda. Peça para a loja onde você compra incluir o seu CPF e tente de novo.";
        } elseif (!in_array(so_numeros($telefone), $telefones_lojas, true)) {
            $erro = "O telefone não confere com o cadastro da loja. Use o mesmo número que a loja tem, ou fale com ela.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $telefone_formatado = formatar_telefone($telefone);
            $stmt = $conn->prepare("INSERT INTO contas_clientes (nome, cpf, telefone, senha) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $cpf, $telefone_formatado, $hash);
            if ($stmt->execute()) {
                $conta_id = (int)$conn->insert_id;
                $stmt->close();
                sincronizar_vinculos($conn, $conta_id, $cpf);
                session_regenerate_id(true);
                unset($_SESSION['usuario_id'], $_SESSION['usuario_nome'], $_SESSION['usuario_nivel'], $_SESSION['comercio_id'],
                    $_SESSION['loja_slug'], $_SESSION['cliente_id'], $_SESSION['plataforma']);
                $_SESSION['conta_cliente_id'] = $conta_id;
                $_SESSION['conta_cliente_nome'] = $nome;
                definir_flash("Conta criada! Cada loja precisa confirmar que é você; depois disso a dívida dela aparece aqui.");
                header("Location: minhas_dividas.php");
                exit();
            }
            $stmt->close();
            $erro = "Não foi possível criar a conta agora. Tente de novo.";
        }
    }
}
$titulo_pagina = "Criar conta de cliente";
$descricao_pagina = "Crie a sua conta com CPF e senha e acompanhe o fiado de todas as lojas num lugar só.";
$classe_container = "container-pequeno";
require "cabecalho.php";
?>
<h1>Criar minha conta</h1>
<p class="subtitulo">Veja o seu fiado em todas as lojas.</p>
<?php if ($erro): ?><div class="erro" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
<form method="POST" action="conta_criar.php">
    <?php campo_csrf(); campo_antispam('conta'); ?>
    <label for="nome">Seu nome</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" maxlength="100" autocomplete="name" required>
    <label for="cpf">CPF</label>
    <input type="text" id="cpf" name="cpf" data-mascara="cpf" inputmode="numeric" placeholder="000.000.000-00" value="<?php echo htmlspecialchars(formatar_cpf($cpf)); ?>" required>
    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" data-mascara="telefone" inputmode="tel" placeholder="(62) 99999-9999" value="<?php echo htmlspecialchars($telefone); ?>" autocomplete="tel" required>
    <div class="dica">O mesmo telefone do cadastro na loja.</div>
    <label for="senha">Crie uma senha</label>
    <input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required>
    <div class="dica">No mínimo 8 caracteres.</div>
    <label for="confirmar_senha">Repita a senha</label>
    <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required>
    <button type="submit" class="botao-total">Criar conta</button>
</form>
<div class="caixa-info">
    <strong>Como funciona</strong>
    <ol>
        <li>A loja coloca o seu CPF no seu cadastro.</li>
        <li>Você cria a conta aqui com o mesmo telefone que a loja tem.</li>
        <li>Cada loja confirma que é você. Pronto: a dívida de todas aparece numa tela só.</li>
    </ol>
</div>
<p class="trocar-loja">Já tem conta? <a href="conta_entrar.php">Entrar</a></p>
<?php require "rodape.php"; ?>
