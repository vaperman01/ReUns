/* 1. VALIDACIÓN DE FORMULARIOS */
document.querySelectorAll('form[data-validar]').forEach(form => {
    form.addEventListener('submit', event => {
        let ok = true;

        form.querySelectorAll('input[required]').forEach(input => {
            const error = input.closest('.campo')?.querySelector('.error');
            let mensaje = '';

            const valor = input.type === 'checkbox'
                ? (input.checked ? 'x' : '')
                : input.value.trim();

            // Campos obligatorios
            if (!valor) {
                mensaje = input.type === 'checkbox'
                    ? 'Debes aceptar para continuar.'
                    : 'Este campo es obligatorio.';
            }

            // Validación del correo electrónico
            else if (
                input.type === 'email' &&
                !input.checkValidity()
            ) {
                mensaje = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
            }

            // Validación de una contraseña nueva
            else if (
                input.name === 'clave' &&
                form.dataset.validar === 'nueva'
            ) {
                if (input.value.length < 8) {
                    mensaje = 'Usa al menos 8 caracteres.';
                } else if (
                    !/[A-Za-z]/.test(input.value) ||
                    !/\d/.test(input.value)
                ) {
                    mensaje = 'Incluye al menos una letra y un número.';
                }
            }

            // Confirmación de contraseña
            else if (input.name === 'clave2') {
                const clave = form.querySelector('[name="clave"]');

                if (clave && input.value !== clave.value) {
                    mensaje = 'Las contraseñas no coinciden.';
                }
            }

            // Mostrar errores y actualizar accesibilidad
            if (error) {
                error.textContent = mensaje;
            }

            input.setAttribute(
                'aria-invalid',
                mensaje ? 'true' : 'false'
            );

            if (mensaje) {
                ok = false;
            }
        });

        // Evitar el envío si hay errores
        if (!ok) {
            event.preventDefault();

            form.querySelector('[aria-invalid="true"]')?.focus();
        }
    });
});


/* 2. CONFIRMACIÓN DE ACCIONES DESTRUCTIVAS */
// Uso en HTML: <form data-confirmar="¿Seguro?">

document.querySelectorAll('form[data-confirmar]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirm(form.dataset.confirmar)) {
            event.preventDefault();
        }
    });
});


/* 3. VALIDACIÓN DE FOTOGRAFÍAS */
// Valida tipo, tamaño y cantidad antes de subir.
// El servidor debe volver a validar todos los archivos.

document
    .querySelectorAll('input[type="file"][data-max-mb]')
    .forEach(input => {
        input.addEventListener('change', () => {
            const error = input.closest('.campo')?.querySelector('.error');

            const maxBytes = Number(input.dataset.maxMb) * 1048576;
            const maxArchivos = Number(input.dataset.maxFiles);

            let mensaje = '';

            // Comprobar la cantidad de archivos
            if (input.files.length > maxArchivos) {
                mensaje = `Máximo ${maxArchivos} foto(s) en esta selección.`;
            }

            // Comprobar el tipo y el tamaño
            for (const archivo of input.files) {
                if (
                    !['image/jpeg', 'image/png', 'image/webp']
                        .includes(archivo.type)
                ) {
                    mensaje = 'Solo JPG, PNG o WebP.';
                    break;
                }

                if (archivo.size > maxBytes) {
                    mensaje = `"${archivo.name}" pesa más de ${input.dataset.maxMb} MB.`;
                    break;
                }
            }

            // Mostrar errores y limpiar la selección inválida
            if (error) {
                error.textContent = mensaje;
            }

            if (mensaje) {
                input.value = '';
            }
        });
    });


/* 4. MODALIDAD DEL PRODUCTO */
// Oculta los campos de precio cuando la modalidad es intercambio.

const modalidad = document.getElementById('modalidad');

if (modalidad) {
    const actualizarPrecios = () => {
        document.querySelectorAll('.campo-precio').forEach(campo => {
            campo.hidden = modalidad.value === 'intercambio';
        });
    };

    modalidad.addEventListener('change', actualizarPrecios);

    // Aplicar el estado inicial
    actualizarPrecios();
}