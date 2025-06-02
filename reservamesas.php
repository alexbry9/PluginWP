<?php
/**
 * Plugin Name: Reserva de Mesas
 * Description: Permite a los clientes reservar mesas desde tu sitio web.
 * Version: 0.8
 * Author: Alejandro Briones
 */

defined('ABSPATH') or die('Sin acceso directo');
if (!defined('RM_MESAS_TOTALES')) {
    define('RM_MESAS_TOTALES', get_option('rm_mesas_totales', 8));
}
if (!defined('RM_PERSONAS_POR_MESA')) {
    define('RM_PERSONAS_POR_MESA', get_option('rm_personas_por_mesa', 2));
}

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

    // Subpágina para los ajustes
    add_submenu_page(
        'rm_reservas_admin',
        'Ajustes',
        'Ajustes',
        'manage_options',
        'rm_ajustes',
        'rm_mostrar_pagina_ajustes'
    );
}

function rm_mostrar_pagina_ajustes() {
    ?>
    <div class="wrap">
        <h1>Ajustes de Reserva de Mesas</h1>
        <form method="post" action="options.php">
            <?php
            // Activar el manejo de opciones
            settings_fields('rm_ajustes_opciones');
            do_settings_sections('rm_ajustes');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function rm_registrar_ajustes() {
    // Registrar el grupo de ajustes
    register_setting('rm_ajustes_opciones', 'rm_mesas_totales');
    register_setting('rm_ajustes_opciones', 'rm_personas_por_mesa');

    // Agregar sección
    add_settings_section(
        'rm_ajustes_seccion', 
        'Configuración de la reserva', 
        'rm_ajustes_seccion_callback', 
        'rm_ajustes'
    );

    // Agregar campos de ajustes
    add_settings_field(
        'rm_mesas_totales', 
        'Mesas Totales', 
        'rm_mesas_totales_callback', 
        'rm_ajustes', 
        'rm_ajustes_seccion'
    );

    add_settings_field(
        'rm_personas_por_mesa', 
        'Personas por Mesa', 
        'rm_personas_por_mesa_callback', 
        'rm_ajustes', 
        'rm_ajustes_seccion'
    );

    // Registrar nuevos campos para horario
    register_setting('rm_ajustes_opciones', 'rm_hora_apertura', 'sanitize_text_field');
    register_setting('rm_ajustes_opciones', 'rm_hora_cierre', 'sanitize_text_field');

    // Añadir sección de horario (opcional)
    add_settings_section(
        'rm_horario_seccion',
        'Configuración de Horario',
        'rm_horario_seccion_callback',
        'rm_ajustes'
    );

    // Campos de horario
    add_settings_field(
        'rm_hora_apertura',
        'Hora de apertura',
        'rm_hora_apertura_callback',
        'rm_ajustes',
        'rm_horario_seccion'
    );

    add_settings_field(
        'rm_hora_cierre',
        'Hora de cierre',
        'rm_hora_cierre_callback',
        'rm_ajustes',
        'rm_horario_seccion'
    );

        // Registrar días cerrados (array)
    register_setting('rm_ajustes_opciones', 'rm_dias_cerrados', 'rm_sanitize_dias_cerrados');

    // Añadir campo de días cerrados
    add_settings_field(
        'rm_dias_cerrados',
        'Días cerrados',
        'rm_dias_cerrados_callback',
        'rm_ajustes',
        'rm_horario_seccion' 
    );

    register_setting('rm_ajustes_opciones', 'rm_max_personas_reserva', 'absint');

    // Añadir campo a la sección
    add_settings_field(
        'rm_max_personas',
        'Máximo de personas por reserva',
        'rm_max_personas_callback',
        'rm_ajustes',
        'rm_ajustes_seccion' // Misma sección de configuración
    );
}

add_action('admin_init', 'rm_registrar_ajustes');

function rm_ajustes_seccion_callback() {
    echo '<p>Ajusta la configuración de las reservas de mesas.</p>';
}

function rm_mesas_totales_callback() {
    $valor = get_option('rm_mesas_totales', RM_MESAS_TOTALES); // Valor por defecto
    echo '<input type="number" name="rm_mesas_totales" value="' . esc_attr($valor) . '" />';
}

function rm_personas_por_mesa_callback() {
    $valor = get_option('rm_personas_por_mesa', RM_PERSONAS_POR_MESA); // Valor por defecto
    echo '<input type="number" name="rm_personas_por_mesa" value="' . esc_attr($valor) . '" />';
}

function rm_horario_seccion_callback() {
    echo '<p>Define el horario en que aceptas reservas.</p>';
}

function rm_hora_apertura_callback() {
    echo '<input type="time" name="rm_hora_apertura" value="' . esc_attr(get_option('rm_hora_apertura', '12:00')) . '">';
}

function rm_hora_cierre_callback() {
    echo '<input type="time" name="rm_hora_cierre" value="' . esc_attr(get_option('rm_hora_cierre', '23:00')) . '">';
}

// Sanitizar el array de días cerrados
function rm_sanitize_dias_cerrados($input) {
    if (!is_array($input)) return [];
    return array_map('sanitize_text_field', $input);
}

// Callback para mostrar checkboxes de días
function rm_dias_cerrados_callback() {
    $dias_cerrados = get_option('rm_dias_cerrados', []);
    $dias_semana = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo'
    ];

    foreach ($dias_semana as $clave => $dia) {
        $checked = in_array($clave, $dias_cerrados) ? 'checked' : '';
        echo "<label><input type='checkbox' name='rm_dias_cerrados[]' value='$clave' $checked> $dia</label><br>";
    }
}

function rm_max_personas_callback() {
    $valor = get_option('rm_max_personas_reserva', 10); 
    echo '<input type="number" min="1" name="rm_max_personas_reserva" value="' . esc_attr($valor) . '">';
    echo '<p class="description">Número máximo permitido en una sola reserva</p>';
}

//JS Horas dinámicas

add_action('wp_enqueue_scripts', function() {
    // Solo cargar el script en páginas que usen el shortcode [reserva_mesa]
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'reserva_mesa')) {
        
        wp_enqueue_script(
            'rm-horas-dinamicas',
            plugin_dir_url(__FILE__) . 'js/horas-dinamicas.js',
            ['jquery'], // Dependencia de jQuery
            filemtime(plugin_dir_path(__FILE__) . 'js/horas-dinamicas.js'),
            true
        );

        // Pasar variables de PHP a JavaScript
        wp_localize_script('rm-horas-dinamicas', 'rmHorasConfig', [
            'horaApertura' => get_option('rm_hora_apertura', '12:00'),
            'horaCierre' => get_option('rm_hora_cierre', '23:00'),
            'intervalo' => 45, // Intervalo en minutos (45 como pediste)
            'baseUrl' => home_url()
        ]);
    }
});

function rm_enqueue_plugin_scripts() {
    wp_enqueue_script(
        'rm-horas-dinamicas',
        plugin_dir_url(__FILE__) . 'js/horas-dinamicas.js',
        [],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'rm_enqueue_plugin_scripts');


function rm_mostrar_calendario_admin() {
    echo '<div class="wrap">';
    echo '<h1>Calendario de Reservas</h1>';
    echo '<div id="rm-calendario" style="max-width: 900px; margin: 20px auto;"></div>';
    echo '</div>';
    
    // Añade este contenedor para el formulario de reserva manual
    echo '<div class="rm-admin-reserva-form" style="margin-bottom: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">';
    echo '<h2>Añadir Reserva Manualmente</h2>';
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    echo '<input type="hidden" name="action" value="rm_add_reserva_manual">';
    wp_nonce_field('rm_add_reserva_manual_nonce', 'rm_manual_nonce');
    
    // Campos del formulario
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="rm_manual_nombre">Nombre</label></th>';
    echo '<td><input type="text" name="rm_manual_nombre" required style="width: 100%;"></td></tr>';
    
    echo '<tr><th scope="row"><label for="rm_manual_email">Email</label></th>';
    echo '<td><input type="email" name="rm_manual_email" required style="width: 100%;"></td></tr>';
    
    echo '<tr><th scope="row"><label for="rm_manual_fecha">Fecha</label></th>';
    echo '<td><input type="date" name="rm_manual_fecha" required style="width: 100%;"></td></tr>';
    
    echo '<tr><th scope="row"><label for="rm_manual_hora">Hora</label></th>';
    echo '<td><select name="rm_manual_hora" required style="width: 100%;"></select></td></tr>';
    
    echo '<tr><th scope="row"><label for="rm_manual_personas">Personas</label></th>';
    echo '<td><input type="number" name="rm_manual_personas" min="1" required style="width: 100%;"></td></tr>';
    echo '</table>';
    
    echo '<p class="submit"><input type="submit" class="button button-primary" value="Añadir Reserva"></p>';
    echo '</form>';
    echo '</div>'; // Cierre del contenedor del formulario

    ?>
    <script>
    jQuery(document).ready(function($) {
        // Generar horas con intervalo de 45 minutos
        function generarHoras() {
            var horaApertura = '<?php echo esc_js(get_option('rm_hora_apertura', '12:00')); ?>';
            var horaCierre = '<?php echo esc_js(get_option('rm_hora_cierre', '23:00')); ?>';
            var selectHora = $('select[name="rm_manual_hora"]');
            
            // Convertir a minutos desde medianoche
            function toMinutes(time) {
                var parts = time.split(':');
                return parseInt(parts[0]) * 60 + parseInt(parts[1]);
            }
            
            var inicio = toMinutes(horaApertura);
            var fin = toMinutes(horaCierre);
            
            selectHora.empty();
            selectHora.append('<option value="">Selecciona hora</option>');
            
            for (var min = inicio; min <= fin; min += 45) {
                var horas = Math.floor(min / 60);
                var minutos = min % 60;
                var horaFormateada = (horas < 10 ? '0' : '') + horas + ':' + (minutos < 10 ? '0' : '') + minutos;
                selectHora.append('<option value="' + horaFormateada + '">' + horaFormateada + '</option>');
            }
        }
        
        generarHoras();
    });
    </script>
    <?php
    // Mensajes de administración
    if (isset($_GET['rm_message'])) {
        $messages = [
            'reserva_added' => ['type' => 'success', 'text' => 'Reserva añadida correctamente'],
            'reserva_error' => ['type' => 'error', 'text' => 'Error al añadir la reserva']
        ];
        
        if (isset($messages[$_GET['rm_message']])) {
            $msg = $messages[$_GET['rm_message']];
            echo '<div class="notice notice-' . $msg['type'] . ' is-dismissible"><p>' . $msg['text'] . '</p></div>';
            
            // Mostrar advertencias si existen
            if ($_GET['rm_message'] === 'reserva_added' && isset($_GET['warnings'])) {
                $warnings = explode('|', urldecode($_GET['warnings']));
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Advertencias:</strong><br>';
                foreach ($warnings as $warning) {
                    echo '- ' . esc_html($warning) . '<br>';
                }
                echo '</p></div>';
            }
        }
    }

    // Encolar FullCalendar con versiones específicas
    wp_enqueue_script(
        'fullcalendar', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', 
        [], 
        '6.1.11', 
        true
    );
    
    wp_enqueue_style(
        'fullcalendar-css', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css',
        [],
        '6.1.11'
    );
    
    wp_enqueue_script(
        'rm-admin-calendar', 
        plugin_dir_url(__FILE__) . 'js/admin-calendar.js', 
        ['fullcalendar', 'jquery'], 
        filemtime(plugin_dir_path(__FILE__) . 'js/admin-calendar.js'), 
        true
    );
    
    // Obtener reservas para los próximos 3 meses
    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';
    $reservas = $wpdb->get_results("
        SELECT id, nombre, fecha, hora, personas 
        FROM $tabla 
        WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
        ORDER BY fecha, hora
    ");
    
    // Preparar datos para JavaScript
    $eventos = array();
    foreach ($reservas as $r) {
        $eventos[] = array(
            'id' => $r->id,
            'title' => $r->nombre . ' (' . $r->personas . ')',
            'start' => $r->fecha . 'T' . $r->hora,
            'color' => '#28a745', // Verde para reservas activas
            'extendedProps' => array(
                'personas' => $r->personas,
                'email' => $r->email // Asegúrate de incluir el email en tu consulta SQL
            )
        );
    }
    
    wp_localize_script(
        'rm-admin-calendar', 
        'rmReservasData', 
        array(
            'eventos' => $eventos,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm-calendario-nonce')
        )
    );
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

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('rm-style', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('rm-horas-dinamicas', plugin_dir_url(__FILE__) . 'js/horas-dinamicas.js', [], false, true);
});


/*if (($mesas_ocupadas + $mesas_requeridas) > RM_MESAS_TOTALES) {
    wp_redirect(add_query_arg('reserva', 'fail', wp_get_referer()));
    exit;
}*/

add_action('admin_post_rm_add_reserva_manual', 'rm_procesar_reserva_manual');

function rm_procesar_reserva_manual() {
    // Verificar permisos y nonce
    if (!current_user_can('manage_options') || 
        !isset($_POST['rm_manual_nonce']) || 
        !wp_verify_nonce($_POST['rm_manual_nonce'], 'rm_add_reserva_manual_nonce')) {
        wp_die('No tienes permisos para esta acción');
    }

    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';

    // Sanitizar datos
    $data = [
        'nombre' => sanitize_text_field($_POST['rm_manual_nombre']),
        'email' => sanitize_email($_POST['rm_manual_email']),
        'fecha' => sanitize_text_field($_POST['rm_manual_fecha']),
        'hora' => sanitize_text_field($_POST['rm_manual_hora']),
        'personas' => intval($_POST['rm_manual_personas'])
    ];

    // --- Verificaciones (solo para mostrar advertencias) ---
    $warnings = [];
    
    // 1. Máximo de personas
    $max_personas = get_option('rm_max_personas_reserva', 10);
    if ($data['personas'] > $max_personas) {
        $warnings[] = "Advertencia: El número de personas ($data[personas]) excede el límite normal ($max_personas).";
    }

    // 2. Día cerrado
    $dias_cerrados = get_option('rm_dias_cerrados', []);
    $dia_semana = strtolower(date('l', strtotime($data['fecha'])));
    $dia_semana_es = [
        'monday' => 'lunes',
        'tuesday' => 'martes',
        'wednesday' => 'miercoles',
        'thursday' => 'jueves',
        'friday' => 'viernes',
        'saturday' => 'sabado',
        'sunday' => 'domingo'
    ];
    
    if (in_array($dia_semana_es[$dia_semana], $dias_cerrados)) {
        $warnings[] = "Advertencia: El restaurante normalmente está cerrado los " . $dia_semana_es[$dia_semana] . "s.";
    }

    // 3. Horario
    $hora_reserva = strtotime($data['hora']);
    $hora_apertura = strtotime(get_option('rm_hora_apertura', '12:00'));
    $hora_cierre = strtotime(get_option('rm_hora_cierre', '23:00'));
    if ($hora_reserva < $hora_apertura || $hora_reserva > $hora_cierre) {
        $warnings[] = "Advertencia: Fuera del horario normal de reservas.";
    }

    // 4. Disponibilidad de mesas
    $mesas_ocupadas = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(CEILING(personas/%d)) 
        FROM $tabla 
        WHERE fecha = %s 
        AND hora = %s",
        RM_PERSONAS_POR_MESA,
        $data['fecha'],
        $data['hora']
    )) ?: 0;

    $mesas_requeridas = ceil($data['personas'] / RM_PERSONAS_POR_MESA);
    if (($mesas_ocupadas + $mesas_requeridas) > RM_MESAS_TOTALES) {
        $warnings[] = "Advertencia: Supera la capacidad normal del restaurante.";
    }

    // Insertar en la base de datos (a pesar de las advertencias)
    $resultado = $wpdb->insert($tabla, $data, ['%s', '%s', '%s', '%s', '%d']);

    // Preparar mensaje de resultado
    if ($resultado) {
        $message = 'reserva_added';
        if (!empty($warnings)) {
            $message .= '&warnings=' . urlencode(implode('|', $warnings));
        }
    } else {
        $message = 'reserva_error';
    }
    
    wp_redirect(add_query_arg('rm_message', $message, wp_get_referer()));
    exit;
}


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
require_once plugin_dir_path(__FILE__) . 'includes/smtp.php';

error_log($hook);

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Opcional: oculta errores en pantalla

?>