<div class="flux-container">
    <!-- Líneas de flujo decorativas -->
    <div class="flux-flow-line flux-flow-line-1"></div>
    <div class="flux-flow-line flux-flow-line-2"></div>
    <div class="flux-flow-line flux-flow-line-3"></div>

    <!-- Header con logo y título -->
    <div class="flux-header">
        <div class="flux-logo">
            <?php
            // Ruta a tu imagen personalizada (relativa a la carpeta del plugin)
            $logo_img = plugin_dir_url(__FILE__) . '../assets/logo.png';

            // Verifica si existe la imagen, si no, muestra el ícono predeterminado
            if (file_exists(plugin_dir_path(__FILE__) . '../assets/logo.png')) {
                echo '<img src="' . esc_url($logo_img) . '" alt="Logo" class="flux-logo-img">';
            } else {
                echo '<span class="dashicons dashicons-rss"></span>';
            }
            ?>
        </div>
        <h1 class="flux-title">Freebird Extractor</h1>
    </div>

    <!-- Tarjeta de nueva tarea -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon">
                <span class="dashicons dashicons-plus-alt"></span>
            </div>
            <h2 class="flux-card-title">Agregar nueva tarea</h2>
        </div>
        <form method="post">
            <div class="flux-form-grid">
                <div class="flux-form-field">
                    <label for="nombre_tarea">Nombre de la tarea</label>
                    <input name="nombre_tarea" id="nombre_tarea" type="text" required
                        placeholder="Ej: Noticias de tecnología">
                </div>
                <div class="flux-form-field">
                    <label for="rss_url">URL del feed RSS</label>
                    <input name="rss_url" id="rss_url" type="url" required placeholder="https://ejemplo.com/feed">
                </div>
                <div class="flux-form-field">
                    <label for="rss_limit">Límite de entradas</label>
                    <input name="rss_limit" id="rss_limit" type="number" value="3" min="1">
                </div>
                <div class="flux-form-field">
                    <label for="rss_category_id">Categoría</label>
                    <select name="rss_category_id" id="rss_category_id">
                        <?php
                        $categorias = get_categories(['hide_empty' => false]);
                        foreach ($categorias as $cat) {
                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>

                </div>
                <div class="flux-form-field">
                    <label for="rss_author_id">Asignar Autor</label>
                    <select name="rss_author_id" id="rss_author_id">
                        <?php
                        $usuarios = get_users(['orderby' => 'display_name']);
                        foreach ($usuarios as $usuario) {
                            echo '<option value="' . esc_attr($usuario->ID) . '">' . esc_html($usuario->display_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="flux-form-field">
                    <label for="rss_post_status">Estado del post</label>
                    <select name="rss_post_status" id="rss_post_status">
                        <option value="draft">Borrador</option>
                        <option value="publish">Publicado</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="nueva_tarea" class="flux-button">
                <span class="dashicons dashicons-plus-alt"></span> Guardar tarea
            </button>
        </form>
    </div>

    <!-- Tarjeta de configuración de hora -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <h2 class="flux-card-title">Hora de ejecución automática</h2>
        </div>

        <?php
        // Obtener la zona horaria configurada en WordPress
        $timezone = wp_timezone();
        $hora_actual = new DateTime('now', $timezone);
        ?>

        <div class="flux-form-field" style="font-weight: 600; font-size: 1rem; padding: 0 16px 12px;">
            <label>Hora actual del servidor:</label>
            <div style="color: #0073aa; font-size: 1.2rem; margin-top: 4px;">
                <?php echo esc_html($hora_actual->format('H:i:s')); ?>
            </div>
        </div>

        <form method="post">
            <div class="flux-form-grid">
                <div class="flux-form-field">
                    <label for="rss_cron_hora">Hora diaria</label>
                    <input type="time" name="rss_cron_hora" id="rss_cron_hora"
                        value="<?= esc_attr(get_option('rss_cron_hora', '07:00')) ?>" required>
                </div>
            </div>
            <button type="submit" name="guardar_hora_cron" class="flux-button">
                <span class="dashicons dashicons-clock"></span> Guardar hora
            </button>
        </form>
    </div>


    <!-- Tarjeta de tareas existentes -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon">
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <h2 class="flux-card-title">Tareas existentes</h2>
        </div>

        <?php if (empty($tareas)): ?>
            <div class="flux-empty-state">
                <div class="flux-empty-icon">
                    <span class="dashicons dashicons-rss"></span>
                </div>
                <h3 class="flux-empty-title">No hay tareas creadas</h3>
                <p class="flux-empty-text">Agrega una nueva tarea para comenzar a importar contenido RSS a tu sitio.</p>
            </div>
        <?php else: ?>
            <div class="flux-tasks-grid">
                <?php foreach ($tareas as $tarea): ?>
                    <div class="flux-task-card">
                        <div class="flux-task-header">
                            <h3 class="flux-task-title">
                                <span class="dashicons dashicons-rss"></span>
                                <?= esc_html($tarea->nombre_tarea) ?>
                            </h3>
                            <span class="flux-task-badge">ID: <?= esc_html($tarea->id) ?></span>
                        </div>
                        <div class="flux-task-details">
                            <div class="flux-task-detail">
                                <span class="flux-task-label">URL:</span>
                                <span class="flux-task-value"><?= esc_html($tarea->rss_url) ?></span>
                            </div>
                            <div class="flux-task-detail">
                                <span class="flux-task-label">Límite:</span>
                                <span class="flux-task-value"><?= esc_html($tarea->rss_limit) ?> entradas</span>
                            </div>
                            <div class="flux-task-detail">
                                <span class="flux-task-label">Autor:</span>
                                <span class="flux-task-value">
                                    <?php
                                    $autor = get_userdata($tarea->rss_author_id);
                                    echo $autor ? esc_html($autor->display_name) : 'N/A';
                                    ?>
                                </span>
                            </div>
                            <div class="flux-task-detail">
                                <span class="flux-task-label">Categoría:</span>
                                <span class="flux-task-value">
                                    <?php
                                    $cat = get_category($tarea->rss_category_id);
                                    echo $cat ? esc_html($cat->name) : 'N/A';
                                    ?>
                                </span>
                            </div>
                            <div class="flux-task-detail">
                                <span class="flux-task-label">Estado:</span>
                                <span class="flux-task-value">
                                    <?= $tarea->rss_post_status == 'publish' ? 'Publicado' : 'Borrador' ?>
                                </span>
                            </div>
                        </div>
                        <div class="flux-task-actions">
                            <a href="?page=rss-admin-extractor&eliminar=<?= intval($tarea->id) ?>"
                                onclick="return confirm('¿Estás seguro de que deseas eliminar esta tarea?');"
                                class="flux-task-button flux-task-button-delete">
                                <span class="dashicons dashicons-trash"></span> Eliminar
                            </a>
                            <a href="?page=rss-admin-extractor&probar=<?= intval($tarea->id) ?>"
                                onclick="return confirm('¿Ejecutar esta tarea ahora?');"
                                class="flux-task-button flux-task-button-run">
                                <span class="dashicons dashicons-controls-play"></span> Probar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tarjeta de ejecutar todas las tareas -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon">
                <span class="dashicons dashicons-controls-play"></span>
            </div>
            <h2 class="flux-card-title">Ejecutar todas las tareas</h2>
        </div>
        <form method="post">
            <p>Ejecuta todas las tareas RSS configuradas para importar contenido a tu sitio.</p>
            <button type="submit" name="ejecutar_todas" class="flux-button">
                <span class="dashicons dashicons-controls-play"></span> Ejecutar todas las tareas
            </button>
        </form>
    </div>


</div>