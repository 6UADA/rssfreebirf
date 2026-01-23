<div class="flux-container">
    <div class="flux-header">
        <div class="flux-logo">
            <?php
            $logo_img = plugin_dir_url(__FILE__) . '../../assets/logo.png';
            if (file_exists(plugin_dir_path(__FILE__) . '../../assets/logo.png')) {
                echo '<img src="' . esc_url($logo_img) . '" alt="Logo" class="flux-logo-img">';
            } else {
                echo '<span class="dashicons dashicons-rss"></span>';
            }
            ?>
        </div>
        <h1 class="flux-title">Listado de Tareas</h1>
    </div>

    <!-- Acciones Masivas -->
    <?php if (!empty($tareas)): ?>
        <div class="flux-card" style="padding: 20px;">
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="?page=rss-listar-tareas&probar_todas=1"
                    onclick="return confirm('¿Ejecutar todas las tareas ahora?');" class="flux-button"
                    style="background: var(--info);">
                    <span class="dashicons dashicons-controls-play"></span> Probar todas las tareas
                </a>
                <a href="?page=rss-listar-tareas&eliminar_todas=1"
                    onclick="return confirm('¿ESTÁS SEGURO? Se borrarán TODAS las tareas.');" class="flux-button"
                    style="background: var(--error);">
                    <span class="dashicons dashicons-trash"></span> Eliminar todas las tareas
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tareas existentes -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon"><span class="dashicons dashicons-list-view"></span></div>
            <h2 class="flux-card-title">Tareas Programadas</h2>
        </div>
        <?php if (empty($tareas)): ?>
            <div class="flux-empty-state">
                <div class="flux-empty-icon"><span class="dashicons dashicons-rss"></span></div>
                <h3 class="flux-empty-title">No hay tareas creadas</h3>
                <p class="flux-empty-text">Ve a "Programar Tarea" para añadir una.</p>
            </div>
        <?php else: ?>
            <div class="flux-tasks-grid">
                <?php foreach ($tareas as $tarea): ?>
                    <div class="flux-task-card">
                        <div class="flux-task-header">
                            <h3 class="flux-task-title">
                                <?= esc_html($tarea->nombre_tarea) ?>
                            </h3>
                            <span class="flux-task-badge">ID:
                                <?= esc_html($tarea->id) ?>
                            </span>
                        </div>
                        <div class="flux-task-details">
                            <div class="flux-task-detail"><span class="flux-task-label">Fuente:</span><span
                                    class="flux-task-value">
                                    <?= esc_html($tarea->periodico) ?> /
                                    <?= esc_html($tarea->tipo_nota) ?>
                                </span></div>
                            <div class="flux-task-detail"><span class="flux-task-label">Límite:</span><span
                                    class="flux-task-value">
                                    <?= esc_html($tarea->rss_limit) ?>
                                </span></div>
                        </div>
                        <div class="flux-task-actions">
                            <a href="?page=rss-listar-tareas&eliminar=<?= intval($tarea->id) ?>"
                                onclick="return confirm('¿Eliminar?');"
                                class="flux-task-button flux-task-button-delete">Eliminar</a>
                            <a href="?page=rss-listar-tareas&probar=<?= intval($tarea->id) ?>"
                                class="flux-task-button flux-task-button-run">Probar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
</div>