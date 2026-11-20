<?php
// Conta unica do cliente: as dividas em TODAS as lojas onde ele compra, numa tela so.
// So aparecem as lojas que confirmaram a ligacao, e so os dados deste cliente.
require_once "funcoes.php";
if (empty($_SESSION['conta_cliente_id'])) {
    header("Location: conta_entrar.php");
    exit();
}
require_once "conexao.php";
$conta_id = (int)$_SESSION['conta_cliente_id'];
$stmt = $conn->prepare("SELECT id, nome, cpf, senha FROM contas_clientes WHERE id = ?");
$stmt->bind_param("i", $conta_id);
$stmt->execute();
$conta = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$conta) {
    session_unset();
    session_destroy();
    header("Location: conta_entrar.php");
    exit();
}
// troca de senha da propria conta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nova = $_POST['nova_senha'] ?? '';
    if (!password_verify($_POST['senha_atual'] ?? '', $conta['senha'])) {
        definir_flash("A senha atual está incorreta.", "erro");
    } elseif (strlen($nova) < 8) {
        definir_flash("A nova senha deve ter no mínimo 8 caracteres.", "erro");
    } elseif ($nova !== ($_POST['confirmar_senha'] ?? '')) {
        definir_flash("A confirmação da nova senha não confere.", "erro");
    } else {
        $hash = password_hash($nova, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE contas_clientes SET senha = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $conta_id);
        $stmt->execute();
        $stmt->close();
        definir_flash("Senha alterada com sucesso!");
    }
    header("Location: minhas_dividas.php");
    exit();
}
sincronizar_vinculos($conn, $conta_id, $conta['cpf']);
// o cadastro da loja precisa continuar com o MESMO CPF da conta (se a loja trocar o CPF, a ligacao deixa de valer)
$stmt = $conn->prepare("SELECT v.status, c.id AS cliente_id, c.limite_credito, m.nome AS loja_nome, m.slug, m.tema, m.chave_pix, m.pix_cidade
    FROM contas_vinculos v
    JOIN clientes c ON c.id = v.cliente_id AND c.comercio_id = v.comercio_id AND c.cpf = ?
    JOIN comercios m ON m.id = v.comercio_id AND m.status = 'ativo'
    WHERE v.conta_id = ?
    ORDER BY m.nome");
$stmt->bind_param("si", $conta['cpf'], $conta_id);
$stmt->execute();
$vinculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$lojas = [];
$aguardando = [];
$total_geral = 0;
foreach ($vinculos as $v) {
    if ($v['status'] !== 'aprovado') {
        $aguardando[] = $v;
        continue;
    }
    $id_cliente = (int)$v['cliente_id'];
    $v['saldo'] = max(0, saldo_do_cliente($conn, $id_cliente));
    $total_geral += $v['saldo'];
    $stmt = $conn->prepare("(SELECT 'compra' AS tipo, descricao, valor, data_compra AS quando FROM compras WHERE cliente_id = ?)
        UNION ALL (SELECT 'pagamento', '', valor, data_pagamento FROM pagamentos WHERE cliente_id = ?)
        ORDER BY quando DESC LIMIT 8");
    $stmt->bind_param("ii", $id_cliente, $id_cliente);
    $stmt->execute();
    $v['movimentos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $v['pix'] = ($v['saldo'] > 0 && !empty($v['chave_pix']))
        ? pix_copia_e_cola($v['chave_pix'], $v['loja_nome'], $v['pix_cidade'] ?? '', $v['saldo'])
        : null;
    $lojas[] = $v;
}
$primeiro_nome = explode(' ', trim($conta['nome']))[0];
require_once __DIR__ . "/layout_publico.php";
topo_publico("Minhas dívidas", "Suas compras no fiado em todas as lojas.", true, ["texto" => "Sair", "href" => "/logout.php", "confirmar" => "Deseja sair da sua conta?"]);
?>
<section class="inicio-secao conta-secao">
<div class="inicio-largura conta-area">
    <h1>Olá, <?php echo htmlspecialchars($primeiro_nome); ?>!</h1>
    <p class="subtitulo">Seu fiado em todas as lojas, num lugar só · CPF <?php echo htmlspecialchars(mascarar_cpf($conta['cpf'])); ?></p>
    <?php mostrar_flash(); ?>
    <div class="cards">
        <div class="card card-calma"><span class="card-titulo">Total que você deve</span><span class="card-valor"><?php echo dinheiro($total_geral); ?></span></div>
        <div class="card"><span class="card-titulo">Lojas ligadas</span><span class="card-valor"><?php echo count($lojas); ?></span></div>
    </div>

    <?php foreach ($lojas as $i => $loja): ?>
    <article class="painel conta-loja" data-tema-loja="<?php echo htmlspecialchars($loja['tema']); ?>">
        <header class="conta-loja-topo">
            <h2><?php echo icone('loja'); ?> <?php echo htmlspecialchars($loja['loja_nome']); ?></h2>
            <?php if ($loja['saldo'] > 0): ?><span class="selo selo-aviso">Em aberto</span><?php else: ?><span class="selo selo-ok">Em dia</span><?php endif; ?>
        </header>
        <div class="conta-numeros">
            <div><span>Você deve</span><strong><?php echo dinheiro($loja['saldo']); ?></strong></div>
            <div><span>Limite</span><strong><?php echo dinheiro($loja['limite_credito']); ?></strong></div>
            <div><span>Disponível</span><strong><?php echo dinheiro(max(0, $loja['limite_credito'] - $loja['saldo'])); ?></strong></div>
        </div>
        <?php echo barra_limite($loja['saldo'], $loja['limite_credito'], true); ?>
        <?php if ($loja['pix']): ?>
        <details class="detalhe-tecnico">
            <summary><?php echo icone('pix'); ?> Pagar com Pix</summary>
            <div class="pix-caixa">
                <div class="pix-qr" data-qrcode="<?php echo htmlspecialchars($loja['pix']); ?>" role="img" aria-label="QR Code Pix de <?php echo dinheiro($loja['saldo']); ?> para <?php echo htmlspecialchars($loja['loja_nome']); ?>"></div>
                <div class="pix-info">
                    <p>Leia o QR Code no app do seu banco. O valor de <strong><?php echo dinheiro($loja['saldo']); ?></strong> já vem preenchido; para pagar só uma parte, mude o valor no app.</p>
                    <div class="copiar-linha"><code id="pix-<?php echo $i; ?>" class="pix-codigo"><?php echo htmlspecialchars($loja['pix']); ?></code><button type="button" class="botao-pequeno" data-copiar="#pix-<?php echo $i; ?>"><?php echo icone('copiar'); ?> Copiar código</button></div>
                    <p class="dica">O pagamento aparece aqui depois que a loja confirmar.</p>
                </div>
            </div>
        </details>
        <?php endif; ?>
        <details class="detalhe-tecnico">
            <summary>Últimas compras e pagamentos</summary>
            <?php if (!$loja['movimentos']): ?><p class="dica">Nenhuma movimentação ainda.</p><?php else: ?>
            <ul class="conta-movimentos">
                <?php foreach ($loja['movimentos'] as $m): $compra = $m['tipo'] === 'compra'; ?>
                <li><span><?php echo date('d/m/Y', strtotime($m['quando'])); ?></span><span><?php echo $compra ? htmlspecialchars($m['descricao'] ?: 'Compra') : 'Pagamento'; ?></span><strong class="<?php echo $compra ? 'aviso' : 'ok'; ?>"><?php echo ($compra ? '+ ' : '− ') . dinheiro($m['valor']); ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </details>
    </article>
    <?php endforeach; ?>

    <?php foreach ($aguardando as $loja): ?>
    <div class="painel conta-aguardando">
        <?php echo icone($loja['status'] === 'pendente' ? 'relogio' : 'atencao'); ?>
        <div><strong><?php echo htmlspecialchars($loja['loja_nome']); ?></strong>
        <p><?php echo $loja['status'] === 'pendente' ? 'Aguardando a loja confirmar que é você. Assim que confirmar, a dívida aparece aqui.' : 'A loja não confirmou a ligação. Fale com a loja.'; ?></p></div>
    </div>
    <?php endforeach; ?>

    <?php if (!$lojas && !$aguardando): ?>
    <div class="painel conta-aguardando"><?php echo icone('atencao'); ?><div><strong>Nenhuma loja ligada ainda.</strong><p>Peça para a loja onde você compra incluir o seu CPF no cadastro. Ela aparece aqui sozinha.</p></div></div>
    <?php endif; ?>

    <p class="dica conta-nota">Não aparece alguma loja? Peça para ela incluir o seu CPF no cadastro. Cada loja só vê os próprios dados: a Mercearia não sabe o que você deve na Padaria.</p>

    <details class="detalhe-tecnico conta-senha">
        <summary><?php echo icone('senha'); ?> Trocar minha senha</summary>
        <form method="POST" action="minhas_dividas.php">
            <?php campo_csrf(); ?>
            <label for="senha_atual">Senha atual</label>
            <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" required>
            <label for="confirmar_senha">Repita a nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required>
            <button type="submit">Trocar senha</button>
        </form>
    </details>
</div>
</section>
<?php rodape_publico(); ?>
