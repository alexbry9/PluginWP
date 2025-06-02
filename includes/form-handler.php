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
            'personas' => $personas
        ],
        ['%s', '%s', '%s', '%s', '%d']
    );

    require_once plugin_dir_path(__FILE__) . 'smtp.php';

    // CORREO 
        if ($resultado) {
    $to_admin = get_option('admin_email'); // Email admin
    $to_client = $email;                   // Email cliente

    // 1. Plantilla HTML para el cliente
    $message_cliente = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .reserva-box { border: 1px solid #e0e0e0; padding: 20px; max-width: 600px; margin: 0 auto; }
            .reserva-title { color: #d4a762; font-size: 24px; }
            .reserva-details { margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="reserva-box">
            <h2 class="reserva-title">¡Reserva confirmada!</h2>
            <p>Hola ' . esc_html($nombre) . ',</p>
            <div class="reserva-details">
                <p><strong>Detalles de tu reserva:</strong></p>
                <ul>
                    <li><strong>Fecha:</strong> ' . esc_html($fecha) . '</li>
                    <li><strong>Hora:</strong> ' . esc_html($hora) . '</li>
                    <li><strong>Personas:</strong> ' . esc_html($personas) . '</li>
                </ul>
            </div>
            <p>¡Gracias por elegirnos! Estamos preparando todo para tu visita.</p>
        </div>
    </body>
    </html>
    ';

    // 2. Plantilla HTML para el admin
    $message_admin = '
    <html>
    <body>
        <h2>Nueva reserva recibida</h2>
        <p><strong>Cliente:</strong> ' . esc_html($nombre) . ' (' . esc_html($email) . ')</p>
        <p><strong>Detalles:</strong></p>
        <ul>
            <li>Fecha: ' . esc_html($fecha) . '</li>
            <li>Hora: ' . esc_html($hora) . '</li>
            <li>Personas: ' . esc_html($personas) . '</li>
        </ul>
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