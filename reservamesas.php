<?php
/**
 * Plugin Name: Reserva de Mesas
 * Description: Permite a los clientes reservar mesas desde tu sitio web.
 * Version: 0.5
 * Author: Alejandro Briones
 */

defined('ABSPATH') or die('Sin acceso directo');
define('RM_MESAS_TOTALES', 8);
define('RM_PERSONAS_POR_MESA', 2);

register_activation_hook(__FILE__, 'rm_crear_tabla_reservas');

function rm_crear_tabla_reservas() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'reservas_mesas';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id INT NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        personas INT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

add_action('admin_menu', 'rm_agregar_menu_admin');

add_action('admin_menu', 'rm_agregar_menu_admin');

function rm_agregar_menu_admin() {
    // Menú principal
    add_menu_page(
        'Reservas de Mesas',
        'Reservas',
        'manage_options',
        'rm_reservas_admin',
        'rm_mostrar_reservas_admin',
        'dashicons-calendar-alt',
        26
    );

    // Subpágina para el calendario
    add_submenu_page(
        'rm_reservas_admin',
        'Calendario de Reservas',
        'Calendario',
        'manage_options',
        'rm_calendario_reservas',
        'rm_mostrar_calendario_admin'
    );
}

function rm_mostrar_calendario_admin() {
    echo '<div class="wrap">';
    echo '<h1>Calendario de Reservas</h1>';
    echo '<div id="rm-calendario" style="max-width: 900px;"></div>';
    echo '</div>';

    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';

    // Obtener todas las reservas
    $reservas = $wpdb->get_results("SELECT id, nombre, fecha, hora, personas FROM $tabla");

    // Convertir a eventos para JS
    $eventos = [];

    foreach ($reservas as $r) {
    $eventos[] = [
        'id' => $r->id,
        'title' => $r->nombre . ' (' . $r->personas . ' pers.)',
        'start' => $r->fecha . 'T' . $r->hora,
        'allDay' => false
    ];
}

// Pasar eventos a JS
wp_localize_script('rm-admin-calendar', 'rmReservasData', [
    'eventos' => $eventos,
    'ajax_url' => admin_url('admin-ajax.php') // necesario para eliminar reservas luego
]);


    // Encolar FullCalendar
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], null, true);
    wp_enqueue_script('rm-admin-calendar', plugin_dir_url(__FILE__) . 'js/admin-calendar.js', ['fullcalendar'], null, true);
    wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.min.css');
}



function rm_mostrar_reservas_admin() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';

    $resultados = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha, hora");

    echo '<div class="wrap"><h1>Reservas de Mesas</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Nombre</th><th>Email</th><th>Fecha</th><th>Hora</th><th>Personas</th><th>Acciones</th></tr></thead><tbody>';

    foreach ($resultados as $reserva) {
        echo '<tr id="reserva-' . esc_attr($reserva->id) . '">';
        echo '<td>' . esc_html($reserva->nombre) . '</td>';
        echo '<td>' . esc_html($reserva->email) . '</td>';
        echo '<td>' . esc_html($reserva->fecha) . '</td>';
        echo '<td>' . esc_html($reserva->hora) . '</td>';
        echo '<td>' . esc_html($reserva->personas) . '</td>';
        echo '<td><button class="button button-danger rm-eliminar-reserva" data-id="' . esc_attr($reserva->id) . '">Eliminar</button></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';

    // Encolar script para AJAX de eliminación
    wp_enqueue_script('rm-eliminar-reserva-admin', plugin_dir_url(__FILE__) . 'js/admin-delete.js', ['jquery'], null, true);
    wp_localize_script('rm-eliminar-reserva-admin', 'rmEliminarReservaData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'confirm_text' => '¿Estás seguro de que deseas eliminar esta reserva?'
    ]);
}


add_action('wp_ajax_rm_eliminar_reserva', 'rm_eliminar_reserva');

function rm_eliminar_reserva() {
    if (!current_user_can('manage_options')) {
        wp_die('No autorizado');
    }

    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';
    $id = intval($_POST['reserva_id']);

    $resultado = $wpdb->delete($tabla, ['id' => $id]);

    if ($resultado) {
        echo 'Reserva eliminada correctamente.';
    } else {
        echo 'Error al eliminar la reserva.';
    }

    wp_die();
}

/*if (($mesas_ocupadas + $mesas_requeridas) > RM_MESAS_TOTALES) {
    wp_redirect(add_query_arg('reserva', 'fail', wp_get_referer()));
    exit;
}*/


// Encolar CSS
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('rm-style', plugin_dir_url(__FILE__) . 'css/style.css');
});

// Mostrar formulario con shortcode
add_shortcode('reserva_mesa', function() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/reserva-form.php';
    return ob_get_clean();
});

// Incluir el handler
include_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';

?>