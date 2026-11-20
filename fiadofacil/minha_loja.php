<?php
// Dados da loja (somente administrador): chave Pix para o QR Code e chave de acesso da API
require_once "verifica_admin.php";
require_once "conexao.php";
$loja = loja_id();
$chave_nova_api = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'pix') {
        $chave = trim($_POST['chave_pix'] ?? '');
        $cidade = trim($_POST['pix_cidade'] ?? '');
        if ($chave !== '' && preg_match('/^[\d.\-\/]+$/', $chave)) {
            $chave = so_numeros($chave); // CPF ou CNPJ digitado com pontos e tracos
        }
        if ($chave === '') {
            $stmt = $conn->prepare("UPDATE comercios SET chave_pix = NULL, pix_cidade = NULL WHERE id = ?");
            $stmt->bind_param("i", $loja);
            $stmt->execute();
            $stmt->close();
            definir_flash("Chave Pix removida. Os clientes não verão mais o QR Code.");
        } elseif (!chave_pix_valida($chave)) {
            definir_flash("Chave Pix inválida. Use e-mail, celular no formato +5562999999999, CPF, CNPJ ou chave aleatória.", "erro");
        } elseif ($cidade === '' || strlen($cidade) > 40) {
            definir_flash("Informe a cidade da loja (ela vai junto no Pix).", "erro");
        } else {
            $cidade_pix = pix_texto_limpo($cidade, 15);
            $stmt = $conn->prepare("UPDATE comercios SET chave_pix = ?, pix_cidade = ? WHERE id = ?");
            $stmt->bind_param("ssi", $chave, $cidade_pix, $loja);
            $stmt->execute();
            $stmt->close();
            definir_flash("Chave Pix salva! O cliente já vê o QR Code na tela \"Minha dívida\".");
        }
        header("Location: minha_loja.php");
        exit();
    }
    if ($acao === 'gerar_api') {
        // a chave aparece uma unica vez; no banco fica so o hash SHA-256 (se o banco vazar, a chave nao vaza)
        $chave_nova_api = "ff_" . bin2hex(random_bytes(24));
        $hash = hash('sha256', $chave_nova_api);
        $stmt = $conn->prepare("UPDATE comercios SET api_token_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $loja);
        $stmt->execute();
        $stmt->close();
    }
    if ($acao === 'revogar_api') {
        $stmt = $conn->prepare("UPDATE comercios SET api_token_hash = NULL WHERE id = ?");
        $stmt->bind_param("i", $loja);
        $stmt->execute();
        $stmt->close();
        definir_flash("Chave da API cancelada. Quem usava a chave antiga perdeu o acesso.");
        header("Location: minha_loja.php");
        exit();
    }
}
$stmt = $conn->prepare("SELECT nome, slug, chave_pix, pix_cidade, api_token_hash IS NOT NULL AS tem_api FROM comercios WHERE id = ?");
$stmt->bind_param("i", $loja);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();
$stmt->close();
$exemplo_pix = $dados['chave_pix'] ? pix_copia_e_cola($dados['chave_pix'], $dados['nome'], $dados['pix_cidade'], 10) : null;
$endereco_api = ENDERECO_SITE . "/api.php";
$titulo_pagina = "Minha loja";
$menu_ativo = "loja";
$classe_container = "container-medio";
require "cabecalho.php";
?>
<h1>Minha loja</h1>
<p class="subtitulo">Integrações da <?php echo htmlspecialchars($dados['nome']); ?>: Pix, WhatsApp e API.</p>
<?php mostrar_flash(); ?>

<section class="painel integracao">
    <h2><?php echo icone('pix'); ?> Receber pelo Pix</h2>
    <p>Com a chave Pix cadastrada, o cliente vê na tela "Minha dívida" um <strong>QR Code com o valor da dívida</strong> e o código "copia e cola". O dinheiro cai direto na conta da loja. Depois de conferir no banco, registre o pagamento normalmente.</p>
    <form method="POST" action="minha_loja.php">
        <?php campo_csrf(); ?>
        <input type="hidden" name="acao" value="pix">
        <div class="grade-form">
            <div class="campo campo-largo">
                <label for="chave_pix">Chave Pix</label>
                <input type="text" id="chave_pix" name="chave_pix" maxlength="77" value="<?php echo htmlspecialchars($dados['chave_pix'] ?? ''); ?>" placeholder="e-mail, +5562999999999, CPF, CNPJ ou chave aleatória">
                <div class="dica">Deixe em branco e salve para desligar o Pix.</div>
            </div>
            <div class="campo">
                <label for="pix_cidade">Cidade</label>
                <input type="text" id="pix_cidade" name="pix_cidade" maxlength="40" value="<?php echo htmlspecialchars($dados['pix_cidade'] ?? ''); ?>" placeholder="Goiânia">
            </div>
        </div>
        <div class="barra-botoes"><button type="submit">Salvar chave Pix</button></div>
    </form>
    <?php if ($exemplo_pix): ?>
    <details class="detalhe-tecnico">
        <summary>Ver um Pix de teste de R$ 10,00</summary>
        <div class="pix-caixa">
            <div class="pix-qr" data-qrcode="<?php echo htmlspecialchars($exemplo_pix); ?>" role="img" aria-label="QR Code Pix de teste"></div>
            <code class="pix-codigo"><?php echo htmlspecialchars($exemplo_pix); ?></code>
        </div>
    </details>
    <?php endif; ?>
</section>

<section class="painel integracao">
    <h2><?php echo icone('whatsapp'); ?> Cobrar pelo WhatsApp</h2>
    <p>Já está ligado. No extrato de quem está devendo aparece o botão <strong>"Cobrar no WhatsApp"</strong>, que abre a conversa com a mensagem pronta, o valor e a chave Pix. Também aparece em "Precisam de atenção", na tela inicial. Nada é enviado sem você apertar "enviar".</p>
</section>

<section class="painel integracao">
    <h2><?php echo icone('acessos'); ?> API da loja</h2>
    <p>A API entrega os dados da loja em <strong>JSON</strong> para outro programa (um app, uma planilha, outro sistema). É <strong>só leitura</strong> e só mostra os dados desta loja.</p>
    <?php if ($chave_nova_api): ?>
        <div class="mensagem" role="status"><strong>Chave criada. Copie agora: ela não aparece de novo.</strong></div>
        <div class="copiar-linha"><code id="chave-api"><?php echo htmlspecialchars($chave_nova_api); ?></code><button type="button" class="botao-pequeno" data-copiar="#chave-api"><?php echo icone('copiar'); ?> Copiar</button></div>
    <?php elseif ($dados['tem_api']): ?>
        <p><span class="selo selo-ok">API ligada</span></p>
    <?php else: ?>
        <p><span class="selo selo-aviso">API desligada</span></p>
    <?php endif; ?>
    <div class="barra-botoes">
        <form method="POST" action="minha_loja.php" <?php if ($dados['tem_api']): ?>data-confirmar="Gerar uma chave nova? A chave antiga para de funcionar."<?php endif; ?>><?php campo_csrf(); ?><input type="hidden" name="acao" value="gerar_api"><button type="submit"><?php echo $dados['tem_api'] ? 'Gerar chave nova' : 'Ligar a API (gerar chave)'; ?></button></form>
        <?php if ($dados['tem_api']): ?>
        <form method="POST" action="minha_loja.php" data-confirmar="Cancelar a chave da API?"><?php campo_csrf(); ?><input type="hidden" name="acao" value="revogar_api"><button type="submit" class="botao-secundario">Desligar a API</button></form>
        <?php endif; ?>
    </div>
    <details class="detalhe-tecnico">
        <summary>Como usar a API</summary>
        <p>Envie a chave no cabeçalho <code>X-Api-Key</code>. Endereços (método GET):</p>
        <ul>
            <li><code>/api.php?recurso=resumo</code>: totais da loja.</li>
            <li><code>/api.php?recurso=clientes</code>: clientes com dívida, limite e situação.</li>
            <li><code>/api.php?recurso=cliente&amp;id=5</code>: extrato de um cliente.</li>
        </ul>
<pre class="codigo">curl -H "X-Api-Key: SUA_CHAVE" "<?php echo htmlspecialchars($endereco_api); ?>?recurso=clientes"</pre>
        <p class="dica">Atenção: a hospedagem gratuita (InfinityFree) tem uma proteção contra robôs que bloqueia chamadas feitas fora do navegador. A API funciona completa no XAMPP e em hospedagens pagas.</p>
    </details>
</section>
<?php require "rodape.php"; ?>
