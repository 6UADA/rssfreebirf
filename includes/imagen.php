<?php
function asignar_imagen_destacada($imagen_url, $post_id) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $imagen_url = html_entity_decode(trim($imagen_url));
    if (empty($imagen_url) || empty($post_id)) return false;

    // --- helper: crea nombre con extensión en base a MIME ---
    $resolver_nombre = function($url, $mime) {
        $path = parse_url($url, PHP_URL_PATH);
        $base = $path ? basename($path) : 'imagen';
        $base = preg_replace('/\?.*$/', '', $base);
        $base = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $base);

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            'image/svg+xml' => 'svg',
        ];
        $ext = isset($map[strtolower($mime)]) ? $map[strtolower($mime)] : null;

        if (!preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $base)) {
            $base .= $ext ? ('.' . $ext) : '.jpg';
        }
        return $base;
    };

    // --- 1) Intento con download_url() ---
    $tmp = download_url($imagen_url);
    $tmp_ok = true;

    if (is_wp_error($tmp)) {
        $tmp_ok = false;
    } else {
        $info = @getimagesize($tmp);
        if ($info === false || empty($info['mime']) || stripos($info['mime'], 'image/') !== 0) {
            // No es imagen válida; borra y marca como fallo
            @unlink($tmp);
            $tmp_ok = false;
        }
    }

    // --- 2) Fallback con wp_remote_get y headers de navegador ---
    if (!$tmp_ok) {
        $args = [
            'timeout'      => 20,
            'redirection'  => 5,
            'headers'      => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'Referer'    => home_url('/'),
                'Accept'     => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ],
            // Si el servidor remoto tiene SSL mal configurado, puedes probar:
            // 'sslverify' => false, // No recomendado en producción
        ];
        $response = wp_remote_get($imagen_url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return false;
        }

        // Valida por cabecera
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $is_image_header = is_string($content_type) && stripos($content_type, 'image/') === 0;

        // Escribe a un tmp y valida por contenido real
        $tmp = wp_tempnam($imagen_url);
        if (!$tmp) return false;

        $bytes = file_put_contents($tmp, $body);
        if ($bytes === false || $bytes === 0) {
            @unlink($tmp);
            return false;
        }

        $info = @getimagesize($tmp);
        if ($info === false && !$is_image_header) {
            @unlink($tmp);
            return false;
        }

        // Si falta MIME, usa el de cabecera
        if ($info === false && $is_image_header) {
            $info = ['mime' => $content_type];
        }
    }

    // --- 3) Construye el file_array con nombre correcto ---
    $mime = isset($info['mime']) ? strtolower($info['mime']) : 'image/jpeg';
    $nombre_archivo = $resolver_nombre($imagen_url, $mime);

    $file_array = [
        'name'     => $nombre_archivo,
        'tmp_name' => $tmp,
    ];

    // --- 4) Sube y asigna ---
    $attach_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return false;
    }

    set_post_thumbnail($post_id, $attach_id);

    // ALT por defecto = título del post
    if ($post = get_post($post_id)) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($post->post_title));
    }

    return $attach_id;
}
