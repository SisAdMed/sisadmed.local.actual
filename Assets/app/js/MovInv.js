//Variables
let item = 0;
let c_consig = 0;
tipo_fac = '';
let id_cli;
mov_inv = true;
mov_inv_sal = false;
tipo_fac = 'X';
//Validación de campos
$(document).ready(function () {
    $.validator.setDefaults({
        ignore: [],
    });
    $("form[name='my_form']").validate({
        rules: {
            id_emp: "required",
            id_tmovinv: "required",
            num_movinv: {
                required: {
                    depends: function (element) {
                        return !$(element).prop('readOnly');
                    }
                }
            },
            fecha_comp: "required",
            id_moneda: "required",
            tasa_cambio: "required",
            id_alm: "required",
            descrip_movinv: {
                required: true,
                minlength: 10,
            },
            status: "required",
            item: "required",
        },
        messages: {
            id_emp: "Debe seleccionar una Empresa",
            id_tmovinv: "Debe seleccionar un Tipo de Movimiento",
            num_movinv: "Debe especificar un Núumero de Movimiento",
            fecha_comp: "Debe seleccionar una Fecha",
            id_moneda: "Debe seleccionar una Moneda",
            tasa_cambio: "Debe indicar un Tipo de Cambio",
            id_alm: "Debe seleccionar un Almacén",
            descrip_movinv: {
                required: "Debes especificar una descripción para el Movimiento",
                minlength: "Debe contenedor al menos {0} carácteres",
            },
            status: "Debe seleccionar un Status",
            item: "Debe existir al menos un registro en el Detalle de Movimiento",
        }
    });
    //Cargar el Index
    form = $("form").attr("id");
    if (form === undefined) {
        initMovInvTable();
    } else {
        id = $("#id").val();
        if (id) {
            dat_form(id);
        } else {
            dat_form_new();
        }
    }
    //Validar al seleccionar empresa
    //Validar empresa seleccionada
    $(document).on('change', '#id_emp', async function (e) {
        e.preventDefault();
        id_emp = $(this).val();
        //Limpiar campos
        $("#id_tmovinv").empty();
        $("#id_alm").empty();
        //Llenar campos
        if (id_emp) {
            listar_InvTipoMov(id_emp, '0', 'id_tmovinv');
            listar_almacenes(id_emp);
            id_tdo_cfg = await tip_doc_fac(id_emp);
            id_alm_cfg = id_tdo_cfg['id_alm'];
            id_ubi_cfg = id_tdo_cfg['id_ubi'];
            nom_ubi_cfg = id_tdo_cfg['nom_ubi'];
            $("#id_alm").val(id_alm_cfg);
            stock = id_tdo_cfg['fac_stock'];
            $("#id_moneda").trigger('change');
        }

    });
    //Validar si el tipo de movimiento utiliza consecutivo o no
    $(document).on('change', "#id_tmovinv", function (e) {
        e.preventDefault();
        id_tmovinv = $(this).val();
        const url = `${base_url}/InvTipoMov/val_InvTipoMov`;
        $.ajax({
            url: url,
            method: 'POST',
            data: { id: id_tmovinv },
            dataSrc: '',
            dataType: 'json',
            success: function (data) {
                if (data) {
                    $("#num_movinv").prop('readonly', false);
                    if (data.consecutiv__tmoinv == "1") {
                        $("#num_movinv").prop('readonly', true);
                    }
                }
            }
        });

    });
    //Guardar y/o Actualizar registro
    $("#my_form").on("submit", function (e) {
        e.preventDefault();
        // 1. Validar el formulario principal con jQuery Validate
        if (!$(this).valid()) {
            return false
        }
        // 2. Extraer los datos del detalle (recorriendo cada fila)
        var detalle = [];
        var table = $('.tblEncaMov').DataTable();
        // Usamos table.$('tr') para incluir todas las filas (incluso con paginación)
        table.$('tbody tr').each(function () {
            var $row = $(this);

            var id_prod = $row.find("input[id^='id_prod']").val();
            var id_ubi = $row.find("input[id^='id_ubi']").val();
            var lote = $row.find("input[id^='lote']").val() || 'S/L';
            var fec_venc = $row.find("input[id^='fec_venc']").val() || null;
            var cantidad = parseFloat($row.find("input[id^='cantidad']").val()) || 0;

            // Solo incluir si tiene producto seleccionado y cantidad mayor a 0
            if (id_prod && cantidad > 0) {
                detalle.push({
                    id_prod: id_prod,
                    id_ubi: id_ubi,
                    lote: lote,
                    fec_venc: fec_venc,
                    cantidad: cantidad
                });
            }
        });
        // Validar que exista al menos un artículo en el detalle
        if (detalle.length === 0) {
            Swal.fire('Error', 'Debe agregar al menos un producto válido al detalle.', 'warning');
            return false;
        }
        // 3. Empaquetar cabecera y detalle
        var formData = new FormData(this);
        formData.append('detalle', JSON.stringify(detalle));
        const url = `${base_url}/MovInv/store`;
        //Ajax para Guardar y/o Actualizar        
        const $btnOk = $("#btnok");
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,       // Prevent jQuery from trying to convert the FormData to a string
            contentType: false,       // Prevent jQuery from setting a default Content-Type header
            dataType: 'json',
            beforeSend: function () {
                $btnOk.prop("disabled", true);
                // Muestra una ventana de SweetAlert2 con spinner activo
                Swal.fire({
                    title: 'Guardando...',
                    text: `Guardando el Movimiento de Inventario, por favor espere.`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading(); // Inicia la animación de carga
                    }
                });
            },
            success: function (data) {
                Swal.fire({
                    title: data.title ?? 'Notificación',
                    text: data.msg ?? '',
                    icon: data.icon ?? 'info',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    if (data.icon !== "error") {
                        window.location.href = `${base_url}/MovInv`;
                    } else {
                        // Solo se reactiva el botón si el backend devolvió un error lógico
                        $btnOk.prop("disabled", false);
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
                    serverErrorMsg = xhr.statusText || error;
                }

                console.error('Detalle del error:', xhr.responseText);

                Swal.fire({
                    title: `Error (${xhr.status || 'Desconocido'})`,
                    text: serverErrorMsg,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });
    //Validar el Tipo de Cambio dependiendo de la moneda seleccionada
    $(document).on('change', '#id_moneda', async function (e) {
        id_moneda = $(this).val();
        fecha_comp = $("#fecha_comp").val();
        xTasaCambio = await xTasa(fecha_comp, id_moneda);
        if (xTasaCambio) {
            $("#tasa_cambio").val(xTasaCambio);
        }
    })
    // Eliminar un registro
    $("#tblIndexMain").on("click", ".btn-delete", function () {
        var recordId = $(this).data("id");
        var recordCode = $(this).data("code");
        var recordName = $(this).data("name");
        var recordNumbre = $(this).data("number");
        var descrip = `¿Está seguro de eliminar el Movimiento de Inventario ${recordCode} - ${recordName} - número ${recordNumbre}?`;

        Swal.fire({
            title: descrip,
            text: "¡No podrá revertir esta eliminación!",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, borrar este registro!",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                const url = `${base_url}/MovInv/destroy`;

                $.ajax({
                    url: url,
                    method: "POST",
                    data: {
                        id: recordId,
                        recordCode: recordCode,
                        recordName: recordName,
                        recordNumbre: recordNumbre
                    },
                    dataType: "json",
                    beforeSend: function () {
                        Swal.fire({
                            title: 'Eliminando...',
                            text: `Eliminando el Movimiento de Inventario ${recordCode} - ${recordName} - número ${recordNumbre}...`,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function (resulta) {
                        Swal.fire({
                            icon: resulta.icon ?? 'success',
                            title: resulta.title ?? 'Registro eliminado',
                            text: resulta.msg ?? 'El registro fue eliminado satisfactoriamente.',
                            showConfirmButton: true,      // Restablece el botón de confirmación
                            confirmButtonText: 'Aceptar',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((res) => {
                            // Al hacer clic en "Aceptar", se refresca la tabla
                            if (res.isConfirmed || res.isDismissed) {
                                if ($.fn.DataTable.isDataTable('#tblIndexMain')) {
                                    $('#tblIndexMain').DataTable().ajax.reload(null, false);
                                }
                            }
                        });
                    },
                    error: function (xhr, status, error) {
                        let serverErrorMsg = 'No se pudo eliminar el registro en el servidor.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            serverErrorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseText) {
                            serverErrorMsg = xhr.statusText || error;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: `Error (${xhr.status || 'Desconocido'})`,
                            text: serverErrorMsg,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });
})
/**Nuevo Registro */
function dat_form_new() {
    listar_empresas();
    $("#fecha_comp").val(GetTodayDate(0));
    listar_monedas(2);
    listar_status(1);
}
/**
 * Description Consultar registro
 *
 * @param {*} id 
 */
function dat_form(id) {
    const url = `${base_url}/MovInv/show_row`;
    $.ajax({
        url: url,
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        beforeSend: function () {
            // Muestra una ventana de SweetAlert2 con spinner activo
            Swal.fire({
                title: 'Consultando...',
                text: `Consultando el Movimiento de Inventario, por favor espere.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading(); // Inicia la animación de carga
                }
            });
        },
        complete: function () {
            Swal.close();
        },
        error: function (PDOException) {
            Swal.close();
            console.log('Ha ocurrido el siguiente error:', PDOException.statusText)
        },
        success: async function (data) {
            row = data[0];
            id_emp = row['id_emp'];
            id_tmovinv = row['id_tmoinv'];
            listar_empresas(id_emp, true);
            if (id_emp) {
                listar_InvTipoMov(id_emp, id_tmovinv, 'id_tmovinv');
                $("#id_tmovinv").css("pointer-events", "none");
                id_alm = row['id_alm'];
                listar_almacenes(id_emp, id_alm);
                id_tdo_cfg = await tip_doc_fac(id_emp);
                id_ubi_cfg = id_tdo_cfg['id_ubi'];
                nom_ubi_cfg = id_tdo_cfg['nom_ubi'];
            }

            $("#num_movinv").val(row['num_movinv']);
            $("#num_movinv").css("pointer-events", "none");
            $("#fecha_comp").val(row['fecha_comp']);
            listar_monedas(row['id_moneda']);
            $("#tasa_cambio").val(row['tasa_cambio']);
            $("#descrip_movinv").val(row['descrip_movinv']);
            listar_status(row['status']);
            //Cargar detalle           
            var detalleLimpio = data.map(function (item) {
                var fec = item.fec_venc;
                var lote = item.lote;
                // Si viene "0000-00-00", convertir a cadena vacía
                if (!fec || fec === '0000-00-00') {
                    fec = '';
                    lote = '';
                }
                return {
                    id_prod: item.id_prod,
                    nom_prod: item.nom_prod,
                    id_ubi: item.id_ubi,
                    nom_ubi: item.nom_ubi,
                    lote: lote,
                    fec_venc: fec,
                    cantidad: item.cantidad,
                    lote_prod: item.lote_prod
                }
            });
            table.clear();
            table.rows.add(detalleLimpio);
            table.draw();

            // Forzar bloqueo inmediato en el DOM para cualquier campo vacío
            $('.tblEncaMov tbody tr').each(function () {
                var $row = $(this);
                var $lote = $row.find("input[id^='lote']");
                var $fec = $row.find("input[id^='fec_venc']");

                if ($.trim($lote.val()) === "") {
                    $lote.prop('readonly', true).removeAttr('required');
                    $fec.prop('readonly', true).removeAttr('required');
                }
            });
        }
    })
}
//Consultar productos añadidos a la tabla de detalles
async function ConsultarProductoInv(id, item) {
    let id_prod = id;
    const datos = new FormData();
    datos.append('id_prod', id_prod);
    try {
        const url = `${base_url}/Productos/consulta01`;
        const respuesta = await fetch(url, {
            method: "POST",
            body: datos,
        });
        const resultado = await respuesta.json();
        if (resultado) {
            monto = 0;
            lote = resultado['lote_prod'];
            if (lote == 1) {
                $("#lote" + item).prop("readonly", false);
                $("#fec_ven" + item).prop("readonly", false);
            } else {
                $("#lote" + item).prop("readonly", true);
                $("#fec_ven" + item).prop("readonly", true);
            }
        }
    } catch (err) {
        console.log(err);
    }
}
//Imprimir Movimiento
async function printer_movement(id) {
    id_mov = id.dataset.id;
    number_mov = id.dataset.number;
    tipo_mov = id.dataset.tipo;
    name_mov = id.dataset.name;
    Swal.fire({
        icon: "question",
        title: "¿Está seguro que desea imprimir el Movimiento de Inventario " + tipo_mov + " - " + name_mov + " número " + number_mov + "?",
        showCancelButton: true,
        confirmButtonText: "Si",
        cancelButtonText: "No",
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(`${base_url}/MovInv/printer_movinv/` + id_mov, "_blank");
        }
    });
}
