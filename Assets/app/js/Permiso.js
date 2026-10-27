$(document).ready(function (e) {
    // 1. Botón para seleccionar todo en el Módulo entero
    $(document).on('click', '.check-all-module', function () {
        const targetClass = '.' + $(this).data('target') + '-chk';
        const $checkboxes = $(targetClass);
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $checkboxes.prop('checked', !allChecked);
    });

    // 2. Botón para seleccionar todo en una Categoría específica
    $(document).on('click', '.check-all-category', function () {
        const targetClass = '.' + $(this).data('target') + '-chk';
        const $checkboxes = $(targetClass);
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $checkboxes.prop('checked', !allChecked);
    });
    /**Guardar Permisos de un Rol */
    $('#formPermisos').on('submit', function (e) {
        // 1. Evitamos que el formulario recargue la página de forma tradicional
        e.preventDefault();
        const $btn = $('#btnGuardar');
        // 2. Capturamos todos los datos del formulario (el id_rol y la matriz de checkboxes)
        const formData = $(this).serialize();
        // 3. Enviamos los datos mediante AJAX al controlador
        $.ajax({
            url: `${base_url}/Permiso/store`, // Ajusta tu ruta según el framework
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                $btn.prop("disabled", true);
                // Muestra una ventana de SweetAlert2 con spinner activo
                Swal.fire({
                    title: 'Guardando...',
                    text: `Guardando los permisos del rol, por favor espere.`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading(); // Inicia la animación de carga
                    }
                });
            },
            success: function (response) {
                if (response.status === 'success') {
                    // Alerta bonita de éxito con SweetAlert2
                    Swal.fire({
                        icon: response.icon,
                        title: response.title,
                        text: response.msg,                                                
                    }).then((result) => {
                        if (result.icon != "error") {
                            window.location.href = `${base_url}/Roles`;                       
                        }else{
                            $btn.prop("disabled", false);
                        }
                    });
                } else {
                    Swal.fire({
                        icon: response.icon,
                        title: response.title,
                        text: response.msg || 'Ocurrió un error al guardar los permisos.'
                    });
                    $btn.prop("disabled", false);
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo comunicar con el servidor.'
                });
                $btn.prop("disabled", false);
            }
        });
    });
});