<?php
/**
 * Fórmulas en LaTeX.
 *
 * El modelo escribe las expresiones matemáticas entre \( ... \) cuando van
 * dentro de una frase y entre \[ ... \] cuando van solas. Desde ahí:
 *
 *   - En pantalla y en la descarga HTML las dibuja MathJax.
 *   - En PDF, Word y PowerPoint no hay JavaScript, así que se convierten a
 *     símbolos Unicode reales (½, √, π, x², ≤) antes de componer el documento.
 *
 * La conversión cubre lo que aparece en matemáticas, física y química
 * escolares. Lo que no se reconoce se deja como estaba, nunca se borra: es
 * preferible que el docente vea el comando a que le falte la fórmula.
 */

/** ¿El texto trae alguna fórmula? */
function tex_present(string $texto): bool
{
    return str_contains($texto, '\\(') || str_contains($texto, '\\[');
}

/** Etiqueta de MathJax para las páginas que muestran fórmulas. */
function tex_mathjax_tag(): string
{
    // Solo los delimitadores de LaTeX: así un precio como $4.600.000 nunca se
    // confunde con una fórmula.
    return '<script>window.MathJax={tex:{inlineMath:[["\\\\(","\\\\)"]],displayMath:[["\\\\[","\\\\]"]],processEscapes:true},'
        . 'options:{skipHtmlTags:["script","noscript","style","textarea","pre","code"]}};</script>'
        . '<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3.2.2/es5/tex-mml-chtml.js"></script>';
}

/** Superíndices y subíndices disponibles en Unicode. */
function tex_scripts(): array
{
    return [
        'sup' => ['0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴', '5' => '⁵', '6' => '⁶', '7' => '⁷',
                  '8' => '⁸', '9' => '⁹', '+' => '⁺', '-' => '⁻', '=' => '⁼', '(' => '⁽', ')' => '⁾', 'n' => 'ⁿ',
                  'i' => 'ⁱ', 'x' => 'ˣ', 'a' => 'ᵃ', 'b' => 'ᵇ', 'c' => 'ᶜ'],
        'sub' => ['0' => '₀', '1' => '₁', '2' => '₂', '3' => '₃', '4' => '₄', '5' => '₅', '6' => '₆', '7' => '₇',
                  '8' => '₈', '9' => '₉', '+' => '₊', '-' => '₋', '=' => '₌', '(' => '₍', ')' => '₎',
                  'a' => 'ₐ', 'e' => 'ₑ', 'i' => 'ᵢ', 'o' => 'ₒ', 'x' => 'ₓ', 'n' => 'ₙ'],
    ];
}

/** Convierte los símbolos y comandos sueltos de una expresión. */
function tex_symbols(string $t): string
{
    $mapa = [
        '\\times' => '×', '\\cdot' => '·', '\\div' => '÷', '\\pm' => '±', '\\mp' => '∓',
        '\\leq' => '≤', '\\le' => '≤', '\\geq' => '≥', '\\ge' => '≥', '\\neq' => '≠', '\\ne' => '≠',
        '\\approx' => '≈', '\\equiv' => '≡', '\\sim' => '∼', '\\propto' => '∝',
        '\\infty' => '∞', '\\partial' => '∂', '\\nabla' => '∇', '\\degree' => '°', '\\circ' => '°',
        '\\rightarrow' => '→', '\\to' => '→', '\\leftarrow' => '←', '\\Rightarrow' => '⇒',
        '\\leftrightarrow' => '↔', '\\Leftrightarrow' => '⇔', '\\uparrow' => '↑', '\\downarrow' => '↓',
        '\\alpha' => 'α', '\\beta' => 'β', '\\gamma' => 'γ', '\\delta' => 'δ', '\\epsilon' => 'ε',
        '\\varepsilon' => 'ε', '\\zeta' => 'ζ', '\\eta' => 'η', '\\theta' => 'θ', '\\lambda' => 'λ',
        '\\mu' => 'μ', '\\nu' => 'ν', '\\xi' => 'ξ', '\\pi' => 'π', '\\rho' => 'ρ', '\\sigma' => 'σ',
        '\\tau' => 'τ', '\\phi' => 'φ', '\\varphi' => 'φ', '\\chi' => 'χ', '\\psi' => 'ψ', '\\omega' => 'ω',
        '\\Gamma' => 'Γ', '\\Delta' => 'Δ', '\\Theta' => 'Θ', '\\Lambda' => 'Λ', '\\Pi' => 'Π',
        '\\Sigma' => 'Σ', '\\Phi' => 'Φ', '\\Psi' => 'Ψ', '\\Omega' => 'Ω',
        '\\sum' => '∑', '\\prod' => '∏', '\\int' => '∫', '\\in' => '∈', '\\notin' => '∉',
        '\\subset' => '⊂', '\\subseteq' => '⊆', '\\cup' => '∪', '\\cap' => '∩', '\\emptyset' => '∅',
        '\\forall' => '∀', '\\exists' => '∃', '\\angle' => '∠', '\\triangle' => '△', '\\perp' => '⊥',
        '\\parallel' => '∥', '\\ldots' => '…', '\\dots' => '…', '\\cdots' => '⋯', '\\%' => '%',
        '\\left' => '', '\\right' => '', '\\,' => ' ', '\\;' => ' ', '\\!' => '', '\\quad' => '  ',
        '\\qquad' => '    ', '\\displaystyle' => '', '\\limits' => '',
    ];
    // Los comandos largos primero, para que \leq no se coma el \le.
    uksort($mapa, fn($a, $b) => strlen($b) <=> strlen($a));
    return strtr($t, $mapa);
}

/** Aplica superíndices/subíndices y limpia llaves sobrantes. */
function tex_inner(string $t): string
{
    $s = tex_scripts();

    // Fracciones conocidas como un solo carácter, el resto como a/b.
    $vulgares = ['1/2' => '½', '1/3' => '⅓', '2/3' => '⅔', '1/4' => '¼', '3/4' => '¾',
                 '1/5' => '⅕', '1/8' => '⅛', '3/8' => '⅜', '5/8' => '⅝', '7/8' => '⅞'];

    // Las llaves se anidan (\frac{\sqrt{x}}{2}), así que se resuelve de adentro
    // hacia afuera: cada vuelta destapa un nivel y se repite hasta que no cambie.
    for ($vuelta = 0; $vuelta < 6; $vuelta++) {
        $antes = $t;

        // \text{...} y \mathrm{...} son texto normal
        $t = preg_replace('/\\\\(?:text|textrm|mathrm|mathit|mathbf|operatorname)\s*\{([^{}]*)\}/u', '$1', $t);
        // \sqrt[3]{x} y \sqrt{x}
        $t = preg_replace('/\\\\sqrt\s*\[\s*3\s*\]\s*\{([^{}]*)\}/u', '∛($1)', $t);
        $t = preg_replace('/\\\\sqrt\s*\{([^{}]*)\}/u', '√($1)', $t);
        // \frac{a}{b} y \dfrac{a}{b}
        $t = preg_replace_callback('/\\\\d?frac\s*\{([^{}]*)\}\s*\{([^{}]*)\}/u', function ($m) use ($vulgares) {
            $num = trim($m[1]);
            $den = trim($m[2]);
            if (isset($vulgares[$num . '/' . $den])) {
                return $vulgares[$num . '/' . $den];
            }
            $parentesis = fn(string $x) => preg_match('/^[\w.,]+$/u', $x) ? $x : '(' . $x . ')';
            return $parentesis($num) . '/' . $parentesis($den);
        }, $t);

        if ($t === $antes) {
            break;
        }
    }

    $t = tex_symbols($t);

    // Exponentes y subíndices: ^{12} ^2 _{n} _3
    $convertir = function (string $contenido, array $tabla): ?string {
        $salida = '';
        foreach (preg_split('//u', $contenido, -1, PREG_SPLIT_NO_EMPTY) as $car) {
            if (!isset($tabla[$car])) {
                return null;                      // si falta un símbolo, mejor no convertir
            }
            $salida .= $tabla[$car];
        }
        return $salida;
    };
    $t = preg_replace_callback('/\^\s*\{([^{}]*)\}|\^\s*(\S)/u', function ($m) use ($s, $convertir) {
        $c = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
        return $convertir($c, $s['sup']) ?? ('^' . $c);
    }, $t);
    $t = preg_replace_callback('/_\s*\{([^{}]*)\}|_\s*(\S)/u', function ($m) use ($s, $convertir) {
        $c = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
        return $convertir($c, $s['sub']) ?? ('_' . $c);
    }, $t);

    // Llaves de agrupación que ya no aportan nada
    $t = preg_replace('/\{([^{}]*)\}/u', '$1', $t);
    return trim(preg_replace('/\s{2,}/u', ' ', $t));
}

/**
 * Convierte las fórmulas de un texto a símbolos Unicode.
 * Es lo que se usa en PDF, Word y PowerPoint, donde MathJax no existe.
 */
function tex_to_unicode(string $texto): string
{
    if (!tex_present($texto)) {
        return $texto;
    }
    // \[ ... \] va en su propio renglón; \( ... \) queda dentro de la frase.
    $texto = preg_replace_callback('/\\\\\[(.+?)\\\\\]/us', fn($m) => "\n" . tex_inner($m[1]) . "\n", $texto);
    $texto = preg_replace_callback('/\\\\\((.+?)\\\\\)/us', fn($m) => tex_inner($m[1]), $texto);
    return $texto;
}

/** Igual que lo anterior, pero recorriendo un arreglo completo (datos de la IA). */
function tex_to_unicode_deep(array $datos): array
{
    foreach ($datos as $k => $v) {
        if (is_string($v)) {
            $datos[$k] = tex_to_unicode($v);
        } elseif (is_array($v)) {
            $datos[$k] = tex_to_unicode_deep($v);
        }
    }
    return $datos;
}
