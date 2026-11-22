<?php
// Funcoes usadas em varias paginas. Todas as paginas carregam este arquivo (direto ou pelo verifica_login.php).
date_default_timezone_set('America/Sao_Paulo');
// a API (api.php) nao usa sessao nem cookie: ela se identifica pela chave de acesso
if (session_status() === PHP_SESSION_NONE && !defined('SEM_SESSAO')) {
    $conexao_segura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $conexao_segura]);
    session_start();
}
// Site de teste (homologacao): a publicacao do teste cria o arquivo ambiente.php com a constante AMBIENTE_TESTE.
// O site oficial nunca tem esse arquivo.
if (is_file(__DIR__ . '/ambiente.php')) { require_once __DIR__ . '/ambiente.php'; }
function eh_ambiente_teste() {
    return defined('AMBIENTE_TESTE') && AMBIENTE_TESTE;
}
// Etiqueta "Site de teste" (so no site de teste)
function faixa_teste() {
    if (!eh_ambiente_teste()) { return ''; }
    return '<div class="faixa-teste" role="note">🧪 <strong>Site de teste</strong>: dados de exemplo. <a href="https://fiadofacil.free.je">Site oficial</a></div>';
}
// Loja (comercio) do usuario logado. Vem SEMPRE da sessao, nunca de formulario ou endereco.
function loja_id() {
    // na API a loja vem da chave de acesso conferida em api.php (constante definida so depois da conferencia)
    if (defined('LOJA_DA_API')) { return LOJA_DA_API; }
    return (int)($_SESSION['comercio_id'] ?? 0);
}
// true se quem esta logado e o dono da plataforma (pode estar "dentro" de uma loja pelo painel)
function eh_plataforma() {
    return !empty($_SESSION['plataforma']);
}
// Dados de uma loja (nome, slug, tema, status) pelo id ou pelo endereco (slug)
function carregar_loja($conn, $id) {
    $stmt = $conn->prepare("SELECT id, nome, slug, tema, status, chave_pix, pix_cidade FROM comercios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $loja = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $loja ?: null;
}
function carregar_loja_por_slug($conn, $slug) {
    $slug = strtolower(trim((string)$slug));
    if (!preg_match('/^[a-z0-9-]{2,60}$/', $slug)) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id, nome, slug, tema, status FROM comercios WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $loja = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $loja ?: null;
}
// Confere se o cliente pertence a loja logada (evita ver ou mexer em cliente de outra loja trocando o id no endereco)
function cliente_da_loja($conn, $cliente_id) {
    $loja = loja_id();
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND comercio_id = ?");
    $stmt->bind_param("ii", $cliente_id, $loja);
    $stmt->execute();
    $existe = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $existe;
}
// Endereco da tela de login de uma loja (mantem a pessoa na loja dela)
function endereco_login($slug = null, $motivo = "") {
    $parametros = [];
    if ($slug) { $parametros['loja'] = $slug; }
    if ($motivo !== "") { $parametros['motivo'] = $motivo; }
    return "login.php" . ($parametros ? "?" . http_build_query($parametros) : "");
}
// Sai do sistema e volta para o login da mesma loja (ou para o painel, se for o dono da plataforma)
function encerrar_sessao($motivo) {
    $slug = $_SESSION['loja_slug'] ?? null;
    $era_plataforma = eh_plataforma();
    session_unset();
    session_destroy();
    header("Location: " . ($era_plataforma ? "painel.php?motivo=" . urlencode($motivo) : endereco_login($slug, $motivo)));
    exit();
}
// true se o usuario logado e administrador
function eh_admin() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}
// guarda uma mensagem para aparecer na proxima pagina (usado depois de redirecionar)
function definir_flash($texto, $tipo = "mensagem") {
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => ($tipo === "erro" ? "erro" : "mensagem")];
}
// mostra a mensagem guardada e apaga
function mostrar_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="' . $flash['tipo'] . '" role="alert">' . htmlspecialchars($flash['texto']) . '</div>';
    }
}
// Circulo com as iniciais do nome (ex.: "Carlos Pereira" -> "CP"). A cor depende do nome, para cada cliente ter sempre a mesma.
function avatar($nome, $classe = "") {
    $partes = preg_split('/\s+/', trim((string)$nome), -1, PREG_SPLIT_NO_EMPTY);
    $iniciais = "";
    if (count($partes) > 0) { $iniciais .= mb_substr($partes[0], 0, 1, 'UTF-8'); }
    if (count($partes) > 1) { $iniciais .= mb_substr($partes[count($partes) - 1], 0, 1, 'UTF-8'); }
    $iniciais = mb_strtoupper($iniciais === "" ? "?" : $iniciais, 'UTF-8');
    $cor = abs(crc32((string)$nome)) % 6;
    return '<span class="avatar avatar-cor-' . $cor . ' ' . $classe . '" aria-hidden="true">' . htmlspecialchars($iniciais) . '</span>';
}
// Icones do menu: desenhos simples em uma cor so (seguem a cor do texto)
function icone($nome, $classe = "") {
    $desenhos = [
        'inicio'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'clientes'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'compra'     => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 2h2l2.7 12.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L22 7H5.1"/>',
        'pagamento'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
        'relatorio'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
        'ranking'    => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'resumo'     => '<path d="M21.2 15.9A10 10 0 1 1 8 2.8"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
        'usuarios'   => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M19 8v6"/><path d="M22 11h-6"/>',
        'aprovar'    => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="m16 11 2 2 4-4"/>',
        'acessos'    => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'senha'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'divida'     => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'sair'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'lua'        => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'sol'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M6.3 17.7l-1.4 1.4M19.1 4.9l-1.4 1.4"/>',
        'mais'       => '<path d="M12 5v14M5 12h14"/>',
        'relogio'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'atencao'    => '<path d="m21.7 18-8-14a2 2 0 0 0-3.5 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'editar'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'seta-esquerda' => '<path d="m15 18-6-6 6-6"/>',
        'recolher'   => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
        'escudo'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'celular'    => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'seta'       => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'caderno'    => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 7h8M8 11h6"/>',
        'whatsapp'   => '<path d="M3 21l1.6-4.7A8.5 8.5 0 1 1 7.9 19.6Z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.6-2-1-1 .8a4 4 0 0 1-1.7-1.7l.8-1-1-2Z"/>',
        'pix'        => '<path d="m12 2.5 4 4-4 4-4-4Z"/><path d="m12 13.5 4 4-4 4-4-4Z"/><path d="m2.5 12 4-4 4 4-4 4Z"/><path d="m13.5 12 4-4 4 4-4 4Z"/>',
        'loja'       => '<path d="M3 9.5 4.5 4h15L21 9.5"/><path d="M3 9.5a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v9h14v-9"/><path d="M10 21v-5h4v5"/>',
        'copiar'     => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
        'cobrancas'  => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15 9a4 4 0 0 1 0 6"/><path d="M18 6a8 8 0 0 1 0 12"/>',
        'lixeira'    => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6M14 11v6"/>',
    ];
    return '<svg class="icone ' . $classe . '" viewBox="0 0 24 24" aria-hidden="true">' . ($desenhos[$nome] ?? '') . '</svg>';
}
// Emblema do FiadoFacil: toldo de mercearia com a moeda "paga". Cada chamada usa um id proprio para o degrade.
function logo($tamanho = 32, $classe = "") {
    static $contador = 0;
    $contador++;
    $id = 'logo-degrade-' . $contador;
    return '<svg class="logo ' . $classe . '" width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 64 64" aria-hidden="true">'
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2563eb"/><stop offset="1" stop-color="#1e3a8a"/></linearGradient></defs>'
        . '<rect width="64" height="64" rx="15" fill="url(#' . $id . ')"/>'
        . '<rect x="10" y="11" width="44" height="5" rx="2.5" fill="#fff"/>'
        . '<rect x="10" y="15" width="8.8" height="12" fill="#fff"/><circle cx="14.4" cy="27" r="4.4" fill="#fff"/>'
        . '<rect x="18.8" y="15" width="8.8" height="12" fill="#facc15"/><circle cx="23.2" cy="27" r="4.4" fill="#facc15"/>'
        . '<rect x="27.6" y="15" width="8.8" height="12" fill="#fff"/><circle cx="32" cy="27" r="4.4" fill="#fff"/>'
        . '<rect x="36.4" y="15" width="8.8" height="12" fill="#facc15"/><circle cx="40.8" cy="27" r="4.4" fill="#facc15"/>'
        . '<rect x="45.2" y="15" width="8.8" height="12" fill="#fff"/><circle cx="49.6" cy="27" r="4.4" fill="#fff"/>'
        . '<rect x="13" y="34" width="38" height="19" rx="2.5" fill="#bfdbfe"/>'
        . '<rect x="17" y="38" width="11" height="15" rx="1.5" fill="#1e3a8a" opacity=".55"/>'
        . '<circle cx="41" cy="44" r="8.5" fill="#facc15" stroke="#1e3a8a" stroke-width="2.6"/>'
        . '<path d="m37 44.2 2.8 2.8 5-5.6" fill="none" stroke="#1e3a8a" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}
// Barra que mostra quanto do limite de credito ja foi usado (verde ate 50%, amarelo ate 80%, vermelho acima)
function barra_limite($divida, $limite, $tela_do_cliente = false, $tema = null) {
    $divida = max(0, (float)$divida);
    $limite = (float)$limite;
    $porcentagem = $limite > 0 ? min(100, round($divida / $limite * 100)) : ($divida > 0 ? 100 : 0);
    $nivel = $porcentagem >= 80 ? 'alto' : ($porcentagem >= 50 ? 'medio' : 'baixo');
    // na tela do proprio cliente nao usamos vermelho, para nao parecer cobranca
    if ($tela_do_cliente && $nivel === 'alto') { $nivel = 'medio'; }
    $html = '<div class="uso-limite uso-' . $nivel . '">';
    $html .= '<div class="uso-limite-texto"><span>Uso do limite de crédito</span><strong>' . $porcentagem . '%</strong></div>';
    $html .= '<div class="uso-limite-trilho" role="progressbar" aria-valuenow="' . $porcentagem . '" aria-valuemin="0" aria-valuemax="100" aria-label="Uso do limite de crédito">';
    $html .= '<div class="uso-limite-barra" style="--uso:' . $porcentagem . '%"></div></div>';
    $html .= '<div class="uso-limite-legenda">' . dinheiro($divida) . ' de ' . dinheiro($limite) . '</div></div>';
    return $html;
}
// Endereco do site no ar (usado na previa de links no WhatsApp e redes sociais)
const ENDERECO_SITE = "https://fiadofacil.free.je";
// Titulo, descricao e imagem que aparecem quando alguem compartilha o link
function metas_compartilhamento($titulo, $descricao) {
    $t = htmlspecialchars($titulo);
    $d = htmlspecialchars($descricao);
    $caminho = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="FiadoFácil">' . "\n";
    echo '<meta property="og:locale" content="pt_BR">' . "\n";
    echo '<meta property="og:title" content="' . $t . '">' . "\n";
    echo '<meta property="og:description" content="' . $d . '">' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars(ENDERECO_SITE . $caminho) . '">' . "\n";
    echo '<meta property="og:image" content="' . ENDERECO_SITE . '/img/previa.png">' . "\n";
    echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n";
    echo '<meta property="og:image:alt" content="FiadoFácil: controle do fiado sem caderninho">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
// Protecao contra robos (spam) em formularios publicos:
// 1) um campo "site" invisivel: pessoas nao veem e deixam vazio, robos costumam preencher;
// 2) tempo minimo: ninguem preenche o formulario em menos de 3 segundos.
function campo_antispam($nome_form) {
    $_SESSION['antispam_' . $nome_form] = time();
    echo '<div class="campo-armadilha" aria-hidden="true"><label for="site">Não preencha este campo</label><input type="text" id="site" name="site" tabindex="-1" autocomplete="off"></div>';
}
function parece_robo($nome_form) {
    $inicio = $_SESSION['antispam_' . $nome_form] ?? 0;
    $preencheu_armadilha = trim((string)($_POST['site'] ?? '')) !== '';
    $rapido_demais = !$inicio || (time() - $inicio) < 3;
    return $preencheu_armadilha || $rapido_demais;
}
// Limite de tentativas de login: depois de 5 erros seguidos, espera 5 minutos
function login_bloqueado() {
    $ate = $_SESSION['login_bloqueado_ate'] ?? 0;
    return $ate > time() ? (int)ceil(($ate - time()) / 60) : 0;
}
function registrar_falha_login() {
    $_SESSION['login_falhas'] = ($_SESSION['login_falhas'] ?? 0) + 1;
    if ($_SESSION['login_falhas'] >= 5) {
        $_SESSION['login_bloqueado_ate'] = time() + 300;
        $_SESSION['login_falhas'] = 0;
    }
}
// protecao contra CSRF: todo formulario POST leva um token escondido
function campo_csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    echo '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function verificar_csrf() {
    $token_enviado = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    $token_sessao = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
    if ($token_sessao === '' || !hash_equals($token_sessao, $token_enviado)) {
        definir_flash("Sua sessão expirou ou o formulário é inválido. Tente novamente.", "erro");
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}
// confere se a string e uma data valida AAAA-MM-DD
function data_valida($data) {
    $data_convertida = DateTime::createFromFormat('Y-m-d', $data);
    return $data_convertida && $data_convertida->format('Y-m-d') === $data;
}
function dinheiro($valor) {
    return "R$ " . number_format((float)$valor, 2, ',', '.');
}
function so_numeros($texto) {
    return preg_replace('/\D/', '', (string)$texto);
}
// (62) 99999-9999 ou (62) 3333-4444. Se nao tiver 10 ou 11 numeros, devolve o texto como veio
function formatar_telefone($texto) {
    $numeros = so_numeros($texto);
    if (strlen($numeros) === 11) {
        return "(" . substr($numeros, 0, 2) . ") " . substr($numeros, 2, 5) . "-" . substr($numeros, 7);
    }
    if (strlen($numeros) === 10) {
        return "(" . substr($numeros, 0, 2) . ") " . substr($numeros, 2, 4) . "-" . substr($numeros, 6);
    }
    return trim($texto);
}
function telefone_valido($texto) {
    $tamanho = strlen(so_numeros($texto));
    return $tamanho === 10 || $tamanho === 11;
}
// pedaco de SQL que deixa so os numeros de uma coluna de telefone (para comparar telefones escritos de formas diferentes)
function sql_telefone_numeros($coluna) {
    return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($coluna,'(',''),')',''),'-',''),' ',''),'.','')";
}
// existe outro cliente com esse telefone? (ignora o cliente de id $ignorar_id)
function telefone_ja_cadastrado($conn, $telefone, $ignorar_id = 0) {
    $numeros = so_numeros($telefone);
    if ($numeros === "") {
        return false;
    }
    $loja = loja_id();
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE comercio_id = ? AND " . sql_telefone_numeros("telefone") . " = ? AND id <> ?");
    $stmt->bind_param("isi", $loja, $numeros, $ignorar_id);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}
// divida atual de um cliente (compras - pagamentos)
function saldo_do_cliente($conn, $cliente_id) {
    $stmt = $conn->prepare("SELECT COALESCE((SELECT SUM(valor) FROM compras WHERE cliente_id = ?), 0) - COALESCE((SELECT SUM(valor) FROM pagamentos WHERE cliente_id = ?), 0) AS saldo");
    $stmt->bind_param("ii", $cliente_id, $cliente_id);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return round((float)$linha['saldo'], 2);
}
// mostra "Anterior / Pagina X de Y / Proxima" mantendo os filtros da URL
function mostrar_paginacao($pagina, $total_paginas, $parametros = []) {
    if ($total_paginas <= 1) {
        return;
    }
    echo '<nav class="paginacao" aria-label="Paginação">';
    if ($pagina > 1) {
        $parametros['pagina'] = $pagina - 1;
        echo '<a href="?' . htmlspecialchars(http_build_query($parametros)) . '">&laquo; Anterior</a>';
    }
    echo '<span>Página ' . $pagina . ' de ' . $total_paginas . '</span>';
    if ($pagina < $total_paginas) {
        $parametros['pagina'] = $pagina + 1;
        echo '<a href="?' . htmlspecialchars(http_build_query($parametros)) . '">Próxima &raquo;</a>';
    }
    echo '</nav>';
}
// Lista todos os clientes com saldo, dias em aberto e situacao. Usada no relatorio, ranking, resumo e inicio.
// Situacao: em_dia (sem divida) | devendo | inadimplente (divida mais antiga com mais de 30 dias)
// Os dias contam a partir da compra mais antiga que ainda NAO foi paga (os pagamentos quitam as compras mais antigas primeiro).
function buscar_clientes_situacao($conn, $data_inicio = null, $data_fim = null, $situacao_filtro = 'todos') {
    $filtra_periodo = ($data_inicio !== null && $data_inicio !== '') || ($data_fim !== null && $data_fim !== '');
    $inicio = ($data_inicio !== null && $data_inicio !== '') ? $data_inicio : '1000-01-01';
    $fim = ($data_fim !== null && $data_fim !== '') ? $data_fim : '9999-12-31';
    $stmt = $conn->prepare("
        SELECT c.id, c.nome, c.telefone, c.limite_credito,
               COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id AND DATE(co.data_compra) BETWEEN ? AND ?), 0) AS compras_periodo,
               COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.cliente_id = c.id AND DATE(p.data_pagamento) BETWEEN ? AND ?), 0) AS pagamentos_periodo,
               COALESCE((SELECT SUM(co2.valor) FROM compras co2 WHERE co2.cliente_id = c.id), 0) AS total_compras,
               COALESCE((SELECT SUM(p2.valor) FROM pagamentos p2 WHERE p2.cliente_id = c.id), 0) AS total_pago,
               (SELECT COUNT(*) FROM pagamentos p3 WHERE p3.cliente_id = c.id) AS total_pagamentos,
               COALESCE(DATEDIFF(NOW(), (
                   SELECT MIN(co3.data_compra) FROM compras co3
                   WHERE co3.cliente_id = c.id
                   AND (SELECT SUM(co4.valor) FROM compras co4
                        WHERE co4.cliente_id = c.id
                        AND (co4.data_compra < co3.data_compra OR (co4.data_compra = co3.data_compra AND co4.id <= co3.id)))
                       > COALESCE((SELECT SUM(p4.valor) FROM pagamentos p4 WHERE p4.cliente_id = c.id), 0)
               )), 0) AS dias_devendo
        FROM clientes c
        WHERE c.comercio_id = ?
        ORDER BY c.nome
    ");
    $loja = loja_id();
    $stmt->bind_param("ssssi", $inicio, $fim, $inicio, $fim, $loja);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $lista = [];
    while ($linha = $resultado->fetch_assoc()) {
        $linha['saldo_devedor'] = round((float)$linha['total_compras'] - (float)$linha['total_pago'], 2);
        $linha['dias_devendo'] = (int)$linha['dias_devendo'];
        $linha['compras_periodo'] = (float)$linha['compras_periodo'];
        $linha['pagamentos_periodo'] = (float)$linha['pagamentos_periodo'];
        $linha['limite_credito'] = (float)$linha['limite_credito'];
        if ($linha['saldo_devedor'] <= 0) {
            $linha['situacao'] = 'em_dia';
            $linha['dias_devendo'] = 0;
        } elseif ($linha['dias_devendo'] > 30) {
            $linha['situacao'] = 'inadimplente';
        } else {
            $linha['situacao'] = 'devendo';
        }
        if ($situacao_filtro !== 'todos' && $linha['situacao'] !== $situacao_filtro) {
            continue;
        }
        if ($filtra_periodo && $linha['compras_periodo'] <= 0 && $linha['pagamentos_periodo'] <= 0) {
            continue;
        }
        $lista[] = $linha;
    }
    $stmt->close();
    usort($lista, function ($a, $b) {
        if ($a['saldo_devedor'] == $b['saldo_devedor']) {
            return strcmp($a['nome'], $b['nome']);
        }
        return $a['saldo_devedor'] < $b['saldo_devedor'] ? 1 : -1;
    });
    return $lista;
}
// Registra uma compra no fiado se o cliente existir, o valor for positivo e a divida nao passar do limite.
// Usa transacao e trava a linha do cliente para duas compras ao mesmo tempo nao estourarem o limite.
// Devolve [true/false, mensagem]
function registrar_compra($conn, $cliente_id, $descricao, $valor) {
    if ($valor <= 0) {
        return [false, "O valor da compra deve ser maior que zero."];
    }
    if ($valor > 99999999.99) {
        return [false, "Valor muito alto. Confira o valor digitado."];
    }
    if ($descricao === "") {
        $descricao = "Compra no fiado";
    }
    $conn->begin_transaction();
    $loja = loja_id();
    $stmt = $conn->prepare("SELECT limite_credito FROM clientes WHERE id = ? AND comercio_id = ? FOR UPDATE");
    $stmt->bind_param("ii", $cliente_id, $loja);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cliente) {
        $conn->rollback();
        return [false, "Cliente não encontrado."];
    }
    $limite = (float)$cliente['limite_credito'];
    $saldo = saldo_do_cliente($conn, $cliente_id);
    if (round($saldo + $valor, 2) > $limite) {
        $conn->rollback();
        $disponivel = max(0, $limite - $saldo);
        return [false, "Limite de crédito excedido. Limite: " . dinheiro($limite) . " | Dívida atual: " . dinheiro($saldo) . " | Disponível: " . dinheiro($disponivel)];
    }
    $stmt = $conn->prepare("INSERT INTO compras (cliente_id, descricao, valor) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $cliente_id, $descricao, $valor);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        return [false, "Erro ao registrar a compra. Nada foi gravado."];
    }
    $stmt->close();
    $conn->commit();
    return [true, "Compra de " . dinheiro($valor) . " registrada. Dívida atual do cliente: " . dinheiro($saldo + $valor) . "."];
}
// Registra um pagamento (nao aceita cliente sem divida nem valor maior que a divida). Devolve [true/false, mensagem]
function registrar_pagamento($conn, $cliente_id, $valor) {
    if ($valor <= 0) {
        return [false, "O valor do pagamento deve ser maior que zero."];
    }
    $conn->begin_transaction();
    $loja = loja_id();
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND comercio_id = ? FOR UPDATE");
    $stmt->bind_param("ii", $cliente_id, $loja);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cliente) {
        $conn->rollback();
        return [false, "Cliente não encontrado."];
    }
    $saldo = saldo_do_cliente($conn, $cliente_id);
    if ($saldo <= 0) {
        $conn->rollback();
        return [false, "Este cliente não possui dívida em aberto."];
    }
    if ($valor > $saldo) {
        $conn->rollback();
        return [false, "O valor pago (" . dinheiro($valor) . ") é maior que a dívida (" . dinheiro($saldo) . ")."];
    }
    $stmt = $conn->prepare("INSERT INTO pagamentos (cliente_id, valor) VALUES (?, ?)");
    $stmt->bind_param("id", $cliente_id, $valor);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        return [false, "Erro ao registrar o pagamento. Nada foi gravado."];
    }
    $stmt->close();
    $conn->commit();
    $nova_divida = round($saldo - $valor, 2);
    if ($nova_divida <= 0) {
        return [true, "Pagamento de " . dinheiro($valor) . " registrado. Dívida quitada!"];
    }
    return [true, "Pagamento de " . dinheiro($valor) . " registrado. Ainda resta " . dinheiro($nova_divida) . "."];
}

// ---------- Integracoes ----------

// Link "clique para conversar" do WhatsApp (wa.me): abre a conversa com a mensagem pronta.
// Nao precisa de chave nem de servidor: quem abre o link e o celular ou o computador de quem clicou.
function link_whatsapp($telefone, $mensagem) {
    $numero = so_numeros($telefone);
    if (strlen($numero) < 10 || strlen($numero) > 11) {
        return null;
    }
    return "https://wa.me/55" . $numero . "?text=" . rawurlencode($mensagem);
}
// Cliente fica inadimplente quando a compra mais antiga ainda nao paga passa de 30 dias
const DIAS_PARA_INADIMPLENCIA = 30;
// Avisar quantos dias antes de o cliente ficar inadimplente
const DIAS_AVISO_ANTES = 3;

// Em quantos dias o cliente fica inadimplente (0 = ja esta; null = nao deve nada)
function dias_para_vencer($saldo, $dias_devendo) {
    if ($saldo <= 0) { return null; }
    return max(0, DIAS_PARA_INADIMPLENCIA + 1 - (int)$dias_devendo);
}

// Mensagem de cobranca organizada para o WhatsApp (o *texto* fica em negrito no WhatsApp).
// Tres modelos: lembrete comum, "vence em X dias" e "em atraso".
function mensagem_cobranca($nome_cliente, $loja, $saldo, $dias_devendo = null) {
    $primeiro_nome = explode(' ', trim($nome_cliente))[0];
    $faltam = $dias_devendo === null ? null : dias_para_vencer($saldo, $dias_devendo);
    $linhas = ["Olá, " . $primeiro_nome . "!", "Aqui é da *" . $loja['nome'] . "*.", ""];
    if ($faltam === 0) {
        $linhas[] = "Sua conta no fiado está em aberto há *" . (int)$dias_devendo . " dias*:";
    } elseif ($faltam !== null && $faltam <= DIAS_AVISO_ANTES) {
        $linhas[] = "Passando para lembrar que sua conta no fiado vence em *" . $faltam . ($faltam === 1 ? " dia" : " dias") . "*:";
    } else {
        $linhas[] = "Passando para lembrar da sua conta no fiado:";
    }
    $linhas[] = "Valor em aberto: *" . dinheiro($saldo) . "*";
    $linhas[] = "";
    if (!empty($loja['chave_pix'])) {
        $linhas[] = "Para pagar pelo Pix:";
        $linhas[] = "Chave Pix: " . $loja['chave_pix'];
        $linhas[] = "Ou, se preferir, pague aqui na loja.";
    } else {
        $linhas[] = "Pode pagar aqui na loja quando passar.";
    }
    $linhas[] = "";
    $linhas[] = $faltam === 0 ? "Se precisar, podemos combinar o pagamento. Se já pagou, desconsidere esta mensagem." : "Se já pagou, desconsidere esta mensagem.";
    $linhas[] = "Obrigado!";
    return implode("\n", $linhas);
}

// Le um valor em reais digitado de qualquer jeito: "R$ 1.234,56", "1234,56" ou "1234.56"
function ler_valor($texto) {
    $texto = trim(str_replace(['R$', ' ', "\u{00A0}"], '', (string)$texto));
    if ($texto === '') { return 0.0; }
    if (strpos($texto, ',') !== false) {
        $texto = str_replace(',', '.', str_replace('.', '', $texto));
    }
    return round((float)$texto, 2);
}

// Pix "copia e cola" (BR Code do Banco Central): texto no padrao EMV, com codigo de conferencia CRC16 no fim.
// O mesmo texto vira o QR Code. E um Pix estatico: o valor ja vai preenchido e o dinheiro cai direto na chave da loja.
function pix_campo($id, $valor) {
    return $id . str_pad((string)strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}
function pix_texto_limpo($texto, $tamanho) {
    $sem_acento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$texto);
    $limpo = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', (string)$sem_acento));
    return substr(trim($limpo), 0, $tamanho);
}
function pix_crc16($texto) {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($texto); $i < $n; $i++) {
        $crc ^= ord($texto[$i]) << 8;
        for ($bit = 0; $bit < 8; $bit++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}
function pix_copia_e_cola($chave, $nome_recebedor, $cidade, $valor, $identificador = '***') {
    $conta = pix_campo('00', 'br.gov.bcb.pix') . pix_campo('01', trim($chave));
    $texto = pix_campo('00', '01')
        . pix_campo('26', $conta)
        . pix_campo('52', '0000')
        . pix_campo('53', '986')
        . pix_campo('54', number_format($valor, 2, '.', ''))
        . pix_campo('58', 'BR')
        . pix_campo('59', pix_texto_limpo($nome_recebedor, 25) ?: 'LOJA')
        . pix_campo('60', pix_texto_limpo($cidade, 15) ?: 'BRASIL')
        . pix_campo('62', pix_campo('05', $identificador))
        . '6304';
    return $texto . pix_crc16($texto);
}
// Confere se a chave Pix tem um formato aceito: e-mail, telefone (+55...), CPF/CNPJ (so numeros) ou chave aleatoria
function chave_pix_valida($chave) {
    $chave = trim($chave);
    return (bool)(filter_var($chave, FILTER_VALIDATE_EMAIL) && strlen($chave) <= 77)
        || preg_match('/^\+55\d{10,11}$/', $chave)
        || preg_match('/^\d{11}$|^\d{14}$/', $chave)
        || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave);
}

// ---------- CPF e conta unica do cliente ----------

// Confere os dois digitos verificadores do CPF (e recusa 000.000.000-00, 111.111.111-11...)
function cpf_valido($cpf) {
    $cpf = so_numeros($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}
function formatar_cpf($cpf) {
    $n = so_numeros($cpf);
    return strlen($n) === 11 ? substr($n, 0, 3) . "." . substr($n, 3, 3) . "." . substr($n, 6, 3) . "-" . substr($n, 9, 2) : $cpf;
}
// mostra so o miolo do CPF (LGPD: a tela nao precisa do numero inteiro)
function mascarar_cpf($cpf) {
    $n = so_numeros($cpf);
    return strlen($n) === 11 ? "***." . substr($n, 3, 3) . "." . substr($n, 6, 3) . "-**" : "";
}
// Ja existe outro cliente com este CPF nesta loja?
function cpf_ja_cadastrado($conn, $cpf, $ignorar_id = 0) {
    $loja = loja_id();
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE comercio_id = ? AND cpf = ? AND id <> ?");
    $stmt->bind_param("isi", $loja, $cpf, $ignorar_id);
    $stmt->execute();
    $existe = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $existe;
}
// Cria os pedidos de ligacao (pendentes) com as lojas que tem este CPF no cadastro e ainda nao foram ligadas
function sincronizar_vinculos($conn, $conta_id, $cpf) {
    $stmt = $conn->prepare("INSERT IGNORE INTO contas_vinculos (conta_id, cliente_id, comercio_id, status)
        SELECT ?, c.id, c.comercio_id, 'pendente' FROM clientes c JOIN comercios m ON m.id = c.comercio_id
        WHERE c.cpf = ? AND m.status = 'ativo'");
    $stmt->bind_param("is", $conta_id, $cpf);
    $stmt->execute();
    $stmt->close();
}
// Pedidos pendentes desta loja (acessos antigos por usuario + contas por CPF), para o contador do menu
function total_pedidos_pendentes($conn, $loja) {
    $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM usuarios WHERE comercio_id = ? AND nivel = 'cliente' AND status = 'pendente')
        + (SELECT COUNT(*) FROM contas_vinculos WHERE comercio_id = ? AND status = 'pendente')");
    $stmt->bind_param("ii", $loja, $loja);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $total;
}

// ---------- Foto da loja (fundo do login, com tratamento de cinema) ----------
const PASTA_FOTOS_LOJAS = __DIR__ . '/uploads/lojas';
// Endereco da foto da loja (ou null se ela ainda nao enviou). O ?v= faz o navegador buscar a foto nova ao trocar.
function foto_da_loja($loja_id) {
    $arquivo = PASTA_FOTOS_LOJAS . '/' . (int)$loja_id . '.jpg';
    // Endereco absoluto (com / no inicio): a foto e usada dentro do CSS, que fica na pasta css/,
    // e um endereco relativo seria procurado em css/uploads/ e nao apareceria.
    return is_file($arquivo) ? '/uploads/lojas/' . (int)$loja_id . '.jpg?v=' . filemtime($arquivo) : null;
}
// Recebe a foto enviada: confere se e imagem de verdade, diminui para ate 1920 px e regrava em JPEG.
// Regravar a imagem descarta qualquer conteudo escondido no arquivo original. Devolve [true/false, mensagem].
function salvar_foto_da_loja($loja_id, $arquivo) {
    if (!isset($arquivo['tmp_name']) || $arquivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
        return [false, "Não foi possível receber a foto. Tente de novo."];
    }
    if ($arquivo['size'] > 6 * 1024 * 1024) {
        return [false, "A foto é grande demais (máximo de 6 MB)."];
    }
    $info = @getimagesize($arquivo['tmp_name']);
    $tipos = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    if (!$info || !isset($tipos[$info[2]]) || !function_exists($tipos[$info[2]])) {
        return [false, "Envie uma foto em JPG, PNG ou WEBP."];
    }
    [$largura, $altura] = $info;
    if ($largura < 800 || $altura < 400) {
        return [false, "A foto é pequena demais. Use uma de pelo menos 800 × 400 pixels."];
    }
    $original = @$tipos[$info[2]]($arquivo['tmp_name']);
    if (!$original) {
        return [false, "Não foi possível abrir a foto."];
    }
    $nova_largura = min(1920, $largura);
    $nova_altura = (int)round($altura * $nova_largura / $largura);
    $foto = imagecreatetruecolor($nova_largura, $nova_altura);
    imagecopyresampled($foto, $original, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura, $altura);
    if (!is_dir(PASTA_FOTOS_LOJAS)) {
        @mkdir(PASTA_FOTOS_LOJAS, 0755, true);
    }
    $ok = imagejpeg($foto, PASTA_FOTOS_LOJAS . '/' . (int)$loja_id . '.jpg', 82);
    imagedestroy($foto);
    imagedestroy($original);
    return $ok ? [true, "Foto da loja salva! Ela já aparece na tela de entrar."] : [false, "Não foi possível salvar a foto no servidor."];
}
function remover_foto_da_loja($loja_id) {
    $arquivo = PASTA_FOTOS_LOJAS . '/' . (int)$loja_id . '.jpg';
    if (is_file($arquivo)) { unlink($arquivo); }
}

