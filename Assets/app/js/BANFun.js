/**Funciones de Bancos
 * Creado por José Vargas el 29-09-2026 a las 03:48 p.m.
 */
//Variables
Tmod = "B";
localStorage.setItem('Tmod', Tmod);
window.conceptoProveedorDefault = null; // Variable global para almacenar el concepto
$(document).ready(function () {
    // Tabla de Detalle de Movimientos
    table = $("#tblDetalleMovBan").DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: false,
        order: [],
        autoWidth: false,
        columns: [
            {
                data: null, title: "Item", className: 'text-right align-middle text-xs, dt-item', width: "3%", orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    return parseInt(meta.row, 10) + 1;;
                }
            },
            {
                data: null, title: "Concepto Bancario", width: "40%",
                render: function (data, type, row, meta) {
                    var item = parseInt(meta.row, 10) + 1;
                    var id = row.id_bancon || '';
                    var nom = row.nom_bancon || '';
                    return `
                        <input type="hidden" name="id_bancon[]" id="id_bancon${item}" class="text-xs id_bancon" value="${id}"><div class="input-group"><input type="text" class="form-control text-xs" id="nom_con${item}" name="nom_con[]" readonly value="${nom}"><div class="input-group-append"><span class="input-group-text nom_con"><a href="#" class="btn-buscar-bancon" data-item="${item}" data-id_bancon="${id}" data-toggle="modal" data-target="#modal-BanConceptos" title="Buscar y seleccionar Conceptos Bancarios"><i class="fas fa-search"></i></a></span></div></div>
                    `;
                }
            },
            {
                data: null, title: "Auxiliar", width: "40%",
                render: function (data, type, row, meta) {
                    var item = parseInt(meta.row, 10) + 1;
                    var id = row.id_aux || '';
                    var nom = row.nombre_aux || '';
                    return `
                        <input type="hidden" name="id_aux[]" id="id_aux${item}" class="text-xs" value="${id}"><div class="input-group"><input type="text" class="form-control text-xs id_aux" id="nom_aux${item}" name="nom_aux[]" readonly value="${nom}"><div class="input-group-append" id="div_aux${item}"><span class="input-group-text nom_aux"><a href="#" class="btn_buscar-auxiliar" data-item="${item}" data_id_aux="${id}" data-toggle="modal" data-target="#modal-AuxiliaresCtb" title="Buscar y seleccionar Auxiliares Contables"><i class="fas fa-search"></i></a></span></div></div>
                    `;
                }
            },
            {
                data: null, title: "Monto", className: "text-right text-xs", width: "12%",
                render: function (data, type, row, meta) {
                    var item = parseInt(meta.row, 10) + 1;
                    var val = row.monto_nac || '0.00';
                    return `
                        <input type="text" name="monto[]" id="monto${item}" class="form-control text-right text-xs monto camponumero tot_movim" value="${format_number_with_dec_new(val, 2)}">
                    `;
                }
            },
            {
                data: null, title: "Acciones", className: "text-center align-middle text-xs", width: "5%",
                render: function (data, type, row, meta) {
                    var item = parseInt(meta.row, 10) + 1;
                    return `
                        <div class="btn-group"><button type="button" class="btn btn-danger btn-xs borrar text-xs" title="Eliminar item" ><i class="far fa-trash-alt text-xs"></i></div>
                    `;
                }
            }
        ],
        language: {
            url: `${base_url}/Assets/json/es-ES.json`,
        },
        // ---- AQUÍ CREAMOS EL FOOTER AUTOMÁTICAMENTE Y CALCULAMOS LA SUMA ----
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            var total = 0;

            // Recorremos los montos para calcular la suma
            $(".tot_movim").each(function () {
                let valorStr = $(this).val() || "0";
                total += parseFloat(valorStr.replace(/\./g, "").replace(",", ".")) || 0;
            });

            // Inyectamos el resultado formateado dentro del input del footer
            $("#footer_total_input").val(format_number_with_dec_new(total, 2));
        },
        // Asignamos el atributo data-item al <tr> de forma automática al crearse
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-item', dataIndex + 1);
        }
    });
    $(document).on("input", ".tot_movim", function () {
        actualizarTotalFooter();
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
            $(nodoTR).find('.id_bancon').attr('id', 'id_bancon' + nuevoItem);
            $(nodoTR).find('input[name="nom_prod[]"]').attr('id', 'nom_prod' + nuevoItem);
            $(nodoTR).find('.id_aux').attr('id', 'id_aux' + nuevoItem);
            $(nodoTR).find('input[name="nom_bancon[]"]').attr('id', 'nom_bancon' + nuevoItem);
            $(nodoTR).find('input[name="monto_nac[]"]').attr('id', 'monto_nac' + nuevoItem);
        });

        // Disparador de cálculos globales si existe
        if (typeof calcularTotalesGenerales === 'function') {
            calcularTotalesGenerales();
        }
    });
    /**Agregar nuevo registro al Movimiento Bancarioa */
    $('#btnAgregarFila').on('click', function () {
        // Estructura limpia del objeto vacío
        var nuevoRegistro = {
            id_bancon: '',
            nom_bancon: '',
            id_aux: 0,
            nombre_aux: 0,
            monto_nac: '0,00',
        };
        // Agregamos a la API y redibujamos la tabla
        table.row.add(nuevoRegistro).draw(false);
    });
    // Al hacer clic en la lupa para buscar concepto en la tabla principal
    $(document).on("click", ".btn-buscar-bancon", function () {
        var item = $(this).data("item"); // Captura el número de fila (1, 2, 3...)
        $("#modal-BanConceptos").attr("data-current-item", item); // Lo guardamos temporalmente en el modal
    });
    //funcion para elimnar una fila de detalle de movimientos
    $(document).on("click", ".borrar", function (e) {
        e.preventDefault();
        var filaHTML = $(this).closest('tr');
        // Removemos de la API y redibujamos la tabla de inmediato
        table.row(filaHTML).remove().draw(false);
        actualizarTotalFooter();
    });
});

//Index Movimientos
function initBanMovim() {
    const title = "Bancos - Movimientos Bancarios";
    const origen = 'BanMovim';
    const id_menu = 159;
    get_permiso(id_menu);

    IndexDataTable(origen, tblIndexMain, title, [
        {
            data: null, title: 'Acciones', className: 'text-center', orderable: false, width: "6%",
            render: function (data, tytpe, row, meta) {
                var t_menu = "";
                if (permisos_cre == 1 || permisos_rea == 1) {
                    t_menu += `<a type="button" data-toggle="tooltip" data-placement="top" title="Consultar registro" class="btn btn-warning btn-xs all" href="${base_url}/${origen}/gestion/${row.token_edit}"><i class="fa fa-edit"></i></a>     `;
                }                
                if (permisos_del == 1 && row.borrable === 1) {                    
                    t_menu += `<button id="Data" data-toggle="tooltip" data-placement="top" title= "Eliminar registro" data-id="${row.id_banmov}" data-number = "${row.num_banmov}" data-name="${row.nom_bantmo}" type="button" class="btn btn-danger btn-xs btn-delete"><i class="fa fa-trash"></i></button>     `;
                }
                t_menu += `<button id="Data" data-toggle="tooltip" data-placement="top" data-id="${row.id_banmov}" data-name="${row.nom_bantmo}" data-code = "${row.num_banmov}" type="button" class="btn btn-primary btn-xs btn_printer_doc" title="Imprimir"><i class="fa-solid fa-print"></i></button>`;
                return t_menu;
            }
        },
        { data: "id_banmov", title: "Id", className: "text-right", visible: false },
        { data: "nombre_emp", title: "Empresa" },
        { data: "nom_bantmo", title: "Tipo Mov." },
        { data: "bancue", title: "Cuenta" },
        { data: "num_banmov", title: "Número", className: "text-right" },
        { data: "fecha_comp", title: "Fecha", className: "text-center", render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN) },
        { data: "id_moneda", title: "Moneda", className: "text-center" },
        { data: "tasa_cambio", title: "Tasa", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
        { data: "create_for", title: "Creado por" },
        { data: "update_for", title: "Modificado por" },
        {
            data: null, title: "Status", className: "text-center",
            render: function (data, type, row, meta) {
                if (row.status == 1) {
                    return '<span class="badge badge-success">Activo</span>';
                } else {
                    return '<span class="badge badge-danger">Inactivo</span>';
                }
            }
        }
    ], "", "", 1, "desc")
}
/**Actualizar Total de Detalle de Movimiento Bancario */
function actualizarTotalFooter() {
    var total = 0;
    $(".tot_movim").each(function () {
        let valorStr = $(this).val() || "0";
        total += formatoMoneda(valorStr) || 0;
    });
    $("#footer_total_input").val(format_number_with_dec_new(total, 2));
}