<?php
function rss_admin_extractor_instalar()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id INT NOT NULL AUTO_INCREMENT,
        nombre_tarea VARCHAR(255) DEFAULT '',
        rss_url TEXT NOT NULL,
        rss_limit INT DEFAULT 3,
        rss_category_id INT,
        rss_post_status VARCHAR(20) DEFAULT 'draft',
        rss_author_id BIGINT DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
