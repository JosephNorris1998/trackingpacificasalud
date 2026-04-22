<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table   = $wpdb->prefix . TPS_CC_DB_TABLE;
$buttons = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d", PHP_INT_MAX ) );
$nonce   = wp_create_nonce( 'tps_cc_admin_nonce' );
?>
<div class="wrap tps-cc-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-hammer"></span>
        <?php esc_html_e( 'Click Button Counter', 'tps-click-counter' ); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- ── Shortcode Guide ──────────────────────────────────── -->
    <div class="tps-cc-info-box">
        <h2><?php esc_html_e( '📌 ¿Cómo usar los shortcodes?', 'tps-click-counter' ); ?></h2>
        <p><?php esc_html_e( 'Copia el shortcode que aparece en la columna "Shortcode" de la tabla y pégalo en cualquier página, entrada o widget de bloques (bloque "Shortcode").', 'tps-click-counter' ); ?></p>
        <p><strong><?php esc_html_e( 'Formato:', 'tps-click-counter' ); ?></strong>
            <code>[tps_click_button id="X"]</code> —
            <?php esc_html_e( 'donde X es el ID del botón.', 'tps-click-counter' ); ?>
        </p>
    </div>

    <!-- ── Button Table ─────────────────────────────────────── -->
    <div class="tps-cc-card">
        <div class="tps-cc-card-header">
            <h2><?php esc_html_e( 'Botones registrados', 'tps-click-counter' ); ?></h2>
            <button id="tps-cc-new-btn" class="button button-primary">
                + <?php esc_html_e( 'Nuevo botón', 'tps-click-counter' ); ?>
            </button>
            <button id="tps-cc-reset-all-btn" class="button button-secondary">
                <?php esc_html_e( 'Resetear todos los clics', 'tps-click-counter' ); ?>
            </button>
        </div>

        <?php if ( empty( $buttons ) ) : ?>
            <p class="tps-cc-empty"><?php esc_html_e( 'No hay botones creados todavía.', 'tps-click-counter' ); ?></p>
        <?php else : ?>
        <div class="tps-cc-table-wrap">
            <table class="wp-list-table widefat fixed striped tps-cc-table" id="tps-cc-table">
                <thead>
                    <tr>
                        <th style="width:50px"><?php esc_html_e( 'ID', 'tps-click-counter' ); ?></th>
                        <th><?php esc_html_e( 'Nombre interno', 'tps-click-counter' ); ?></th>
                        <th><?php esc_html_e( 'Etiqueta del botón', 'tps-click-counter' ); ?></th>
                        <th style="width:100px"><?php esc_html_e( 'Vista previa', 'tps-click-counter' ); ?></th>
                        <th style="width:90px"><?php esc_html_e( 'Clics', 'tps-click-counter' ); ?></th>
                        <th style="width:220px"><?php esc_html_e( 'Shortcode', 'tps-click-counter' ); ?></th>
                        <th style="width:200px"><?php esc_html_e( 'Acciones', 'tps-click-counter' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $buttons as $btn ) : ?>
                    <tr id="tps-cc-row-<?php echo (int) $btn->id; ?>">
                        <td><?php echo (int) $btn->id; ?></td>
                        <td><?php echo esc_html( $btn->button_name ); ?></td>
                        <td><?php echo esc_html( $btn->button_label ); ?></td>
                        <td>
                            <span class="tps-cc-preview-btn" style="
                                background-color:<?php echo esc_attr( $btn->bg_color ); ?>;
                                color:<?php echo esc_attr( $btn->text_color ); ?>;
                                font-size:<?php echo (int) $btn->font_size; ?>px;
                                padding:<?php echo esc_attr( $btn->btn_padding ); ?>;
                                border-radius:<?php echo (int) $btn->border_radius; ?>px;
                            "><?php echo esc_html( $btn->button_label ); ?></span>
                        </td>
                        <td class="tps-cc-count" id="tps-cc-count-<?php echo (int) $btn->id; ?>">
                            <?php echo number_format_i18n( (int) $btn->click_count ); ?>
                        </td>
                        <td>
                            <code class="tps-cc-shortcode-code">[tps_click_button id=&quot;<?php echo (int) $btn->id; ?>&quot;]</code>
                            <button class="button tps-cc-copy-btn" data-shortcode='[tps_click_button id="<?php echo (int) $btn->id; ?>"]'>
                                📋
                            </button>
                        </td>
                        <td>
                            <button class="button tps-cc-edit-btn" data-id="<?php echo (int) $btn->id; ?>">
                                ✏️ <?php esc_html_e( 'Editar', 'tps-click-counter' ); ?>
                            </button>
                            <button class="button tps-cc-reset-btn" data-id="<?php echo (int) $btn->id; ?>">
                                🔄 <?php esc_html_e( 'Reset', 'tps-click-counter' ); ?>
                            </button>
                            <button class="button tps-cc-delete-btn" data-id="<?php echo (int) $btn->id; ?>">
                                🗑️ <?php esc_html_e( 'Eliminar', 'tps-click-counter' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div><!-- .tps-cc-card -->

    <!-- ── Modal Form ───────────────────────────────────────── -->
    <div id="tps-cc-modal-overlay" class="tps-cc-modal-overlay" style="display:none;">
        <div class="tps-cc-modal">
            <div class="tps-cc-modal-header">
                <h2 id="tps-cc-modal-title"><?php esc_html_e( 'Nuevo botón', 'tps-click-counter' ); ?></h2>
                <button id="tps-cc-modal-close" class="tps-cc-modal-close" aria-label="Cerrar">&times;</button>
            </div>
            <div class="tps-cc-modal-body">
                <form id="tps-cc-form">
                    <input type="hidden" id="tps-cc-id" name="id" value="0">

                    <div class="tps-cc-field">
                        <label for="tps-cc-button-name">
                            <?php esc_html_e( 'Nombre interno (referencia tuya)', 'tps-click-counter' ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="tps-cc-button-name" name="button_name" class="regular-text" placeholder="Ej: Botón preparación colonoscopia" required>
                    </div>

                    <div class="tps-cc-field">
                        <label for="tps-cc-button-label">
                            <?php esc_html_e( 'Texto visible del botón', 'tps-click-counter' ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="tps-cc-button-label" name="button_label" class="regular-text" placeholder="Ej: ¡Haz clic aquí si ya iniciaste tu preparación!" required>
                    </div>

                    <div class="tps-cc-field-row">
                        <div class="tps-cc-field">
                            <label for="tps-cc-bg-color"><?php esc_html_e( 'Color de fondo', 'tps-click-counter' ); ?></label>
                            <input type="text" id="tps-cc-bg-color" name="bg_color" class="tps-cc-color-picker" value="#0073aa">
                        </div>
                        <div class="tps-cc-field">
                            <label for="tps-cc-text-color"><?php esc_html_e( 'Color del texto', 'tps-click-counter' ); ?></label>
                            <input type="text" id="tps-cc-text-color" name="text_color" class="tps-cc-color-picker" value="#ffffff">
                        </div>
                    </div>

                    <div class="tps-cc-field-row">
                        <div class="tps-cc-field">
                            <label for="tps-cc-font-size"><?php esc_html_e( 'Tamaño de fuente (px)', 'tps-click-counter' ); ?></label>
                            <input type="number" id="tps-cc-font-size" name="font_size" class="small-text" value="16" min="10" max="72">
                        </div>
                        <div class="tps-cc-field">
                            <label for="tps-cc-border-radius"><?php esc_html_e( 'Borde redondeado (px)', 'tps-click-counter' ); ?></label>
                            <input type="number" id="tps-cc-border-radius" name="border_radius" class="small-text" value="4" min="0" max="100">
                        </div>
                    </div>

                    <div class="tps-cc-field-row">
                        <div class="tps-cc-field">
                            <label for="tps-cc-btn-width"><?php esc_html_e( 'Ancho (ej: auto, 200px, 100%)', 'tps-click-counter' ); ?></label>
                            <input type="text" id="tps-cc-btn-width" name="btn_width" class="regular-text" value="auto">
                        </div>
                        <div class="tps-cc-field">
                            <label for="tps-cc-btn-padding"><?php esc_html_e( 'Padding (ej: 12px 24px)', 'tps-click-counter' ); ?></label>
                            <input type="text" id="tps-cc-btn-padding" name="btn_padding" class="regular-text" value="12px 24px">
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div class="tps-cc-field tps-cc-preview-wrap">
                        <label><?php esc_html_e( 'Vista previa en tiempo real', 'tps-click-counter' ); ?></label>
                        <div class="tps-cc-live-preview">
                            <button type="button" id="tps-cc-live-btn" style="background-color:#0073aa;color:#ffffff;font-size:16px;padding:12px 24px;border-radius:4px;border:none;cursor:pointer;">
                                <?php esc_html_e( 'Vista previa', 'tps-click-counter' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="tps-cc-modal-footer">
                        <button type="submit" class="button button-primary" id="tps-cc-save-btn">
                            <?php esc_html_e( 'Guardar botón', 'tps-click-counter' ); ?>
                        </button>
                        <button type="button" class="button" id="tps-cc-cancel-btn">
                            <?php esc_html_e( 'Cancelar', 'tps-click-counter' ); ?>
                        </button>
                        <span id="tps-cc-form-msg" class="tps-cc-msg"></span>
                    </div>
                </form>
            </div>
        </div>
    </div><!-- #tps-cc-modal-overlay -->
</div><!-- .wrap -->

<script>
var tpsCCAdmin = {
    ajaxUrl : '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce   : '<?php echo esc_js( $nonce ); ?>'
};
</script>
