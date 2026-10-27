//Contabilidad
let table;
tabla = "tblAsiento"
$(document).ready(function(){
	table = $("#tblAsiento").DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: false,
        order: [],
        autoWidth: false, // ⚠️ Vital para que respete los anchos asignados abajo
        columns: [
            // 01.- Item correlativo (Ancho fijo pequeño)
            { data: null, title: "Item", className: 'text-center text-xs dt-item align-middle', width: "50px", orderable: false, searchable: false,
                render: function(data, type, row, meta){
                    return meta.row + 1;
                }
            },
            // 02.- Cuenta contable
            { data: null, title: "Cuenta Contable", width: "25%",
                render: function(data, type, row, meta){
                    var item = meta.row + 1;
                    var id = row.id_cue || '';
                    var nom = row.nombre_cta || '';
                    return `
                    <input type="hidden" name="id_cue[]" id="id_cue${item}" value="${id}">
                    <div class="input-group input-group-xs">
                        <input type="text" class="form-control text-xs" id="nom_ctb${item}" name="nom_ctb[]" value="${nom}" title="${nom}" readonly required>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <a href="#" class="btn-buscar-ctbcue" data-item="${item}" data-id_cue="${id}" data-toggle="modal" data-target="#modal-CuentasCtb" title="Buscar cuentas"><i class="fas fa-search text-xs"></i></a>
                            </span>
                        </div>
                    </div>`;
                }
            }, 
            // 03.- Auxiliar contable
            { data: null, title: "Auxiliar Contable", width: "22%",
                render: function( data, type, row, meta){
                    var item = meta.row + 1;
                    var id_aux = row.id_aux || '';
                    var nom = row.nombre_aux || '';
                    var ocultar = (row.id_aux == 0 || !row.id_aux) ? 'hidden' : '';
                    return `
                    <input type="hidden" name="id_aux[]" id="id_aux${item}" class="text-xs" value="${id_aux}">
                    <div class="input-group input-group-xs">
                        <input type="text" class="form-control text-xs" id="nom_aux${item}" name="nom_aux[]" value="${nom}" title="${nom}" readonly>
                        <div class="input-group-append">
                            <span id="div_aux${item}" class="input-group-text" ${ocultar}>
                                <a href="#" data-item="${item}" data-id_aux="${id_aux}" data-toggle="modal" data-target="#modal-AuxiliaresCtb" title="Buscar auxiliares"><i class="fas fa-search text-xs"></i></a>
                            </span>
                        </div>
                    </div>`;
                }
            },
            // 04.- Descripcion de detalle
            { data: null, title: 'Descripción', width: "23%",
                render: function(data, type, row, meta){
                    var item = meta.row + 1; // 💡 Declaramos item para evitar errores
                    var observacion = row.det_observa || '';
                    return `<input type="text" name="descrip_deta[]" id="descrip_deta${item}" class="form-control text-xs" value="${observacion}">`;
                }
            },
            // 05.- Tipo
            { data: null, title: "Tipo", width: "8%",
                render: function(data, type, row, meta){
                    var item = meta.row + 1;
					 // Determinamos si ya viene un valor guardado (por si la fila tenía datos)
                    var seleccionD = (data === 'D') ? 'selected' : '';
                    var seleccionH = (data === 'H') ? 'selected' : '';
					// Nota: El select se renderiza vacío; abajo te muestro cómo poblarlo
                    return `<select
					select name="tipo[]" id="tipo${item}" class="form-control tipo text-xs" data-item="${item}" required>
						<option value="">Seleccione...</option>
						<option value="D" ${seleccionD}>Debe</option>
						<option value="H" ${seleccionH}>Haber</option>
					</select>`;
                }
            },
            // 06.- Monto Debe
            { data: null, title: 'Debe', className: 'text-right', width: "10%",
                render: function(data, type, row, meta){
                    var item = meta.row + 1;
                    var debe = row.mon_debe || 0;
                    return `<input type="text" name="mon_debe[]" id="mon_debe${item}" class="form-control text-right xtotal_debe text-xs camponumero" onkeyup="total_asiento()" value="${format_number_with_dec_new(debe, 2)}">`;
                }
            },
            // 07.- Monto Haber (⚠️ Corregido el name a mon_habe y el id a mon_habe)
            { data: null, title: 'Haber', className: 'text-right', width: "10%",
                render: function(data, type, row, meta){
                    var item = meta.row + 1;
                    var haber = row.mon_habe || row.mon_debe || 0; // Ajusta según tu propiedad de haber
                    return `<input type="text" name="mon_habe[]" id="mon_habe${item}" class="form-control text-right xtotal_haber text-xs camponumero" onkeyup="total_asiento()" value="${format_number_with_dec_new(haber, 2)}">`;
                }
            },
            // 08. Acciones (Ancho fijo pequeño para los botones)
            { data: null, title: "Acciones", className: "text-center align-middle", width: "60px", orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    return `
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-xs btn-borrar text-xs" title="Eliminar item"><i class="far fa-trash-alt"></i></button>                        
                    </div>`;
                }
            }
        ],
        language: {
            url: `${base_url}/Assets/json/es-ES.json`,
        },
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-item', dataIndex + 1);            
        }
    });
	$("#item").val(item);	
	$("#btnAgregate").on('click', function(){
		//Estrutura limpia del objeto
		var nuevoRegistro = {
			id_cue: '',
			nom_ctb: '',
			id_aux: '',
			nombre_aux: '',
			descrip_deta: '',
			tipo: '',
			mon_debe: '0,00',
			mon_habe: '0,00'

		};
		// Agregamos a la API y redibujamos la tabla
		table.row.add(nuevoRegistro).draw(false);			
	});
	$("#tblAsiento tbody").on('click', '.btn-buscar-ctbcue', function(e) {
		e.preventDefault();
		var itemSeleccionadoTarget = $(this).attr('data-item') || $(this).closest('tr').attr('data-item');
		$('#modal-CuentasCtb').data('item-target', itemSeleccionadoTarget);
	})
});
//Auxiliares Contables
function initAuxilirCTB() {
	const title = "Contabilidad - Auxiliares Contables ";
	const origen = "AuxiliarCtb";
	const id_menu = 11;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{
			data: null,
			title: "Acciones",
			className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_cre == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_aux}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_aux}" data-name="${row.nombre_aux}" data-code = "${row.cod_aux}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id_aux", title: "Id", className: "text-right", visible: false },
		{ data: "cod_aux", title: "Código" },
		{ data: "nombre_aux", title: "Nombre" },
		{ data: "agrupa_aux", title: "Agrupa", className: "text-center" },
		{
			data: null,
			title: "Status",
			className: "text-center",
			render: function (data, type, row) {
				if (row.status_aux == 1) {
					return '<span class="badge badge-success">Activo</span>';
				} else if (row.status_aux == 0) {
					return '<span class="badge badge-danger">Inactivo</span>';
				} else if (row.status_aux == 9) {
					return '<span class="badge badge-warning">Por aprobar</span>';
				}
			},
		},
	], '', '', 1, 'asc');
}
//Cuentas Contables
function initCuentasCtb() {
	const title = "Contabilidad - Cuentas Contables ";
	const origen = "CuentasCtb";
	const id_menu = 12;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{ data: null, title: "Acciones", className: "text-center",
			render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_cre == 1) {
					t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_cta}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1) {
					t_menu += `<button id="Data" data-id="${row.id_cta}" data-name="${row.nombre_cta}" data-code = "${row.cod_cta}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
				}
				return t_menu;
			},
		},
		{ data: "id_cta", title: "Id", className: "text-right", visible: false },
		{ data: "cod_cta", title: "Código" },
		{ data: "nombre_cta", title: "Nombre" },
		{ data: "agrupa_cta", title: "Agrupador", className: "text-center" }, 
		{ data: "aux_cta", title: "Auxiliar", className: "text-center" },
		{ data: "tip_cta", title: "Tipo"},
		{ data: null, title: "Status", className: "text-center",
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
		{ data: "codigo", title: "Código", visible: false},
	], '', '', 7, 'asc');
}
//Asientos Contables
function initAsientos() {
	const title = "Contabilidad - Asientos Contables";
	const origen = "Asientos";
	const id_menu = 23;
	get_permiso(id_menu);
	IndexDataTable(origen, tblIndexMain, title, [
		{ data: null, title: "Acciones", className: "text-center",
				render: function (data, type, row) {
				var t_menu = "";
				if (permisos_cre == 1 && permisos_cre == 1) {
				t_menu += `<a type="button" class="btn btn-warning btn-xs" href="${base_url}/${origen}/edit/${row.id_comp}"><i class="fa fa-edit"></i></a>     `;
				}
				if (permisos_del == 1 && row.ori_comp == "CTB" && row.borrable == 0) {
				t_menu += `<button id="Data" data-id="${row.id_comp}" data-name="${row.nombre_tipcom}" data-code = "${row.num_comp}" type="button" class="btn btn-danger btn-xs btn-delete-index"><i class="fa fa-trash"></i></button>`;
					}
					t_menu += `     <a type="button" class="btn btn-primary btn-xs" href="${base_url}/Asientos/print/${row.id_comp}" target="_blank" title='Imprimir asiento'><i class="fa fa-print"></i> </a>`;
				return t_menu;
			}
		},
		{ data: "id_comp", title: "Id", className: "text-right", visible: false},
		{ data: "nombre_emp", title: "Empresa" },
		{ data: "nombre_tipcom", title: "Tipo Cbte." },
		{ data: "num_comp", title: "Nro. Cbte.", className: "text-right" },
		{ data: "fecha_comp", title: "Fecha", className: "text-center", render: $.fn.dataTable.render.moment(FROM_PATTERN, TO_PATTERN) },
		{ data: "codigo_moneda", title: "Moneda", className: "text-center" },
		{ data: "tasa_cambio", title: "Cambio", className: "text-right", render: $.fn.dataTable.render.number(".", ",", 2) },
		{ data: "ori_comp", title: "Origen"},
		{ data: null, title: "Status", className: "text-center",
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
//Listar Tipos de Cuentas
function listar_seltipoCuenta(id) {
	//Data
	var data = [
		{ id: "A", name: "Activo" },
		{ id: "P", name: "Pasivo" },
		{ id: "C", name: "Capital" },
		{ id: "I", name: "Ingreso" },
		{ id: "S", name: "Costo"},
		{ id: "E", name: "Egreso" },
		{ id: "T", name: "Contra" },
		{ id: "O", name: "Per Contra"},
	];
	//Seleccionar el dstino
	var $selectElement = $("#tip_cta");
	//Limpiar en caso de que este lleno
	$selectElement.empty();
	//Agrgar registro de Seleccione
	$selectElement.append($("<option>", { value: "", text: "Selecione..." }));
	//Cargar data
	$.each(data, function (index, item) {
		$selectElement.append($("<option>", {
			value: item.id,
			text: item.name,
		}));
	});
	if (id) {
		$(`#tip_cta option[value="${id}"]`).prop('selected', true);
	}
}