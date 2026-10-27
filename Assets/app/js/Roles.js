/** Funciones de Roles
 * Creado por Jossé Vargas el 05-10-2026 a las 10:51 a.m.
 */
//Iniciar
$(document).ready(function () {
    //Validaciones
    $("form#my_form").validate({
        rules: {
            "nombre_rol": {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            status: {
                required: true,
            },
        },
        messages: {},
    });
    //Cargar el index
    form = $("form").attr("id");
    if (form === undefined) {
        initRoles();
    } else {
        id = $("#id").val();
        if (id) {
            show_row(id);
        } else {
            dat_form_new();
        }
    }
    /**Eliminar Roles */
    $(document).on("click", ".btn-delete-index", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var nombre = $(this).data("name");
        Swal.fire({
            title: `¿Está seguro de eliminar el Rol ${nombre}?`,
            text: "¡No podrás revertir esto!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) {
                url = `${base_url}/Roles/delete`;
                $.ajax({
                    url: url,
                    method: "POST",
                    data: { id: id },
                    dataType: "json",
                    beforeSend: function () {
                        Swal.fire({
                            title: "Eliminando...",
                            text: `Eliminando el Rol ${nombre}, por favor espere.`,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });
                    },
                    success: function (data) {
                        if (data.success) {
                            Swal.fire({
                                title: "¡Eliminado!",
                                text: `El Rol ${nombre} ha sido eliminado satisfactoriamente`,
                                icon: 'success'
                            });
                            if ($.fn.DataTable.isDataTable('#tblIndexMain')) {
                                $('#tblIndexMain').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.msg || "No se pudo eliminar el registro",
                                icon: 'error'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        // Extraer el mensaje real devuelto por el servidor si viene en JSON o texto
                        let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            serverErrorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseText) {
                            // Si el backend devolvió un fatal error / HTML de PHP, se toma un fragmento legible
                            serverErrorMsg = xhr.responseText || error;
                        }
                        Swal.fire({
                            title: `Error (${xhr.status || 'Desconocido'})`,
                            text: serverErrorMsg,
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });
    /**Guardar y/o actualizar */
    $("#my_form").submit(function (e) {
        e.preventDefault();
        if ((!$(this).valid())) {
            return false;
        }
        const $btnOk = $("#btnok");
        var formData = new FormData(this);
        url = `${base_url}/Roles/store`;
        //Ajax para Guardar y/o Actualizar
        $.ajax({
            url: url,
            method: "POST",
            data: formData,
            dataType: "json",
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $btnOk.prop("disabled", true);
                Swal.fire({
                    title: 'Guardando...',
                    text: `Guardando el Rol, por favor espere.`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            error: function (xhr, status, error) {
                $btnOk.prop("disabled", false);
                // Extraer el mensaje real devuelto por el servidor si viene en JSON o texto
                let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    serverErrorMsg = xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    // Si el backend devolvió un fatal error / HTML de PHP, se toma un fragmento legible
                    serverErrorMsg = xhr.responseText || error;
                }
                Swal.fire({
                    title: `Error (${xhr.status || 'Desconocido'})`,
                    text: serverErrorMsg,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            },
            success: function (data) {
                Swal.fire({
                    title: data.title,
                    text: data.msg,
                    icon: data.icon,
                }).then((result) => {
                    if (data.icon != "error") {
                        window.location.href = `${base_url}/Roles`;
                    } else {
                        // Solo se reactiva el botón si el backend devolvió un error lógico
                        $btnOk.prop("disabled", false);
                    }
                })
            },
        })
    });

});
/**Nuevo Registro */
function dat_form_new() {
    listar_status(1);
}
/**
 * Mostrar los datos de un registro en el formulario
 * @param {int} id 
 */
function show_row(id) {
    url = `${base_url}/Roles/show_row`;
    $btnOk = $("#btnok");
    $.ajax({
        url: url,
        method: "POST",
        data: { id: id },
        dataType: "json",
        beforeSend: function () {
            Swal.fire({
                title: 'Consultando...',
                text: `Consultando el Rol, por favor espere.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        complete: function () {
            Swal.close();
        },
        success: function (data) {
            if (data) {
                $("#id").val(data.id_rol);
                $("#nombre_rol").val(data.nombre_rol);
                listar_status(data.status_rol);
            }
        }
    });
}