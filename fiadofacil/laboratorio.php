<?php
// Laboratorio visual: so existe no SITE DE TESTE. Troca o visual na hora para comparar opcoes.
// As escolhas (e as fotos) ficam salvas so neste navegador; nada vai para o servidor.
require_once __DIR__ . "/funcoes.php";
if (!eh_ambiente_teste()) {
    require __DIR__ . "/404.php";
    exit();
}
require_once __DIR__ . "/layout_publico.php";
topo_publico("Laboratório visual", "Compare as opções de visual do FiadoFácil no site de teste.", true);
$grupos = [
    'rolagem' => ['Rolagem da página inicial', 'Como a cena "do caderninho ao celular" reage quando a pessoa rola a página.', [
        'presa' => ['Presa (como está hoje)', 'A tela fica parada por umas 4 rolagens enquanto a história acontece.'],
        'curta' => ['Presa curta', 'Fica parada só por 1 rolagem, mais macia.'],
        'livre' => ['Livre', 'Não prende: a cena toca sozinha quando aparece. A rolagem nunca trava.'],
    ]],
    'fundo' => ['Fundo do login das lojas', 'O que aparece atrás do nome da loja na tela de entrar.', [
        'desenhos' => ['Desenhos (como está hoje)', 'Pães, carnes e frutas desenhados, com o letreiro 3D.'],
        'escuro' => ['Escuro de cinema', 'Fundo escuro com luz na cor da loja, grão de filme e letreiro limpo.'],
        'foto' => ['Foto + cinema', 'A foto que você escolher abaixo, escurecida, com zoom lento e faixas de cinema.'],
    ]],
    'desenhos' => ['Desenhos e animações das lojas', 'Dentro do sistema: bife, pão, frutas, toldo, lousa, etiquetas e termômetro.', [
        'como-hoje' => ['Como está hoje', 'Todos os desenhos e animações temáticas.'],
        'discretos' => ['Discretos', 'Sem desenhos: ficam só as cores de cada loja.'],
    ]],
];
?>
<section class="lab">
    <h1>Laboratório visual</h1>
    <p class="subtitulo">Escolha as opções e abra as páginas para ver funcionando. Fica salvo só neste navegador e só no site de teste: o site oficial não muda.</p>
    <?php foreach ($grupos as $chave => [$titulo, $explicacao, $opcoes]): ?>
    <div class="lab-grupo">
        <h2><?php echo $titulo; ?></h2>
        <p><?php echo $explicacao; ?></p>
        <div class="lab-opcoes" data-grupo="<?php echo $chave; ?>">
            <?php $primeira = true; foreach ($opcoes as $valor => [$nome, $descricao]): ?>
            <button type="button" data-valor="<?php echo $valor; ?>" data-padrao="<?php echo $primeira ? '1' : '0'; ?>" aria-pressed="false"><b><?php echo $nome; ?></b><span><?php echo $descricao; ?></span></button>
            <?php $primeira = false; endforeach; ?>
        </div>
        <?php if ($chave === 'fundo'): ?>
        <div class="lab-fotos">
            <?php foreach (['mercearia' => 'Mercearia do Zé', 'padaria' => 'Padaria Pão Quente', 'acougue' => 'Açougue Bom Corte'] as $loja => $nome_loja): ?>
            <div class="lab-foto" data-loja="<?php echo $loja; ?>">
                <b><?php echo $nome_loja; ?></b>
                <div class="previa">sem foto</div>
                <div class="linha">
                    <label class="botao botao-pequeno">Escolher foto<input type="file" accept="image/*" hidden></label>
                    <button type="button" class="botao-pequeno botao-secundario" data-remover>Remover</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="dica">A foto fica só no seu navegador, não é enviada para o site. Sem foto, a loja usa o "Escuro de cinema".</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="lab-grupo">
        <h2>Ver funcionando</h2>
        <p>Abra e compare. Para voltar, use a etiqueta "Laboratório visual" no rodapé das páginas.</p>
        <div class="lab-ver">
            <a class="botao" href="index.php">Página inicial</a>
            <a class="botao botao-secundario" href="login.php?loja=mercearia-do-ze">Login da Mercearia</a>
            <a class="botao botao-secundario" href="login.php?loja=padaria-pao-quente">Login da Padaria</a>
            <a class="botao botao-secundario" href="login.php?loja=acougue-bom-corte">Login do Açougue</a>
            <button type="button" class="botao botao-secundario" id="lab-padrao">Voltar tudo ao padrão</button>
        </div>
    </div>
</section>
<div class="lab-salvo" id="lab-salvo" role="status">Salvo ✓</div>
<script>
(function () {
    var escolhas = {};
    try { escolhas = JSON.parse(localStorage.getItem('lab-visual') || '{}') || {}; } catch (e) {}
    var aviso = document.getElementById('lab-salvo'), tempo;
    function salvo(texto) { aviso.textContent = texto || 'Salvo ✓'; aviso.classList.add('ver'); clearTimeout(tempo); tempo = setTimeout(function () { aviso.classList.remove('ver'); }, 1300); }
    function guardar() { try { localStorage.setItem('lab-visual', JSON.stringify(escolhas)); } catch (e) {} }
    function marcar() {
        document.querySelectorAll('.lab-opcoes').forEach(function (grupo) {
            var atual = escolhas[grupo.getAttribute('data-grupo')];
            grupo.querySelectorAll('button').forEach(function (b) {
                var ligado = atual ? b.getAttribute('data-valor') === atual : b.getAttribute('data-padrao') === '1';
                b.setAttribute('aria-pressed', ligado ? 'true' : 'false');
            });
        });
    }
    document.querySelectorAll('.lab-opcoes button').forEach(function (b) {
        b.addEventListener('click', function () {
            var grupo = b.closest('.lab-opcoes').getAttribute('data-grupo');
            if (b.getAttribute('data-padrao') === '1') { delete escolhas[grupo]; } else { escolhas[grupo] = b.getAttribute('data-valor'); }
            guardar(); marcar(); salvo();
        });
    });
    document.getElementById('lab-padrao').addEventListener('click', function () { escolhas = {}; guardar(); marcar(); salvo('Tudo voltou ao padrão'); });
    // fotos: diminui no proprio navegador (ate 1600 px) e guarda so aqui
    document.querySelectorAll('.lab-foto').forEach(function (caixa) {
        var loja = caixa.getAttribute('data-loja'), chave = 'lab-foto-' + loja, previa = caixa.querySelector('.previa');
        function mostrar() {
            var foto = null; try { foto = localStorage.getItem(chave); } catch (e) {}
            previa.style.backgroundImage = foto ? 'url("' + foto + '")' : '';
            previa.textContent = foto ? '' : 'sem foto';
        }
        caixa.querySelector('input[type=file]').addEventListener('change', function (e) {
            var arquivo = e.target.files[0]; if (!arquivo) { return; }
            var leitor = new FileReader();
            leitor.onload = function () {
                var img = new Image();
                img.onload = function () {
                    var largura = Math.min(1600, img.width), escala = largura / img.width;
                    var tela = document.createElement('canvas'); tela.width = largura; tela.height = Math.round(img.height * escala);
                    tela.getContext('2d').drawImage(img, 0, 0, tela.width, tela.height);
                    try { localStorage.setItem(chave, tela.toDataURL('image/jpeg', 0.82)); salvo('Foto salva ✓'); }
                    catch (erro) { salvo('Foto grande demais para o navegador'); }
                    mostrar();
                };
                img.src = leitor.result;
            };
            leitor.readAsDataURL(arquivo);
        });
        caixa.querySelector('[data-remover]').addEventListener('click', function () { try { localStorage.removeItem(chave); } catch (e) {} mostrar(); salvo('Foto removida'); });
        mostrar();
    });
    marcar();
})();
</script>
<?php rodape_publico(); ?>
