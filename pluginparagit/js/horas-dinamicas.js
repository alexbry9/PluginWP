jQuery(document).ready(function($) {
    // Función para generar horas cada 45 minutos
    function generarHoras() {
        const selectorHora = $('#rm_hora');
        selectorHora.empty().append('<option value="">Selecciona una hora</option>');

        // Configuración básica (luego la haremos dinámica desde PHP)
        const horaApertura = '12:00';  // Hora de apertura del local
        const horaCierre = '23:00';    // Hora de cierre del local
        const intervalo = 45;          // Intervalo en minutos

        // Convertir horas a minutos desde medianoche
        const [aperturaH, aperturaM] = horaApertura.split(':').map(Number);
        const [cierreH, cierreM] = horaCierre.split(':').map(Number);
        
        let minutosActual = aperturaH * 60 + aperturaM;
        const minutosCierre = cierreH * 60 + cierreM;

        // Generar opciones cada 45 minutos
        while (minutosActual <= minutosCierre) {
            const horas = Math.floor(minutosActual / 60);
            const minutos = minutosActual % 60;
            
            // Formatear a HH:MM
            const horaFormateada = 
                `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;
            
            // Añadir al selector
            selectorHora.append(`<option value="${horaFormateada}">${horaFormateada}</option>`);
            
            minutosActual += intervalo; // Sumar 45 minutos
        }
    }

    // Generar horas al cargar la página
    generarHoras();
    
    // Regenerar horas cuando cambie la fecha (para futuras validaciones)
    $('#rm_fecha').on('change', generarHoras);
});