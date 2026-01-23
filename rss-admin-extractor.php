<?php
/*
Plugin Name: RSS Admin Extractor
Description: Extrae y publica noticias desde un feed RSS con imagen destacada, usando inteligencia artificial local (Ollama).
Version: 2.1
Author: TuNombre
*/

define('RSS_ADMIN_EXTRACTOR_DIR', plugin_dir_path(__FILE__));

require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/db.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/ollama.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/imagen.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/cron.php';  
require_once RSS_ADMIN_EXTRACTOR_DIR . 'admin/tareas.php';

// Hooks de activación y desactivación para registrar el cron personalizado
register_activation_hook(__FILE__, 'rss_admin_extractor_cron_activate');
register_deactivation_hook(__FILE__, 'rss_admin_extractor_cron_deactivate');

function rss_admin_extractor_cron_activate() {
    if (!wp_next_scheduled('rss_admin_extractor_ejecutar_cron')) {
        wp_schedule_event(time(), 'cada_24_horas', 'rss_admin_extractor_ejecutar_cron');
    }
}

function rss_admin_extractor_cron_deactivate() {
    $timestamp = wp_next_scheduled('rss_admin_extractor_ejecutar_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'rss_admin_extractor_ejecutar_cron');
    }
}



register_activation_hook(__FILE__, 'rss_admin_extractor_instalar');

add_action('admin_menu', 'rss_admin_extractor_menu');


