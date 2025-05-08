document.addEventListener('DOMContentLoaded', function () {
    const calendario = document.getElementById('rm-calendario');
    if (!calendario || typeof rmReservasData === 'undefined') return;

    const calendar = new FullCalendar.Calendar(calendario, {
        initialView: 'dayGridMonth',
        locale: 'es',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events: rmReservasData.eventos,
        eventClick: function(info) {
            const confirmacion = confirm(`¿Quieres eliminar la reserva de:\n\n${info.event.title}?`);
            if (confirmacion) {
                // Enviamos la solicitud AJAX para eliminar
                fetch(rmReservasData.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'rm_eliminar_reserva',
                        reserva_id: info.event.id
                    })
                })
                .then(res => res.text())
                .then(res => {
                    alert(res);
                    info.event.remove(); // Quitar del calendario visualmente
                });
            }
        }
    });

    calendar.render();
});

