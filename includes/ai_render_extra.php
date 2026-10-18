<?php
/**
 * Documentos de las herramientas del segundo bloque.
 *
 * Cada función arma el Markdown que luego se vuelve HTML, PDF o Word. La del
 * proyecto ABP es la más larga a propósito: está pensada para imprimirse y
 * llevarse al aula, con sus anexos y espacios para escribir a mano.
 *
 * @return string|null null cuando el slug no es de este bloque
 */
function ai_render_extra(string $slug, array $data): ?string
{
    return match ($slug) {
        'abp'                   => render_abp($data),
        'unidad'                => render_unidad($data),
        'modelo-5e'             => render_5e($data),
        'gancho'                => render_gancho($data),
        'estandares'            => render_estandares($data),
        'problemas-matematicos' => render_problemas($data),
        'tablero-opciones'      => render_tablero($data),
        'vocabulario'           => render_vocabulario($data),
        'metas-apoyo'           => render_metas($data),
        'conducta'              => render_conducta($data),
        'plan-salud'            => render_plan_salud($data),
        'talentos'              => render_talentos($data),
        'circular'              => render_circular($data),
        'redes'                 => render_redes($data),
        'chistes'               => render_chistes($data),
        'consulta-documento'    => render_consulta($data),
        default                 => null,
    };
}

/** Quita del título lo que el documento ya numera: "Sesión 3: x" queda en "x". */
function md_sin_prefijo(string $titulo, string $palabra): string
{
    return trim(preg_replace('/^' . $palabra . '\s*\d*\s*[:.\-–]?\s*/ui', '', trim($titulo)));
}

/** Línea para rellenar a mano en el documento impreso. */
function md_linea(string $etiqueta, int $largo = 40): string
{
    return '**' . $etiqueta . ':** ' . str_repeat('_', $largo) . "\n\n";
}

/** Sección opcional: se escribe solo si hay contenido. */
function md_seccion(string $titulo, array $items, string $nivel = '## '): string
{
    $items = array_filter($items, fn($i) => trim((string) (is_array($i) ? implode('', $i) : $i)) !== '');
    return $items ? $nivel . $titulo . "\n\n" . md_list($items) : '';
}

// ---------------------------------------------------------------------------
// Proyecto ABP: documento largo, listo para imprimir
// ---------------------------------------------------------------------------
function render_abp(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Proyecto de aula') . "\n\n";

    if (!empty($d['resumen'])) {
        $md .= '*' . $d['resumen'] . "*\n\n";
    }
    $md .= "## Ficha del proyecto\n\n";
    $md .= md_linea('Docente', 36) . md_linea('Curso', 20) . md_linea('Fecha de inicio', 20) . md_linea('Fecha de cierre', 20);

    $md .= "## Pregunta orientadora\n\n**" . ($d['pregunta_orientadora'] ?? '') . "**\n\n";
    if (!empty($d['reto'])) {
        $md .= "## El reto\n\n" . $d['reto'] . "\n\n";
    }
    if (!empty($d['justificacion'])) {
        $md .= "## Por qué este proyecto\n\n" . $d['justificacion'] . "\n\n";
    }

    $a = (array) ($d['aprendizajes'] ?? []);
    if ($a) {
        $md .= "## Lo que van a aprender\n\n";
        $md .= md_seccion('Referentes del MEN (DBA y estándares)', (array) ($a['dba'] ?? []), '### ');
        $md .= md_seccion('Competencias', (array) ($a['competencias'] ?? []), '### ');
        $md .= md_seccion('Habilidades para la vida', (array) ($a['habilidades'] ?? []), '### ');
        $md .= md_seccion('Contenidos', (array) ($a['contenidos'] ?? []), '### ');
    }

    $p = (array) ($d['producto_publico'] ?? []);
    if ($p) {
        $md .= "## Producto público\n\n" . ($p['descripcion'] ?? '') . "\n\n";
        if (!empty($p['audiencia'])) {
            $md .= '**Audiencia:** ' . $p['audiencia'] . "\n\n";
        }
        if (!empty($p['formato'])) {
            $md .= '**Formato:** ' . $p['formato'] . "\n\n";
        }
        $md .= md_seccion('Qué hace que quede bien hecho', (array) ($p['criterios_calidad'] ?? []), '### ');
    }

    $e = (array) ($d['evento_detonante'] ?? []);
    if ($e) {
        $md .= "## Arranque: evento detonante\n\n" . ($e['descripcion'] ?? '') . "\n\n";
        $md .= md_seccion('Materiales', (array) ($e['materiales'] ?? []), '### ');
        $md .= md_seccion('Preguntas para abrir la conversación', (array) ($e['preguntas_de_arranque'] ?? []), '### ');
    }
    $md .= md_seccion('Lo que necesitamos saber para resolver el reto', (array) ($d['necesitamos_saber'] ?? []));

    // --- Desarrollo sesión por sesión
    $numero = 0;
    foreach ((array) ($d['fases'] ?? []) as $fase) {
        $semanas = !empty($fase['semanas']) ? ' (' . $fase['semanas'] . ')' : '';
        $md .= '## Fase: ' . ($fase['nombre'] ?? '') . $semanas . "\n\n";
        if (!empty($fase['proposito'])) {
            $md .= '**Propósito:** ' . $fase['proposito'] . "\n\n";
        }
        foreach ((array) ($fase['sesiones'] ?? []) as $s) {
            $numero++;
            $dur = !empty($s['duracion_min']) ? ' · ' . (int) $s['duracion_min'] . ' min' : '';
            $md .= '### Sesión ' . $numero . '. ' . ($s['titulo'] ?? '') . $dur . "\n\n";
            if (!empty($s['objetivo'])) {
                $md .= '**Objetivo:** ' . $s['objetivo'] . "\n\n";
            }
            if (!empty($s['inicio'])) {
                $md .= '**Inicio.** ' . $s['inicio'] . "\n\n";
            }
            if (!empty($s['desarrollo'])) {
                $md .= '**Desarrollo.** ' . $s['desarrollo'] . "\n\n";
            }
            if (!empty($s['cierre'])) {
                $md .= '**Cierre.** ' . $s['cierre'] . "\n\n";
            }
            if (!empty($s['materiales'])) {
                $md .= '**Materiales:** ' . implode(', ', (array) $s['materiales']) . "\n\n";
            }
            if (!empty($s['evidencia'])) {
                $md .= '**Evidencia que queda:** ' . $s['evidencia'] . "\n\n";
            }
        }
        if (!empty($fase['hito'])) {
            $md .= '**Hito de la fase:** ' . $fase['hito'] . "\n\n";
        }
    }

    if (!empty($d['roles_equipo'])) {
        $md .= "## Roles dentro de cada equipo\n\n";
        foreach ((array) $d['roles_equipo'] as $r) {
            $md .= '- **' . ($r['rol'] ?? '') . ':** ' . ($r['responsabilidades'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    if (!empty($d['andamiajes'])) {
        $md .= "## Apoyos previstos\n\n";
        foreach ((array) $d['andamiajes'] as $x) {
            $md .= '- **Si ' . lcfirst((string) ($x['dificultad'] ?? '')) . ':** ' . ($x['apoyo'] ?? '') . "\n";
        }
        $md .= "\n";
    }

    $dua = (array) ($d['dua'] ?? []);
    if ($dua) {
        $md .= "## Diseño universal para el aprendizaje\n\n";
        $md .= md_seccion('Varias formas de presentar la información', (array) ($dua['representacion'] ?? []), '### ');
        $md .= md_seccion('Varias formas de participar y demostrar', (array) ($dua['accion_expresion'] ?? []), '### ');
        $md .= md_seccion('Varias formas de motivar', (array) ($dua['motivacion'] ?? []), '### ');
    }

    $ev = (array) ($d['evaluacion'] ?? []);
    if ($ev) {
        $md .= "## Evaluación\n\n";
        if (!empty($ev['formativa'])) {
            $md .= "### Seguimiento durante el proyecto\n\n";
            foreach ((array) $ev['formativa'] as $f) {
                $md .= '- **' . ($f['momento'] ?? '') . '** · ' . ($f['instrumento'] ?? '')
                    . (!empty($f['que_observar']) ? ': ' . $f['que_observar'] : '') . "\n";
            }
            $md .= "\n";
        }
        if (!empty($ev['rubrica'])) {
            $md .= "### Rúbrica del producto\n\n";
            foreach ((array) $ev['rubrica'] as $c) {
                $peso = !empty($c['peso']) ? ' (' . $c['peso'] . ')' : '';
                $md .= '#### ' . ($c['criterio'] ?? '') . $peso . "\n\n";
                $md .= '- **Superior:** ' . ($c['superior'] ?? '') . "\n";
                $md .= '- **Alto:** ' . ($c['alto'] ?? '') . "\n";
                $md .= '- **Básico:** ' . ($c['basico'] ?? '') . "\n";
                $md .= '- **Bajo:** ' . ($c['bajo'] ?? '') . "\n\n";
            }
        }
        $md .= md_seccion('Autoevaluación del estudiante', (array) ($ev['autoevaluacion'] ?? []), '### ');
        $md .= md_seccion('Coevaluación entre compañeros', (array) ($ev['coevaluacion'] ?? []), '### ');
    }

    if (!empty($d['cronograma'])) {
        $md .= "## Cronograma\n\n";
        foreach ((array) $d['cronograma'] as $c) {
            $md .= '- **' . ($c['semana'] ?? '') . ':** ' . ($c['foco'] ?? '')
                . (!empty($c['entregable']) ? ' → entrega: ' . $c['entregable'] : '') . "\n";
        }
        $md .= "\n";
    }

    $r = (array) ($d['recursos'] ?? []);
    if ($r) {
        $md .= "## Recursos\n\n";
        $md .= md_seccion('Materiales', (array) ($r['materiales'] ?? []), '### ');
        $md .= md_seccion('Si no hay internet ni equipos', (array) ($r['sin_internet'] ?? []), '### ');
        $md .= md_seccion('Aliados posibles', (array) ($r['aliados'] ?? []), '### ');
    }

    $fam = (array) ($d['comunicacion_familias'] ?? []);
    if ($fam) {
        $md .= "## Mensaje para las familias\n\n" . ($fam['mensaje'] ?? '') . "\n\n";
        $md .= md_seccion('Cómo pueden apoyar desde casa', (array) ($fam['como_apoyar'] ?? []), '### ');
    }

    if (!empty($d['riesgos'])) {
        $md .= "## Si algo sale distinto\n\n";
        foreach ((array) $d['riesgos'] as $x) {
            $md .= '- **' . ($x['riesgo'] ?? '') . ':** ' . ($x['plan_b'] ?? '') . "\n";
        }
        $md .= "\n";
    }

    // --- Anexos para fotocopiar
    $an = (array) ($d['anexos'] ?? []);
    if ($an) {
        $md .= "## Anexo 1. Bitácora del estudiante\n\n";
        $md .= md_linea('Nombre', 40) . md_linea('Equipo', 24) . md_linea('Sesión n.º', 10);
        foreach ((array) ($an['bitacora'] ?? []) as $pregunta) {
            $md .= '**' . $pregunta . "**\n\n" . str_repeat('_', 70) . "\n\n" . str_repeat('_', 70) . "\n\n";
        }
        if (!empty($an['lista_cotejo'])) {
            $md .= "## Anexo 2. Lista de chequeo antes de entregar\n\n";
            foreach ((array) $an['lista_cotejo'] as $item) {
                $md .= '- [ ] ' . $item . "\n";
            }
            $md .= "\n";
        }
        if (!empty($an['acuerdos_equipo'])) {
            $md .= "## Anexo 3. Acuerdos del equipo\n\n";
            foreach ((array) $an['acuerdos_equipo'] as $item) {
                $md .= '- ' . $item . "\n";
            }
            $md .= "\n" . md_linea('Firmas del equipo', 60) . "\n";
        }
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_unidad(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Unidad didáctica') . "\n\n";
    if (!empty($d['pregunta_esencial'])) {
        $md .= '**Pregunta esencial:** ' . $d['pregunta_esencial'] . "\n\n";
    }
    $md .= md_seccion('Metas de aprendizaje', (array) ($d['metas'] ?? []));
    $md .= md_seccion('Referentes del MEN', (array) ($d['referentes'] ?? []));
    $md .= md_seccion('Saberes previos que conviene revisar', (array) ($d['saberes_previos'] ?? []));

    $n = 0;
    foreach ((array) ($d['secuencia'] ?? []) as $sem) {
        $md .= '## ' . ($sem['semana'] ?? 'Semana') . ': ' . ($sem['foco'] ?? '') . "\n\n";
        foreach ((array) ($sem['clases'] ?? []) as $c) {
            $n++;
            $md .= '### Clase ' . $n . '. ' . ($c['titulo'] ?? '') . "\n\n";
            $md .= '**Objetivo:** ' . ($c['objetivo'] ?? '') . "\n\n";
            $md .= md_list((array) ($c['actividades'] ?? []));
            if (!empty($c['evidencia'])) {
                $md .= '**Evidencia:** ' . $c['evidencia'] . "\n\n";
            }
        }
    }

    $ev = (array) ($d['evaluacion'] ?? []);
    if ($ev) {
        $md .= "## Evaluación\n\n";
        if (!empty($ev['diagnostica'])) {
            $md .= '**Al inicio.** ' . $ev['diagnostica'] . "\n\n";
        }
        $md .= md_seccion('Durante la unidad', (array) ($ev['formativa'] ?? []), '### ');
        if (!empty($ev['sumativa'])) {
            $md .= '**Al final.** ' . $ev['sumativa'] . "\n\n";
        }
        $md .= md_seccion('Criterios', (array) ($ev['criterios'] ?? []), '### ');
    }
    $dif = (array) ($d['diferenciacion'] ?? []);
    if ($dif) {
        $md .= "## Para que todos avancen\n\n";
        $md .= md_seccion('Si necesita más apoyo', (array) ($dif['apoyo'] ?? []), '### ');
        $md .= md_seccion('Si necesita más reto', (array) ($dif['reto'] ?? []), '### ');
    }
    $md .= md_seccion('Recursos', (array) ($d['recursos'] ?? []));
    $md .= md_seccion('Vocabulario de la unidad', (array) ($d['vocabulario'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_5e(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Clase 5E') . "\n\n";
    $md .= '**Objetivo:** ' . ($d['objetivo'] ?? '') . "\n\n";
    if (!empty($d['referente'])) {
        $md .= '**Referente:** ' . $d['referente'] . "\n\n";
    }
    if (!empty($d['idea_clave'])) {
        $md .= '**Idea clave:** ' . $d['idea_clave'] . "\n\n";
    }
    foreach ((array) ($d['momentos'] ?? []) as $m) {
        $min = !empty($m['minutos']) ? ' (' . (int) $m['minutos'] . ' min)' : '';
        $md .= '## ' . ($m['fase'] ?? '') . $min . "\n\n";
        $md .= '**El docente.** ' . ($m['que_hace_el_docente'] ?? '') . "\n\n";
        $md .= '**Los estudiantes.** ' . ($m['que_hacen_los_estudiantes'] ?? '') . "\n\n";
        $md .= md_seccion('Preguntas para esta fase', (array) ($m['preguntas'] ?? []), '### ');
    }
    $md .= md_seccion('Materiales', (array) ($d['materiales'] ?? []));
    $md .= md_seccion('Cómo saber si aprendieron', (array) ($d['evaluacion'] ?? []));
    $md .= md_seccion('Errores frecuentes en este tema', (array) ($d['errores_frecuentes'] ?? []));
    if (!empty($d['tarea'])) {
        $md .= "## Para la casa\n\n" . $d['tarea'] . "\n\n";
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_gancho(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Ganchos para la clase') . "\n\n";
    foreach ((array) ($d['ganchos'] ?? []) as $i => $g) {
        $md .= '## ' . ($i + 1) . '. ' . ($g['nombre'] ?? '') . "\n\n";
        $md .= ($g['como_hacerlo'] ?? '') . "\n\n";
        if (!empty($g['que_decir'])) {
            $md .= '**Lo que puedes decir:** *"' . $g['que_decir'] . "\"*\n\n";
        }
        if (!empty($g['materiales'])) {
            $md .= '**Materiales:** ' . $g['materiales'] . "\n\n";
        }
        if (!empty($g['conexion_con_el_tema'])) {
            $md .= '**Cómo conecta con el tema:** ' . $g['conexion_con_el_tema'] . "\n\n";
        }
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_estandares(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Desglose del estándar') . "\n\n";
    if (!empty($d['en_palabras_simples'])) {
        $md .= '**En palabras simples:** ' . $d['en_palabras_simples'] . "\n\n";
    }
    $md .= md_seccion('Lo que el estudiante debe saber', (array) ($d['saber'] ?? []));
    $md .= md_seccion('Lo que debe saber hacer', (array) ($d['saber_hacer'] ?? []));
    $md .= md_seccion('Objetivos de clase', (array) ($d['objetivos_de_clase'] ?? []));
    if (!empty($d['vocabulario'])) {
        $md .= "## Vocabulario\n\n";
        foreach ((array) $d['vocabulario'] as $v) {
            $md .= '- **' . ($v['palabra'] ?? '') . ':** ' . ($v['significado'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    if (!empty($d['niveles_de_desempeno'])) {
        $md .= "## Niveles de desempeño\n\n";
        foreach ((array) $d['niveles_de_desempeno'] as $n) {
            $md .= '- **' . ($n['nivel'] ?? '') . ':** ' . ($n['descripcion'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    $md .= md_seccion('Evidencias de aprendizaje', (array) ($d['evidencias'] ?? []));
    $md .= md_seccion('Preguntas rápidas para chequear', (array) ($d['preguntas_de_chequeo'] ?? []));
    $md .= md_seccion('Errores frecuentes', (array) ($d['errores_frecuentes'] ?? []));
    $md .= md_seccion('Lo que debieron aprender antes', (array) ($d['prerrequisitos'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_problemas(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Problemas') . "\n\n";
    if (!empty($d['instrucciones'])) {
        $md .= '*' . $d['instrucciones'] . "*\n\n";
    }
    $md .= md_linea('Nombre', 40) . md_linea('Curso', 16) . md_linea('Fecha', 16);

    $soluciones = '';
    foreach ((array) ($d['problemas'] ?? []) as $i => $p) {
        $n = $i + 1;
        $md .= '**' . $n . '.** ' . ($p['enunciado'] ?? '') . "\n\n";
        if (!empty($p['datos'])) {
            $md .= '*Datos:* ' . implode(' · ', (array) $p['datos']) . "\n\n";
        }
        $md .= str_repeat('_', 70) . "\n\n";

        $soluciones .= '**' . $n . '.** ';
        if (!empty($p['operacion'])) {
            $soluciones .= $p['operacion'] . "\n\n";
        }
        foreach ((array) ($p['procedimiento'] ?? []) as $paso) {
            $soluciones .= '- ' . $paso . "\n";
        }
        $soluciones .= "\n**Respuesta:** " . ($p['respuesta'] ?? '') . "\n\n";
        if (!empty($p['error_tipico'])) {
            $soluciones .= '*Error típico:* ' . $p['error_tipico'] . "\n\n";
        }
    }
    if ($soluciones !== '') {
        $md .= "## Solucionario (para el docente)\n\n" . $soluciones;
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_tablero(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Tablero de opciones') . "\n\n";
    if (!empty($d['instrucciones'])) {
        $md .= $d['instrucciones'] . "\n\n";
    }
    foreach ((array) ($d['opciones'] ?? []) as $i => $o) {
        $md .= '## Opción ' . ($i + 1) . '. ' . ($o['titulo'] ?? '') . "\n\n";
        $md .= ($o['consigna'] ?? '') . "\n\n";
        $meta = array_filter([
            !empty($o['forma_de_expresion']) ? 'Forma: ' . $o['forma_de_expresion'] : '',
            !empty($o['materiales']) ? 'Materiales: ' . $o['materiales'] : '',
            !empty($o['tiempo_estimado']) ? 'Tiempo: ' . $o['tiempo_estimado'] : '',
        ]);
        if ($meta) {
            $md .= '*' . implode(' · ', $meta) . "*\n\n";
        }
    }
    $md .= md_seccion('Criterios con los que se califica cualquier opción', (array) ($d['criterios_comunes'] ?? []));
    $md .= md_seccion('Cómo usar el tablero', (array) ($d['como_usarlo'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_vocabulario(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Vocabulario') . "\n\n";
    foreach ((array) ($d['palabras'] ?? []) as $i => $p) {
        $md .= '## ' . ($i + 1) . '. ' . ($p['palabra'] ?? '') . "\n\n";
        $md .= ($p['definicion'] ?? '') . "\n\n";
        foreach ([
            'ejemplo' => 'Ejemplo',
            'en_ingles' => 'En inglés',
            'truco_para_recordarla' => 'Para recordarla',
            'error_comun' => 'Error común',
        ] as $clave => $etiqueta) {
            if (!empty($p[$clave])) {
                $md .= '**' . $etiqueta . ':** ' . $p[$clave] . "\n\n";
            }
        }
    }
    $md .= md_seccion('Actividades para practicar', (array) ($d['actividades'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_metas(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Metas de apoyo') . "\n\n";
    if (!empty($d['punto_de_partida'])) {
        $md .= "## Punto de partida\n\n" . $d['punto_de_partida'] . "\n\n";
    }
    foreach ((array) ($d['metas'] ?? []) as $i => $m) {
        $md .= '## Meta ' . ($i + 1) . "\n\n**" . ($m['meta'] ?? '') . "**\n\n";
        foreach ([
            'especifica' => 'Específica',
            'medible' => 'Medible',
            'alcanzable' => 'Alcanzable',
            'relevante' => 'Relevante',
            'plazo' => 'Plazo',
            'como_medir' => 'Cómo se mide',
        ] as $clave => $etiqueta) {
            if (!empty($m[$clave])) {
                $md .= '- **' . $etiqueta . ':** ' . $m[$clave] . "\n";
            }
        }
        $md .= "\n";
        $md .= md_seccion('Apoyos', (array) ($m['apoyos'] ?? []), '### ');
    }
    if (!empty($d['seguimiento'])) {
        $md .= "## Seguimiento\n\n";
        foreach ((array) $d['seguimiento'] as $s) {
            $md .= '- **' . ($s['fecha'] ?? '') . ':** ' . ($s['que_registrar'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    $md .= md_seccion('Acuerdos con la familia', (array) ($d['acuerdos_con_la_familia'] ?? []));
    $md .= md_seccion('Cuándo pedir apoyo del equipo de orientación', (array) ($d['senales_de_alerta'] ?? []));
    $md .= "## Firmas\n\n" . md_linea('Docente', 36) . md_linea('Acudiente', 36) . md_linea('Fecha', 20);
    return $md;
}

// ---------------------------------------------------------------------------
function render_conducta(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Apoyo conductual positivo') . "\n\n";
    if (!empty($d['conducta_en_terminos_observables'])) {
        $md .= "## La conducta, descrita sin juicios\n\n" . $d['conducta_en_terminos_observables'] . "\n\n";
    }
    $md .= md_seccion('Para qué podría estar sirviéndole esa conducta', (array) ($d['hipotesis_de_funcion'] ?? []));
    $md .= md_seccion('Antes: cómo prevenirla', (array) ($d['antes_de_que_ocurra'] ?? []));
    $md .= md_seccion('Durante: qué hacer cuando ocurre', (array) ($d['cuando_ocurre'] ?? []));
    $md .= md_seccion('Después: cómo responder', (array) ($d['despues'] ?? []));
    $md .= md_seccion('Qué enseñarle en lugar de esa conducta', (array) ($d['ensenar_en_lugar_de'] ?? []));

    $r = (array) ($d['registro'] ?? []);
    if ($r) {
        $md .= "## Registro\n\n";
        foreach (['que_registrar' => 'Qué registrar', 'cada_cuanto' => 'Cada cuánto', 'meta' => 'Meta'] as $k => $et) {
            if (!empty($r[$k])) {
                $md .= '- **' . $et . ':** ' . $r[$k] . "\n";
            }
        }
        $md .= "\n";
    }
    $md .= md_seccion('Con la familia', (array) ($d['con_la_familia'] ?? []));
    $md .= md_seccion('Cuándo remitir a orientación escolar', (array) ($d['cuando_remitir'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_plan_salud(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Plan de apoyo por condición de salud') . "\n\n";
    if (!empty($d['proposito'])) {
        $md .= $d['proposito'] . "\n\n";
    }
    $md .= md_seccion('Lo que el colegio debe saber', (array) ($d['lo_que_el_colegio_debe_saber'] ?? []));
    if (!empty($d['ajustes'])) {
        $md .= "## Ajustes por momento del día\n\n";
        foreach ((array) $d['ajustes'] as $a) {
            $md .= '- **' . ($a['momento'] ?? '') . ':** ' . ($a['ajuste'] ?? '')
                . (!empty($a['responsable']) ? ' *(responsable: ' . $a['responsable'] . ')*' : '') . "\n";
        }
        $md .= "\n";
    }
    $md .= md_seccion('Evaluación y asistencia', (array) ($d['evaluacion_y_asistencia'] ?? []));
    if (!empty($d['que_hacer_si'])) {
        $md .= "## Qué hacer si…\n\n";
        foreach ((array) $d['que_hacer_si'] as $q) {
            $md .= '### ' . ($q['situacion'] ?? '') . "\n\n" . ($q['paso_a_paso'] ?? '') . "\n\n";
            if (!empty($q['a_quien_avisar'])) {
                $md .= '**Avisar a:** ' . $q['a_quien_avisar'] . "\n\n";
            }
        }
    }
    $md .= md_seccion('Acuerdos con la familia', (array) ($d['acuerdos_con_la_familia'] ?? []));
    $md .= md_seccion('Manejo de la información', (array) ($d['confidencialidad'] ?? []));
    if (!empty($d['seguimiento'])) {
        $md .= "## Seguimiento\n\n" . $d['seguimiento'] . "\n\n";
    }
    $md .= "## Firmas\n\n";
    foreach ((array) ($d['firmas'] ?? ['Docente', 'Acudiente', 'Orientación escolar']) as $f) {
        $md .= md_linea((string) $f, 36);
    }
    $md .= md_linea('Fecha', 20);
    return $md;
}

// ---------------------------------------------------------------------------
function render_talentos(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Plan de enriquecimiento') . "\n\n";
    if (!empty($d['perfil_observado'])) {
        $md .= "## Lo que se observa\n\n" . $d['perfil_observado'] . "\n\n";
    }
    $md .= md_seccion('Metas', (array) ($d['metas'] ?? []));
    if (!empty($d['estrategias'])) {
        $md .= "## Estrategias de enriquecimiento\n\n";
        foreach ((array) $d['estrategias'] as $e) {
            $md .= '### ' . ($e['estrategia'] ?? '') . "\n\n" . ($e['en_que_consiste'] ?? '') . "\n\n";
            if (!empty($e['cuando'])) {
                $md .= '**Cuándo:** ' . $e['cuando'] . "\n\n";
            }
        }
    }
    $p = (array) ($d['proyecto_personal'] ?? []);
    if ($p) {
        $md .= "## Proyecto personal\n\n" . ($p['descripcion'] ?? '') . "\n\n";
        if (!empty($p['producto'])) {
            $md .= '**Producto:** ' . $p['producto'] . "\n\n";
        }
        $md .= md_seccion('Hitos', (array) ($p['hitos'] ?? []), '### ');
    }
    $md .= md_seccion('Recursos', (array) ($d['recursos'] ?? []));
    $md .= md_seccion('Acompañamiento socioemocional', (array) ($d['acompanamiento_socioemocional'] ?? []));
    $md .= md_seccion('Con la familia', (array) ($d['con_la_familia'] ?? []));
    if (!empty($d['seguimiento'])) {
        $md .= "## Seguimiento\n\n";
        foreach ((array) $d['seguimiento'] as $s) {
            $md .= '- **' . ($s['momento'] ?? '') . ':** ' . ($s['que_revisar'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_circular(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Circular para las familias') . "\n\n";
    $md .= ($d['saludo'] ?? '') . "\n\n";
    $md .= md_seccion('Lo que hicimos', (array) ($d['lo_que_hicimos'] ?? []));
    $md .= md_seccion('Lo que viene', (array) ($d['lo_que_viene'] ?? []));
    if (!empty($d['fechas'])) {
        $md .= "## Fechas para tener en cuenta\n\n";
        foreach ((array) $d['fechas'] as $f) {
            $md .= '- **' . ($f['fecha'] ?? '') . ':** ' . ($f['evento'] ?? '') . "\n";
        }
        $md .= "\n";
    }
    $md .= md_seccion('Cómo apoyar desde casa', (array) ($d['como_apoyar_en_casa'] ?? []));
    $md .= md_seccion('Recordatorios', (array) ($d['recordatorios'] ?? []));
    $md .= ($d['despedida'] ?? '') . "\n\n";
    if (!empty($d['version_corta_whatsapp'])) {
        $md .= "## Versión corta para WhatsApp\n\n" . $d['version_corta_whatsapp'] . "\n\n";
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_redes(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Publicación para redes') . "\n\n";
    foreach ((array) ($d['publicaciones'] ?? []) as $p) {
        $md .= '## ' . ($p['red'] ?? 'Publicación') . "\n\n" . ($p['texto'] ?? '') . "\n\n";
        if (!empty($p['etiquetas'])) {
            $md .= '**Etiquetas:** ' . implode(' ', (array) $p['etiquetas']) . "\n\n";
        }
        if (!empty($p['idea_de_imagen'])) {
            $md .= '**Imagen sugerida:** ' . $p['idea_de_imagen'] . "\n\n";
        }
    }
    if (!empty($d['texto_alternativo_imagen'])) {
        $md .= "## Descripción de la imagen (accesibilidad)\n\n" . $d['texto_alternativo_imagen'] . "\n\n";
    }
    $md .= md_seccion('Antes de publicar', (array) ($d['cuidados'] ?? []));
    return $md;
}

// ---------------------------------------------------------------------------
function render_chistes(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Humor de aula') . "\n\n";
    foreach ((array) ($d['elementos'] ?? []) as $i => $e) {
        $md .= '## ' . ($i + 1) . '. ' . ($e['tipo'] ?? '') . "\n\n" . ($e['texto'] ?? '') . "\n\n";
        if (!empty($e['respuesta'])) {
            $md .= '**Respuesta:** ' . $e['respuesta'] . "\n\n";
        }
        if (!empty($e['para_que_sirve'])) {
            $md .= '*' . $e['para_que_sirve'] . "*\n\n";
        }
    }
    return $md;
}

// ---------------------------------------------------------------------------
function render_consulta(array $d): string
{
    $md = '# ' . ($d['titulo'] ?? 'Consulta sobre el documento') . "\n\n";
    $md .= ($d['respuesta'] ?? '') . "\n\n";
    $md .= md_seccion('Puntos clave', (array) ($d['puntos_clave'] ?? []));
    if (!empty($d['tareas'])) {
        $md .= "## Lo que hay que hacer\n\n";
        foreach ((array) $d['tareas'] as $t) {
            $extra = array_filter([$t['responsable'] ?? '', $t['fecha'] ?? '']);
            $md .= '- ' . ($t['tarea'] ?? '') . ($extra ? ' *(' . implode(' · ', $extra) . ')*' : '') . "\n";
        }
        $md .= "\n";
    }
    if (!empty($d['citas'])) {
        $md .= "## Dónde lo dice el documento\n\n";
        foreach ((array) $d['citas'] as $c) {
            $md .= '- *"' . ($c['fragmento'] ?? '') . '"*'
                . (!empty($c['por_que_importa']) ? ' → ' . $c['por_que_importa'] : '') . "\n";
        }
        $md .= "\n";
    }
    $md .= md_seccion('Lo que el documento no dice', (array) ($d['no_esta_en_el_documento'] ?? []));
    return $md;
}
