<?php
/**
 * Plugin Name: Tracking Pacifica Salud – Click Button Counter
 * Plugin URI:  https://github.com/JosephNorris1998/trackingpacificasalud
 * Description: Crea botones personalizados con contador de clics. Compatible con LiteSpeed Cache. Usa shortcodes para mostrar los botones en cualquier página o entrada.
 * Version:     1.2.0
 * Author:      Joseph Norris
 * Text Domain: tps-click-counter
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'TPS_CC_VERSION',  '1.2.0' );
define( 'TPS_CC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'TPS_CC_URL',      plugin_dir_url( __FILE__ ) );
define( 'TPS_CC_DB_TABLE', 'tps_click_buttons' );

// ─── Activation / Deactivation ────────────────────────────────────────────────
register_activation_hook( __FILE__,   'tps_cc_activate' );
register_deactivation_hook( __FILE__, 'tps_cc_deactivate' );

function tps_cc_activate() {
    tps_cc_create_table();
    flush_rewrite_rules();
}

function tps_cc_deactivate() {
    flush_rewrite_rules();
}

// ─── Database ─────────────────────────────────────────────────────────────────
function tps_cc_create_table() {
    global $wpdb;
    $table      = $wpdb->prefix . TPS_CC_DB_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        button_name   VARCHAR(255)        NOT NULL DEFAULT '',
        button_label  VARCHAR(255)        NOT NULL DEFAULT '',
        bg_color      VARCHAR(20)         NOT NULL DEFAULT '#0073aa',
        text_color    VARCHAR(20)         NOT NULL DEFAULT '#ffffff',
        font_size     SMALLINT(5) UNSIGNED NOT NULL DEFAULT 16,
        btn_width     VARCHAR(20)         NOT NULL DEFAULT 'auto',
        btn_padding   VARCHAR(40)         NOT NULL DEFAULT '12px 24px',
        border_radius SMALLINT(5) UNSIGNED NOT NULL DEFAULT 4,
        btn_align     VARCHAR(10)         NOT NULL DEFAULT 'center',
        click_count   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'tps_cc_db_version', TPS_CC_VERSION );
}

// Run dbDelta on plugin update
add_action( 'plugins_loaded', 'tps_cc_maybe_upgrade_db' );
function tps_cc_maybe_upgrade_db() {
    if ( get_option( 'tps_cc_db_version' ) !== TPS_CC_VERSION ) {
        tps_cc_create_table();
    }
}

// ─── Admin Menu ───────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'tps_cc_admin_menu' );
function tps_cc_admin_menu() {
    add_menu_page(
        __( 'Click Counter', 'tps-click-counter' ),
        __( 'Click Counter', 'tps-click-counter' ),
        'manage_options',
        'tps-click-counter',
        'tps_cc_admin_page',
        'dashicons-hammer',
        30
    );
}

// ─── Admin Enqueue ────────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'tps_cc_admin_enqueue' );
function tps_cc_admin_enqueue( $hook ) {
    if ( 'toplevel_page_tps-click-counter' !== $hook ) {
        return;
    }
    wp_enqueue_style(
        'tps-cc-admin',
        TPS_CC_URL . 'assets/admin.css',
        array(),
        TPS_CC_VERSION
    );
    wp_enqueue_script(
        'tps-cc-admin',
        TPS_CC_URL . 'assets/admin.js',
        array( 'jquery', 'wp-color-picker' ),
        TPS_CC_VERSION,
        true
    );
    wp_enqueue_style( 'wp-color-picker' );
}

// ─── Frontend Enqueue ─────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'tps_cc_frontend_enqueue' );
function tps_cc_frontend_enqueue() {
    // Always enqueue so buttons added via HTML (not just shortcode) work on any page.
    wp_enqueue_style(
        'tps-cc-frontend',
        TPS_CC_URL . 'assets/frontend.css',
        array(),
        TPS_CC_VERSION
    );
    wp_enqueue_script(
        'tps-cc-frontend',
        TPS_CC_URL . 'assets/frontend.js',
        array( 'jquery' ),
        TPS_CC_VERSION,
        true
    );
    wp_localize_script( 'tps-cc-frontend', 'tpsCCData', array(
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'tps_cc_click_nonce' ),
        'confirmMsg'    => __( '¿Confirmas este registro?', 'tps-click-counter' ),
        'confirmYes'    => __( 'Sí, confirmar', 'tps-click-counter' ),
        'confirmNo'     => __( 'Cancelar', 'tps-click-counter' ),
        'thankYouMsg'   => __( '¡Gracias! Tu clic ha sido registrado.', 'tps-click-counter' ),
        'errorMsg'      => __( 'Ocurrió un error. Intenta de nuevo.', 'tps-click-counter' ),
    ) );
}

// ─── AJAX – register click (logged-in & guests) ───────────────────────────────
add_action( 'wp_ajax_tps_cc_register_click',        'tps_cc_ajax_register_click' );
add_action( 'wp_ajax_nopriv_tps_cc_register_click', 'tps_cc_ajax_register_click' );

function tps_cc_ajax_register_click() {
    // Prevent LiteSpeed Cache from storing AJAX responses
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
    header( 'Pragma: no-cache' );

    check_ajax_referer( 'tps_cc_click_nonce', 'nonce' );

    $button_id = isset( $_POST['button_id'] ) ? absint( $_POST['button_id'] ) : 0;
    if ( ! $button_id ) {
        wp_send_json_error( array( 'message' => 'Invalid button ID.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . TPS_CC_DB_TABLE;

    $updated = $wpdb->query(
        $wpdb->prepare( "UPDATE {$table} SET click_count = click_count + 1 WHERE id = %d", $button_id )
    );

    if ( false === $updated ) {
        wp_send_json_error( array( 'message' => 'DB error.' ) );
    }

    $new_count = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT click_count FROM {$table} WHERE id = %d", $button_id )
    );

    wp_send_json_success( array( 'count' => $new_count ) );
}

// ─── Shortcode ────────────────────────────────────────────────────────────────
// Usage: [tps_click_button id="1"]
add_shortcode( 'tps_click_button', 'tps_cc_shortcode' );

function tps_cc_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'tps_click_button' );
    $id   = absint( $atts['id'] );
    if ( ! $id ) {
        return '';
    }

    global $wpdb;
    $table  = $wpdb->prefix . TPS_CC_DB_TABLE;
    $button = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

    if ( ! $button ) {
        return '';
    }

    $style = sprintf(
        'background-color:%s;color:%s;font-size:%dpx;width:%s;padding:%s;border-radius:%dpx;',
        esc_attr( $button->bg_color ),
        esc_attr( $button->text_color ),
        (int) $button->font_size,
        esc_attr( $button->btn_width ),
        esc_attr( $button->btn_padding ),
        (int) $button->border_radius
    );

    $align = in_array( $button->btn_align, array( 'left', 'center', 'right' ), true ) ? $button->btn_align : 'center';

    return sprintf(
        '<div style="text-align:%s;"><button type="button" class="tps-cc-btn" data-id="%d" style="%s">%s</button></div>',
        esc_attr( $align ),
        (int) $button->id,
        $style,
        esc_html( $button->button_label )
    );
}

// ─── Admin Page ───────────────────────────────────────────────────────────────
function tps_cc_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    require_once TPS_CC_DIR . 'admin/admin-page.php';
}

// ─── Admin AJAX – save button ─────────────────────────────────────────────────
add_action( 'wp_ajax_tps_cc_save_button', 'tps_cc_ajax_save_button' );
function tps_cc_ajax_save_button() {
    check_ajax_referer( 'tps_cc_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'No permission.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . TPS_CC_DB_TABLE;

    $id           = isset( $_POST['id'] )           ? absint( $_POST['id'] )                    : 0;
    $button_name  = isset( $_POST['button_name'] )  ? sanitize_text_field( $_POST['button_name'] )  : '';
    $button_label = isset( $_POST['button_label'] ) ? sanitize_text_field( $_POST['button_label'] ) : '';
    $bg_color     = isset( $_POST['bg_color'] )     ? ( sanitize_hex_color( $_POST['bg_color'] )   ?: '#0073aa' ) : '#0073aa';
    $text_color   = isset( $_POST['text_color'] )   ? ( sanitize_hex_color( $_POST['text_color'] ) ?: '#ffffff' ) : '#ffffff';
    $font_size    = isset( $_POST['font_size'] )    ? absint( $_POST['font_size'] )                 : 16;
    $btn_width    = isset( $_POST['btn_width'] )    ? sanitize_text_field( $_POST['btn_width'] )    : 'auto';
    $btn_padding  = isset( $_POST['btn_padding'] )  ? sanitize_text_field( $_POST['btn_padding'] )  : '12px 24px';
    $border_radius = isset( $_POST['border_radius'] ) ? absint( $_POST['border_radius'] )           : 4;
    $btn_align    = isset( $_POST['btn_align'] )    ? sanitize_text_field( $_POST['btn_align'] )    : 'center';
    if ( ! in_array( $btn_align, array( 'left', 'center', 'right' ), true ) ) {
        $btn_align = 'center';
    }

    if ( empty( $button_name ) || empty( $button_label ) ) {
        wp_send_json_error( array( 'message' => 'Nombre y etiqueta son obligatorios.' ) );
    }

    $data = array(
        'button_name'   => $button_name,
        'button_label'  => $button_label,
        'bg_color'      => $bg_color,
        'text_color'    => $text_color,
        'font_size'     => $font_size,
        'btn_width'     => $btn_width,
        'btn_padding'   => $btn_padding,
        'border_radius' => $border_radius,
        'btn_align'     => $btn_align,
    );
    $formats = array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' );

    if ( $id ) {
        $result = $wpdb->update( $table, $data, array( 'id' => $id ), $formats, array( '%d' ) );
        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Error al actualizar en la base de datos.' . ( $wpdb->last_error ? ' ' . $wpdb->last_error : '' ) ) );
        }
        wp_send_json_success( array( 'id' => $id, 'action' => 'updated' ) );
    } else {
        $data['click_count'] = 0;
        $formats[]           = '%d';
        $result = $wpdb->insert( $table, $data, $formats );
        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Error al guardar en la base de datos.' . ( $wpdb->last_error ? ' ' . $wpdb->last_error : '' ) ) );
        }
        wp_send_json_success( array( 'id' => $wpdb->insert_id, 'action' => 'created' ) );
    }
}

// ─── Admin AJAX – delete button ───────────────────────────────────────────────
add_action( 'wp_ajax_tps_cc_delete_button', 'tps_cc_ajax_delete_button' );
function tps_cc_ajax_delete_button() {
    check_ajax_referer( 'tps_cc_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'No permission.' ) );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'ID inválido.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . TPS_CC_DB_TABLE;
    $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
    wp_send_json_success( array( 'id' => $id ) );
}

// ─── Admin AJAX – reset clicks ────────────────────────────────────────────────
add_action( 'wp_ajax_tps_cc_reset_clicks', 'tps_cc_ajax_reset_clicks' );
function tps_cc_ajax_reset_clicks() {
    check_ajax_referer( 'tps_cc_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'No permission.' ) );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

    global $wpdb;
    $table = $wpdb->prefix . TPS_CC_DB_TABLE;

    if ( $id ) {
        $wpdb->update( $table, array( 'click_count' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
    } else {
        // Reset all
        $wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET click_count = %d", 0 ) );
    }

    wp_send_json_success();
}

// ─── Admin AJAX – get button (for edit form) ──────────────────────────────────
add_action( 'wp_ajax_tps_cc_get_button', 'tps_cc_ajax_get_button' );
function tps_cc_ajax_get_button() {
    check_ajax_referer( 'tps_cc_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id ) {
        wp_send_json_error();
    }

    global $wpdb;
    $table  = $wpdb->prefix . TPS_CC_DB_TABLE;
    $button = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

    if ( ! $button ) {
        wp_send_json_error();
    }

    wp_send_json_success( $button );
}
