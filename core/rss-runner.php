<?php
function rss_admin_extractor_ejecutar_tarea($tarea)
{
    $feed_url = esc_url_raw($tarea->rss_url);
    $limite = max(0, intval($tarea->rss_limit)); // límite definido en el formulario

    // 1) Descargar el XML
    $resp = wp_remote_get($feed_url, [
        'timeout' => 25,
        'headers' => ['Accept' => 'application/xml,text/xml,*/*;q=0.9'],
    ]);
    if (is_wp_error($resp)) {
        echo "<pre>Error al descargar XML: " . $resp->get_error_message() . "</pre>";
        return;
    }
    $body = wp_remote_retrieve_body($resp);
    if (!$body) {
        echo "<pre>XML vacío en la URL: $feed_url</pre>";
        return;
    }

    // 2) Parsear XML
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if ($xml === false) {
        echo "<pre>Error al parsear XML desde: $feed_url</pre>";
        return;
    }

    // 3) Localizar nodos <noticia>
    $items = [];
    if ($xml->getName() === 'noticia') {
        $items = [$xml];
    } elseif (isset($xml->noticia)) {
        foreach ($xml->noticia as $n)
            $items[] = $n;
    } else {
        foreach ($xml->children() as $child) {
            if ($child->getName() === 'noticia')
                $items[] = $child;
            if (isset($child->noticia)) {
                foreach ($child->noticia as $n)
                    $items[] = $n;
            }
        }
    }

    if (empty($items)) {
        echo "<pre>No se encontraron nodos <noticia>.</pre>";
        return;
    }

    // 4) Preprocesar todas las noticias
    $noticias = [];
    foreach ($items as $n) {
        $titulo_original = trim((string) ($n->titulo ?? ''));
        $contenido_original = (string) ($n->texto ?? '');
        $imagen_url = trim((string) ($n->imagen_url ?? ''));
        $url_fuente = trim((string) ($n->url ?? ''));
        $autores = trim((string) ($n->autores ?? ''));

        $titulo_clean = trim(wp_strip_all_tags($titulo_original));
        $contenido_clean = trim(wp_strip_all_tags($contenido_original));

        if ($titulo_clean === '' && $contenido_clean === '') {
            continue;
        }

        $hash_original = hash('sha256', $titulo_clean . '|' . $contenido_clean);

        $noticias[] = [
            'titulo_original' => $titulo_original,
            'contenido_original' => $contenido_original,
            'titulo_clean' => $titulo_clean,
            'contenido_clean' => $contenido_clean,
            'hash_original' => $hash_original,
            'url_fuente' => $url_fuente,
            'imagen_url' => $imagen_url,
            'autores' => $autores,
        ];
    }

    if (empty($noticias)) {
        echo "<pre>No se pudieron mapear noticias válidas.</pre>";
        return;
    }

    // 5) Recolectar duplicados en lote
    global $wpdb;
    $hashes = wp_list_pluck($noticias, 'hash_original');
    $urls = array_filter(wp_list_pluck($noticias, 'url_fuente'));

    $hashes_sql = $hashes ? "'" . implode("','", array_map('esc_sql', $hashes)) . "'" : "''";
    $urls_sql = $urls ? "'" . implode("','", array_map('esc_sql', $urls)) . "'" : "''";

    $duplicados = [];

    if ($hashes) {
        $res = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_original_hash' AND meta_value IN ($hashes_sql)");
        $duplicados = array_merge($duplicados, $res);
    }
    if ($urls) {
        $res = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_source_url' AND meta_value IN ($urls_sql)");
        $duplicados = array_merge($duplicados, $res);
    }

    $duplicados = array_unique($duplicados);


    $contador = 0;
    $importados = 0;
    $saltados = 0;
    $procesados = 0;

    foreach ($noticias as $n) {
        $contador++;


        // 👉 respetar el límite de NOTICIAS INSERTADAS
        if ($limite > 0 && $importados >= $limite) {
            break;
        }

        if (in_array($n['hash_original'], $duplicados) || ($n['url_fuente'] && in_array($n['url_fuente'], $duplicados))) {
            echo "<pre>Saltado duplicado: {$n['titulo_clean']}</pre>";
            $saltados++;


            continue;
        }

        // Reescrituras opcionales
        $titulo = function_exists('reescribir_titulo_con_ollama')
            ? reescribir_titulo_con_ollama($n['titulo_original'])
            : $n['titulo_original'];

        $contenido = function_exists('reescribir_contenido_con_ollama')
            ? reescribir_contenido_con_ollama($n['contenido_original'])
            : $n['contenido_original'];

        $titulo = wp_strip_all_tags($titulo);
        $contenido = wp_kses_post($contenido);



        $post_data = [
            'post_title' => $titulo ?: '(Sin título)',
            'post_content' => $contenido,
            'post_status' => $tarea->rss_post_status,
            'post_category' => [intval($tarea->rss_category_id)],
            'post_author' => intval($tarea->rss_author_id), // Asignar el autor seleccionado
        ];

        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id) || !$post_id) {
            echo "<pre>Error insertando: {$n['titulo_clean']}</pre>";

            $procesados++;
            continue;
        }

        if ($n['url_fuente']) {
            update_post_meta($post_id, '_source_url', esc_url_raw($n['url_fuente']));
        }
        update_post_meta($post_id, '_original_hash', $n['hash_original']);

        if ($n['autores']) {
            update_post_meta($post_id, '_source_authors', sanitize_text_field($n['autores']));
        }

        if (!empty($n['imagen_url'])) {
            asignar_imagen_destacada(esc_url_raw($n['imagen_url']), $post_id);
        }

        echo "<pre>Insertado post ID $post_id: {$n['titulo_clean']}</pre>";
        $importados++;
        $procesados++;


    }

    echo "<pre>Importados: $importados, Duplicados ignorados: $saltados</pre>";
}

/**
 * Une errores de SimpleXML para mensaje legible
 */
function collect_simplexml_errors()
{
    $errs = libxml_get_errors();
    libxml_clear_errors();
    if (!$errs)
        return 'desconocido';
    $msgs = array_map(function ($e) {
        return trim($e->message);
    }, $errs);
    return implode('; ', array_unique($msgs));
}
