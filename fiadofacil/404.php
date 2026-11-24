<?php
// Pagina mostrada quando o endereco digitado nao existe
// Plano B para o endereco da loja: /loja/Mercearia-do-Ze (com maiuscula, como o celular costuma escrever)
// ou so /mercearia-do-ze vao para o login da loja, mesmo se a hospedagem nao aplicar a regra do .htaccess
require_once __DIR__ . "/funcoes.php";
// caminho pedido, sem a pasta do sistema (no XAMPP o site fica em /fiadofacil/)
$caminho = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
if (strpos($caminho, base_url()) === 0) {
    $caminho = '/' . substr($caminho, strlen(base_url()));
}
if (preg_match('#^/loja/([A-Za-z0-9-]{2,60})/?$#', $caminho, $partes)) {
    header("Location: " . base_url() . "login.php?loja=" . rawurlencode(strtolower($partes[1])));
    exit();
}
if (preg_match('#^/([A-Za-z0-9-]{2,60})/?$#', $caminho, $partes)) {
    require_once __DIR__ . "/conexao.php";
    if (carregar_loja_por_slug($conn, $partes[1])) {
        header("Location: " . base_url() . "login.php?loja=" . rawurlencode(strtolower($partes[1])));
        exit();
    }
}
http_response_code(404);
require_once __DIR__ . "/layout_publico.php";
topo_publico("Página não encontrada", "Este endereço não existe no FiadoFácil.", true);
?>
<section class="inicio-secao pagina-404">
    <div class="inicio-largura erro-404">
        <div class="erro-404-numero" aria-hidden="true">4<span><?php echo logo(120); ?></span>4</div>
        <h1>Página não encontrada</h1>
        <p>Confira o endereço ou volte para o início.</p>
        <div class="inicio-botoes erro-404-botoes">
            <a href="<?php echo base_url(); ?>index.php" class="botao botao-grande">Ir para o início</a>
            <a href="<?php echo base_url(); ?>login.php" class="botao botao-secundario botao-grande">Encontrar a minha loja</a>
        </div>
    </div>
</section>
<?php rodape_publico(); ?>
