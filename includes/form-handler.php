<?php
add_action('init', 'rm_procesar_formulario');

function rm_procesar_formulario() {
    // Verificar que el formulario ha sido enviado y el nonce es válido
    if (isset($_POST['rm_submit']) && isset($_POST['rm_nonce']) && wp_verify_nonce($_POST['rm_nonce'], 'rm_reserva_form')) {
        global $wpdb;

        // Obtener los datos del formulario
        $nombre = sanitize_text_field($_POST['rm_nombre']);
        $fecha = sanitize_text_field($_POST['rm_fecha']);
        $hora = sanitize_text_field($_POST['rm_hora']);
        $personas = intval($_POST['rm_personas']);
        $email = sanitize_email($_POST['rm_email']);

        // Agregar depuración para verificar que los datos se están recibiendo
        error_log("Formulario recibido: Nombre = $nombre, Fecha = $fecha, Hora = $hora, Personas = $personas, Email = $email");

        // Calcular las mesas necesarias para la reserva
        $mesas_requeridas = ceil($personas / RM_PERSONAS_POR_MESA);

        // Consultar mesas ocupadas para el horario y fecha seleccionados
        $tabla = $wpdb->prefix . 'reservas_mesas';
        $mesas_ocupadas = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(CEIL(personas / %d)) FROM $tabla WHERE fecha = %s AND hora = %s",
                RM_PERSONAS_POR_MESA, $fecha, $hora
            )
        );

        // Si no hay resultados de mesas ocupadas, asumimos 0
        $mesas_ocupadas = $mesas_ocupadas ?? 0;

        // Verificar si hay suficientes mesas disponibles
        if (($mesas_ocupadas + $mesas_requeridas) > RM_MESAS_TOTALES) {
            // Redirigir con un mensaje de error
            wp_redirect(add_query_arg('reserva', 'fail', wp_get_referer()));
            exit;
        }

        // Si todo está bien, hacer la reserva
        $resultado = $wpdb->insert(
            $tabla,
            [
                'nombre' => $nombre,
                'email' => $email,
                'fecha' => $fecha,
                'hora' => $hora,
                'personas' => $personas
            ]
        );

        // Verificar si la inserción fue exitosa
        if ($resultado) {
            // Enviar notificación al administrador
            $mensaje_admin = "Nueva reserva:\n\n";
            $mensaje_admin .= "Nombre: $nombre\n";
            $mensaje_admin .= "Email: $email\n";
            $mensaje_admin .= "Fecha: $fecha\n";
            $mensaje_admin .= "Hora: $hora\n";
            $mensaje_admin .= "Personas: $personas\n";
            wp_mail("tu_email@dominio.com", 'Nueva reserva de mesa', $mensaje_admin);

            // Enviar confirmación al usuario
            $mensaje_usuario = "Hola $nombre,\n\nTu reserva se ha recibido correctamente.\n\nDetalles:\nFecha: $fecha\nHora: $hora\nPersonas: $personas\n\nGracias por reservar.";
            wp_mail($email, 'Confirmación de tu reserva', $mensaje_usuario);

            // Redirigir a la página de confirmación
            wp_redirect(home_url('/reservas/'));
            exit;
        } else {
            // Si la inserción no fue exitosa, puedes agregar un mensaje de error
            error_log('Error al guardar la reserva en la base de datos.');
        }
    }
}

?>
