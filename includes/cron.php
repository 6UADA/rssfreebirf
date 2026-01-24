<?php
defined('ABSPATH') || exit;

// Agregar intervalo personalizado de 5 minutos (para que el cron corra frecuente)
add_filter('cron_schedules', function ($schedules) {
    $schedules['cada_5_minutos'] = array(
        'interval' => 300, // 5 minutos = 300 segundos
        'display' => 'Cada 5 minutos'
    );
    return $schedules;
});

// Hook para verificar si es hora de ejecutar tareas según hora configurada
add_action('rss_admin_extractor_verificar_hora', function () {
    // Obtenemos la hora configurada (ejemplo formato '09:11'), por defecto '07:00'
    $hora_configurada = get_option('rss_cron_hora', '07:00');
    $zona_horaria = wp_timezone();

    $ahora = new DateTime('now', $zona_horaria);
    $hora_actual = $ahora->format('H:i');

    // Creamos DateTime para la hora configurada con la fecha actual
    $fecha_hora_configurada = DateTime::createFromFormat('H:i', $hora_configurada, $zona_horaria);
    $fecha_hora_configurada->setDate(
        $ahora->format('Y'),
        $ahora->format('m'),
        $ahora->format('d')
    );

    $ts_configurada = $fecha_hora_configurada->getTimestamp();
    $ts_actual = $ahora->getTimestamp();

    $diferencia = abs($ts_actual - $ts_configurada);

    // Si la diferencia es menor o igual a 5 minutos (300 segundos), ejecutamos el cron
    if ($diferencia <= 300) {
        do_action('rss_admin_extractor_ejecutar_cron');
    }
});

// 5) Acción que ejecuta todas las tareas
add_action('rss_admin_extractor_ejecutar_cron', 'rss_admin_extractor_ejecutar_todas_las_tareas');

function rss_admin_extractor_ejecutar_todas_las_tareas()
{
    // Evitar ejecuciones duplicadas muy seguidas (bloqueo de 4 min)
    if (get_transient('rss_ejecutando_cron')) {
        return;
    }
    set_transient('rss_ejecutando_cron', true, 4 * MINUTE_IN_SECONDS);

    error_log('[RSS Cron] Iniciando ejecución de tareas programadas');

    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $tareas = $wpdb->get_results("SELECT * FROM $tabla");

    if (!$tareas) {
        error_log('[RSS Cron] No se encontraron tareas para ejecutar.');
        delete_transient('rss_ejecutando_cron');
        return;
    }

    require_once plugin_dir_path(__FILE__) . '/../core/rss-runner.php';

    foreach ($tareas as $tarea) {
        error_log("[RSS Cron] Ejecutando tarea ID: {$tarea->id} ({$tarea->nombre_tarea})");
        rss_admin_extractor_ejecutar_tarea($tarea);
    }

    error_log('[RSS Cron] Ejecución completada.');
    delete_transient('rss_ejecutando_cron');
}
