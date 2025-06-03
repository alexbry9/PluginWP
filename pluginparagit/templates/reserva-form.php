<?php if (isset($_GET['reserva']) && $_GET['reserva'] == 'ok') : ?>
    <p class="rm-mensaje-exito">¡Reserva realizada con éxito!</p>
<?php endif; 

?>

<form method="post" class="reserva-form" id="rm_formulario_reserva">
    <?php wp_nonce_field('rm_reserva_form', 'rm_nonce'); ?>

    <?php if (isset($_GET['reserva'])) {
    $mensajes = [
        'max_personas' => 'El número de personas excede el límite permitido.',
        'dia_cerrado' => 'No aceptamos reservas este día.',
        'fuera_horario' => 'Fuera del horario de reservas.',
        'no_disponible' => 'No hay mesas disponibles para esa fecha y hora.',
        'error_bd' => 'Error al guardar la reserva.'
    ];
    if (isset($mensajes[$_GET['reserva']])) {
        echo '<div class="rm-mensaje-error">' . esc_html($mensajes[$_GET['reserva']]) . '</div>';
    }
    }
    ?>
    
    <label for="rm_nombre">Nombre y apellido:</label>
    <input type="text" name="rm_nombre" required>

    
    <label for="rm_fecha">Fecha:</label>
    <?php
    $manana = date('Y-m-d', strtotime('+1 day'));
    ?>
    <input type="date" name="rm_fecha" min="<?php echo esc_attr($manana); ?>" required>
    
    <!--
    <label for="rm_fecha">Fecha:</label>
    <input type="date" name="rm_fecha" id="rm_fecha" required min="// echo esc_attr($manana); ?>">
    -->
    <label for="rm_hora">Hora:</label>
    <select id="rm_hora" name="rm_hora" required>
        <option value="">Selecciona una hora</option>
    </select>


    
    <!--
    <label for="rm_hora">Hora:</label>
    <select id="rm_hora" name="rm_hora" required>
    <?php
    // Rango de horas, por ejemplo, desde las 10:00 hasta las 22:00
    /*$inicio = 10;
    $fin = 22;
    $hora_actual = current_time('H:i');
    $fecha_seleccionada = isset($_POST['rm_fecha']) ? $_POST['rm_fecha'] : '';
    
    for ($hora = $inicio; $hora < $fin; $hora++) {
        foreach (['00', '30'] as $minuto) {
            $hora_formateada = sprintf('%02d:%s', $hora, $minuto);
            $deshabilitar = '';
    
            if ($fecha_seleccionada === date('Y-m-d')) {
                if ($hora_formateada <= $hora_actual) {
                    $deshabilitar = 'disabled';
                }
            }
    
            echo '<option value="' . $hora_formateada . '" ' . $deshabilitar . '>' . $hora_formateada . '</option>';
        }
    }    
    ^*/?>
    </select>
    -->
    <label for="rm_mail">Email:</label>
    <input type="rm_email" name="rm_email" required placeholder="Tu correo electrónico">

    <label for="telefono">Teléfono</label>
    <input type="tel" id="telefono" name="telefono" required>


    <label for="rm_personas">Número de personas:</label>
    <input type="number" name="rm_personas" min="1" required>

    <input type="submit" name="rm_submit" value="Solicitar reserva">

    <script>jQuery('#rm_formulario_reserva').on('submit', function(e) {
    var maxPersonas = <?php echo esc_js(get_option('rm_max_personas_reserva', 10)); ?>;
    var personas = parseInt(jQuery('#rm_personas').val());
    
    if (personas > maxPersonas) {
        alert('¡Error! Máximo permitido: ' + maxPersonas + ' personas.');
        e.preventDefault();
        return false;
    }
});</script>

</form>
