<?php
add_action('init', 'rm_procesar_formulario');

function rm_procesar_formulario() {
    if (!isset($_POST['rm_submit']) || !wp_verify_nonce($_POST['rm_nonce'], 'rm_reserva_form')) {
        return;
    }

    // Sanitizar datos primero
    $nombre = sanitize_text_field($_POST['rm_nombre']);
    $fecha = sanitize_text_field($_POST['rm_fecha']);
    $hora = sanitize_text_field($_POST['rm_hora']);
    $personas = intval($_POST['rm_personas']);
    $email = sanitize_email($_POST['rm_email']);
    $telefono = sanitize_text_field($_POST['telefono']);


    // --- Validaciones estrictas (si alguna falla, NO se guarda) ---
    $errores = [];

    // 1. Validar máximo de personas
    $max_personas = get_option('rm_max_personas_reserva', 10);
    if ($personas > $max_personas) {
        $errores[] = 'max_personas';
    }

    // 2. Validar día cerrado
    $dias_cerrados = get_option('rm_dias_cerrados', []);
    $dia_semana = strtolower(date('l', strtotime($fecha)));
    $dia_semana_es = [
        'monday'    => 'lunes',
        'tuesday'   => 'martes',
        'wednesday' => 'miercoles',
        'thursday'  => 'jueves',
        'friday'    => 'viernes',
        'saturday'  => 'sabado',
        'sunday'    => 'domingo'
    ];
    
    if (in_array($dia_semana_es[$dia_semana], $dias_cerrados)) {
        $errores[] = 'dia_cerrado';
    }

    // 3. Validar horario
    $hora_reserva = strtotime($hora);
    $hora_apertura = strtotime(get_option('rm_hora_apertura', '12:00'));
    $hora_cierre = strtotime(get_option('rm_hora_cierre', '23:00'));
    if ($hora_reserva < $hora_apertura || $hora_reserva > $hora_cierre) {
        $errores[] = 'fuera_horario';
    }

    // --- Si hay errores, redirigir SIN guardar ---
    if (!empty($errores)) {
        wp_redirect(add_query_arg('reserva', $errores[0], wp_get_referer()));
        exit;
    }

    // --- Verificar disponibilidad de mesas ---
    global $wpdb;
    $tabla = $wpdb->prefix . 'reservas_mesas';

    // 1. Calcular mesas ocupadas para esa fecha y hora
    $mesas_ocupadas = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(CEILING(personas/%d)) 
        FROM $tabla 
        WHERE fecha = %s 
        AND hora = %s",
        RM_PERSONAS_POR_MESA,
        $fecha,
        $hora
    )) ?: 0;

    // 2. Calcular mesas necesarias para esta reserva
    $mesas_requeridas = ceil($personas / RM_PERSONAS_POR_MESA);

    // 3. Verificar disponibilidad
    if (($mesas_ocupadas + $mesas_requeridas) > RM_MESAS_TOTALES) {
        wp_redirect(add_query_arg('reserva', 'no_disponible', wp_get_referer()));
        exit;
    }

    // --- Si todo está OK, guardar la reserva ---
    $resultado = $wpdb->insert(
        $tabla,
        [
            'nombre' => $nombre,
            'email' => $email,
            'fecha' => $fecha,
            'hora' => $hora,
            'personas' => $personas,
            'telefono' => $telefono
        ],
        ['%s', '%s', '%s', '%s', '%d']
    );

    require_once plugin_dir_path(__FILE__) . 'smtp.php';

    // CORREO 
        if ($resultado) {
    $to_admin = 'alexbripi@gmail.com'; // Email admin
    $to_client = $email; // Email cliente

    // 1. Plantilla HTML para el cliente
$message_cliente = '
<html>
<head>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, Arial, sans-serif;
            color: #333333;
            margin: 0; padding: 0;
            background-color: #f9f9f9;
            text-align: center;
        }
        .reserva-box {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            padding: 30px 20px;
            max-width: 600px;
            margin: 20px auto;
            border-radius: 8px;
            text-align: center;
        }
        .reserva-title {
            color: #d4a762;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
        }
        .reserva-saludo {
            margin-bottom: 20px;
            font-size: 18px;
            text-align: center;
        }
        .reserva-details {
            margin-top: 20px;
            font-size: 16px;
            line-height: 1.5;
            text-align: center;
        }
        .reserva-details ul {
            padding-left: 0;
            list-style: none;
            display: inline-block;
            text-align: left;
        }
        .reserva-footer {
            margin-top: 30px;
            font-size: 16px;
            color: #555555;
            text-align: center;
        }
        img.logo {
            display: block;
            margin: 0 auto 25px auto;
            width: 180px;
            height: auto;
            border-radius: 8px;
            user-select: none;
            -webkit-user-drag: none;
            pointer-events: none;
        }
        a.map-link {
            display: inline-block;
            margin-top: 25px;
            font-size: 16px;
            color: #d4a762;
            text-decoration: none;
            font-weight: 600;
        }
        a.map-link:hover {
            text-decoration: underline;
        }
        .copyright {
            margin-top: 40px;
            font-size: 12px;
            color: #999999;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="reserva-box">
        <img class="logo" src="https://lasiciliabella.com/wp-content/uploads/2025/06/lasiciliabella_logoCF.png" alt="Logo La Sicilia Bella" />
        <h2 class="reserva-title">¡Reserva confirmada!</h2>
        <p class="reserva-saludo">Hola ' . esc_html($nombre) . ',</p>
        <div class="reserva-details">
            <p><strong>Detalles de tu reserva:</strong></p>
            <ul>
                <li><strong>📅 Fecha:</strong> ' . esc_html($fecha) . '</li>
                <li><strong>⏰ Hora:</strong> ' . esc_html($hora) . '</li>
                <li><strong>👥 Personas:</strong> ' . esc_html($personas) . '</li>
            </ul>
        </div>
        <p class="reserva-footer">¡Gracias por elegirnos! Estamos preparando todo para tu visita.</p>
        <a class="map-link" href="https://www.google.com/maps/place/Calle+de+Graus,+9,+Delicias,+50010+Zaragoza" target="_blank" rel="noopener noreferrer">
            Ver ubicación en Google Maps
        </a>
        <div class="copyright">
            &copy; ' . date('Y') . ' La Sicilia Bella. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
';



    // 2. Plantilla HTML para el admin
$message_admin = '
<html>
<head>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .admin-box {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        h2 {
            font-size: 26px;
            color: #d4a762;
            margin-bottom: 20px;
            text-align: center;
        }
        .detalle {
            font-size: 18px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .detalle strong {
            display: inline-block;
            width: 120px;
            color: #000;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="admin-box">
        <h2>📩 Nueva reserva recibida</h2>

        <div class="detalle"><strong>Cliente:</strong> ' . esc_html($nombre) . '</div>
        <div class="detalle"><strong>Email:</strong> ' . esc_html($email) . '</div>
        <div class="detalle"><strong>📞 Teléfono:</strong> ' . esc_html($telefono) . '</div>
        <div class="detalle"><strong>📅 Fecha:</strong> ' . esc_html($fecha) . '</div>
        <div class="detalle"><strong>⏰ Hora:</strong> ' . esc_html($hora) . '</div>
        <div class="detalle"><strong>👥 Personas:</strong> ' . esc_html($personas) . '</div>

        <div class="footer">
            Este es un aviso automático generado por el sistema de reservas de <strong>' . get_bloginfo('name') . '</strong>.
        </div>
    </div>
</body>
</html>
';


    // 3. Enviar correos
    $mail_client = enviar_correo_smtp(
        $to_client,
        'Confirmación de reserva - ' . get_bloginfo('name'),
        $message_cliente
    );

    $mail_admin = enviar_correo_smtp(
        $to_admin,
        'Nueva reserva de ' . esc_html($nombre),
        $message_admin
    );

    if (!$mail_client || !$mail_admin) {
        error_log('Error enviando correo de confirmación o notificación de reserva');
    }

    // 4. Redireccionar a página de éxito
    wp_redirect(home_url('/reservas/'));
    exit;
} else {
    wp_redirect(add_query_arg('reserva', 'error_bd', wp_get_referer()));
    exit;
}

}
?>