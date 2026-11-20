<?php
// Desenhos (SVG) de cada tipo de loja, usados nas animacoes: icone do menu, tela vazia, comemoracao e cenario do login.
// Tudo em SVG: fica nitido em qualquer tela, pesa pouco e o CSS anima as partes (fumaca, frutas, pecas penduradas).
if (!function_exists('dinheiro')) { http_response_code(404); exit(); }

// Tema da loja da tela atual (definido no cabecalho), ou vazio
function tema_da_tela() {
    return $GLOBALS['tema_loja'] ?? '';
}

// Icone animado do ramo da loja: bife (acougue), pao (padaria) ou sacola com frutas (mercearia)
function desenho_loja($tema, $classe = "") {
    $fumaca = '<g class="fumaca" fill="none" stroke-linecap="round" stroke-width="3"><path d="M22 19c-4-4 4-6 0-11"/><path d="M32 17c-4-4 4-6 0-11"/><path d="M42 19c-4-4 4-6 0-11"/></g>';
    $desenhos = [
        'acougue' => $fumaca
            . '<path d="M8 39c0-10 11-16 25-15 13 1 23 7 22 16-1 9-9 15-23 15S8 49 8 39z" fill="#b91c1c"/>'
            . '<path d="M9 37c2-8 12-13 24-12 12 1 21 6 22 14" fill="none" stroke="#fde2e4" stroke-width="3.5" stroke-linecap="round"/>'
            . '<path d="M16 42c4-5 11-6 16-3M23 48c5-2 10-2 14 0" fill="none" stroke="#7f1d1d" stroke-width="2" stroke-linecap="round" opacity=".6"/>'
            . '<circle cx="43" cy="41" r="6" fill="#fff7ed"/><circle cx="43" cy="41" r="2.5" fill="#fecaca"/>',
        'padaria' => $fumaca
            . '<path d="M7 45c0-11 11-19 25-19s25 8 25 19c0 4-3 7-7 7H14c-4 0-7-3-7-7z" fill="#d97706"/>'
            . '<path d="M9 42c2-9 12-15 23-15s21 6 23 15" fill="none" stroke="#92400e" stroke-width="2.5" opacity=".45"/>'
            . '<path d="M20 34l4 7M30 31l4 7M40 33l4 7" stroke="#fde68a" stroke-width="3.5" stroke-linecap="round"/>',
        'mercearia' => '<g class="frutas"><g class="fruta fruta-1"><circle cx="24" cy="21" r="8" fill="#dc2626"/><path d="M24 13c1-4 4-5 6-5" stroke="#166534" stroke-width="2.5" fill="none" stroke-linecap="round"/></g>'
            . '<g class="fruta fruta-2"><circle cx="39" cy="20" r="8" fill="#f59e0b"/><circle cx="37" cy="17" r="1.5" fill="#fde68a"/></g>'
            . '<g class="fruta fruta-3"><path d="M47 9c-7 3-9 10-6 16" stroke="#facc15" stroke-width="5.5" fill="none" stroke-linecap="round"/></g></g>'
            . '<path d="M12 25h40l-4 33H16z" fill="#c08a4a"/><path d="M12 25h40l-1 6H13z" fill="#a8743a"/>'
            . '<path d="M25 33v3a7 7 0 0 0 14 0v-3" fill="none" stroke="#7c4a1e" stroke-width="2.5" stroke-linecap="round"/>',
    ];
    if (!isset($desenhos[$tema])) {
        return '';
    }
    return '<svg class="desenho-loja desenho-' . $tema . ' ' . $classe . '" viewBox="0 0 64 64" aria-hidden="true" focusable="false">' . $desenhos[$tema] . '</svg>';
}

// Tela vazia ilustrada ("ninguem devendo"): o desenho da loja pulando, com o texto
function tela_vazia($texto, $tema = null) {
    $tema = $tema ?? tema_da_tela();
    $desenho = desenho_loja($tema, 'desenho-grande');
    if ($desenho === '') {
        return '<p class="lista-vazia">' . htmlspecialchars($texto) . '</p>';
    }
    return '<div class="vazio-loja">' . $desenho . '<p>' . htmlspecialchars($texto) . '</p></div>';
}

// Cenario do login: faixa ilustrada atras do letreiro (vitrine de carnes, prateleira da mercearia ou balcao de paes)
function cena_loja($tema) {
    $svg = '';
    if ($tema === 'acougue') {
        $svg .= '<path d="M0 22H330M870 22H1200" stroke="#fecdd3" stroke-width="4" stroke-linecap="round" opacity=".7"/>';
        foreach ([[70, 'peca'], [170, 'linguica'], [270, 'peca'], [930, 'linguica'], [1030, 'peca'], [1130, 'linguica']] as $i => [$x, $tipo]) {
            $svg .= '<g class="pendurada" style="--atraso:' . ($i * 0.6) . 's" transform="translate(' . $x . ' 22) scale(1.25)"><g class="balanco">'
                . '<path d="M0 0v16a6 6 0 1 0 6 6" fill="none" stroke="#e2e8f0" stroke-width="3" stroke-linecap="round"/>';
            $svg .= $tipo === 'peca'
                ? '<path d="M-14 30c0-8 8-10 14-10s16 4 15 14c-1 12-9 24-16 24s-13-14-13-28z" fill="#be123c"/><path d="M-11 32c3-6 9-8 15-7" stroke="#ffe4e6" stroke-width="3" fill="none" stroke-linecap="round"/>'
                : '<path d="M-3 28c-8 6-8 20 0 26M3 28c8 6 8 20 0 26" fill="none" stroke="#9f1239" stroke-width="9" stroke-linecap="round"/>';
            $svg .= '</g></g>';
        }
        $svg .= '<rect x="0" y="92" width="1200" height="28" fill="#fff1f2" opacity=".18"/><path d="M0 92H1200" stroke="#ffe4e6" stroke-width="2" opacity=".5"/>';
        foreach ([110, 250, 950, 1090] as $x) {
            $svg .= '<ellipse cx="' . $x . '" cy="104" rx="38" ry="9" fill="#9f1239" opacity=".75"/><path d="M' . ($x - 26) . ' 101c10-5 30-5 46 1" stroke="#fecdd3" stroke-width="2.5" fill="none"/>';
        }
    } elseif ($tema === 'mercearia') {
        foreach ([60, 105] as $y) {
            $svg .= '<rect x="0" y="' . $y . '" width="1200" height="6" fill="#a16207" opacity=".75"/>';
        }
        $itens = [[70, 'caixa'], [170, 'frutas'], [280, 'pote'], [880, 'frutas'], [990, 'caixa'], [1100, 'pote']];
        foreach ($itens as $i => [$x, $tipo]) {
            foreach ([0, 1] as $prateleira) {
                $base = $prateleira ? 105 : 60;
                $px = $x + ($prateleira ? 40 : 0);
                if ($tipo === 'caixa') {
                    $svg .= '<rect x="' . ($px - 26) . '" y="' . ($base - 26) . '" width="52" height="26" rx="2" fill="#b7793e"/><path d="M' . ($px - 26) . ' ' . ($base - 17) . 'h52M' . ($px - 26) . ' ' . ($base - 8) . 'h52" stroke="#8a5523" stroke-width="2"/>';
                } elseif ($tipo === 'frutas') {
                    $cores = ['#dc2626', '#f59e0b', '#16a34a', '#dc2626'];
                    foreach ($cores as $j => $cor) {
                        $svg .= '<circle class="fruta-cena" style="--atraso:' . (($i + $j) * 0.25) . 's" cx="' . ($px - 24 + $j * 16) . '" cy="' . ($base - 9) . '" r="8" fill="' . $cor . '"/>';
                    }
                } else {
                    $svg .= '<rect x="' . ($px - 12) . '" y="' . ($base - 30) . '" width="24" height="30" rx="5" fill="#fef3c7" opacity=".9"/><rect x="' . ($px - 13) . '" y="' . ($base - 34) . '" width="26" height="7" rx="2" fill="#dc2626"/>';
                }
            }
        }
    } elseif ($tema === 'padaria') {
        $svg .= '<rect x="0" y="96" width="1200" height="24" fill="#78350f" opacity=".55"/><path d="M0 96H1200" stroke="#fde68a" stroke-width="2" opacity=".45"/>';
        foreach ([[120, 'redondo'], [250, 'baguete'], [380, 'redondo'], [830, 'baguete'], [960, 'redondo'], [1090, 'baguete']] as $i => [$x, $tipo]) {
            $svg .= '<g class="vapor-cena" style="--atraso:' . ($i * 0.5) . 's" fill="none" stroke="#fff7ed" stroke-width="3" stroke-linecap="round"><path d="M' . ($x - 10) . ' 62c-5-6 5-9 0-16"/><path d="M' . ($x + 8) . ' 60c-5-6 5-9 0-16"/></g>';
            $svg .= $tipo === 'redondo'
                ? '<path d="M' . ($x - 30) . ' 96c0-18 13-27 30-27s30 9 30 27z" fill="#d97706"/><path d="M' . ($x - 12) . ' 78l6 9M' . ($x + 4) . ' 76l6 9" stroke="#fde68a" stroke-width="3.5" stroke-linecap="round"/>'
                : '<rect x="' . ($x - 46) . '" y="78" width="92" height="18" rx="9" fill="#b45309" transform="rotate(-6 ' . $x . ' 87)"/><path d="M' . ($x - 26) . ' 84l8 -6M' . ($x - 4) . ' 82l8 -6M' . ($x + 18) . ' 80l8 -6" stroke="#fde68a" stroke-width="3" stroke-linecap="round"/>';
        }
    } else {
        return '';
    }
    $toldo = $tema === 'mercearia' ? '<div class="toldo toldo-login" aria-hidden="true"></div>' : '';
    return $toldo . '<svg class="cena-loja cena-' . $tema . '" viewBox="0 0 1200 120" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">' . $svg . '</svg>';
}

// Balanca de ponteiro (acougue) para o uso do limite
function balanca_limite($porcentagem) {
    $marcas = '';
    for ($i = 0; $i <= 10; $i++) {
        $angulo = deg2rad(180 + $i * 18);
        $x1 = 60 + cos($angulo) * 46; $y1 = 62 + sin($angulo) * 46;
        $x2 = 60 + cos($angulo) * ($i % 5 === 0 ? 36 : 40); $y2 = 62 + sin($angulo) * ($i % 5 === 0 ? 36 : 40);
        $marcas .= '<line x1="' . round($x1, 1) . '" y1="' . round($y1, 1) . '" x2="' . round($x2, 1) . '" y2="' . round($y2, 1) . '"/>';
    }
    return '<div class="balanca" style="--pct:' . (int)$porcentagem . '" aria-hidden="true"><svg viewBox="0 0 120 72">'
        . '<path class="balanca-faixa" d="M14 62A46 46 0 0 1 106 62"/>'
        . '<g class="balanca-marcas">' . $marcas . '</g>'
        . '<g class="ponteiro"><line x1="60" y1="62" x2="60" y2="22"/></g><circle cx="60" cy="62" r="6" class="balanca-eixo"/></svg></div>';
}
