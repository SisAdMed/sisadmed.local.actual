let nom_menu = "Compras - " + localStorage.getItem("page_name");
$(document).ready(function () {
    table = $(`#tblDetalle`).DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: false,
        order: [],
        columns: [
            {
                data: null, title: 'Item', className: 'text-right dt-item', orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            {
                data: null, title: 'Descripción', width: '25%',
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
            {
                data: null, title: 'Cantidad', width: '8%',
                render: function (data, type, row, meta) {
                    var itm = meta.row + 1;
                    var val = row.cant || 1;
                    return `<input type="number" name="cant[]" id="cant${item}" value="${val}" class="form-control text-right text-xs reCalcular cantidad" data-campo="cantidad" min="1" style="width:100%">`;
                }
            },
            {
                data: null,
                title: "Lote",
                width: "14%",
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = (row.lote !== null && row.lote !== undefined) ? String(row.lote).trim() : '';

                    // Si el objeto tiene un producto asignado y usa lote (o ya tiene texto en lote)
                    var usaLote = (parseInt(row.usa_lote, 10) === 1) || (val !== '');

                    var readonlyAttr = usaLote ? '' : 'readonly';
                    var requiredAttr = usaLote ? 'required' : '';

                    return `<input type="text" name="lote_${item}" id="lote${item}" value="${val}" class="form-control text-xs text-uppercase txt-lote" ${readonlyAttr} ${requiredAttr}>`;
                }
            },
            {
                data: null,
                title: "Fecha Venc.",
                width: "12%",
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var rawVal = row.fec_venc;
                    var val = (rawVal && rawVal !== '0000-00-00') ? String(rawVal).trim() : '';
                    var loteVal = (row.lote !== null && row.lote !== undefined) ? String(row.lote).trim() : '';

                    var usaLote = (parseInt(row.usa_lote, 10) === 1) || (val !== '') || (loteVal !== '');

                    var readonlyAttr = usaLote ? '' : 'readonly';
                    var requiredAttr = usaLote ? 'required' : '';

                    return `<input type="date" name="fec_venc_${item}" id="fec_venc${item}" value="${val}" class="form-control text-xs txt-fec-venc" ${readonlyAttr} ${requiredAttr}>`;
                }
            },
            {
                data: null,
                title: "Uni.Com.",
                width: '7%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.uni_com_prod || '';
                    return `<input type="text" name="uni_com_prod[]" id="uni_com_prod${item}" value="${val}" readonly class="form-control text-right text-xs reCalcular" data-campo="uni_vta" style="width:100%">`;
                }
            },
            {
                data: null,
                title: "PVP Unit.",
                width: '8%',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.costo_prod || '0.00';
                    return `<input type="text" name="costo_prod[]" id="costo_prod${item}" value="${format_number_with_dec_new(val, 4)}" readonly class="form-control text-right text-xs pre_uni" style="width:100%">`;
                }
            },
            {
                data: null,
                title: "Precio Compra.",
                width: '8%',
                className: 'text-right text-xs',
                render: function (data, type, row, meta) {
                    var item = meta.row + 1;
                    var val = row.costo_prod1 || '0.00';
                    return `<input type="text" name="costo_prod1[]" id="costo_prod1${item}" value="${format_number_with_dec_new(val, 4)}" class="form-control text-right text-xs camponumero reCalcular" data-campo="pre_vta" style="width:100%">`;
                }
            },
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
            url: `${base_url}/Assets/json/es-Es.json`,
        },
    });
    listar_si_no("", `iva_prod${item}`);
    // Delegación de eventos para capturar el clic en el botón de eliminar
    $('#tblDetalle tbody').on('click', '.btn-borrar', function () {
        var filaHTML = $(this).closest('tr');
        // Removemos de la API y redibujamos la tabla de inmediato
        table.row(filaHTML).remove().draw(false);
        recorreTable_com(xtasa);
    });
    $('#btnAgregarFila').on('click', function () {
        // Estructura limpia del objeto vacío
        var nuevoRegistro = {
            id_prod: '',
            nom_prod: '',
            cant: 0,
            stock: 0,
            uni_com_prod: '',
            costo_prod: '0,00',
            costo_prod1: '0,00',
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
        recorreTable_com(tasa_cambio);
        // Actualizar la fila en DataTables sin reiniciar la paginación        
    });
})
function initPurInv() {
    const title = nom_menu;
    const origen = "PurInv";
    const id_menu = 122;
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
                    t_menu += `<button id="Data" data-toggle="tooltip" data-placement="top" title= "Eliminar registro" data-id="${row.id}" data-code = "${row.num_tdo}" data-name="${row.nom_tdoc}" type="button" class="btn btn-danger btn-xs btn-delete"><i class="fa fa-trash"></i></button>     `;
                }                         
                if (row.ret_iva === 1) {                   
                    t_menu += `<button id="Data" data-toggle="tooltip" data-placement="top" title= "Imp. Retención de IVA" data-id="${row.id_cot}" data-code = "${row.num_tdo}" data-name="${row.nom_tdoc}" type="button" class="btn btn-primary btn-xs btn-printer-retiva"><i class="fa-solid fa-print"></i></button>     `;
                }
                return t_menu;
            },
        },
        { data: 'id_cot', title: "Id", className: 'text-right', visible: false, orderable: false },
        { data: "nombre_emp", title: "Empresa" },
        { data: "tipo_codigo", title: "Código" },
        { data: "nom_tdoc", title: "Descripción" },
        { data: "num_tdo", title: "Número", className: 'text-right' },
        { data: "fecha_comp", title: "Fecha", render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN) },
        { data: "nom_ent", title: "Proveedor" },
        { data: "codigo_moneda", title: "Moneda", className: 'text-center' },
        { data: "tasa_cambio", title: "Tasa", className: "text-right", render: DataTable.render.number(".", ",", 4) },
        { data: "origen", title: "Origen", className: 'text-center' },
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
    ], '', '', 1, "desc");
}