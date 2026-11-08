// ReUns - Etapas 5, 7 y 8: Interacciones de Operaciones, Confirmación, Cierre y Calificaciones
document.addEventListener('DOMContentLoaded', () => {
    // 1. Confirmación de acciones (aceptar, confirmar, cancelar)
    document.querySelectorAll('form[data-op-confirmar]').forEach(form => {
        form.addEventListener('submit', ev => {
            const accion = form.querySelector('input[name="accion"]')?.value;
            const mensaje = form.dataset.opConfirmar || '¿Estás seguro de realizar esta acción?';

            if (!confirm(mensaje)) {
                ev.preventDefault();
                return;
            }

            // Si es cancelación, permite ingresar un motivo opcional
            if (accion === 'cancelar') {
                const motivo = prompt('Puedes especificar un motivo de cancelación (opcional):');
                if (motivo !== null && motivo.trim() !== '') {
                    let inputMotivo = form.querySelector('input[name="motivo"]');
                    if (!inputMotivo) {
                        inputMotivo = document.createElement('input');
                        inputMotivo.type = 'hidden';
                        inputMotivo.name = 'motivo';
                        form.appendChild(inputMotivo);
                    }
                    inputMotivo.value = motivo.trim().substring(0, 150);
                }
            }
        });
    });

    // 2. Validación de formularios de calificación
    document.querySelectorAll('form.form-calificar').forEach(form => {
        form.addEventListener('submit', ev => {
            const puntuacion = form.querySelector('input[name="puntuacion"]:checked');
            if (!puntuacion) {
                ev.preventDefault();
                alert('Por favor selecciona una puntuación de 1 a 5 estrellas.');
            }
        });
    });
});
