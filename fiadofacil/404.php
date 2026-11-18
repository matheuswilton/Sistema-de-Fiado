<?php
// Pagina mostrada quando o endereco digitado nao existe
http_response_code(404);
require_once __DIR__ . "/layout_publico.php";
topo_publico("Página não encontrada", "Este endereço não existe no FiadoFácil.", true);
?>
<section class="inicio-secao pagina-404">
    <div class="inicio-largura erro-404">
        <div class="erro-404-numero" aria-hidden="true">4<span><?php echo logo(120); ?></span>4</div>
        <h1>Essa página não está no caderninho.</h1>
        <p>O endereço que você abriu não existe ou mudou de lugar. Confira se digitou certo, ou volte para um lugar conhecido:</p>
        <div class="inicio-botoes erro-404-botoes">
            <a href="/index.php" class="botao botao-grande">Ir para o início</a>
            <a href="/login.php" class="botao botao-secundario botao-grande">Entrar no sistema</a>
        </div>
    </div>
</section>
<?php rodape_publico(); ?>
