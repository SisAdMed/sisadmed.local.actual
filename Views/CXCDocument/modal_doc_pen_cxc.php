<!-- Modal para listar y buscar Conceptos de Cuentas por Cobrar-->
<div class="modal fade" id="modal_doc_pen_cxc">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar y seleccionar Documentos de Cuentas por Cobrar</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group col-md-12 col-sm-12 col-xs-12 dt-table" id="modal_doc_pen_cxc_data" name="contenedor_tabla_modal_cxc">                    
                    <table id="tblModalDocPend_cxc" name="tblModalDocPend_cxc" class="display responsive compact table table-hover text-xs" style="width:100%">
                    </table>                    
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    //Mostar los Documentos pendientes de Cuentas por Cobrar, tanto desde Banco Movimientos como desde Movimientos de Cuenas por Pagar. José Vargas 20-10-2026 a las 15:10:00        
    function cargarConfiguracionBanco(id_emp, efecto) {
        if (!id_emp) return;
        $.ajax({
            url: `${base_url}/BanMovim/getConfigBanco`, // Ajusta la ruta a tu controlador
            method: "POST",
            data: { id_emp: id_emp, efecto: efecto},
            dataType: "json",
            success: function(response) {
                if (response && response.status) {
                    // Suponiendo que tu backend te devuelve el ID o código del concepto configurado
                    conceptoProveedorDefault = response.data;
                } else {
                    console.warn("No se encontró un concepto configurado para proveedores.");
                }
            },
            error: function(error) {
                console.log("Error al cargar la configuración bancaria:", error);
            }
        });
    }

    function cargar_documentos_pendientes_cxc() {
        id_ent = $("#id_cli").val();
        fecha_comp = $("#fecha_comp").val();
        id_moneda = $("#id_moneda").val();
        var url = `${base_url}/CXCDocument/doc_ped_cli`;
        datos = {
            id_emp: id_emp,
            id_cli: id_ent,
            fecha_comp: fecha_comp,
            id_moneda: id_moneda
        };
        $.ajax({
            url: url,
            method: "POST",
            data: datos,
            dataType: "json",
            beforeSend: function() {
                loader.show();
            },
            success: function(data) {
                $("#tblModalDocPend_cxc").DataTable({
                    destroy: true,
                    data: data,
                    responsive: true,
                    processing: true,
                    columns: [{
                            data: "id_doc",
                            title: "Id",
                            visible: false
                        },
                        {
                            data: "tipo_codigo",
                            title: "Código"
                        },
                        {
                            data: "nom_tdoc",
                            title: "Descripción"
                        },
                        {
                            data: "num_tdo",
                            title: "Número",
                            className: "text-right"
                        },
                        {
                            data: "nro_control",
                            title: "Control",
                            className: "text-right"
                        },
                        {
                            data: "fecha_comp",
                            render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN),
                            title: "Fecha Emi."
                        },
                        {
                            data: "fecha_venci",
                            render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN),
                            title: "Fecha Venc."
                        },
                        {
                            data: "codigo_moneda",
                            title: "Moneda",
                            className: "text-center"
                        },
                        {
                            data: "tasa_cambio",
                            className: "text-right",
                            render: DataTable.render.number(".", ",", 4),
                            title: "Tasa Cambio"
                        },
                        {
                            data: null,
                            className: "text-right",
                            title: "Monto Doc.",
                            render: function(data, type, row) {                                
                                if (id_moneda == id_moneda_cia) {
                                    return `${format_number_with_dec_new(row.mon_doc_dom, 2)}`;
                                } else {
                                    return `${format_number_with_dec_new(row.mon_doc_for, 2)}`;
                                }
                            },
                        },
                        {
                            data: null,
                            className: "text-right",
                            title: "Saldo Doc.",
                            render: function(data, type, row) {
                                if (id_moneda == id_moneda_cia) {
                                    return `${format_number_with_dec_new(row.sal_doc_dom, 2)}`;
                                } else {
                                    return `${format_number_with_dec_new(row.sal_doc_for, 2)}`;
                                }
                            },
                        },
                        {
                            data: null,
                            className: "text-center",
                            title: "Acción",
                            render: function(data, type, row) {
                                return `<button type="button" class="btn btn-success btn-xs btn_seleccionar_doc_cxc" title="Seleccionar documento">
                                <i class="fa fa-check"></i> Seleccionar </button>`;
                            }
                        }
                    ],
                    language: {
                        url: `${base_url}/Assets/json/es-ES.json`,
                    },
                });
            },
            complete: function() {
                loader.hide();
            },
            error: function(error) {
                loader.hide();
                console.log("Error al cargar los datos: ", error);
            },
        });
    }

   function validarComprobantesRetencion() {
    console.log('entre a validarComprobantesRetencion');
    let valido = true;
    let mensajeError = "";

    // Seleccionamos directamente todas las filas del tbody de la tabla
    let filas = jQuery('#tblSeatDetail_cxc tbody tr');
    console.log('Cantidad de filas encontradas en la tabla CXC:', filas.length);

    filas.each(function(index) {
        let tr = jQuery(this);
        let inputRet = tr.find('input[data-campo="mon_ret"]');
        let inputComp = tr.find('input[data-campo="num_ret"]');

        // Si por alguna razón no encuentra los inputs en esta fila, saltamos a la siguiente
        if (inputRet.length === 0 || inputComp.length === 0) {
            return true; // equivale al continue en jQuery each
        }

        let rawRet = inputRet.val() || "0";
        let montoRetVal = parseFloat(rawRet.toString().replace(/\./g, '').replace(',', '.')) || 0;
        let numComprobante = inputComp.val() ? inputComp.val().trim() : "";

        console.log(`Fila ${index} -> Monto Retención:`, montoRetVal, '| Comprobante:', numComprovenre = numComprobante);

        if (montoRetVal != 0) {
            console.log('entre-001: El monto de retención es mayor a 0');
            
            // Validar exactamente 20 dígitos numéricos
            if (!/^\d{14}$/.test(numComprobante)) {
                valido = false;
                inputComp.addClass('is-invalid');
                mensajeError = "Hay documentos con Retención de IVA cuyo número de comprobante no es válido. Debe contener exactamente 14 dígitos numéricos.";
            } else {
                inputComp.removeClass('is-invalid');
            }
        }
    });

    if (!valido) {
        Swal.fire({
            icon: 'error',
            title: 'Comprobante requerido',
            text: mensajeError,
            confirmButtonText: 'Aceptar'
        });
    }

    return valido;
}

    function sincronizarDetalleMovimientoBanco_cxc() {         
        if (!id_emp) return;

        // 1. Obtener el total general actual de la pestaña de Cuentas por Cobrar
        let totalCXPStr = jQuery('#total_monto_cancelar').text().replace(/\./g, '').replace(',', '.') || '0';
        let totalCXPNum = parseFloat(totalCXPStr) || 0;

        // Obtener el total general de retención de la pestaña de Cuentas por Cobrar
        let totalCXPStrRet = jQuery('#total_monto_retenido').text().replace(/\./g, '').replace(',', '.') || '0';
        let totalCXPNumRet = parseFloat(totalCXPStrRet) || 0;

        // Si no hay concepto configurado, no hacemos nada
        if (!window.conceptoProveedorDefault) {
            console.warn("No hay un concepto de proveedor configurado.");
            return;
        }

        let idConceptoBuscado = window.conceptoProveedorDefault['id_bancon_CXP'];
        let idConceptoBuscadoRet = window.conceptoProveedorDefault['id_bancon_RETIVA'];

        // 2. Instancia de la tabla de la pestaña "Detalles"
        let tableDetalles = jQuery('#tblDetalleMovBan').DataTable();
        let encontrado = false;
        let encontradoRet = false;

        // 3. Recorremos las filas existentes en la tabla de detalles
        tableDetalles.rows().every(function(rowIdx, tableLoop, rowLoop) {
            let data = this.data();

            // Actualizar concepto principal (CXP)
            if (data.id_bancon == idConceptoBuscado) {
                encontrado = true;
                data.monto_nac = totalCXPNum;
                this.data(data);
            }

            // Actualizar concepto de retención (NOTA: Usamos monto_nac que es la propiedad del input de la tabla)            
            if (data.id_bancon == idConceptoBuscadoRet) {
                encontradoRet = true;
                console.log('totalCXPNumRet');
                console.log(totalCXPNumRet);
                data.monto_nac = totalCXPNumRet; // Si debe ir en negativo, cámbialo a: totalCXPNumRet * -1
                this.data(data);
            }
        });

        // 4. Agregar concepto principal si no existe y el monto es mayor a 0
        if (!encontrado && totalCXPNum != 0) {
            let nuevaFilaDetalle = {
                id_bancon: idConceptoBuscado,
                nom_bancon: window.conceptoProveedorDefault['nom_con'],
                id_aux: window.conceptoProveedorDefault['id_aux'],
                nombre_aux: window.conceptoProveedorDefault['nom_aux'],
                monto_nac: totalCXPNum
            };
            tableDetalles.row.add(nuevaFilaDetalle);
        }

        // 5. Agregar concepto de retención si no existe y el monto es mayor a 0
        if (!encontradoRet && totalCXPNumRet != 0) {
            let nuevaFilaDetalleRet = {
                id_bancon: idConceptoBuscadoRet,
                nom_bancon: window.conceptoProveedorDefault['nom_con_ret'],
                id_aux: window.conceptoProveedorDefault['id_aux_ret'],
                nombre_aux: window.conceptoProveedorDefault['nom_aux_ret'],
                monto_nac: totalCXPNumRet// O con signo negativo si aplica
            };
            tableDetalles.row.add(nuevaFilaDetalleRet);
        }

        // 6. Redibujamos la tabla UNA SOLA VEZ al final para actualizar toda la vista limpiamente
        tableDetalles.draw(false);

        // Opcional: Actualizar el total general en el footer de la pestaña detalles
        if (typeof calcularTotalFooterDetalles === 'function') {
            calcularTotalFooterDetalles();
        }
    }
    window.addEventListener('load', function() {
        //Crear DAtatable para la carga de los documentos a pagar de CXC
        table_cxc = $("#tblSeatDetail_cxc").DataTable({
            destroy: true,
            responsive: true,
            processing: true,
            autoWidth: false,
            paging: false, // Usualmente los detalles no se paginan para no perder los inputs de vista
            info: false,
            searching: false,
            ordering: false, // <-- Desactiva el ordenamiento en TODA la tabla y quita todas las flechas
            order: [],
            language: {
                url: `${base_url}/Assets/json/es-ES.json`,
            },
            columns: [{
                    data: null,
                    title: "Item",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        return `${item}`;
                    }
                },
                {
                    data: null,
                    title: "Id",
                    className: 'text-right',
                    visible: false,
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var id_doc = row.id_doc || '';
                        return `${id_doc}`;
                    }
                },
                {
                    data: null,
                    title: "Tipo Doc",
                    className: 'text-right',
                    visible: false,
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var id_tdo = row.id_tdo || '';
                        return `${id_tdo}`;
                    }
                },
                {
                    data: null,
                    title: "Tipo",
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var tipo_codigo = row.tipo_codigo || '';
                        return tipo_codigo;
                    }
                },
                {
                    data: null,
                    title: "Descripción",
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var nom_tdoc = row.nom_tdoc || '';
                        return nom_tdoc;
                    }
                },
                {
                    data: null,
                    title: "Número",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var num_tdo = row.num_tdo || '';
                        return num_tdo;
                    }
                },
                {
                    data: null,
                    title: "Fec.Emis.",
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var fec_emi = row.fec_emi;
                        // Si no hay fecha o viene vacía, retornamos un espacio en blanco para que no explote
                        if (!fec_emi || fec_emi === '') {
                            return '';
                        }
                        // Validamos con moment y devolvemos el formato deseado
                        let fechaMoment = moment(fec_emi);
                        return fechaMoment.isValid() ? fechaMoment.format('DD-MM-YYYY') : fec_emi;
                    }
                },
                {
                    data: null,
                    title: "Fec.Venc.",
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var fecha_venci = row.fecha_venci;
                        // Si no hay fecha o viene vacía, retornamos un espacio en blanco para que no explote
                        if (!fecha_venci || fecha_venci === '') {
                            return '';
                        }
                        // Validamos con moment y devolvemos el formato deseado
                        let fechaMoment = moment(fecha_venci);
                        return fechaMoment.isValid() ? fechaMoment.format('DD-MM-YYYY') : fecha_venci;
                    }
                },
                {
                    data: null,
                    title: "Moneda",
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var codigo_moneda = row.codigo_moneda || '';
                        return codigo_moneda;
                    }
                },
                {
                    data: null,
                    title: "Tasa",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var tasa_cambio = row.tasa_cambio || '';
                        return format_number_with_dec_new(tasa_cambio, 4);
                    }
                },
                {
                    data: null,
                    title: "Monto",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var monto = row.mon_doc_dom                        
                        id_moneda = $("#id_moneda").val();                        
                        if (row.id_moneda_cia != id_moneda) {
                            monto = row.mon_doc_for;
                        }
                        return format_number_with_dec_new(monto, 2);
                    }
                },
                {
                    data: null,
                    title: "Saldo",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var monto = row.sal_doc_dom
                        id_moneda = $("#id_moneda").val();
                        if (row.id_moneda_cia != id_moneda) {
                            monto = row.sal_doc_for;
                        }
                        return format_number_with_dec_new(monto, 2);
                    }
                },
                {
                    data: null,
                    title: "Cancelar",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var monto = row.mon_can_dom
                        id_moneda = $("#id_moneda").val();
                        if (row.id_moneda_cia != id_moneda) {
                            monto = row.mon_can_for;
                        }
                        return `<input type="text" name="monto[]" id="monto${item}" value="${format_number_with_dec_new(monto, 2)}" class="form-control-sm text-right text-xs camponumero" data-campo="mon_can" style="height: 26px; padding: 2px 6px;">`;
                    }
                },
                {
                    data: null,
                    title: "Ret. IVA",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var monto = row.mon_ret_dom
                        id_moneda = $("#id_moneda").val();
                        if (row.id_moneda_cia != id_moneda) {
                            monto = row.mon_ret_for ;
                        }
                        return `<input type="text" name="monto[]" id="monto${item}" value="${format_number_with_dec_new(monto, 2)}" class="form-control-sm text-right text-xs camponumero" data-campo="mon_ret" style="height: 26px; padding: 2px 6px;">`;
                    }
                },
                {
                    data: null,
                    title: "Comprobante",
                    className: 'text-right',
                    render: function(data, type, row, meta) {
                        var item = meta.row + 1;
                        var num_ret = row.num_ret
                        return `<input type="number" name="monto[]" id="monto${item}" value="${num_ret}" class="form-control-sm text-right text-xs" data-campo="num_ret" style="height: 26px; padding: 2px 6px;">`;
                    }
                },
                {
                    data: null,
                    title: "Acciones",
                    className: "text-center",
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return `
                                <div class="btn-group">
                                <button type="button" class="btn btn-danger btn-xs btn-borrar_cxc text-xs" title="Eliminar item"><i class="far fa-trash-alt"></i></button>
                        </div>`;
                    }
                }
            ],
            // Callback para construir el pie de página de forma dinámica
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();
                // Creamos o actualizamos el <tfoot> en el HTML de la tabla si no existe
                if (jQuery('#tblSeatDetail_cxc tfoot').length === 0) {
                    jQuery('#tblSeatDetail_cxc').append(
                        `<tfoot>
                            <tr>
                                <th colspan="10" style="text-align:right; font-weight:bold;">Total General a Cancelar:</th>
                                <th id="total_monto_cancelar" style="text-align:right; font-weight:bold;">0,00</th>
                                <th id="total_monto_retenido" style="text-align:right; font-weight:bold;">0,00</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>`
                    );
                }
                // Llamamos a la función que calcula los montos de los inputs
                calcularTotalMontoCancelar_cxc();
            }
        });
        // Todo tu código de clic y de la función del modal va aquí adentro        
        $(document).off('click', '.btn_seleccionar_doc_cxc').on('click', '.btn_seleccionar_doc_cxc', function() {
            let table_mod = jQuery('#tblModalDocPend_cxc').DataTable();
            let tr = jQuery(this).closest('tr');
            if (tr.hasClass('child')) {
                tr = tr.prev('.parent');
            }
            let dataRow = table_mod.row(tr).data();
            if (!dataRow) return;
            //Poblar la Tabla de Documentos de CXP a Cancelar             
            var newRow = {
                id_doc: dataRow['id_doc'],
                tipo_codigo: dataRow['tipo_codigo'],
                id_tdo: dataRow['id_tdo'],
                nom_tdoc: dataRow['nom_tdoc'],
                num_tdo: dataRow['num_tdo'],
                fec_emi: dataRow['fecha_comp'],
                fecha_venci: dataRow['fecha_venci'],
                id_moneda: dataRow['id_moneda'],
                id_moneda_cia: dataRow['id_moneda_cia'],
                codigo_moneda: dataRow['codigo_moneda'],
                tasa_cambio: dataRow['tasa_cambio'],
                mon_doc_dom: dataRow['mon_doc_dom'],
                mon_doc_for: dataRow['mon_doc_for'],
                sal_doc_dom: dataRow['sal_doc_dom'],
                sal_doc_for: dataRow['sal_doc_for'],
                mon_can_dom: dataRow['mon_can_dom'] || dataRow['sal_doc_dom'],
                mon_can_for: dataRow['mon_can_for'] || dataRow['sal_doc_for'],
                mon_ret_dom: dataRow['monret_iva_dom'] * -1,
                num_ret: dataRow['num_ret'] || '',
            }
            // Agregamos a la API y redibujamos la tabla
            table_cxc.row.add(newRow).draw(false);
            // Cierras el modal de forma limpia
            jQuery("#modal_doc_pen_cxc").modal("hide");
            // HACEMOS FOCO Y SELECCIONAMOS EL TEXTO DEL INPUT RECIÉN CREADO
            setTimeout(function() {
                let inputRecienCreado = jQuery(`#monto${item}`);
                if (inputRecienCreado.length) {
                    inputRecienCreado.focus();
                    inputRecienCreado.select(); // Selecciona todo el texto para facilitar la edición
                } else {
                    // Plan B por si el ID dinámico varía: buscamos el último input de la tabla
                    jQuery('#tblSeatDetail_cxc tbody tr:last input[data-campo="mon_can"]').focus().select();
                }
                // RECALCULAMOS LA SUMA TOTAL Y SINCRONIZAMOS DESPUÉS DEL FOCO
                calcularTotalMontoCancelar_cxc();
            }, 150); // Subimos ligeramente el tiempo para garantizar que el DOM esté listo            
        });
        //Eliminar un registro del Datatable de Detalle de CXP
        jQuery(document).on('click', '.btn-borrar_cxc', function() {
            var filaHTML = $(this).closest('tr');
            // Removemos de la API y redibujamos la tabla de inmediato
            table_cxc.row(filaHTML).remove().draw(false);
            calcularTotalMontoCancelar_cxc();
        })

        function calcularTotalMontoCancelar_cxc() {
            var var_table = jQuery('#tblSeatDetail_cxc').DataTable();
            var inputs = var_table.$('input[data-campo="mon_can"]');
            var mon_ret = var_table.$('input[data-campo="mon_ret"]');
            // 1. Cálculo de Montos a Cancelar
            var totalCancelar = 0;
            inputs.each(function() {
                let inputVal = jQuery(this).val();
                if (inputVal) {
                    let valorStr = formatoMoneda(inputVal) || 0;
                    let valorNum = parseFloat(valorStr);
                    if (!isNaN(valorNum)) {
                        totalCancelar += valorNum;
                    }
                }
            });
            // Pintamos el total en el footer de CXC
            jQuery('#total_monto_cancelar').text(format_number_with_dec_new(totalCancelar, 2));
            // 2. Cálculo de Retenciones
            var totalRetenido = 0;
            mon_ret.each(function() {
                let inputVal = jQuery(this).val();
                if (inputVal) {
                    let valorStr = formatoMoneda(inputVal) || 0;
                    let valorNum = parseFloat(valorStr);
                    if (!isNaN(valorNum)) {
                        totalRetenido += valorNum;
                    }
                }
            });
            // Pintamos el total en el footer de CXP
            jQuery('#total_monto_retenido').text(format_number_with_dec_new(totalRetenido, 2));
            // Sincronizamos automáticamente con el detalle del movimiento bancario (tercera pestaña)
            sincronizarDetalleMovimientoBanco_cxc();
        }
        // Validación en tiempo real mientras el usuario escribe el monto a cancelar
        jQuery(document).on('keyup change', '#tblSeatDetail_cxc tbody input[data-campo="mon_can"]', function() {
            let inputActual = jQuery(this);
            let tr = inputActual.closest('tr');

            if (tr.hasClass('child')) {
                tr = tr.prev('.parent');
            }

            let var_table = jQuery('#tblSeatDetail_cxc').DataTable();
            let rowData = var_table.row(tr).data();
            if (!rowData) return;

            let id_moneda_actual = $("#id_moneda").val();
            let saldoBase = 0;
            let montoAnterior = 0;

            if (id_moneda_actual == rowData.id_moneda_cia) {
                saldoBase = parseFloat(rowData.sal_doc_dom) || 0;
                montoAnterior = parseFloat(rowData.mon_can_dom) || 0;
            } else {
                saldoBase = parseFloat(rowData.sal_doc_for) || 0;
                montoAnterior = parseFloat(rowData.mon_can_for) || 0;
            }

            // El tope máximo permitido es el saldo actual + lo que ya estaba asignado en este movimiento
            let saldoMaximo = saldoBase + montoAnterior;

            let textoIngresado = inputActual.val().trim();
            let valorIngresadoNum = parseFloat(textoIngresado) || 0;

            // Validamos si supera el saldo máximo permitido
            if (valorIngresadoNum > saldoMaximo) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Monto excedido',
                        text: `El monto a cancelar no puede ser mayor al saldo disponible (${format_number_with_dec_new(saldoMaximo, 2)}).`,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    alert(`El monto a cancelar no puede ser mayor al saldo disponible (${format_number_with_dec_new(saldoMaximo, 2)}).`);
                }

                // Lo devolvemos al saldo máximo permitido para proteger la regla de negocio
                inputActual.val(format_number_with_dec_new(saldoMaximo, 2));
            }

            // Recalculamos el total general y sincronizamos los detalles con la tercera pestaña
            calcularTotalMontoCancelar_cxc();
        });
        // Validación en tiempo real mientras el usuario escribe el monto a retencion de IVA
        jQuery(document).on('keyup change', '#tblSeatDetail_cxc tbody input[data-campo="mon_ret"]', function() {
            let inputActual = jQuery(this);
            let tr = inputActual.closest('tr');

            if (tr.hasClass('child')) {
                tr = tr.prev('.parent');
            }

            let var_table = jQuery('#tblSeatDetail_cxc').DataTable();
            let rowData = var_table.row(tr).data();
            if (!rowData) return;

            let id_moneda_actual = $("#id_moneda").val();
            let saldoBase = 0;
            let montoAnterior = 0;

            if (id_moneda_actual == rowData.id_moneda_cia) {
                saldoBase = parseFloat(rowData.sal_doc_dom) || 0;
                montoAnterior = parseFloat(rowData.mon_can_dom) || 0;
            } else {
                saldoBase = parseFloat(rowData.sal_doc_for) || 0;
                montoAnterior = parseFloat(rowData.mon_can_for) || 0;
            }

            // El tope máximo permitido es el saldo actual + lo que ya estaba asignado en este movimiento
            let saldoMaximo = saldoBase + montoAnterior;

            let textoIngresado = inputActual.val().trim();
            let valorIngresadoNum = parseFloat(textoIngresado) || 0;

            // Validamos si supera el saldo máximo permitido
            if (valorIngresadoNum > saldoMaximo) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Monto excedido',
                        text: `El monto a cancelar no puede ser mayor al saldo disponible (${format_number_with_dec_new(saldoMaximo, 2)}).`,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    alert(`El monto a cancelar no puede ser mayor al saldo disponible (${format_number_with_dec_new(saldoMaximo, 2)}).`);
                }

                // Lo devolvemos al saldo máximo permitido para proteger la regla de negocio
                inputActual.val(format_number_with_dec_new(saldoMaximo, 2));
            }

            // Recalculamos el total general y sincronizamos los detalles con la tercera pestaña
            calcularTotalMontoCancelar_cxc();
        });
        // Validación en tiempo real para el número de comprobante de retención de IVA
        jQuery(document).on('keyup change', '#tblSeatDetail_cxc tbody input[data-campo="num_ret"]', function() {
            let inputComprobante = jQuery(this);
            let tr = inputComprobante.closest('tr');
            if (tr.hasClass('child')) {
                tr = tr.prev('.parent');
            }

            let var_table = jQuery('#tblSeatDetail_cxc').DataTable();
            let row = var_table.row(tr);
            let rowData = row.data();
            if (!rowData) return;

            // Obtenemos el valor de la retención de IVA correspondiente a esta fila
            let inputRet = tr.find('input[data-campo="num_ret"]');
            let montoRetVal = parseFloat(inputRet.val().replace(/\./g, '').replace(',', '.')) || 0;

            let numComprobante = inputComprobante.val().trim();

            // Si hay un monto de retención mayor a cero, el comprobante es obligatorio y debe tener 20 dígitos numéricos
            if (montoRetVal > 0) {
                // Expresión regular para validar exactamente 20 números
                let esSoloDigitos20 = /^\d{20}$/.test(numComprobante);

                if (!esSoloDigitos20) {
                    inputComprobante.addClass('is-invalid'); // Marca el input en rojo si usas Bootstrap
                } else {
                    inputComprobante.removeClass('is-invalid').addClass('is-valid');
                }
            } else {
                // Si no hay retención, limpia las clases de validación
                inputComprobante.removeClass('is-invalid is-valid');
            }
        });
    });
</script>