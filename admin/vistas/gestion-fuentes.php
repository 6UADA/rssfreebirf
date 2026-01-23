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
        <h1 class="flux-title">Catálogo de Fuentes</h1>
    </div>

    <!-- Tarjeta de Gestión de Fuentes RSS -->
    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon">
                <span class="dashicons dashicons-database-add"></span>
            </div>
            <h2 class="flux-card-title">Gestionar Fuentes RSS (Catálogo)</h2>
        </div>
        <form method="post">
            <div class="flux-form-grid">
                <div class="flux-form-field">
                    <label for="periodico">Periódico</label>
                    <input name="periodico" id="periodico" type="text" required placeholder="Ej: El Universal">
                </div>
                <div class="flux-form-field">
                    <label for="tipo_nota">Tipo de Nota</label>
                    <input name="tipo_nota" id="tipo_nota" type="text" required placeholder="Ej: Mundo">
                </div>
                <div class="flux-form-field">
                    <label for="rss_url">URL del feed RSS</label>
                    <input name="rss_url" id="rss_url" type="url" required placeholder="https://ejemplo.com/feed">
                </div>
            </div>
            <button type="submit" name="nueva_fuente" class="flux-button">
                <span class="dashicons dashicons-plus-alt"></span> Guardar en Catálogo
            </button>
        </form>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <h3>Fuentes en el Catálogo</h3>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Periódico</th>
                    <th>Tipo</th>
                    <th>URL</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fuentes)): ?>
                    <tr>
                        <td colspan="4">No hay fuentes guardadas</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fuentes as $f): ?>
                        <tr>
                            <td>
                                <?= esc_html($f->periodico) ?>
                            </td>
                            <td>
                                <?= esc_html($f->tipo_nota) ?>
                            </td>
                            <td style="font-size: 0.8em;">
                                <?= esc_html($f->url) ?>
                            </td>
                            <td>
                                <a href="?page=rss-gestionar-fuentes&eliminar_fuente=<?= intval($f->id) ?>"
                                    onclick="return confirm('¿Eliminar esta fuente?');"
                                    style="color: #d63638; text-decoration: none;">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>