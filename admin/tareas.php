<?php
function rss_admin_extractor_menu()
{
    add_menu_page(
        'RSS Admin Extractor',
        'RSS Extractor',
        'manage_options',
        'rss-admin-extractor',
        'rss_admin_extractor_pagina',
        'dashicons-rss',

        20
    );
}


add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_rss-admin-extractor')
        return;

    wp_enqueue_style(
        'rss_admin_styles',
        plugin_dir_url(__DIR__) . 'assets/css/rss-admin.css',
        [],
        '1.0'
    );
});




function rss_admin_extractor_pagina()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';

    // Agregar nueva tarea
    if (isset($_POST['nueva_tarea'])) {
        $wpdb->insert($tabla, [
            'nombre_tarea' => sanitize_text_field($_POST['nombre_tarea']),
            'rss_url' => esc_url_raw($_POST['rss_url']),
            'rss_limit' => intval($_POST['rss_limit']),
            'rss_category_id' => intval($_POST['rss_category_id']),
            'rss_post_status' => sanitize_text_field($_POST['rss_post_status']),
            'rss_author_id' => intval($_POST['rss_author_id']) // Guardar el autor seleccionado
        ]);
        echo '<div class="flux-notification flux-success"><div class="flux-notification-icon"><span class="dashicons dashicons-yes-alt"></span></div><div class="flux-notification-content"><h4>Tarea Creada</h4><p>La tarea ha sido agregada correctamente</p></div></div>';
    }

    // Eliminar tarea
    if (isset($_GET['eliminar'])) {
        $wpdb->delete($tabla, ['id' => intval($_GET['eliminar'])]);
        echo '<div class="flux-notification flux-warning"><div class="flux-notification-icon"><span class="dashicons dashicons-trash"></span></div><div class="flux-notification-content"><h4>Tarea Eliminada</h4><p>La tarea ha sido eliminada correctamente</p></div></div>';
    }

    // Ejecutar tarea de prueba
    if (isset($_GET['probar'])) {
        $id = intval($_GET['probar']);
        $tarea = $wpdb->get_row("SELECT * FROM $tabla WHERE id = $id");
        if ($tarea) {
            require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
            $resultado = rss_admin_extractor_ejecutar_tarea($tarea);
            echo '<div class="flux-notification flux-info"><div class="flux-notification-icon"><span class="dashicons dashicons-controls-play"></span></div><div class="flux-notification-content"><h4>Tarea Ejecutada</h4><p>' . esc_html($resultado) . '</p></div></div>';
        } else {
            echo '<div class="flux-notification flux-error"><div class="flux-notification-icon"><span class="dashicons dashicons-warning"></span></div><div class="flux-notification-content"><h4>Error</h4><p>Tarea no encontrada</p></div></div>';
        }
    }

    // Ejecutar todas las tareas
    if (isset($_POST['ejecutar_todas'])) {
        $tareas = $wpdb->get_results("SELECT * FROM $tabla");
        $resultado = '';
        foreach ($tareas as $tarea) {
            require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
            $resultado .= rss_admin_extractor_ejecutar_tarea($tarea) . '<br>';
        }
        echo '<div class="flux-notification flux-info"><div class="flux-notification-icon"><span class="dashicons dashicons-controls-play"></span></div><div class="flux-notification-content"><h4>Tareas Ejecutadas</h4><p>' . esc_html($resultado) . '</p></div></div>';
    }

    // Guardar hora de cron
    if (isset($_POST['guardar_hora_cron'])) {
        $hora = sanitize_text_field($_POST['rss_cron_hora']);
        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            update_option('rss_cron_hora', $hora);
            echo '<div class="flux-notification flux-success"><div class="flux-notification-icon"><span class="dashicons dashicons-yes-alt"></span></div><div class="flux-notification-content"><h4>Hora guardada</h4><p>El cron se ejecutará diariamente a las ' . esc_html($hora) . '</p></div></div>';
        } else {
            echo '<div class="flux-notification flux-error"><div class="flux-notification-icon"><span class="dashicons dashicons-warning"></span></div><div class="flux-notification-content"><h4>Error</h4><p>Hora no válida</p></div></div>';
        }
    }

    // Obtener todas las tareas
    $tareas = $wpdb->get_results("SELECT * FROM $tabla");

    // Pasar variables necesarias a la vista:
    include plugin_dir_path(__FILE__) . 'vistas/pagina-admin.php';


    ?>


    <?php
}
