document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('rm-calendario') || !rmReservasData) return;

    const calendarEl = document.getElementById('rm-calendario');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: rmReservasData.eventos,
        eventClick: function(info) {
            // Mostrar detalles en un modal o alerta
            const detalles = `
                <h3>${info.event.title}</h3>
                <p><strong>Fecha:</strong> ${info.event.start.toLocaleString()}</p>
                <p><strong>Personas:</strong> ${info.event.extendedProps.personas}</p>
                <p><strong>Email:</strong> ${info.event.extendedProps.email || 'No disponible'}</p>
            `;
            
            if (confirm(`${detalles}\n\n¿Deseas cancelar esta reserva?`)) {
                fetch(rmReservasData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'rm_eliminar_reserva',
                        reserva_id: info.event.id,
                        nonce: rmReservasData.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        info.event.remove();
                        alert('Reserva cancelada correctamente');
                    } else {
                        alert('Error: ' + data.data);
                    }
                })
                .catch(error => {
                    alert('Error al procesar la solicitud');
                    console.error(error);
                });
            }
        }
    });

    calendar.render();
});