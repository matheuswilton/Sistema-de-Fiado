<?php
require_once "layout_publico.php";
topo_publico("Política de Privacidade", "Quais dados o FiadoFácil guarda, para que servem, quem pode ver e como pedir correção ou exclusão, de acordo com a LGPD.");
?>
<section class="inicio-secao pagina-texto">
    <div class="inicio-largura texto-legal">
        <span class="inicio-sobretitulo">LGPD · Lei 13.709/2018</span>
        <h1>Política de Privacidade</h1>
        <p class="texto-legal-data">Última atualização: 18 de novembro de 2026</p>

        <p>O FiadoFácil é um sistema para pequenos comércios anotarem compras no fiado, pagamentos e dívidas de clientes. Esta página explica, em linguagem simples, quais dados pessoais o sistema guarda, para que servem e quais são os seus direitos.</p>

        <h2>Quem é responsável pelos dados</h2>
        <p>Quem decide cadastrar os clientes e usar os dados é o <strong>comércio que usa o FiadoFácil</strong> (o controlador, nos termos da LGPD). O FiadoFácil é a ferramenta que guarda e organiza essas informações. Este site é um Projeto Integrador do curso de Gestão da Tecnologia da Informação da FPM, em Goiânia/GO.</p>

        <h2>Quais dados o sistema guarda</h2>
        <ul>
            <li><strong>Dos clientes da loja:</strong> nome, telefone, endereço, limite de crédito e, se a loja quiser, o CPF.</li>
            <li><strong>Das compras e pagamentos:</strong> descrição da compra, valor e data.</li>
            <li><strong>Dos usuários que entram no sistema</strong> (dono, atendentes e clientes com acesso): nome, nome de usuário, senha e, no pedido de acesso do cliente, o telefone informado.</li>
            <li><strong>Da conta do cliente:</strong> nome, CPF, telefone e senha (guardada só como código, nunca a senha em si).</li>
        </ul>
        <p>O CPF serve <strong>só</strong> para juntar, na conta do cliente, o fiado das lojas onde ele compra. Nas telas ele aparece mascarado (***.456.789-**). O sistema <strong>não</strong> guarda documentos, dados de cartão ou conta bancária.</p>

        <h2>Para que os dados são usados</h2>
        <ul>
            <li>Anotar as compras no fiado e os pagamentos de cada cliente.</li>
            <li>Calcular a dívida, o limite disponível e a situação (em dia, devendo ou inadimplente).</li>
            <li>Mostrar ao cliente a própria dívida, quando ele tem acesso.</li>
            <li>Controlar quem pode entrar no sistema e o que cada perfil pode fazer.</li>
        </ul>
        <p>Os dados <strong>não</strong> são vendidos, não são usados para propaganda e não são compartilhados com outras empresas.</p>

        <h2>Quem pode ver os dados</h2>
        <ul>
            <li><strong>Administrador (dono da loja):</strong> vê todos os clientes, compras, pagamentos e relatórios.</li>
            <li><strong>Atendente:</strong> vê os clientes e registra compras e pagamentos, mas não altera limites nem exclui clientes.</li>
            <li><strong>Cliente com acesso:</strong> vê somente a própria dívida, as próprias compras e os próprios pagamentos. Com a conta por CPF, vê isso em todas as lojas que confirmaram que é ele.</li>
            <li><strong>Cada loja vê só os próprios dados:</strong> uma loja nunca sabe quanto o cliente deve em outra.</li>
        </ul>

        <h2>Como os dados são protegidos</h2>
        <ul>
            <li>As senhas são guardadas embaralhadas (com <em>hash</em>), e ninguém consegue ler a senha original.</li>
            <li>O site usa conexão segura (HTTPS).</li>
            <li>Os formulários têm proteção contra ataques comuns, como SQL Injection e CSRF, e contra envios automáticos de robôs.</li>
            <li>O login é bloqueado por alguns minutos depois de várias tentativas com senha errada.</li>
        </ul>

        <h2>Cookies e dados guardados no aparelho</h2>
        <p>O FiadoFácil usa apenas um <strong>cookie essencial</strong>, que mantém você conectado depois de fazer login. Sem ele, o sistema não funciona. Também guarda no seu próprio aparelho duas preferências: modo claro ou noturno e menu lateral aberto ou recolhido. Essas preferências não saem do seu aparelho.</p>
        <p>O site <strong>não</strong> usa cookies de propaganda, de rastreamento ou de análise de visitas.</p>

        <h2>Por quanto tempo os dados ficam guardados</h2>
        <p>Os dados ficam guardados enquanto o cliente estiver cadastrado na loja. Quando o administrador exclui um cliente, o histórico de compras, os pagamentos e o acesso dele são apagados juntos. Um cliente com dívida em aberto só pode ser excluído depois de quitar a dívida.</p>

        <h2>Seus direitos</h2>
        <p>Pela LGPD (art. 18), você pode pedir para <strong>saber quais dados</strong> estão guardados sobre você, <strong>corrigir</strong> dados errados e <strong>excluir</strong> seus dados, entre outros direitos. Para isso, fale com o administrador da loja onde você compra: é ele quem cadastra, corrige e exclui as informações no sistema. Se você tem acesso como cliente, já pode conferir a sua dívida a qualquer momento em "Minha dívida".</p>

        <h2>Mudanças nesta política</h2>
        <p>Se esta política mudar, a nova versão será publicada nesta página, com a data de atualização no topo.</p>

        <p class="texto-legal-voltar"><a href="<?php echo base_url(); ?>index.php" class="botao botao-secundario">&larr; Voltar ao início</a></p>
    </div>
</section>
<?php rodape_publico(); ?>
