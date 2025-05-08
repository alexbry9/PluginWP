<?php if (isset($_GET['reserva']) && $_GET['reserva'] == 'ok') : ?>
    <p class="rm-mensaje-exito">¡Reserva realizada con éxito!</p>
<?php endif; ?>

<form method="post" class="reserva-form">
    <?php wp_nonce_field('rm_reserva_form', 'rm_nonce'); ?>

    <label for="rm_nombre">Nombre:</label>
    <input type="text" name="rm_nombre" required>

    <label for="rm_fecha">Fecha:</label>
    <input type="date" name="rm_fecha" required>

    <label for="rm_mail">Email:</label>
    <input type="rm_email" name="rm_email" required placeholder="Tu correo electrónico">

    <label for="rm_hora">Hora:</label>
    <select id="rm_hora" name="rm_hora" required>
    <?php
    // Rango de horas, por ejemplo, desde las 10:00 hasta las 22:00
    $inicio = 10;
    $fin = 22;

    for ($hora = $inicio; $hora < $fin; $hora++) {
        echo '<option value="' . sprintf('%02d:00', $hora) . '">' . sprintf('%02d:00', $hora) . '</option>';
        echo '<option value="' . sprintf('%02d:30', $hora) . '">' . sprintf('%02d:30', $hora) . '</option>';
    }
    ?>
    </select>

    <label for="rm_personas">Número de personas:</label>
    <input type="number" name="rm_personas" min="1" required>

    <input type="submit" name="rm_submit" value="Solicitar reserva">

    <?php if (isset($_GET['reserva']) && $_GET['reserva'] == 'ok') : ?>
    <p class="rm-mensaje-exito">¡Reserva realizada con éxito!</p>
    <?php elseif (isset($_GET['reserva']) && $_GET['reserva'] == 'fail') : ?>
    <p class="rm-mensaje-error">Lo sentimos, no hay mesas disponibles para esa fecha y hora.</p>
    <?php endif; ?>

</form>
