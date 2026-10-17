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
