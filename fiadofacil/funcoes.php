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
