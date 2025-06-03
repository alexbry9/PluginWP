jQuery(document).ready(function($) {
    $('.rm-eliminar-reserva').on('click', function() {
        const id = $(this).data('id');

        if (!confirm(rmEliminarReservaData.confirm_text)) return;

        $.post(rmEliminarReservaData.ajax_url, {
            action: 'rm_eliminar_reserva',
            reserva_id: id
        }, function(response) {
            alert(response);
            $('#reserva-' + id).fadeOut();
        });
    });
});
