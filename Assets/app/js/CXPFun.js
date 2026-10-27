/**Funciones de Cuentas por Pagar
 * 
 * Creado por José Vargas el 09-10-2026 a las 11:08 a.m.
 */
//Variables
Tmod = "P";
localStorage.setItem('Tmod', Tmod);
$(document).ready(function () {
	//Detalle de Movimientos
	table = $("#tblDetalleMovCxp").DataTable({
		paging: false, // Usualmente los detalles no se paginan para no perder los inputs de vista
		info: false,
		searching: false,
		ordering: false, // <-- Desactiva el ordenamiento en TODA la tabla y quita todas las flechas
		order: [],
		columns: [
			// 1. Item
			{
				data: null, title: "item", className: "text-right text-xs dt-item", orderable: false, searchable: false, width: "5%",
				render: function (data, type, row, meta) {
					return meta.row + 1;
				}
			},
			// 2. ID Doc (Oculto)
			{ data: null, title: "ID Doc.", visible: false },
			// 3. Tipo
			{ data: "tipo_codigo", title: "Tipo", className: "text-center", width: "6%" },
			// 4. Descripción (Columna más ancha para el nombre del documento/concepto)
			{ data: "nom_tdoc", title: "Descripción", width: "20%" },
			// 5. Número
			{ data: "num_tdo", title: "Número", className: "text-right", width: "8%" },
			// 6. Fec. Emisión
			{ data: "fecha_emi", title: "Fec. Emisión", className: "text-center", width: "7%" },
			// 7. Fec. Vencim.
			{ data: "fecha_ven", title: "Fec. Vencim.", className: "text-center", width: "7%" },
			// 8. Moneda
			{ data: "id_moneda_doc", title: "Moneda", className: "text-center", width: "6%" },
			// 9. Tasa
			{ data: "tasa_cambio", title: "Tasa", className: "text-right", width: "8%" },
			// 10. Monto
			{ data: "monto_doc", title: "Monto", className: "text-right", width: "9%" },
			// 11. Saldo
			{ data: "sal_doc", title: "Saldo", className: "text-right", width: "9%" },
			// 12. Cancelar
			{
				data: null, title: "Cancelar", className: "text-right", width: "9%",
				render: function (data, type, row, meta) {
					var item_doc = meta.row + 1; // Definimos el índice correcto de la fila
					var mon_can = row.mon_can || 0;
					return `
						<input type="text" id="mon_can${item_doc}" name="mon_can[]" class="form-control form-control-sm text-right text-xs fila-input camponumero" value="${mon_can}" style="height: 28px; padding: 2px 5px;"/>
					`;
				}
			},
			// 13. Acción
			{
				data: null, title: "Acción", className: "text-center", width: "10%",
				render: function (data, type, row, meta) {
					return `						
						&nbsp;&nbsp; <button type="button" class="btn btn-danger btn-xs btn-borrar text-center" title="Eliminar item"><i class="far fa-trash-alt"></i></button>
					`;
				}
			},
		],
		language: {
			url: `${base_url}/Assets/json/es-ES.json`,
		},
		// Opcional recomendado para que respete los anchos de columna fijos
		autoWidth: false,
		// --- ESTO ES LO NUEVO PARA ACTUALIZAR LOS ÍTEMS AUTOMÁTICAMENTE AL DIBUJAR/ELIMINAR ---
		drawCallback: function (settings) {
			var api = this.api();
			api.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
				var rowNode = this.node();
				var nuevoItem = rowLoop + 1;

				// Actualiza visualmente la celda del item (columna 0)
				$(rowNode).find('td.dt-item').html(nuevoItem);

				// Actualiza los IDs y atributos de los inputs internos de la fila para mantener el orden correcto
				$(rowNode).find('input[name="mon_can[]"]').attr('id', 'mon_can' + nuevoItem);

				var checkbox = $(rowNode).find('.check-row-cxc');
				if (checkbox.length) {
					checkbox.attr('data-input-id', 'mon_can' + nuevoItem);
					checkbox.attr('data-input-ret', 'mon_ret' + nuevoItem);
					checkbox.attr('data-input-num', 'num_ret' + nuevoItem);
				}
			});
			// --- ESTO ES LO QUE FALTABA PARA QUE RECALCULE AL CARGAR Y AL REDIBUJAR ---
			calcularTotalCancelar();
		}
	});
	//funcion para elimnar una fila de detalle de documentos
	$('#tblDetalleMovCxp tbody').on('click', ".btn-borrar", function (e) {
		e.preventDefault();
		var filaHTML = $(this).closest('tr');
		// Removemos de la API y redibujamos la tabla de inmediato
		table.row(filaHTML).remove().draw(false);
		setTimeout(function () {
			calcularTotalCancelar();
		}, 100);
	});
	// Validar en tiempo real que el monto a cancelar no sea cero y no supere el saldo
	$(document).on('input keyup change', 'input[name="mon_can[]"]', function () {
		var $inputCancelar = $(this);
		var valorCancelarStr = $inputCancelar.val().trim();

		if (valorCancelarStr === '') {
			calcularTotalCancelar();
			return;
		}

		// 1. Obtener los datos de la fila del DataTable
		var tr = $inputCancelar.closest('tr');
		var rowData = table.row(tr).data();

		var saldoStr = rowData ? rowData.sal_doc : tr.find('td:nth-child(11)').text().trim();

		// 2. Normalizar valores a números flotantes válidos
		var cancelarNum = parseFloat(valorCancelarStr.replace(/\./g, '').replace(',', '.'));
		var saldoNum = parseFloat(String(saldoStr).replace(/\./g, '').replace(',', '.'));

		if (!isNaN(cancelarNum)) {

			// A) VALIDACIÓN: El monto no puede ser cero
			if (cancelarNum === 0) {
				Swal.fire({
					icon: 'warning',
					title: '¡Atención!',
					text: 'El monto a cancelar no puede ser igual a cero.',
					confirmButtonText: 'Entendido'
				});
				$inputCancelar.val(saldoStr); // Limpiamos el campo
				calcularTotalCancelar();
				return;
			}

			// B) VALIDACIÓN: El monto no puede ser mayor al saldo (manejando correctamente positivos y negativos)
			// Nota: Si el saldo es negativo (ej: -100), un monto válido sería -50 (donde -50 > -100 matemáticamente). 
			// Si manejas montos absolutos o validación estricta de límites, ajustamos según tu lógica:
			if (!isNaN(saldoNum)) {
				// Validamos superación de límite respetando la dirección del saldo
				var superaLimite = false;
				if (saldoNum >= 0) {
					if (cancelarNum > saldoNum || cancelarNum < 0) { // Si el saldo es positivo, no puede ser mayor al saldo ni negativo (si aplica)
						// Si permites negativos menores al saldo, ajusta esta condición
					}
				}

				// Validación estándar de tope máximo comparado con el saldo:
				if (cancelarNum >( saldoNum + cancelarNum)) {
					Swal.fire({
						icon: 'warning',
						title: '¡Atención!',
						text: 'El monto a cancelar no puede ser mayor al saldo del documento (' + saldoStr + ').',
						confirmButtonText: 'Entendido'
					});
					$inputCancelar.val(saldoStr); // Auto-ajustar al saldo máximo
				}
			}
		}

		// Recalcular el total del footer
		calcularTotalCancelar();
	});
	$("#modal_doc_pen_cxp").on("hidden.bs.modal", function () {
		// Destruye la DataTable con ID 'miDataTable'
		$("#tblModalDocPend_cxp").DataTable().destroy();
		// O si usas la sintaxis antigua:
		// $('#miDataTable').dataTable().fnDestroy();
	});
	//Al seleccionar un registro de un documento a cancelar, tanto desde banco Movimitnos, como desde Cuentas por Cobrar Movimientos. José Vargas 28-08-2025 a las 120:19:00
	$("body").on("click", "#tblModalDocPend_cxp tr", function () {
		try {
			item++;
			var row_select = $(this).closest("tr");
			var adicional = "";
			var datosFila = row_select.find("td").map(function () {
				return $(this).text();
			}).get();
			var newRow = {
				id_cot: datosFila[0],
				tipo_codigo: datosFila[1],
				nom_tdoc: datosFila[2],
				num_tdo: datosFila[3],
				fecha_emi: datosFila[5],
				fecha_ven: datosFila[6],
				id_moneda_doc: datosFila[7],
				tasa_cambio: datosFila[8],
				monto_doc: datosFila[9],
				sal_doc: datosFila[10],
				mon_can: datosFila[10],
			}
			// Agregamos a la API y redibujamos la tabla
			table.row.add(newRow).draw(false);
			$("#item").val(item);
			$("#modal_doc_pen_cxp").modal("hide");
		} catch (error) {
			console.log("Ha ocurrido el siguiente error: " + error);
		}
	});

});
//Cuentas por Pagar - Tipos de Documentos
function initTipoDocCXP() {
	const title = "Cuentas por Pagar - Tipos de Documentos ";
	const origen = "TipoDocCXP";
	const id_menu = 104;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_tdoc}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_tdoc}" data-name="${row.nom_tdoc}" data-code = "${row.tipo_codigo}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id_tdoc", title: "Id", className: "text-right" },
		{ data: "nombre_emp", title: "Empresa" },
		{ data: "tipo_codigo", title: "Código", className: "text-center" },
		{ data: "nom_tdoc", title: "Nombre" },
		{ data: "tipo_tdoc", title: "Tipo Doc." },
		{
			data: null,
			title: "Usa Consec.",
			className: "text-center",
			render: function (data, type, row) {
				if (row.con_tdoc == 1) {
					return `<input type="checkbox" checked disabled>`;
				} else {
					return `<input type="checkbox" unchecked disabled>`;
				}
			},
		},
		{
			data: null,
			title: "Req. Aprob.",
			className: "text-center",
			render: function (data, type, row) {
				if (row.sol_aprob == 1) {
					return `<input type="checkbox" checked disabled>`;
				} else {
					return `<input type="checkbox" unchecked disabled>`;
				}
			},
		},
		{ data: "num_tdoc", title: "Próximo", className: "text-right" },
		{ data: "cod_cta", title: "Cuenta Contable" },
		{ data: "cod_aux", title: "Auxiliar Contable" },
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
	]);
}
//Cuentas por Pagar - Tipos de Movimientos
function initTipoMovCXP() {
	const title = "Cuentas por Pagar - Tipos de Movimientos ";
	const origen = "TipoMovCXP";
	const id_menu = 105;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_tmocxc}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_tmocxc}" data-name="${row.des_tmocxc}" data-code = "${row.cod_tmocxc}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id_tmocxc", title: "Id", className: "text-right" },
		{ data: "nombre_emp", title: "Empresa" },
		{ data: "cod_tmocxc", title: "Código" },
		{ data: "des_tmocxc", title: "Descripción" },
		{ data: "acc_tmocxc", title: "Acción" },
		{ data: "rec_tmocxc", title: "Rel. Caja" },
		{
			data: null,
			title: "Usa Conse.",
			className: "text-center",
			render: function (data, type, row) {
				if (row.con_tmocxc == "S") {
					return "<input type='checkbox' checked disabled>";
				} else {
					return "<input type='checkbox' unchecked disabled>";
				}
			},
		},
		{ data: "next_tmocxc", title: "Próximo", className: "text-right" },
		{ data: "nombre_cta", title: "Cuenta Contable" },
		{ data: "nombre_aux", title: "Auxiliar Contable" },
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
	]);
}
//Cuentas por Pagar - Conceptos
function initConcepCXP() {
	const title = "Cuentas por Pagar - Conceptos ";
	const origen = "ConcepCXP";
	const id_menu = 107;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id}" data-name="${row.nombre_con}" data-code = "${row.codigo_con}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id", title: "Id", className: "text-right" },
		{ data: "codigo_con", title: "Código" },
		{ data: "nombre_con", title: "Nombre" },
		{ data: "agrupa_con", title: "Agrupador", className: "text-center" },
		{ data: "descrip", title: "Retención" },
		{ data: "por_reten", title: "porcentaje", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
		{ data: "id_ctb", title: "Cuenta Contable" },
		{ data: "id_aux", title: "Auxiliar Contable" },
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
	]);
}
//Cuentas por Pagar - Proveedores
function initProveedores() {
	const title = "Cuentas por Pagar - Proveedores ";
	const origen = "Proveedores";
	const id_menu = 108;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_ent}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_ent}" data-name="${row.nom_ent}" data-code = "${row.rif_ent}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id_ent", title: "Id" },
		{ data: "rif_ent", title: "RIF" },
		{ data: "nom_ent", title: "Nombre" },
		{ data: "nombre_pais", title: "Pais" },
		{ data: "nombre_edo", title: "Estado" },
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
	]);
}
//Cuentas por Pagar - Documentos
function initCXPDocument() {
	const title = "Cuentas por Pagar - Documentos ";
	const origen = "CXPDocument";
	const id_menu = 109;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_cot}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_cot}" data-name="${row.nom_tdoc} del Proveedor ${row.nom_ent}" data-code = "${row.tipo_codigo} ${row.num_tdo}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>     `;
				}
				if (row.ret_iva != 0) {
					t_menu += `<a type="button" class="btn btn-primary btn-xs" data-code="${row.num_tdo}" href="${base_url}/CXPDocument/print_RetIva/${row.id_cot}" target="_blank"><i class="fa-solid fa-print" title="Imprimir Retención de IVA"></i></a>     `;
				}
				if (row.ret_islr != 0) {
					t_menu += `<a type="button" class="btn btn-info btn-xs" data-code="${row.num_tdo}" href="${base_url}/CXPDocument/print_RetISLR/${row.id_cot}" target="_blank"><i class="fa-solid fa-print" title="Imprimir Retención de ISLR"></i></a>     `;
				}
				t_menu += `<a type="button" class="btn btn-success btn-xs" data-code="${row.num_tdo}" href="${base_url}/CXPDocument/print_CXPDocument/${row.id_cot}" target="_blank"><i class="fa-solid fa-print" title="Imprimir Documento"></i></a>`;
				return t_menu;
			},
		},
		{ data: "id_cot", title: "Id", className: "text-right" },
		{ data: "nombre_emp", title: "Empresa" },
		{ data: "tipo_codigo", title: "Código", className: "text-center" },
		{ data: "nom_tdoc", title: "Descripción" },
		{ data: "num_tdo", title: "Número", className: "text-right" },
		{ data: "fecha_comp", title: "Fecha", render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN) },
		{ data: "nom_ent", title: "Proveedor" },
		{ data: "codigo_moneda", title: "Moneda", className: "text-center" },
		{ data: "tasa_cambio", title: "Tasa", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
		{ data: "mon_doc", title: "Monto", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
		{ data: "sal_doc", title: "Saldo", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
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
	]);
}
//Cuentas por Pagar - Movimientos
function initCXPMovement() {
	const title = "Cuentas por Pagar Movimientos";
	const origen = "CXPMovement";
	const id_menu = 110;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 || permisos_upd == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_movement}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1 && row.movem_origen == "CXP" && row.fecha_comp > row.fec_cxp) {
					t_menu += `<button id="Data" data-id="${row.id_movement}" data-name="${row.des_tmocxc}" data-code="${row.cod_tmocxc}" data-number="${row.movem_number}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>     `;
				}
				if (permisos_cre == 1) {
					t_menu += `<button id="Data" data-id="${row.id_movement}" data-name="${row.cod_tmocxc} - ${row.des_tmocxc}  - ${row.movem_number}" type="button" class="btn btn-primary btn-xs" onclick="print_mov(this)" title="Imprimir"><i class="fa-solid fa-print"></i></button>`;
				}
				return t_menu;
			},
		},
		{
			data: "id_movement",
			title: "Id",
			className: "text-right",
			visible: false,
		},
		{ data: "nombre_emp", title: "Empresa" },
		{ data: "cod_tmocxc", title: "Código", className: "text-center" },
		{ data: "des_tmocxc", title: "Descripción" },
		{ data: "movem_number", title: "Número", className: "text-right" },
		{
			data: "fecha_comp",
			title: "Fecha",
			render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN),
		},
		{ data: "nom_ent", title: "Proveedor" },
		{ data: "codigo_moneda", title: "Moneda", className: "text-center" },
		{
			data: "tasa_cambio",
			title: "Tasa",
			className: "text-right",
			render: $.fn.dataTable.render.number(".", ",", 8),
		},
		{
			data: "movem_amount",
			title: "Monto",
			className: "text-right",
			render: $.fn.dataTable.render.number(".", ",", 2),
		},
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
	]);
}
function calcularTotalCancelar() {
	var totalGral = 0;

	// Recorremos cada input de la columna cancelar
	$('input[name="mon_can[]"]').each(function () {
		var valorStr = $(this).val().trim();

		if (valorStr !== '') {
			valorStr = formatoMoneda(valorStr);
			var valorNum = parseFloat(valorStr);

			if (!isNaN(valorNum)) {
				totalGral += valorNum;
			}
		}
	});

	// Formateamos el total
	var totalFormateado = totalGral.toLocaleString('es-VE', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 4
	});

	// Lo inyectamos directamente en el footer de la tabla
	$('#footer_total_cancelar').text(totalFormateado);
}