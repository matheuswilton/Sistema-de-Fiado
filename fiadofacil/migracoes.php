<?php
// Atualiza o banco automaticamente para o modelo de VARIAS LOJAS (multi-loja).
// Roda sozinho na primeira visita depois da atualizacao e nao faz nada se o banco ja estiver atualizado.
// Pode rodar quantas vezes for preciso: cada passo confere antes de mudar.

if (!isset($conn)) { http_response_code(404); exit(); }
const VERSAO_BANCO = 5;

function migracao_coluna_existe($conn, $tabela, $coluna) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->bind_param("ss", $tabela, $coluna);
    $stmt->execute();
    $existe = (int)$stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();
    return $existe;
}

function migracao_indice_existe($conn, $tabela, $indice) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->bind_param("ss", $tabela, $indice);
    $stmt->execute();
    $existe = (int)$stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();
    return $existe;
}

function migracao_sql($conn, $sql) {
    if (!$conn->query($sql)) {
        error_log("Migracao FiadoFacil falhou: " . $conn->error . " | " . substr($sql, 0, 120));
        return false;
    }
    return true;
}

function migrar_banco($conn) {
    $ok = true;
    // 1) tabela das lojas
    $ok = migracao_sql($conn, "CREATE TABLE IF NOT EXISTS comercios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        slug VARCHAR(60) NOT NULL UNIQUE,
        tema VARCHAR(30) NOT NULL DEFAULT 'padrao',
        status ENUM('ativo','bloqueado') NOT NULL DEFAULT 'ativo',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci") && $ok;
    // a loja 1 recebe os dados que ja existiam antes da atualizacao
    $ok = migracao_sql($conn, "INSERT IGNORE INTO comercios (id, nome, slug, tema) VALUES (1, 'Loja principal', 'principal', 'padrao')") && $ok;

    // 2) clientes passam a pertencer a uma loja
    if (!migracao_coluna_existe($conn, 'clientes', 'comercio_id')) {
        $ok = migracao_sql($conn, "ALTER TABLE clientes ADD comercio_id INT NOT NULL DEFAULT 1 AFTER id,
            ADD INDEX idx_clientes_comercio (comercio_id),
            ADD CONSTRAINT fk_clientes_comercio FOREIGN KEY (comercio_id) REFERENCES comercios(id) ON DELETE CASCADE") && $ok;
    }

    // 3) usuarios: novo perfil "plataforma" e loja de cada usuario
    $ok = migracao_sql($conn, "ALTER TABLE usuarios MODIFY nivel ENUM('admin','comum','cliente','plataforma') NOT NULL DEFAULT 'comum'") && $ok;
    if (!migracao_coluna_existe($conn, 'usuarios', 'comercio_id')) {
        $ok = migracao_sql($conn, "ALTER TABLE usuarios ADD comercio_id INT NULL AFTER id,
            ADD CONSTRAINT fk_usuarios_comercio FOREIGN KEY (comercio_id) REFERENCES comercios(id) ON DELETE CASCADE") && $ok;
        $ok = migracao_sql($conn, "UPDATE usuarios SET comercio_id = 1 WHERE comercio_id IS NULL AND nivel <> 'plataforma'") && $ok;
    }
    // o mesmo nome de usuario pode existir em lojas diferentes: unico POR LOJA
    $resultado = $conn->query("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND NON_UNIQUE = 0 AND INDEX_NAME <> 'PRIMARY' GROUP BY INDEX_NAME HAVING COUNT(*) = 1 AND MAX(COLUMN_NAME) = 'usuario'");
    if ($resultado) {
        while ($linha = $resultado->fetch_row()) {
            $ok = migracao_sql($conn, "ALTER TABLE usuarios DROP INDEX `" . str_replace('`', '', $linha[0]) . "`") && $ok;
        }
    }
    if (!migracao_indice_existe($conn, 'usuarios', 'uq_usuario_por_loja')) {
        $ok = migracao_sql($conn, "ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_por_loja (comercio_id, usuario)") && $ok;
    }

    if ($ok) {
        semear_dados_exemplo($conn);
    }
    return $ok;
}

// Lojas de exemplo e o usuario do dono da plataforma (so entram se ainda nao existirem).
// As senhas ficam aqui apenas como hash; as senhas em texto nao estao no codigo.
function semear_dados_exemplo($conn) {
    $existe = (int)$conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'plataforma'")->fetch_row()[0];
    if ($existe === 0) {
        $stmt = $conn->prepare("INSERT INTO usuarios (comercio_id, nome, usuario, senha, nivel, status) VALUES (NULL, 'Dono da plataforma', 'dono.plataforma', ?, 'plataforma', 'aprovado')");
        $hash = '$2y$10$hUjLNgPZ5DYWDz9yHRC3aedBXA.TtGJ1qwK5tmxjKO6b66VCoFEfy';
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $stmt->close();
    }
    $senha_cliente = '$2y$10$J5muAbnKOmo0Hf4hOyqR4ON.8wLLz5t6RRzqQu.44dwweo1GumjjC';
    $lojas = [
        [
            'nome' => 'Mercearia do Zé', 'slug' => 'mercearia-do-ze', 'tema' => 'mercearia',
            'senha' => '$2y$10$YZGr70qUr/.zTgo1RJykVe.AVK/RySzU0ec2F53quZLC77OsBToe2',
            'admin' => ['Zé da Mercearia', 'ze'], 'comum' => ['Ana (caixa)', 'ana.caixa'], 'cliente_usuario' => 'joao',
            'clientes' => [
                ['João Pereira', '(62) 98111-0001', 'Rua das Mangueiras, 12', 400, [['Arroz, feijão e óleo', 85.00, 12], ['Verduras e legumes', 23.50, 4]], [[40.00, 6]], true],
                ['Lúcia Santos', '(62) 98111-0002', 'Rua 7, 140', 300, [['Frutas da semana', 46.00, 40]], [], false],
                ['Roberto Alves', '(62) 98111-0003', 'Av. Central, 88', 500, [['Compra do mês', 120.00, 8]], [[120.00, 2]], false],
            ],
        ],
        [
            'nome' => 'Padaria Pão Quente', 'slug' => 'padaria-pao-quente', 'tema' => 'padaria',
            'senha' => '$2y$10$GGiJ38vN.m.TNtmF4dRTzebyBmmhC.9ofjxjHFDB7zqz64ZwSJIvy',
            'admin' => ['Rosa (dona)', 'rosa'], 'comum' => ['Pedro (balcão)', 'pedro.balcao'], 'cliente_usuario' => 'joao',
            'clientes' => [
                ['João Pereira', '(62) 98222-0001', 'Rua das Mangueiras, 12', 200, [['Pão francês e leite', 18.90, 3], ['Bolo de fubá', 25.00, 1]], [], true],
                ['Dona Cida', '(62) 98222-0002', 'Rua 3, 51', 150, [['Pães da semana', 62.00, 35]], [], false],
                ['Marcos Lima', '(62) 98222-0003', 'Rua 9, 300', 200, [['Café da manhã', 30.00, 10]], [[15.00, 5]], false],
            ],
        ],
        [
            'nome' => 'Açougue Bom Corte', 'slug' => 'acougue-bom-corte', 'tema' => 'acougue',
            'senha' => '$2y$10$nT26Btk8Ahn64ipdTJSzCeHx9MXqYVny9yR/wf//5FxRT9Li2S25y',
            'admin' => ['Carlos (dono)', 'carlos.dono'], 'comum' => ['Bia (caixa)', 'bia.caixa'], 'cliente_usuario' => 'maria',
            'clientes' => [
                ['Maria Oliveira', '(62) 98333-0001', 'Rua das Palmeiras, 7', 600, [['Picanha 1,2 kg', 95.00, 9], ['Frango inteiro', 28.00, 2]], [[50.00, 5]], true],
                ['Seu Antônio', '(62) 98333-0002', 'Rua 15, 22', 500, [['Costela 3 kg', 135.00, 33]], [], false],
                ['Paula Reis', '(62) 98333-0003', 'Av. Goiás, 1200', 300, [['Carne moída 2 kg', 70.00, 15]], [[70.00, 10]], false],
            ],
        ],
    ];
    foreach ($lojas as $loja) {
        $stmt = $conn->prepare("SELECT id FROM comercios WHERE slug = ?");
        $stmt->bind_param("s", $loja['slug']);
        $stmt->execute();
        $ja_existe = $stmt->get_result()->fetch_row();
        $stmt->close();
        if ($ja_existe) {
            continue;
        }
        $conn->begin_transaction();
        $stmt = $conn->prepare("INSERT INTO comercios (nome, slug, tema) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $loja['nome'], $loja['slug'], $loja['tema']);
        $stmt->execute();
        $comercio_id = $conn->insert_id;
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO usuarios (comercio_id, nome, usuario, senha, nivel, status) VALUES (?, ?, ?, ?, ?, 'aprovado')");
        foreach ([['admin', $loja['admin']], ['comum', $loja['comum']]] as $par) {
            [$nivel, [$nome, $usuario]] = $par;
            $stmt->bind_param("issss", $comercio_id, $nome, $usuario, $loja['senha'], $nivel);
            $stmt->execute();
        }
        $stmt->close();
        foreach ($loja['clientes'] as $c) {
            [$nome, $telefone, $endereco, $limite, $compras, $pagamentos, $tem_acesso] = $c;
            $stmt = $conn->prepare("INSERT INTO clientes (comercio_id, nome, telefone, endereco, limite_credito) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $comercio_id, $nome, $telefone, $endereco, $limite);
            $stmt->execute();
            $cliente_id = $conn->insert_id;
            $stmt->close();
            foreach ($compras as [$descricao, $valor, $dias]) {
                $stmt = $conn->prepare("INSERT INTO compras (cliente_id, descricao, valor, data_compra) VALUES (?, ?, ?, NOW() - INTERVAL ? DAY)");
                $stmt->bind_param("isdi", $cliente_id, $descricao, $valor, $dias);
                $stmt->execute();
                $stmt->close();
            }
            foreach ($pagamentos as [$valor, $dias]) {
                $stmt = $conn->prepare("INSERT INTO pagamentos (cliente_id, valor, data_pagamento) VALUES (?, ?, NOW() - INTERVAL ? DAY)");
                $stmt->bind_param("idi", $cliente_id, $valor, $dias);
                $stmt->execute();
                $stmt->close();
            }
            if ($tem_acesso) {
                $stmt = $conn->prepare("INSERT INTO usuarios (comercio_id, nome, usuario, senha, nivel, status, cliente_id, telefone_solicitado) VALUES (?, ?, ?, ?, 'cliente', 'aprovado', ?, ?)");
                $stmt->bind_param("isssis", $comercio_id, $nome, $loja['cliente_usuario'], $senha_cliente, $cliente_id, $telefone);
                $stmt->execute();
                $stmt->close();
            }
        }
        $conn->commit();
    }
}

// Versao 3: senhas simples das contas de exemplo (so troca se a senha ainda for a antiga, sem mexer em senha que o dono ja trocou)
function atualizar_senhas_exemplo($conn) {
    $trocas = [
        ['$2y$10$KSQ3cxt4n.AJ6Mn86hzvDOtBc.qbh82xjJSXLruon5jutgAPvDFSO', '$2y$10$hUjLNgPZ5DYWDz9yHRC3aedBXA.TtGJ1qwK5tmxjKO6b66VCoFEfy'],
        ['$2y$10$JCdvVtRqiu0lUrz.90md1Oln9Qx7CGYH4J5ic9fnsH6j.Sb.l/tQq', '$2y$10$J5muAbnKOmo0Hf4hOyqR4ON.8wLLz5t6RRzqQu.44dwweo1GumjjC'],
        ['$2y$10$vCx9NofjODziHFmByFw87uenFr5MlJg8lLr/yChCucVSX.Cm9eKdm', '$2y$10$YZGr70qUr/.zTgo1RJykVe.AVK/RySzU0ec2F53quZLC77OsBToe2'],
        ['$2y$10$8e2uYniWbOfVOpFZy8JCUexhbhqtnk2LKq6y0KLCN83xK6whiwJ5q', '$2y$10$GGiJ38vN.m.TNtmF4dRTzebyBmmhC.9ofjxjHFDB7zqz64ZwSJIvy'],
        ['$2y$10$Ogt9VdJS8C5RI/xMxAj9i.B80uAVYbBM047GVQXsiUjQNPChdnWIe', '$2y$10$nT26Btk8Ahn64ipdTJSzCeHx9MXqYVny9yR/wf//5FxRT9Li2S25y'],
    ];
    $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE senha = ?");
    foreach ($trocas as [$antiga, $nova]) {
        $stmt->bind_param("ss", $nova, $antiga);
        $stmt->execute();
    }
    $stmt->close();
}

// Versao 4: integracoes (chave Pix da loja e chave de acesso da API, guardada so como hash)
function migrar_integracoes($conn) {
    $ok = true;
    if (!migracao_coluna_existe($conn, 'comercios', 'chave_pix')) {
        $ok = migracao_sql($conn, "ALTER TABLE comercios ADD chave_pix VARCHAR(77) NULL, ADD pix_cidade VARCHAR(15) NULL") && $ok;
        // chaves Pix ficticias so para as lojas de exemplo mostrarem o QR Code (o dono troca em "Minha loja")
        $ok = migracao_sql($conn, "UPDATE comercios SET chave_pix = CONCAT('pix@', slug, '.com.br'), pix_cidade = 'GOIANIA' WHERE slug IN ('mercearia-do-ze','padaria-pao-quente','acougue-bom-corte') AND chave_pix IS NULL") && $ok;
    }
    if (!migracao_coluna_existe($conn, 'comercios', 'api_token_hash')) {
        $ok = migracao_sql($conn, "ALTER TABLE comercios ADD api_token_hash CHAR(64) NULL, ADD UNIQUE KEY uq_api_token (api_token_hash)") && $ok;
    }
    return $ok;
}

// Versao 5: conta unica do cliente (CPF + senha) que junta as dividas de todas as lojas
function migrar_contas_cpf($conn) {
    $ok = true;
    if (!migracao_coluna_existe($conn, 'clientes', 'cpf')) {
        $ok = migracao_sql($conn, "ALTER TABLE clientes ADD cpf CHAR(11) NULL AFTER telefone, ADD UNIQUE KEY uq_cliente_cpf_por_loja (comercio_id, cpf)") && $ok;
    }
    $ok = migracao_sql($conn, "CREATE TABLE IF NOT EXISTS contas_clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        cpf CHAR(11) NOT NULL UNIQUE,
        telefone VARCHAR(20) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci") && $ok;
    // ligacao da conta com o cadastro do cliente em cada loja; cada loja aprova a sua
    $ok = migracao_sql($conn, "CREATE TABLE IF NOT EXISTS contas_vinculos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conta_id INT NOT NULL,
        cliente_id INT NOT NULL,
        comercio_id INT NOT NULL,
        status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_conta_cliente (conta_id, cliente_id),
        INDEX idx_vinculo_loja (comercio_id, status),
        CONSTRAINT fk_vinculo_conta FOREIGN KEY (conta_id) REFERENCES contas_clientes(id) ON DELETE CASCADE,
        CONSTRAINT fk_vinculo_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
        CONSTRAINT fk_vinculo_comercio FOREIGN KEY (comercio_id) REFERENCES comercios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci") && $ok;
    if ($ok) {
        semear_contas_exemplo($conn);
    }
    return $ok;
}

// Exemplo: o Joao Pereira compra na Mercearia e na Padaria (mesmo CPF); a Maria Oliveira no Acougue. Senha: cliente123
function semear_contas_exemplo($conn) {
    if ((int)$conn->query("SELECT COUNT(*) FROM contas_clientes")->fetch_row()[0] > 0) {
        return;
    }
    $hash = '$2y$10$haA1uwTmMLbCquL7FRnN1.y5HQ88MN1B2LJ3C88dHd4X/P22b4Uza';
    $exemplos = [
        ['João Pereira', '52998224725', '(62) 98111-0001', ['mercearia-do-ze', 'padaria-pao-quente']],
        ['Maria Oliveira', '11144477735', '(62) 98333-0001', ['acougue-bom-corte']],
    ];
    foreach ($exemplos as [$nome, $cpf, $telefone, $lojas]) {
        $marcas = implode(',', array_fill(0, count($lojas), '?'));
        $stmt = $conn->prepare("SELECT c.id, c.comercio_id FROM clientes c JOIN comercios m ON m.id = c.comercio_id WHERE c.nome = ? AND c.cpf IS NULL AND m.slug IN ($marcas)");
        $parametros = array_merge([$nome], $lojas);
        $stmt->bind_param(str_repeat('s', count($parametros)), ...$parametros);
        $stmt->execute();
        $cadastros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!$cadastros) {
            continue;
        }
        $stmt = $conn->prepare("INSERT INTO contas_clientes (nome, cpf, telefone, senha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $cpf, $telefone, $hash);
        $stmt->execute();
        $conta_id = $conn->insert_id;
        $stmt->close();
        foreach ($cadastros as $cadastro) {
            $stmt = $conn->prepare("UPDATE clientes SET cpf = ? WHERE id = ?");
            $stmt->bind_param("si", $cpf, $cadastro['id']);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("INSERT IGNORE INTO contas_vinculos (conta_id, cliente_id, comercio_id, status) VALUES (?, ?, ?, 'aprovado')");
            $stmt->bind_param("iii", $conta_id, $cadastro['id'], $cadastro['comercio_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// roda uma vez por sessao do navegador (depois que o banco esta atualizado, so faz uma consulta rapida)
if (($_SESSION['versao_banco'] ?? 0) !== VERSAO_BANCO) {
    $atualizado = migracao_coluna_existe($conn, 'usuarios', 'comercio_id')
        && migracao_indice_existe($conn, 'usuarios', 'uq_usuario_por_loja')
        && (int)$conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'plataforma'")->fetch_row()[0] > 0;
    if ($atualizado || migrar_banco($conn)) {
        atualizar_senhas_exemplo($conn);
        if (migrar_integracoes($conn) && migrar_contas_cpf($conn)) {
            $_SESSION['versao_banco'] = VERSAO_BANCO;
        }
    }
}
