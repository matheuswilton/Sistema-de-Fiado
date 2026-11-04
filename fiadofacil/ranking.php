<?php
require_once "verifica_login.php";
require_once "conexao.php";
/*
  Score de confiabilidade (0 a 100):
  - comeca em 100 pontos
  - perde 2 pontos por cada dia acima de 7 dias que a divida mais antiga esta em aberto
  - perde 10 pontos se a divida for maior que 50% do limite
  - ganha 5 pontos se o cliente ja fez algum pagamento
  O ranking e ordenado do maior para o menor score ANTES de dividir em paginas.
*/
$lista = buscar_clientes_situacao($conn);
foreach ($lista as $posicao => $linha) {
    $score = 100;
    $dias_extras = max(0, $linha['dias_devendo'] - 7);
    $score -= $dias_extras * 2;
    if ($linha['limite_credito'] > 0 && $linha['saldo_devedor'] > ($linha['limite_credito'] * 0.5)) { $score -= 10; }
    if ($linha['total_pagamentos'] > 0) { $score += 5; }
    $lista[$posicao]['score'] = max(0, min(100, $score));
}
usort($lista, function ($a, $b) {
    if ($a['score'] !== $b['score']) { return $b['score'] - $a['score']; }
    if ($a['saldo_devedor'] != $b['saldo_devedor']) { return $a['saldo_devedor'] < $b['saldo_devedor'] ? -1 : 1; }
    return strcmp($a['nome'], $b['nome']);
});
$por_pagina = 10;
$total = count($lista);
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) { $pagina = 1; }
if ($pagina > $total_paginas) { $pagina = $total_paginas; }
$offset = ($pagina - 1) * $por_pagina;
$linhas_da_pagina = array_slice($lista, $offset, $por_pagina);
$titulo_pagina = "Ranking de confiabilidade";
$menu_ativo = "ranking";
require "cabecalho.php";
?>
<h1>Ranking de confiabilidade</h1>
<p class="subtitulo">Do cliente mais confiável para o mais arriscado. Score: 80+ confiável, 50 a 79 regular, abaixo de 50 risco.</p>
<div class="tabela-rolagem"><table>
    <tr><th>#</th><th>Cliente</th><th class="num">Saldo devedor</th><th class="num">Dias em aberto</th><th>Score</th></tr>
    <?php foreach ($linhas_da_pagina as $posicao => $linha): ?>
    <tr>
        <td><?php echo $offset + $posicao + 1; ?>º</td>
        <td><a href="cliente.php?id=<?php echo $linha['id']; ?>"><?php echo htmlspecialchars($linha['nome']); ?></a></td>
        <td class="num"><?php echo dinheiro(max(0, $linha['saldo_devedor'])); ?></td>
        <td class="num"><?php echo $linha['dias_devendo']; ?> dias</td>
        <td><strong><?php echo $linha['score']; ?>/100</strong>
            <?php if ($linha['score'] >= 80): ?><span class="selo selo-ok">Confiável</span>
            <?php elseif ($linha['score'] >= 50): ?><span class="selo selo-aviso">Regular</span>
            <?php else: ?><span class="selo selo-alerta">Risco</span><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($total === 0): ?><tr><td colspan="5" class="vazio">Nenhum cliente cadastrado.</td></tr><?php endif; ?>
</table></div>
<?php mostrar_paginacao($pagina, $total_paginas); ?>
<?php require "rodape.php"; ?>
