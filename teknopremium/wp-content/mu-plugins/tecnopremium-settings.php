<?php
/* ==========================================================================
   PÁGINA DE AJUSTES DEL TEMA (SIN ACF)
   ========================================================================== */
add_action('admin_menu', function() {
    add_options_page(
        'Ajustes Teknopremium', 
        'Ajustes Teknopremium', 
        'manage_options', 
        'teknopremium-settings', 
        'tp_render_settings_page'
    );
});

add_action('admin_init', function() {
    register_setting('tp_settings_group', 'tp_default_linea');
    register_setting('tp_settings_group', 'tp_default_garantia');
    register_setting('tp_settings_group', 'tp_default_plazo');
    register_setting('tp_settings_group', 'tp_default_instalacion');
    register_setting('tp_settings_group', 'tp_b2b_text');
});

function tp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Ajustes Globales Teknopremium</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tp_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Línea Arquitectónica por defecto</th>
                    <td><input type="text" name="tp_default_linea" value="<?php echo esc_attr(get_option('tp_default_linea', 'Serie Wellness Oficial 2026')); ?>" style="width: 100%;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Garantía por defecto</th>
                    <td><input type="text" name="tp_default_garantia" value="<?php echo esc_attr(get_option('tp_default_garantia', '10 años estructura / 5 años casco ProLast™')); ?>" style="width: 100%;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Plazo de Entrega por defecto</th>
                    <td><input type="text" name="tp_default_plazo" value="<?php echo esc_attr(get_option('tp_default_plazo', 'Stock Directo · Envío Especializado')); ?>" style="width: 100%;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Servicio Técnico por defecto</th>
                    <td><input type="text" name="tp_default_instalacion" value="<?php echo esc_attr(get_option('tp_default_instalacion', 'Servicio Técnico Oficial en Obra')); ?>" style="width: 100%;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Texto Aviso B2B (Completo)</th>
                    <td><textarea name="tp_b2b_text" style="width: 100%; height: 60px;"><?php echo esc_textarea(get_option('tp_b2b_text', '<strong>✦ Atención Profesional B2B:</strong> ¿Eres Arquitecto, Interiorista o Distribuidor? <a href="' . esc_url(wp_login_url()) . '" style="color: #D4CB92; text-decoration: underline;">Inicia sesión</a> para aplicar automáticamente tu tarifa especial en obra (-15% a -30%).')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
