/**Function de Retenciónes de ISLR 
 * Creado por José Vargas el 06-10-2026 a las 15:14:00
*/
$(document).ready(function () {
    //Validaciones
    $("form#my_form").validate({
        ignore: null,
        rules: {
            fecha_vigencia: "required",
            descrip: {
                required: true,
                minlength: 5,   // Corregido: minlength
                maxlength: 100, // Corregido: maxlength
            },
            por_reten: "required",
            code_seniat: {
                required: true,
                minlength: 3,   // Corregido: minlength
                maxlength: 3    // Corregido: maxlength
            },
            por_imp_suj_ret: "required",
            status: "required"
        },
        messages: {
            fecha_vigencia: "Debe especificar una Fecha de Vigencia",
            descrip: {
                required: "Debe especificar una Descripción",
                minlength: "Debe especificar al menos {0} carácteres",
                maxlength: "Debe especificar máximo {0} carácteres",
            },
            por_reten: "Debe especificar un Porcentaje de Retención",
            code_seniat: {
                required: "Debe especificar el Código del Seniat",
                minlength: "Debe especificar al menos {0} carácteres",
                maxlength: "Debe especificar máximo {0} carácteres",
            },
            por_imp_suj_ret: "Debe especificar el porcentaje sujeto a retención, por lo general el 100%",
            status: "Debe especificar un Status"
        },
    });
    //Cargar el index
    form = $("form").attr("id");
    if (form === undefined) {
        initRetISLR();
    } else {
        id = $("#id").val();
        if (id) {
            show_row(id);
        } else {
            dat_form_new();
        }
        //Para el reporte
        listar_empresas(0);
        $("#fec_ini").val(getFirstDateofMonth());
        $("#fec_ini").trigger('change');
    }
    /**Eliminar un registro
     * Eliminar un registro mientras no tenga movimientos en los Concpetos de CXP
     */
    $(document).on('click', '.btn-delete-index', function (e) {
        e.preventDefault();
        const id = this;
        const id_con = id.dataset.id;
        const name = id.dataset.name;
        const title = `¿Está seguro que desea eliminar el Concepto de Retención de ISLR ${name}?`;
        Swal.fire({
            title: title,
            icon: 'question',
            text: '¡No podrás revertir esta acción!',
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, elimiar registro!",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${base_url}/RetISLR/destroy`,
                    method: 'POST',
                    data: { id_con: id_con },
                    dataType: 'json',
                    success: function (response) {                        
                        if (response.success) {
                            Swal.fire({
                                title: response.title,
                                text: response.msg,
                                icon: response.icon
                            }).then((result) => {
                                if (response.icon != 'error') {
                                    if ($.fn.DataTable.isDataTable('#tblIndexMain')) {
                                        $('#tblIndexMain').DataTable().ajax.reload(null, false);
                                    }
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: response.msg || "No se pudo eliminar el registro",
                                icon: error
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            serverErrorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseText) {
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
            }
        });
    });
    /**Guardar y/o Actualizar */
    $("#my_form").on("submit", function (e) {
        e.preventDefault();
        if (!$(this).valid()) {
            return false;
        }
        const $btnOk = $("#btnok");
        var formData = new FormData(this);
        const url = `${base_url}/RetISLR/store`;
        //Ajax para Guardar y/o Actualizar
        $.ajax({
            url: url,
            method: 'POST',
            dataSrc: '',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $btnOk.prop("disabled", true);
                Swal.fire({
                    title: 'Guardando...',
                    text: `Guardando el concepto de Retencón de ISLR, por favor espere.`,
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
                let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    serverErrorMsg = xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    serverErrorMsg = xhr.statusText || error;
                }
                console.error('Detalle del error:', xhr.responseText);
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
                        window.location.href = `${base_url}/RetISLR`;
                    } else {
                        $btnOk.prop("disabled", false);
                    }
                })
            },
        });
    });
})
listar_empresas();
/**Nuevo registro */
function dat_form_new() {
    listar_status(1);
}
/**
 * Consultar un Cocnepto de Retención
 * @param {*} id 
 */
function show_row(id) {
    url = `${base_url}/RetISLR/show_row`;
    $btnOk = $("#btnok");
    $.ajax({
        url: url,
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        beforeSend: function () {
            Swal.fire({
                title: 'Consultando...',
                text: `Consultando el Cocnepto de Retención de ISLR, por favor espere.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        complete: function () {
            Swal.close()
        },
        error: function (xhr, status, error) {
            $btnOk.prop("disabled", false);
            let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                serverErrorMsg = xhr.responseJSON.msg;
            } else if (xhr.responseText) {
                serverErrorMsg = xhr.statusText || error;
            }
            console.error('Detalle del error:', xhr.responseText);
            Swal.fire({
                title: `Error (${xhr.status || 'Desconocido'})`,
                text: serverErrorMsg,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        },
        success: function (data) {
            $("#fecha_vigencia").val(data["fecha_vigencia"]);
            $("#descrip").val(data["descrip"]);
            $("#minimo").val(format_number_with_dec_new(data["minimo"], 2));
            $("#maximo").val(format_number_with_dec_new(data["maximo"], 2))
            $("#por_reten").val(format_number_with_dec_new(data["por_reten"], 2));
            $("#sustraendo").val(format_number_with_dec_new(data["sustraendo"], 2))
            $("#por_imp_suj_ret").val(format_number_with_dec_new(data["por_imp_suj_ret"], 2));
            $("#fac_reten").val(format_number_with_dec_new(data["fac_reten"], 2));
            $("#code_seniat").val(data["code_seniat"]);
            status = data["status"];
            listar_status(status);
        }
    });
}
function report_retislr(e){    
     //Validar que se haya seleccionado una empresa
    let id = e.dataset.id;
    if( validarcampos()){
       generate_report(id);
    }
}
//Validar los campos del formulario
function validarcampos(){
    id_emp = $("#id_emp").val();
    if(!id_emp){
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Debe indicar una empresa para generar el reporte",
            didClose: () => { $("#id_emp").focus(); }
          });
        return false;
    }
    //Validar que se haya seleccionado una fecha de inicio
    fec_ini = $("#fec_ini").val();
    if(!fec_ini){
        Swal.fire({
            icon: "error",
            title: "Error...",
            text: "Debe indicar una fecha de inicio para generar el reporte",
            didClose: () => { $("#fec_ini").focus(); }
          });
        return false;
    }
    //Validar que se haya seleccionado una fecha de fin
    fec_fin = $("#fec_fin").val();
    if(!fec_fin){
        Swal.fire({
            icon: "error",
            title: "Error...",
            text: "Debe indicar una fecha de corte para generar el reporte",
            didClose: () => { $("#fec_fin").focus(); }
          });
        return false;
    }
    //Validar que la fecha final no sea menor a la fecha inciial
    if(fec_fin < fec_ini){
        Swal.fire({
            icon: "error",
            title: "Error...",
            text: "Fecha final no puede ser menor a la fecha incial",
            didClose: () => { $("#fec_fin").focus(); }
          });
        return false;
    }
    return true;
}
function generate_report(id){
    //Reporte en Excel
    id_emp = $("#id_emp").val();
    nom_empresa = $("#id_emp option:selected").text();
    fec_ini = $("#fec_ini").val();
    fec_fin = $("#fec_fin").val();
    fecha_ini = fec_ini.split('-');
    fecha_fin = fec_fin.split('-');
    Swal.fire({
        icon: 'question',
        title: "¿Está seguro que desea generar el Reporte de Retención de I.S.L.R. de la empresa " +  nom_empresa +  " correspondiente al período del " + fecha_ini[2] + '/' + fecha_ini[1] + '/' + fecha_ini[0] + "  al " + fecha_fin[2] + '/' + fecha_fin[1] + '/' + fecha_fin[0] + " en " + id + "?",
        showCancelButton: true,
        confirmButtonText: "Imprimir",
    }).then((result) => {
        if (result.isConfirmed) {
            if(id=="excel"){
                window.open(`${base_url}/RetISLR/report_retislr?id_emp=` + id_emp + `&fec_ini=` + fec_ini + `&fec_fin=` + fec_fin, '_blank');
            }else if(id=="xml"){
                window.open(`${base_url}/RetISLR/report_retislr_xml?id_emp=` + id_emp + `&fec_ini=` + fec_ini + `&fec_fin=` + fec_fin, '_blank');
            }
        }
    });
}