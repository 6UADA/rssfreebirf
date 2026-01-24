<?php
defined('ABSPATH') || exit;

function rss_admin_extractor_ejecutar_tarea($tarea)
{
    $feed_url = esc_url_raw($tarea->rss_url);
    $limite = max(0, intval($tarea->rss_limit));

    // 1) Descargar XML
    $resp = wp_remote_get($feed_url, [
        'timeout' => 25,
        'headers' => ['Accept' => 'application/xml,text/xml,*/*;q=0.9'],
    ]);
    if (is_wp_error($resp)) {
        error_log("RSS ERROR: " . $resp->get_error_message());
        return "Error: " . $resp->get_error_message();
    }

    require_once plugin_dir_path(__FILE__) . '../includes/ollama.php';
    require_once plugin_dir_path(__FILE__) . '../includes/imagen.php';

    $body = wp_remote_retrieve_body($resp);
    if (!$body)
        return "Error: Cuerpo de respuesta vacío.";

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if ($xml === false) {
        error_log("RSS ERROR XML");
        return "Error: Formato XML inválido.";
    }

    // 2) Extraer nodos <noticia> o <item> (Soporte básico RSS estándar)
    $items = [];
    $is_standard_rss = false;

    if ($xml->xpath('//noticia')) {
        $items = $xml->xpath('//noticia');
    } elseif ($xml->xpath('//item')) {
        $items = $xml->xpath('//item');
        $is_standard_rss = true;
    }

    if (!$items)
        return "No se encontraron noticias en el feed.";

    global $wpdb;

    $importados = 0;
    $saltados = 0;

    foreach ($items as $n) {

        if ($limite > 0 && $importados >= $limite)
            break;

        if ($is_standard_rss) {
            $titulo_original = trim((string) ($n->title ?? ''));
            $contenido_original = trim((string) ($n->description ?? $n->children('content', true)->encoded ?? ''));
            $imagen_url = '';
            $url_fuente = trim((string) ($n->link ?? ''));
            $autores = '';
        } else {
            $titulo_original = trim((string) ($n->titulo ?? ''));
            $contenido_original = trim((string) ($n->texto ?? ''));
            $imagen_url = trim((string) ($n->imagen_url ?? ''));
            $url_fuente = trim((string) ($n->url ?? ''));
            $autores = trim((string) ($n->autores ?? ''));
        }

        if (!$titulo_original || !$contenido_original)
            continue;

        $hash = hash('sha256', $titulo_original . '|' . $contenido_original);

        // duplicados
        $ya = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_original_hash' AND meta_value = %s LIMIT 1",
            $hash
        ));

        if ($ya) {
            $saltados++;
            continue;
        }

        // 🔥 REESCRITURA REAL
        error_log("[RSS RUNNER] Iniciando reescritura para: " . substr($titulo_original, 0, 40));

        $titulo = function_exists('reescribir_titulo_con_ollama')
            ? reescribir_titulo_con_ollama($titulo_original)
            : $titulo_original;

        if (function_exists('reescribir_contenido_con_ollama')) {
            $contenido = reescribir_contenido_con_ollama($contenido_original);
        } else {
            $contenido = $contenido_original;
        }

        // Insertar post
        $post_id = wp_insert_post([
            'post_title' => wp_strip_all_tags($titulo),
            'post_content' => wp_kses_post($contenido),
            'post_status' => $tarea->rss_post_status,
            'post_category' => [intval($tarea->rss_category_id)],
            'post_author' => intval($tarea->rss_author_id),
        ], true);

        if (is_wp_error($post_id))
            continue;

        update_post_meta($post_id, '_original_hash', $hash);

        if ($url_fuente)
            update_post_meta($post_id, '_source_url', esc_url_raw($url_fuente));

        if ($autores)
            update_post_meta($post_id, '_source_authors', sanitize_text_field($autores));

        if ($imagen_url)
            asignar_imagen_destacada(esc_url_raw($imagen_url), $post_id);

        $importados++;
    }

    $res = "Finalizado: $importados importados, $saltados saltados.";
    error_log("RSS RESULTADO: $res");
    return $res;
}
