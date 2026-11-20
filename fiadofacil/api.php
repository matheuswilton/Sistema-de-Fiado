<?php
// API REST do FiadoFacil (somente leitura). Cada loja liga a sua em "Minha loja" e recebe uma chave.
// A chave vai no cabecalho X-Api-Key; a loja vem da chave, e todas as consultas filtram por ela.
//   GET api.php?recurso=resumo            totais da loja
//   GET api.php?recurso=clientes          clientes com divida, limite e situacao
//   GET api.php?recurso=cliente&id=5      extrato de um cliente
define('SEM_SESSAO', true);
require_once "funcoes.php";

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store");

function responder($codigo, $dados) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Allow: GET");
    responder(405, ["erro" => "Use o método GET. A API é somente leitura."]);
}
$chave = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
if (!preg_match('/^ff_[0-9a-f]{48}$/', $chave)) {
    responder(401, ["erro" => "Envie a chave da loja no cabeçalho X-Api-Key."]);
}

require_once "conexao.php";
// no banco fica so o hash da chave: compara o hash do que chegou
$hash = hash('sha256', $chave);
$stmt = $conn->prepare("SELECT id, nome, slug, status FROM comercios WHERE api_token_hash = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();
$loja = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$loja) {
    responder(401, ["erro" => "Chave inválida ou cancelada."]);
}
if ($loja['status'] !== 'ativo') {
    responder(403, ["erro" => "Loja bloqueada."]);
}
define('LOJA_DA_API', (int)$loja['id']);

$recurso = $_GET['recurso'] ?? '';
$dados_loja = ["nome" => $loja['nome'], "endereco" => "/loja/" . $loja['slug']];

if ($recurso === 'resumo') {
    $lista = buscar_clientes_situacao($conn);
    $total = 0; $devendo = 0; $inadimplentes = 0;
    foreach ($lista as $c) {
        if ($c['saldo_devedor'] > 0) { $total += $c['saldo_devedor']; $devendo++; }
        if ($c['situacao'] === 'inadimplente') { $inadimplentes++; }
    }
    responder(200, [
        "loja" => $dados_loja,
        "clientes" => count($lista),
        "clientes_devendo" => $devendo,
        "inadimplentes" => $inadimplentes,
        "total_a_receber" => round($total, 2),
        "gerado_em" => date('c'),
    ]);
}

if ($recurso === 'clientes') {
    $clientes = array_map(function ($c) {
        return [
            "id" => (int)$c['id'],
            "nome" => $c['nome'],
            "telefone" => $c['telefone'],
            "limite_credito" => $c['limite_credito'],
            "divida" => max(0, $c['saldo_devedor']),
            "situacao" => $c['situacao'],
            "dias_devendo" => $c['dias_devendo'],
        ];
    }, buscar_clientes_situacao($conn));
    responder(200, ["loja" => $dados_loja, "total" => count($clientes), "clientes" => $clientes]);
}

if ($recurso === 'cliente') {
    $id = (int)($_GET['id'] ?? 0);
    // o cliente precisa ser desta loja; se nao for, responde como se nao existisse
    $stmt = $conn->prepare("SELECT id, nome, telefone, limite_credito, criado_em FROM clientes WHERE id = ? AND comercio_id = ?");
    $loja_id = LOJA_DA_API;
    $stmt->bind_param("ii", $id, $loja_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cliente) {
        responder(404, ["erro" => "Cliente não encontrado nesta loja."]);
    }
    $compras = [];
    $stmt = $conn->prepare("SELECT descricao, valor, data_compra FROM compras WHERE cliente_id = ? ORDER BY data_compra DESC, id DESC LIMIT 100");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $c) {
        $compras[] = ["data" => $c['data_compra'], "descricao" => $c['descricao'], "valor" => (float)$c['valor']];
    }
    $stmt->close();
    $pagamentos = [];
    $stmt = $conn->prepare("SELECT valor, data_pagamento FROM pagamentos WHERE cliente_id = ? ORDER BY data_pagamento DESC, id DESC LIMIT 100");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
        $pagamentos[] = ["data" => $p['data_pagamento'], "valor" => (float)$p['valor']];
    }
    $stmt->close();
    $saldo = saldo_do_cliente($conn, $id);
    responder(200, [
        "loja" => $dados_loja,
        "cliente" => [
            "id" => (int)$cliente['id'],
            "nome" => $cliente['nome'],
            "telefone" => $cliente['telefone'],
            "limite_credito" => (float)$cliente['limite_credito'],
            "divida" => max(0, $saldo),
            "limite_disponivel" => max(0, round((float)$cliente['limite_credito'] - $saldo, 2)),
            "cliente_desde" => $cliente['criado_em'],
        ],
        "compras" => $compras,
        "pagamentos" => $pagamentos,
    ]);
}

responder(400, ["erro" => "Recurso desconhecido. Use resumo, clientes ou cliente&id=N."]);
