<?php
// Cobrancas: quem fica inadimplente nos proximos dias e quem ja esta em atraso,
// com a mensagem de cobranca pronta para mandar pelo WhatsApp com um clique.
require_once "verifica_login.php";
require_once "conexao.php";
$vencem = [];
$atrasados = [];
foreach (buscar_clientes_situacao($conn) as $c) {
    $faltam = dias_para_vencer($c['saldo_devedor'], $c['dias_devendo']);
    if ($faltam === null) { continue; }
    $c['faltam'] = $faltam;
    $c['mensagem'] = mensagem_cobranca($c['nome'], $loja_atual, $c['saldo_devedor'], $c['dias_devendo']);
    $c['link'] = link_whatsapp($c['telefone'] ?? '', $c['mensagem']);
    if ($faltam === 0) {
        $atrasados[] = $c;
    } elseif ($faltam <= DIAS_AVISO_ANTES) {
        $vencem[] = $c;
    }
}
usort($vencem, function ($a, $b) { return $a['faltam'] <=> $b['faltam']; });
usort($atrasados, function ($a, $b) { return $b['dias_devendo'] <=> $a['dias_devendo']; });

function linha_cobranca($c, $atrasado) {
    $prazo = $atrasado
        ? '<span class="selo selo-alerta">Atrasado há ' . (int)$c['dias_devendo'] . ' dias</span>'
        : '<span class="selo selo-aviso">Vence em ' . $c['faltam'] . ($c['faltam'] === 1 ? ' dia' : ' dias') . '</span>';
    $id_mensagem = 'mensagem-' . (int)$c['id'];
    ob_start(); ?>
    <li class="cobranca">
        <div class="cobranca-cliente">
            <a class="link-nome com-avatar" href="cliente.php?id=<?php echo (int)$c['id']; ?>"><?php echo avatar($c['nome']); ?><span><?php echo htmlspecialchars($c['nome']); ?></span></a>
            <span class="cobranca-telefone"><?php echo htmlspecialchars($c['telefone'] ?: 'sem telefone'); ?></span>
        </div>
        <strong class="cobranca-valor"><?php echo dinheiro($c['saldo_devedor']); ?></strong>
        <?php echo $prazo; ?>
        <div class="cobranca-acoes">
            <?php if ($c['link']): ?>
            <a class="botao botao-whatsapp botao-pequeno" href="<?php echo htmlspecialchars($c['link']); ?>" target="_blank" rel="noopener"><?php echo icone('whatsapp'); ?> Enviar no WhatsApp</a>
            <?php else: ?>
            <span class="dica">Cadastre o telefone para cobrar pelo WhatsApp.</span>
            <?php endif; ?>
        </div>
        <details class="cobranca-mensagem">
            <summary>Ver mensagem</summary>
            <pre id="<?php echo $id_mensagem; ?>"><?php echo htmlspecialchars($c['mensagem']); ?></pre>
            <button type="button" class="botao-pequeno botao-secundario" data-copiar="#<?php echo $id_mensagem; ?>"><?php echo icone('copiar'); ?> Copiar mensagem</button>
        </details>
    </li>
    <?php return ob_get_clean();
}

$titulo_pagina = "Cobranças";
$menu_ativo = "cobrancas";
require "cabecalho.php";
?>
<h1>Cobranças</h1>
<p class="subtitulo">Quem fica inadimplente nos próximos <?php echo DIAS_AVISO_ANTES; ?> dias e quem já está em atraso, com a mensagem pronta para o WhatsApp.</p>
<?php mostrar_flash(); ?>
<div class="cards">
    <div class="card card-aviso"><span class="card-titulo">Vencem em até <?php echo DIAS_AVISO_ANTES; ?> dias</span><span class="card-valor"><?php echo count($vencem); ?></span></div>
    <div class="card card-alerta"><span class="card-titulo">Em atraso (+<?php echo DIAS_PARA_INADIMPLENCIA; ?> dias)</span><span class="card-valor"><?php echo count($atrasados); ?></span></div>
    <div class="card"><span class="card-titulo">Total a cobrar</span><span class="card-valor"><?php echo dinheiro(array_sum(array_column(array_merge($vencem, $atrasados), 'saldo_devedor'))); ?></span></div>
</div>

<section class="painel painel-cobranca">
    <h2><?php echo icone('relogio'); ?> Vencem nos próximos <?php echo DIAS_AVISO_ANTES; ?> dias</h2>
    
    <?php if (!$vencem): ?><p class="lista-vazia">Ninguém vence nos próximos dias.</p><?php else: ?>
    <ul class="lista-cobrancas"><?php foreach ($vencem as $c) { echo linha_cobranca($c, false); } ?></ul>
    <?php endif; ?>
</section>

<section class="painel painel-cobranca">
    <h2><?php echo icone('atencao'); ?> Em atraso</h2>
    <?php if (!$atrasados): ?><p class="lista-vazia">Nenhum cliente em atraso. 🎉</p><?php else: ?>
    <ul class="lista-cobrancas"><?php foreach ($atrasados as $c) { echo linha_cobranca($c, true); } ?></ul>
    <?php endif; ?>
</section>
<p class="dica">A mensagem abre pronta no WhatsApp. Você revisa e envia.</p>
<?php require "rodape.php"; ?>
