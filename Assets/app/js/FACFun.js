//Facturación
let table;
tabla = "tblDetalle"
xori_tipo_doc = '';
//Facturas
$(document).ready(function () {
    table = $(`#tblDetalle`).DataTable({
        paging: false, // Usualmente los detalles no se paginan para no perder los inputs de vista
        info: false,
        searching: false,
        ordering: false, // <-- Desactiva el ordenamiento en TODA la tabla y quita todas las flechas
        order: [],
        columns: [
            // 1. Ítem correlativo
            {
                data: null,
                title: "item",
                className: 'text-right text-xs dt-item',
                orderable: false, // <-- Súper importante para evitar que las flechas alteren el orden
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            // 2. Producto (Hidden + Input + Lupa Modal)
            {
                data: null,
                title: "Descripción",
                width: '30%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var id = row.id_prod || '';
                    var nom = row.nom_prod || '';
                    return `
                    <input type="hidden" name="id_prod[]" id="id_prod${item}" value="${id}" class="text-xs photo id_prod">
                    <div class="input-group">
                        <input type="text" class="form-control text-xs" id="nom_prod${item}" name="nom_prod[]" value="${nom}" title="${nom}" readonly>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <a href="#" class="btn-buscar-producto" data-item="${item}" data-id_prod = "${id}" data-toggle="modal" data-target="#modal-productos" title="Buscar y seleccionar productos"><i class="fas fa-search"></i></a>
                            </span>
                        </div>
                    </div>`;
                }
            },
            // 3. Cantidad
            {
                data: null,
                title: "Cantidad",
                width: '8%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.cantidad || 1;
                    return `<input type="number" name="cant[]" id="cant${item}" value="${val}" class="form-control text-right text-xs reCalcular cantidad" data-campo="cantidad" min="1" style="width:100%">`;
                }
            },
            // 4. Stock (Disabled)
            {
                data: null,
                title: "Stock",
                width: '8%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.stock || 0;
                    return `<input type="number" name="stock[]" id="stock${item}" value="${val}" class="form-control text-right text-xs stock" style="width:100%" disabled>`;
                }
            },
            // 5. Unidad de Venta (Readonly)
            {
                data: null,
                title: "Uni.Vta.",
                width: '7%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.uni_ven_prod || '';
                    return `<input type="text" name="uni_ven_prod[]" id="uni_ven_prod${item}" value="${val}" readonly class="form-control text-right text-xs reCalcular" data-campo="uni_vta" style="width:100%">`;
                }
            },
            // 6. Ventas Prod (Readonly)
            {
                data: null,
                title: "PVP Unit.",
                width: '8%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.ventas_prod || '0.00';
                    return `<input type="text" name="ventas_prod[]" id="ventas_prod${item}" value="${format_number_with_dec_new(val, 4)}" readonly class="form-control text-right text-xs pre_uni" style="width:100%">`;
                }
            },
            // 7. Ventas Prod 1 (Precio Modificable)
            {
                data: null,
                title: "Precio Vta.",
                width: '8%',
                className: 'text-right text-xs',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.ventas_prod1 || '0.00';
                    return `<input type="text" name="ventas_prod1[]" id="ventas_prod1${item}" value="${format_number_with_dec_new(val, 4)}" class="form-control text-right text-xs camponumero reCalcular" data-campo="pre_vta" style="width:100%">`;
                }
            },
            // 8. Descuento (Hidden + Input + Lupa Modal)
            {
                data: null,
                title: "Dcto.",
                width: '10%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var idDes = row.id_des_item || '';
                    var nomDes = row.nom_des || '';
                    return `
                    <input type="hidden" name="id_des_item[]" id="id_des_item${item}" value="${idDes}" class="text-xs">
                    <div class="input-group">
                        <input type="text" class="form-control text-xs text-right" id="nom_des${item}" name="nom_des[]" value="${nomDes}" readonly>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <a href="#" class="btn-buscar-descuento" data-toggle="modal" data-target="#modal-descuentos" title="Buscar y seleccionar descuento"><i class="fas fa-search"></i></a>
                            </span>
                        </div>
                    </div>`;
                }
            },
            // 9. IVA (Select)
            {
                data: "iva_prod",
                title: "IVA",
                width: '10%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    // Determinamos si ya viene un valor guardado (por si la fila tenía datos)
                    var seleccionN = (data === 'N') ? 'selected' : '';
                    var seleccionS = (data === 'S') ? 'selected' : '';
                    // Nota: El select se renderiza vacío; abajo te muestro cómo poblarlo con tus tasas (Exento, 16%, etc.)
                    return `
					<select name="iva_prod[]" id="iva_prod${item}" class="form-control text-xs input-iva reCalcular" data-item="${item}" data_campo="iva">
							<option value="">Seleccione...</option>
							<option value="N" ${seleccionN}>NO</option>
							<option value="S" ${seleccionS}>SI</option>
					</select>
					`;
                }
            },
            // 10. Total Fila (Readonly)
            {
                data: null,
                title: "Sub-Total",
                width: '10%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.total || '0.00';
                    return `<input type="text" name="total[]" id="total${item}" value="${format_number_with_dec_new(val, 4)}" class="form-control text-right text-xs input-fila sub_tot" readonly>`;
                }
            },
            // 11. Acciones (Botones Eliminar y Ver Foto)
            {
                data: null,
                title: "Acciones",
                className: "text-center",
                orderable: false,
                render: function (data, type, row, meta) {
                    return `
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-xs btn-borrar text-xs" title="Eliminar item"><i class="far fa-trash-alt"></i></button>
                        &nbsp;&nbsp;
                        <button type="button" class="btn btn-warning btn-xs show-picture text-xs" data-toggle="modal" data-target="#modal-showpicture" title="Ver fotos"><i class="fa fa-eye"></i></button>
                    </div>`;
                }
            }
        ],
        language: {
            url: `${base_url}/Assets/json/es-ES.json`,
        },
        // Asignamos el atributo data-item al <tr> de forma automática al crearse
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-item', dataIndex + 1);
            if (parseFloat(data.stock) <= 0 || data.nom_pro == '') {
                $(row).addClass('table-danger'); // Pinta la fila en rojo tenue
            }
        }
    });
    listar_si_no("", `iva_prod${item}`);
    // Delegación de eventos para capturar el clic en el botón de eliminar
    $('#tblDetalle tbody').on('click', '.btn-borrar', function () {
        var filaHTML = $(this).closest('tr');
        // Removemos de la API y redibujamos la tabla de inmediato
        table.row(filaHTML).remove().draw(false);
        recorreTable_fac(1, xtasa, '', tabla);
    });
    // ==========================================
    // 3. RE-INDEXADO DE CORRELATIVOS E IDS (EVENTO DRAW CORREGIDO)
    // ==========================================
    table.on('draw.dt', function () {
        // Buscamos y recorremos las filas basándonos estrictamente en el orden visual aplicado en pantalla
        table.rows({ order: 'applied', search: 'applied' }).every(function (rowIdx) {

            // El 'rowIdx' interno de DataTables ya no nos sirve para el correlativo visual,
            // así que calculamos la posición real de la fila en el DOM actual:
            var nodoTR = this.node(); // El elemento <tr>
            var nuevoItem = $(nodoTR).index() + 1; // Su posición física en el tbody (1, 2, 3...)			
            $("#item").val(nuevoItem);
            // 1. Re-indexamos el atributo del TR y el texto visible de la primera columna (item)
            $(nodoTR).attr('data-item', nuevoItem);
            $('td', nodoTR).eq(0).html(nuevoItem);

            // 2. Re-indexamos dinámicamente cada uno de los IDs de los inputs internos
            $(nodoTR).find('.id_prod').attr('id', 'id_prod' + nuevoItem);
            $(nodoTR).find('input[name="nom_prod[]"]').attr('id', 'nom_prod' + nuevoItem);
            $(nodoTR).find('.reCalcular').filter('input[type="number"]').attr('id', 'cant' + nuevoItem);
            $(nodoTR).find('.stock').attr('id', 'stock' + nuevoItem);
            $(nodoTR).find('input[name="uni_ven_prod[]"]').attr('id', 'uni_ven_prod' + nuevoItem);
            $(nodoTR).find('input[name="ventas_prod[]"]').attr('id', 'ventas_prod' + nuevoItem);
            $(nodoTR).find('.camponumero').attr('id', 'ventas_prod1' + nuevoItem);
            $(nodoTR).find('input[name="id_des_item[]"]').attr('id', 'id_des_item' + nuevoItem);
            $(nodoTR).find('input[name="nom_des[]"]').attr('id', 'nom_des' + nuevoItem);
            $(nodoTR).find('.input-iva').attr('id', 'iva_prod' + nuevoItem);
            $(nodoTR).find('.sub-total').attr('id', 'total' + nuevoItem);
        });

        // Disparador de cálculos globales si existe
        if (typeof calcularTotalesGenerales === 'function') {
            calcularTotalesGenerales();
        }
    });
    $('#btnAgregarFila').on('click', function () {
        // Estructura limpia del objeto vacío
        var nuevoRegistro = {
            id_prod: '',
            nom_prod: '',
            cantidad: 0,
            stock: 0,
            uni_ven_prod: '',
            ventas_prod: '0,00',
            ventas_prod1: '0,00',
            id_des_item: '',
            nom_des: '',
            iva_prod: '',
            total: '0,00'
        };
        // Agregamos a la API y redibujamos la tabla
        table.row.add(nuevoRegistro).draw(false);

        // Opcional: Si necesitas poblar el select de IVA de la última fila creada
        var totalFilas = table.rows().count();
        item = totalFilas;
        if (typeof cargarOpcionesIva === 'function') {
            cargarOpcionesIva(totalFilas);
        }
    });
    // Cuando el usuario hace clic en la lupa de CUALQUIER fila
    $('#tblDetalle tbody').on('click', '.btn-buscar-producto', function (e) {
        e.preventDefault(); // Evita que la página salte al hacer clic en el '#'
        // 1. Capturamos el número exacto de ítem asignado a esa lupa
        var itemSeleccionadoTarget = $(this).attr('data-item') || $(this).closest('tr').attr('data-item');
        // 2. Se lo inyectamos directamente al contenedor del modal
        $('#modal-productos').data('item-target', itemSeleccionadoTarget);
    });
    //Ocultar Almacén y Ubicación de Entrada en los consignados
    $("#hide-in").change(function (e) {
        e.preventDefault()
        if ($(this).is(':checked')) {
            $(".hide_entrada").hide();
            listar_almacenes(id_emp, 0, 0, "id_alm_ent");
            listar_ubicaciones("id_ubi_ent", 0, "N", id_emp);
        } else {
            listar_almacenes(id_emp, id_alm_cli, 0, "id_alm_ent");
            listar_ubicaciones("id_ubi_ent", id_ubi_cli, "N", id_emp);
            $(".hide_entrada").show();
        }
    });
    //Recalcular en caso de cambiar el IVA a si o No
    $('#tblDetalle tbody').on('change input', '.reCalcular', function () {
        // 1. Identificar la fila actual
        var $row = $(this).closest('tr');
        // Obtener el objeto de datos de la fila        
        // 2. Extraer todos los valores de esa fila específica
        var cantidad = formatoMoneda($row.find('.reCalcular[data-campo="cantidad"]').val()) || 0;
        var uni_vta = formatoMoneda($row.find('.reCalcular[data-campo="uni_vta"]').val()) || 0;
        var pre_vta = formatoMoneda($row.find('.reCalcular[data-campo="pre_vta"]').val()) || 0;
        // 3. Calcular los nuevos valores para las dos columnas     
        var new_pre_uni = pre_vta / uni_vta;
        var new_total = pre_vta * cantidad;
        // 4. Actualizar las DOS columnas en la fila        
        $row.find('.pre_uni').val(format_number_with_dec_new(new_pre_uni, 2));
        $row.find('.sub_tot').val(format_number_with_dec_new(new_total, 2));
        //Mostrar total en formulario
        tasa_cambio = formatoMoneda($("#tasa_cambio").val());
        recorreTable_fac(0, tasa_cambio, tipo_fac, "tblDetalle");
        // Actualizar la fila en DataTables sin reiniciar la paginación        
    });
})
function initFacturacion() {
    const title = "Facturación - Factura";
    const origen = "Facturacion";
    const id_menu = 72;
    get_permiso(id_menu);
    IndexDataTable(origen, tblIndexMain, title, [
        {
            data: null,
            title: "Acciones",
            className: "text-center",
            render: function (data, type, row) {
                var t_menu = "";
                if (permisos_cre == 1 || permisos_rea == 1) {
                    t_menu += `<a type="button" data-toggle="tooltip" data-placement="top" title="Consultar registro" class="btn btn-warning btn-xs all" href="${base_url}/${origen}/gestion/${row.token_edit}"><i class="fa fa-edit"></i></a>     `;
                }
                if (permisos_del == 1) {
                    t_menu += `<button id="Data" data-toggle="tooltip" data-placement="top" title= "Eliminar registro" data-id="${row.id_cot}" data-code = "${row.num_tdo}" data-name="${row.nom_tdoc}" type="button" class="btn btn-danger btn-xs btn-delete"><i class="fa fa-trash"></i></button>     `;
                }
                t_menu += `<button id="Data" data-id="${row.id_cot}" data-name="${row.nom_tdoc}" data-code = "${row.num_tdo}" data-tasa="${row.tasa_cambio}" data-moneda="${row.id_moneda}" data-print="${row.print_special}" type="button" class="btn btn-primary btn-xs btn_printer_doc" title="Imprimir"><i class="fa-solid fa-print"></i></button>`;
                return t_menu;
            },
        },
        { data: "id_cot", tittle: "Id", visible: false },
        { data: "nombre_emp", tittle: "Empresa" },
        { data: "nom_tdoc", title: "Tipo" },
        { data: "num_tdo", title: "Número", className: "text-right" },
        { data: "nro_control", title: "Control", className: "text-right" },
        { data: "nom_ent", title: "Cliente" },
        { data: "fecha_comp", title: "Fecha", render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN) },
        { data: "codigo_moneda", title: "Moneda", className: "text-center" },
        { data: "tasa_cambio", title: "Tasa", className: "text-right", render: DataTable.render.number(".", ",", 2) },
        { data: "nom_vend", title: "Vendedor" },
        { data: "fuente", title: "Origen" },
        {
            data: null,
            title: "Status",
            className: "text-center",
            render: function (data, type, row) {
                if (row.status == 1) {
                    return '<span class="badge badge-success">Activo</span>';
                } else if (row.status == 0) {
                    return '<span class="badge badge-danger">Inactivo</span>';
                } else if (row.status == 9) {
                    return '<span class="badge badge-warning">Por aprobar</span>';
                }
            },
        },
    ], '', '', 1, "desc")
}
//Buscar Cotizaciones pendientes
async function listar_cotizacones(emp, id = 0, tag, id_cli = 0) {
    const url = `${base_url}/Facturacion/listar_cotizacones`;
    let $idOrigen = $(`#${tag}`);
    $idOrigen.empty();
    $.ajax({
        url: url,
        type: 'POST',
        data: { id_emp: id_emp, id_cli: id_cli },
        dataType: 'json',
        success: function (response) {
            // 3. Poblar las nuevas opciones
            $.each(response, function (index, item) {
                $idOrigen.append(new Option(item.cliente, item.id_cot, false, false));
            });

            // 4. Reinicializar o actualizar Select2 y FORZAR selección nula
            $idOrigen.select2({
                placeholder: "Seleccione un registro",
                allowClear: true,
                width: '100%'
            }).val(null).trigger('change'); // <--- AQUÍ está la clave para limpiar la selección activa
        }
    });
}
//Buscar Notas de entregas No Fiscales pendientes
function listar_notas(emp, id = 0, tag, id_cli, fuente = 0) {
    let $idOrigen = $(`#${tag}`);
    $idOrigen.empty();
    const url = `${base_url}/Facturacion/listar_notas`;
    $.ajax({
        url: url,
        type: 'POST',
        data: { id_emp: id_emp, id_cli: id_cli, fuente: fuente },
        dataType: 'json',
        success: function (response) {
            // 3. Poblar las nuevas opciones
            $.each(response, function (index, item) {
                $idOrigen.append(new Option(item.cliente, item.id_cot, false, false));
            });

            // 4. Reinicializar o actualizar Select2 y FORZAR selección nula
            $idOrigen.select2({
                placeholder: "Seleccione un registro",
                allowClear: true,
                width: '100%'
            }).val(null).trigger('change'); // <--- AQUÍ está la clave para limpiar la selección activa
        }
    });
}
function CalculateTotalFac() {
    let xcantidad;
    let xprecio_venta;
    let xunidad_venta;
    let iva = 1;
    precio_unit = formatoMoneda($(`#ventas_prod${item}`).val());
    xcantidad = parseFloat($(`#cant${item}`).val());
    xprecio_venta = $(`#ventas_prod1${item}`).val()
    xprecio_venta = parseFloat(formatoMoneda($(`#ventas_prod1${item}`).val()));
    xunidad_venta = parseFloat($(`#uni_ven_prod${item}`).val());
    $(`#ventas_prod${item}`).val(
        format_number_with_dec_new(xprecio_venta / xunidad_venta, 2)
    );
    $(`#total${item}`).val(
        format_number_with_dec_new(xcantidad * xprecio_venta, 2)
    );
    monto = xcantidad * xprecio_venta;
    let modo = "N";
    id_cot = $("#id").val();
    if (id_cot) {
        modo = "M";
    }


    val_col = 0;
    if (tipo_fac == "C") {
        val_vol = -1;
    } else if (tipo_fac == "N") {
        val_vol = 1;
    }
    tasa_cambio = formatoMoneda($("#tasa_cambio").val());

    recorreTable_fac(val_col, tasa_cambio, tipo_fac, tabla);
}
// Mostar Datos cuando se selecciona una cotizacion
function show_data_origen(id_cot, origen) {    
    if (!id_cot) {
        console.warn("No se proporcionó un ID de cotización válido.");
        return;
    }
    var url = "";
    var doc_orig;
    if (origen == 'P') {
        url = `${base_url}/Cotizaciones/consultar_cotizacion`;
        doc_orig = "Cotización";
    } else if (origen == 'F') {
        url = `${base_url}/Facturacion/consultar_factura_nc`;
        doc_orig = "Factura";
    } else {
        url = `${base_url}/Facturacion/consultar_nota`;
        doc_orig = "Nota de Entrega No Fiscal";
    }

    $.ajax({
        url: url,
        method: 'POST',
        data: { id_cot: id_cot, tipo: '' },
        dataSrc: '',
        dataType: 'json',
        beforeSend: function () {
            loader.show();
        },
        complete: function () {
            loader.hide();
        },
        error: function (error) {
            console.error("Error al recibir datos del servidor:", error);
            loader.hide();
        },
        success: function (data) {            
            if (data) {                
                id_moneda_ori = data[0]['id_moneda'];
                tasa_cambio_ori = data[0]['tasa_cambio'];
                num_cotiza = data[0]['num_tdo'];
                id_moneda = $("#id_moneda").val();
                tasa_cambio = formatoMoneda($("#tasa_cambio").val());
                if (id_moneda_ori == id_moneda && tasa_cambio_ori != tasa_cambio) {
                    title = `¿Desea utilizar la Tasa de Cambio del Documento ${num_cotiza} con el valor de ${format_number_with_dec_new(tasa_cambio_ori, 4)}.?`
                    Swal.fire({
                        title: title,
                        icon: 'question',
                        showDenyButton: true,
                        confirmButtonText: "Si",
                        denyButtonText: `No`,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            tasa_cambio_ori = format_number_with_dec_new(tasa_cambio_ori, 4)
                            tasa_cambio = tasa_cambio_ori
                            $("#tasa_cambio").val(tasa_cambio_ori);
                        }
                    })
                }
                //Cargar detalles
                tasa_cambio = $("#tasa_cambio").val();
                valor = 1;
                if (tipo_fac == "C") {
                    valor = -1;
                }                
                var detalleLimpio = data.map(function (item, index) {
                    var $row = $('#tblDetalle').DataTable().row(index).node();
                    // Capturamos el valor dinámico actual del input de cantidad
                    var cantidadReal = $($row).find("input[id^='cant']").val() || item.cant;
                    return {
                        id_prod: item.id_prod,
                        nom_prod: item.nom_prod,
                        cantidad: cantidadReal,
                        stock: item.stock,
                        uni_ven_prod: item.uni_vta * valor,
                        ventas_prod: item.pre_unit * valor,
                        ventas_prod1: item.pre_vta * valor,
                        id_des_item: '',
                        nom_des: '',
                        iva_prod: item.iva_prod,
                        total: item.sub_total * valor
                    };
                });
                table.clear();
                table.rows.add(detalleLimpio);
                table.draw();
                tasa_cambio = formatoMoneda($("#tasa_cambio").val());
                recorreTable_fac('', tasa_cambio, tipo_fac, tabla);
            }
        }
    })
}