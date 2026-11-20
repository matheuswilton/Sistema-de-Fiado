<?php
// Testes de unidade das funcoes de funcoes.php (sem navegador e sem banco).
// Uso: php testes/unidade.php
define('SEM_SESSAO', true);
require __DIR__ . '/../fiadofacil/funcoes.php';

$falhas = 0;
function confere($condicao, $mensagem) {
    global $falhas;
    echo ($condicao ? "OK    " : "FALHA ") . $mensagem . "\n";
    if (!$condicao) { $falhas++; }
}

// CPF
confere(cpf_valido('529.982.247-25'), 'CPF válido com pontos e traço');
confere(cpf_valido('11144477735'), 'CPF válido só com números');
confere(!cpf_valido('52998224724'), 'CPF com dígito verificador errado');
confere(!cpf_valido('111.111.111-11'), 'CPF com todos os números iguais');
confere(!cpf_valido('123'), 'CPF curto demais');
confere(formatar_cpf('52998224725') === '529.982.247-25', 'Formata o CPF');
confere(mascarar_cpf('52998224725') === '***.982.247-**', 'Mascara o CPF (LGPD)');

// Telefone
confere(telefone_valido('(62) 99999-9999'), 'Celular com DDD é válido');
confere(!telefone_valido('9999-999'), 'Telefone sem DDD é inválido');
confere(formatar_telefone('62999999999') === '(62) 99999-9999', 'Formata o telefone');

// Dinheiro
confere(dinheiro(1234.5) === 'R$ 1.234,50', 'Formata dinheiro em reais');

// Pix (BR Code)
confere(pix_crc16('123456789') === '29B1', 'CRC16 do Pix igual ao padrão (CCITT-FALSE)');
$pix = pix_copia_e_cola('pix@mercearia-do-ze.com.br', 'Mercearia do Zé', 'Goiânia', 68.5);
confere(strpos($pix, '0014br.gov.bcb.pix') !== false, 'Pix tem o identificador do Banco Central');
confere(strpos($pix, '540568.50') !== false, 'Pix leva o valor com 2 casas');
confere(strpos($pix, 'MERCEARIA DO ZE') !== false, 'Pix tira os acentos do nome');
confere(substr($pix, -4) === pix_crc16(substr($pix, 0, -4)), 'Pix termina com o CRC certo');
confere(chave_pix_valida('pix@loja.com.br') && chave_pix_valida('+5562999999999') && chave_pix_valida('52998224725'), 'Aceita chaves Pix de e-mail, celular e CPF');
confere(!chave_pix_valida('chave errada'), 'Recusa chave Pix inválida');

// WhatsApp
$link = link_whatsapp('(62) 98111-0001', 'Olá, tudo bem?');
confere($link === 'https://wa.me/5562981110001?text=Ol%C3%A1%2C%20tudo%20bem%3F', 'Link do WhatsApp com DDI 55 e texto codificado');
confere(link_whatsapp('123', 'x') === null, 'Sem link para telefone inválido');
$msg = mensagem_cobranca('Lúcia Santos', ['nome' => 'Mercearia do Zé', 'chave_pix' => 'pix@x.com'], 46);
confere(strpos($msg, 'Lúcia!') !== false && strpos($msg, 'R$ 46,00') !== false && strpos($msg, 'pix@x.com') !== false, 'Mensagem de cobrança com nome, valor e Pix');

echo $falhas ? "Funções: $falhas FALHA(S)\n" : "Funções: todos os testes passaram\n";
exit($falhas ? 1 : 0);
