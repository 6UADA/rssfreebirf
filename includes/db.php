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
        periodico VARCHAR(255) DEFAULT '',
        tipo_nota VARCHAR(255) DEFAULT '',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    $tabla_fuentes = $wpdb->prefix . 'rss_fuentes';
    $sql_fuentes = "CREATE TABLE $tabla_fuentes (
        id INT NOT NULL AUTO_INCREMENT,
        periodico VARCHAR(255) NOT NULL,
        tipo_nota VARCHAR(255) NOT NULL,
        url TEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql_fuentes);

    // Semilla: Agregar fuentes de ejemplo de Crónica
    $bloque_existente = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_fuentes");
    if ($bloque_existente == 0) {
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/cronica_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/cronica_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/cronica_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);
    }
}
