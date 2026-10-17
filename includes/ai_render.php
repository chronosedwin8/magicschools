<?php
/**
 * Convierte la respuesta estructurada de cada herramienta en un documento
 * en Markdown, y el Markdown en HTML seguro para mostrar y exportar.
 */

/** Une una lista en viñetas; ignora los elementos vacíos. */
function md_list(array $items, string $bullet = '- '): string
{
    $out = [];
    foreach ($items as $item) {
        $text = is_array($item) ? implode(' · ', array_filter($item, 'is_scalar')) : (string) $item;
        $text = trim($text);
        if ($text !== '') {
            $out[] = $bullet . $text;
        }
    }
    return $out ? implode("\n", $out) . "\n\n" : '';
}

/** Markdown del resultado, según la herramienta. */
function ai_render_markdown(array $tool, array $data): string
{
    $md = '';
    switch ($tool['slug']) {

        case 'plan-de-clase':
            $md .= '# ' . ($data['titulo'] ?? 'Plan de clase') . "\n\n";
            $md .= '**Objetivo de aprendizaje:** ' . ($data['objetivo'] ?? '') . "\n\n";
            if (!empty($data['dba'])) {
                $md .= '**Referente (DBA o estándar):** ' . $data['dba'] . "\n\n";
            }
            if (!empty($data['saberes_previos'])) {
                $md .= "## Saberes previos\n\n" . md_list((array) $data['saberes_previos']);
            }
            $md .= "## Desarrollo de la clase\n\n";
            foreach ((array) ($data['momentos'] ?? []) as $m) {
                $min = !empty($m['minutos']) ? ' (' . (int) $m['minutos'] . ' min)' : '';
                $md .= '### ' . ($m['nombre'] ?? 'Momento') . $min . "\n\n" . ($m['actividad'] ?? '') . "\n\n";
                if (!empty($m['rol_docente'])) {
                    $md .= '*El docente:* ' . $m['rol_docente'] . "\n\n";
                }
            }
            if (!empty($data['recursos']))    $md .= "## Recursos\n\n" . md_list((array) $data['recursos']);
            if (!empty($data['evaluacion']))  $md .= "## Evaluación\n\n" . md_list((array) $data['evaluacion']);
            if (!empty($data['ajustes_dua'])) $md .= "## Ajustes para la diversidad (DUA)\n\n" . md_list((array) $data['ajustes_dua']);
            if (!empty($data['tarea']))       $md .= "## Para la casa\n\n" . $data['tarea'] . "\n";
            break;

        case 'ideas':
            $md .= '# ' . ($data['titulo'] ?? 'Ideas para el aula') . "\n\n";
            $i = 0;
            foreach ((array) ($data['ideas'] ?? []) as $idea) {
                $i++;
                $min = !empty($idea['minutos']) ? ' · ' . (int) $idea['minutos'] . ' min' : '';
                $md .= '## ' . $i . '. ' . ($idea['nombre'] ?? '') . $min . "\n\n" . ($idea['descripcion'] ?? '') . "\n\n";
                if (!empty($idea['materiales']))       $md .= '**Materiales:** ' . $idea['materiales'] . "\n\n";
                if (!empty($idea['por_que_funciona'])) $md .= '**Por qué funciona:** ' . $idea['por_que_funciona'] . "\n\n";
            }
            break;

        case 'correo':
            $md .= '# ' . ($data['asunto'] ?? 'Correo') . "\n\n";
            $md .= ($data['cuerpo'] ?? '') . "\n\n";
            if (!empty($data['version_corta'])) {
                $md .= "## Versión corta para WhatsApp\n\n" . $data['version_corta'] . "\n\n";
            }
            if (!empty($data['sugerencias'])) {
                $md .= "## Antes de enviarlo\n\n" . md_list((array) $data['sugerencias']);
            }
            break;

        case 'quiz':
            $md .= '# ' . ($data['titulo'] ?? 'Evaluación') . "\n\n";
            if (!empty($data['instrucciones'])) {
                $md .= '*' . $data['instrucciones'] . "*\n\n";
            }
            $letras = ['A', 'B', 'C', 'D', 'E', 'F'];
            $clave = [];
            $n = 0;
            foreach ((array) ($data['preguntas'] ?? []) as $q) {
                $n++;
                $md .= '**' . $n . '.** ' . ($q['enunciado'] ?? '') . "\n\n";
                foreach ((array) ($q['opciones'] ?? []) as $k => $op) {
                    $md .= '- ' . ($letras[$k] ?? $k) . ') ' . $op . "\n";
                }
                $md .= "\n";
                $correcta = $letras[(int) ($q['respuesta'] ?? 0)] ?? '?';
                $clave[] = $n . '. ' . $correcta
                    . (!empty($q['justificacion']) ? ' — ' . $q['justificacion'] : '')
                    . (!empty($q['habilidad']) ? ' *(' . $q['habilidad'] . ')*' : '');
            }
            if ($clave) {
                $md .= "## Clave de respuestas\n\n" . md_list($clave, '');
            }
            break;

        case 'presentacion':
            $md .= '# ' . ($data['titulo'] ?? 'Presentación') . "\n\n";
            $n = 0;
            foreach ((array) ($data['diapositivas'] ?? []) as $d) {
                $n++;
                $md .= '## Diapositiva ' . $n . ': ' . ($d['titulo'] ?? '') . "\n\n";
                $md .= md_list((array) ($d['puntos'] ?? []));
                if (!empty($d['nota_docente']))    $md .= '**Para decir:** ' . $d['nota_docente'] . "\n\n";
                if (!empty($d['imagen_sugerida'])) $md .= '*Imagen sugerida: ' . $d['imagen_sugerida'] . "*\n\n";
            }
            break;

        case 'escritos':
            $md .= "# Retroalimentación\n\n" . ($data['resumen'] ?? '') . "\n\n";
            if (!empty($data['fortalezas'])) $md .= "## Lo que está bien logrado\n\n" . md_list((array) $data['fortalezas']);
            if (!empty($data['mejoras'])) {
                $md .= "## Qué puede mejorar\n\n";
                foreach ((array) $data['mejoras'] as $m) {
                    $md .= '### ' . ($m['aspecto'] ?? '') . "\n\n";
                    if (!empty($m['ejemplo_del_texto'])) $md .= '*En su texto:* “' . $m['ejemplo_del_texto'] . "”\n\n";
                    $md .= ($m['como_mejorarlo'] ?? '') . "\n\n";
                }
            }
            if (!empty($data['comentario_para_entregar'])) {
                $md .= "## Comentario para entregar\n\n" . $data['comentario_para_entregar'] . "\n\n";
            }
            if (!empty($data['siguiente_paso'])) $md .= "## Siguiente paso\n\n" . $data['siguiente_paso'] . "\n";
            break;

        case 'boletin':
            $md .= "# Observación de desempeño\n\n" . ($data['observacion'] ?? '') . "\n\n";
            if (!empty($data['version_breve']))      $md .= "## Versión breve para el boletín\n\n" . $data['version_breve'] . "\n\n";
            if (!empty($data['fortalezas']))         $md .= "## Fortalezas\n\n" . md_list((array) $data['fortalezas']);
            if (!empty($data['a_trabajar']))         $md .= "## Aspectos por trabajar\n\n" . md_list((array) $data['a_trabajar']);
            if (!empty($data['sugerencia_familia'])) $md .= "## Sugerencia para la familia\n\n" . $data['sugerencia_familia'] . "\n";
            break;

        case 'reescribir':
            $md .= '# ' . ($data['titulo'] ?? 'Texto adaptado') . "\n\n";
            $md .= ($data['texto_adaptado'] ?? '') . "\n\n";
            if (!empty($data['glosario'])) {
                $md .= "## Glosario\n\n";
                foreach ((array) $data['glosario'] as $g) {
                    $md .= '- **' . ($g['palabra'] ?? '') . ':** ' . ($g['significado'] ?? '') . "\n";
                }
                $md .= "\n";
            }
            if (!empty($data['preguntas'])) $md .= "## Preguntas sobre la lectura\n\n" . md_list((array) $data['preguntas']);
            if (!empty($data['que_cambio'])) $md .= "## Qué se ajustó\n\n" . $data['que_cambio'] . "\n";
            break;

        case 'rubrica':
            $md .= '# ' . ($data['titulo'] ?? 'Rúbrica') . "\n\n";
            $niveles = (array) ($data['niveles'] ?? []);
            if ($niveles) {
                $md .= '**Niveles de desempeño:** ' . implode(' · ', $niveles) . "\n\n";
            }
            foreach ((array) ($data['criterios'] ?? []) as $c) {
                $peso = !empty($c['peso']) ? ' (' . $c['peso'] . ')' : '';
                $md .= '## ' . ($c['nombre'] ?? 'Criterio') . $peso . "\n\n";
                foreach ((array) ($c['descriptores'] ?? []) as $k => $desc) {
                    $md .= '- **' . ($niveles[$k] ?? ('Nivel ' . ($k + 1))) . ':** ' . $desc . "\n";
                }
                $md .= "\n";
            }
            if (!empty($data['uso_sugerido'])) $md .= "## Cómo usarla\n\n" . $data['uso_sugerido'] . "\n";
            break;

        case 'pasos':
            $md .= '# ' . ($data['titulo'] ?? 'Guía de trabajo') . "\n\n";
            if (!empty($data['para_el_estudiante'])) $md .= ($data['para_el_estudiante']) . "\n\n";
            if (!empty($data['lectura']))            $md .= "## Lectura\n\n" . $data['lectura'] . "\n\n";
            $md .= "## Paso a paso\n\n";
            foreach ((array) ($data['pasos'] ?? []) as $p) {
                $min = !empty($p['minutos']) ? ' (' . (int) $p['minutos'] . ' min)' : '';
                $md .= '**' . (int) ($p['numero'] ?? 0) . '.**' . $min . ' ' . ($p['instruccion'] ?? '') . "\n\n";
            }
            if (!empty($data['preguntas']))    $md .= "## Preguntas\n\n" . md_list((array) $data['preguntas']);
            if (!empty($data['para_entregar'])) $md .= "## Qué debes entregar\n\n" . $data['para_entregar'] . "\n\n";
            if (!empty($data['respuestas_orientadoras'])) {
                $md .= "## Respuestas orientadoras (solo para el docente)\n\n" . md_list((array) $data['respuestas_orientadoras']);
            }
            break;

        case 'refinar':
            $contenido = trim((string) ($data['contenido'] ?? ''));
            // Si el modelo ya devolvió su propio título, no se duplica
            if (!str_starts_with($contenido, '# ')) {
                $md .= '# ' . ($data['titulo'] ?? 'Documento ajustado') . "

";
            }
            $md .= $contenido . "
";
            break;

        default:
            // Herramienta sin renderizador propio: se vuelca de forma legible
            foreach ($data as $k => $v) {
                $md .= '## ' . ucfirst(str_replace('_', ' ', (string) $k)) . "\n\n";
                $md .= is_array($v) ? md_list($v) : $v . "\n\n";
            }
    }
    return trim($md) . "\n";
}

/**
 * Markdown a HTML. Se escapa primero y luego se aplican las marcas, así que
 * el texto del modelo nunca puede inyectar etiquetas.
 */
function md_to_html(string $md): string
{
    $html = '';
    $lista = false;
    $cerrarLista = function () use (&$html, &$lista) {
        if ($lista) { $html .= "</ul>\n"; $lista = false; }
    };

    foreach (preg_split('/\r\n|\r|\n/', $md) as $linea) {
        $t = trim($linea);
        if ($t === '') { $cerrarLista(); continue; }

        $esc = e($t);
        // **negrita** y *cursiva*
        $esc = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $esc);
        $esc = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $esc);

        if (str_starts_with($t, '### '))      { $cerrarLista(); $html .= '<h4>' . mb_substr($esc, 4) . "</h4>\n"; }
        elseif (str_starts_with($t, '## '))   { $cerrarLista(); $html .= '<h3>' . mb_substr($esc, 3) . "</h3>\n"; }
        elseif (str_starts_with($t, '# '))    { $cerrarLista(); $html .= '<h2>' . mb_substr($esc, 2) . "</h2>\n"; }
        elseif (str_starts_with($t, '- ')) {
            if (!$lista) { $html .= "<ul>\n"; $lista = true; }
            $html .= '<li>' . mb_substr($esc, 2) . "</li>\n";
        } else {
            $cerrarLista();
            $html .= '<p>' . $esc . "</p>\n";
        }
    }
    $cerrarLista();
    return $html;
}

/** Nombre de archivo seguro a partir del título del documento. */
function ai_filename(string $title, string $ext): string
{
    $base = preg_replace('/[^A-Za-z0-9áéíóúñÁÉÍÓÚÑ \-_]/u', '', $title);
    $base = trim(preg_replace('/\s+/', '-', (string) $base), '-');
    $base = mb_substr($base !== '' ? $base : 'documento', 0, 60);
    return $base . '.' . $ext;
}

/**
 * Descompone el Markdown en bloques simples. Lo usan las exportaciones a Word,
 * que necesitan saber si cada línea es título, viñeta o párrafo.
 * @return array<int, array{type:string, text:string}>
 */
function md_tokens(string $md): array
{
    $tokens = [];
    foreach (preg_split('/\r\n|\r|\n/', $md) as $linea) {
        $t = trim($linea);
        if ($t === '') {
            continue;
        }
        if (str_starts_with($t, '### '))    $tokens[] = ['type' => 'h3', 'text' => mb_substr($t, 4)];
        elseif (str_starts_with($t, '## ')) $tokens[] = ['type' => 'h2', 'text' => mb_substr($t, 3)];
        elseif (str_starts_with($t, '# '))  $tokens[] = ['type' => 'h1', 'text' => mb_substr($t, 2)];
        elseif (str_starts_with($t, '- '))  $tokens[] = ['type' => 'li', 'text' => mb_substr($t, 2)];
        else                                $tokens[] = ['type' => 'p',  'text' => $t];
    }
    return $tokens;
}

/**
 * Parte un texto en fragmentos con y sin negrita, para Word.
 * @return array<int, array{text:string, bold:bool, italic:bool}>
 */
function md_runs(string $text): array
{
    $runs = [];
    $resto = $text;
    while ($resto !== '') {
        if (preg_match('/^(.*?)\*\*(.+?)\*\*(.*)$/su', $resto, $m)) {
            if ($m[1] !== '') $runs[] = ['text' => $m[1], 'bold' => false, 'italic' => false];
            $runs[] = ['text' => $m[2], 'bold' => true, 'italic' => false];
            $resto = $m[3];
        } elseif (preg_match('/^(.*?)\*(.+?)\*(.*)$/su', $resto, $m)) {
            if ($m[1] !== '') $runs[] = ['text' => $m[1], 'bold' => false, 'italic' => false];
            $runs[] = ['text' => $m[2], 'bold' => false, 'italic' => true];
            $resto = $m[3];
        } else {
            $runs[] = ['text' => $resto, 'bold' => false, 'italic' => false];
            $resto = '';
        }
    }
    return $runs;
}
