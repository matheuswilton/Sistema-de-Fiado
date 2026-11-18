<?php
// Funcoes usadas em varias paginas. Todas as paginas carregam este arquivo (direto ou pelo verifica_login.php).
date_default_timezone_set('America/Sao_Paulo');
if (session_status() === PHP_SESSION_NONE) {
    $conexao_segura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $conexao_segura]);
    session_start();
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
        'recolher'   => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
        'escudo'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'celular'    => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'seta'       => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'caderno'    => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 7h8M8 11h6"/>',
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
function barra_limite($divida, $limite, $tela_do_cliente = false) {
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
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE " . sql_telefone_numeros("telefone") . " = ? AND id <> ?");
    $stmt->bind_param("si", $numeros, $ignorar_id);
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
        ORDER BY c.nome
    ");
    $stmt->bind_param("ssss", $inicio, $fim, $inicio, $fim);
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
    $stmt = $conn->prepare("SELECT limite_credito FROM clientes WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $cliente_id);
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
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $cliente_id);
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
