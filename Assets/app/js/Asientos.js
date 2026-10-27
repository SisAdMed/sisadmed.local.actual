/*
 * Funciones Asientos
 * Copyright 2025-2025
 * 02-12-2025 Creación de Archivo José Vargas 09:31:00
 */
let item = 0;
let xTotDebe = 0;
let xTotHaber = 0;
//Al iniciar la aplicacción
$().ready(function () {
	//Validaciones
	$("form#my_form").validate({
		ignore: null,
		rules: {
			id_emp: "required",
			id_tipcom: "required",
			num_comp: {
				required: function () {
					var isReadonly = $("#num_comp").prop("readonly");
					return !isReadonly;
				},
			},
			fecha_comp: "required",
			id_moneda: "required",
			tasa_cambio: "required",
			desc_comp: "required",
			status: "required",
			item: {
				required: true,
				min: 1,
			},
			mondebe: {
				required: true,
				minlength: 4,
			},
			monhabe: {
				required: true,
				minlength: 4,
				equalTo: "#mondebe",
			},
		},
		messages: {
			id_emp: "Debe especificar una Empresa",
			id_tipcom: "Debe especificar un Tipo",
			num_comp: "Debe especificar un Número",
			fecha_comp: "Debe especificar una Fecha",
			id_moneda: "Debe especificar una Moneda",
			tasa_cambio: "Debe especificar la Tasa de Cambio",
			desc_comp: "Debe especificar una Descripción",
			status: "Debe especificar un Status",
			item: {
				required: "Debe especificar al menos un Detalle",
				min: "Debe especificar al menos {0} un Detalle",
			},
			mondebe: {
				required: "El Monto del Debe no puede estar vacío o en cero (0,00)",
			},
			monhabe: {
				required: "El Monto del Haber no puede estar vacío o en cero (0,00)",
			}
		},
	});
	//Cargar el index
	form = $("form").attr("id");
	if (form === undefined) {
		initAsientos();
	} else {
		id = $("#id").val();
		if (id) {
			show_row(id);
		} else {
			dat_form_new();
		}
	}
	//Al seleccionar una empresa
	$("#id_emp").on("change", async function () {
		id_emp = $("#id_emp").val();
		$("#id_tipcom").empty();
		$("#id_tipcom").attr("readonly", false);
		$("#num_comp").attr("readonly", false);
		if (id_emp) {
			//Configuración Móudlo
			const xttipo_cbte = await ttipo_cbte(id_emp, "O", "id_tipcom");

			listar_tipos_comprobantes(xttipo_cbte["id_tipcom"], "id_tipcom", true);
			$("#num_comp").attr("readonly", true);
			if (
				xttipo_cbte["consecu_config"] == "N" &&
				xttipo_cbte["numdia_config"] == "N"
			) {
				$("#num_comp").attr("readonly", false);
			}
			//Configuración Empresa
			id_tdo_cfg = await tip_doc_com(id_emp);
			id_moneda = id_tdo_cfg["id_moneda"];
			listar_monedas(id_moneda);
			$("#id_moneda").trigger("change");
		}
	});
	//Al seleccioanr y/o cambiar Moneda
	$("#id_moneda").on("change", async function () {
		id_moneda = $("#id_moneda").val();
		fecha_comp = $("#fecha_comp").val();
		xTasaCambio = await getExchangerate(fecha_comp, id_moneda);
		$("#tasa_cambio").val(xTasaCambio);
		$("#tasa_cambio").attr("readonly", true);
	});
	//Al cambiar fecha del Asiento
	$("#fecha_comp").on("change", async function () {
		$("#id_moneda").trigger("change");
		//Validar Fechas Abiertas
		id_emp_config = await get_empresa_config(id_emp);
		gfec_ctb = id_emp_config["fec_ctb"];
		gfec_cierre = id_emp_config["fec_ini_fis"];
		fecha_comp = $("#fecha_comp").val();
		if (fecha_comp <= gfec_ctb) {
			$("#fecha_comp").addClass("is-invalid");
			$("#fecha_comp").attr("title", "La fecha del movimiento no puede ser menor a la fecha de contabilidad de la empresa");
			$("#btnok").prop("disabled", true);
		} else {
			$("#fecha_comp").removeClass("is-invalid");
			id_moneda = $("#id_moneda").val();
			$("#tasa_cambio").val("");
			if (id_moneda && fecha_comp) {
				obtener_cambio(fecha_comp, id_moneda);
			}
			$("#btnok").prop("disabled", false);
		}
	});
	//Eliminar un registro
	$("#tblIndexMain").on("click", ".btn-delete-index", function () {
		var recordId = $(this).data("id"); // Obtiene el ID del registro
		var recordCode = $(this).data("code"); // Obtine el Tipo Doc
		var recordName = $(this).data("name"); // Obtine el nombre
		var descrip = `¿Está seguro de eliminar el ${recordName} Número ${recordCode} ?.`;
		Swal.fire({
			title: descrip,
			text: "¡No podrá revertir esta eliminación!",
			icon: "question",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Si, borrar este registro!",
			cancelButtonText: "Cancelar",
		}).then((result) => {
			if (result.isConfirmed) {
				const url = `${base_url}/Asientos/destroy`;
				$.ajax({
					url: url, // URL de tu script de eliminación en el servidor
					type: "POST",
					data: { id: recordId, recordCode: recordCode, recordName: recordName },
					dataType: "json",
					beforeSend: function () {
						loader.show();
					},
					complete: function () {
						loader.hide();
					},
					success: function (resulta) {
						// La respuesta del servidor debe indicar si fue exitoso
						Swal.fire({
							icon: `${resulta.icon}`,
							title: `${resulta.title}`,
							text: `${resulta.msg}`,
						}).then((result) => {
							if (result.isConfirmed) {
								// Recarga el DataTable
								if ($.fn.DataTable.isDataTable('#tblIndexMain')) {
									$('#tblIndexMain').DataTable().ajax.reload(null, false);
								}
							}
						});
					},
					error: function (xhr, status, error) {
						loader.hide();
						alert("Hubo un error en la solicitud.");
						console.error(xhr.responseText);
					},
				});
			}
		});
	});
	//Guardar y/o Actualizarregistro
	$("#my_form").on("submit", function (e) {
		e.preventDefault();
		if ($(this).valid()) {
			var formData = $(this).serialize();
			const url = `${base_url}/Asientos/store`;
			//Ajax para Guardar y/o Actualizar
			$.ajax({
				url: url,
				method: "POST",
				dataSrc: "",
				data: formData,
				dataType: "json",
				beforeSend: function () {
					loader.show();
				},
				complete: function () {
					loader.hide();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					loader.hide();
					console.error("Error en la solicitud AJAX:");
					console.error("Estado de texto:", textStatus); // Por ejemplo: "timeout", "error", "Not Found", "Internal Server Error"
					console.error("Error lanzado:", errorThrown); // Mensaje de error detallado del servidor
					console.error("Código de estado HTTP:", jqXHR.status); // Código numérico (ej: 404, 500)
					console.error("Respuesta del servidor:", jqXHR.responseText); // Contenido de la respuesta del servidor
				},
				success: function (data) {
					console.log(data);
					Swal.fire({
						title: data.title,
						text: data.msg,
						icon: data.icon,
					}).then((result) => {
						if (data.icon != "error") {
							window.location.href = `${base_url}/Asientos`;
						}
					});
				},
			});
		} else {
			return false;
		}
	});
	//funcion para elimnar una fila de detalle de Comprobante
	$(document).on("click", ".borrar", function (event) {
		event.preventDefault();
		$(this).closest("tr").remove();
		total_asiento();
	});
	//Al seleccionar el tipo de Asiento Debe/haber
	$(document).on("change", ".tipo", function (e) {
		e.preventDefault();
		let tipo_dh = $("#tipo" + item).val();
		if (tipo_dh == "D") {
			$("#mon_habe" + item).prop("readonly", true);
			$("#mon_debe" + item).prop("readonly", false);
			$("#mon_habe" + item).val("");
			$("#mon_debe" + item).val((xTotHaber - xTotDebe).toFixed(2));
			$("#mon_debe" + item).focus();
			$("#mon_debe" + item).select();
		} else if (tipo_dh == "H") {
			$("#mon_debe" + item).prop("readonly", true);
			$("#mon_habe" + item).prop("readonly", false);
			$("#mon_debe" + item).val("");
			$("#mon_habe" + item).val((xTotDebe - xTotHaber).toFixed(2));
			$("#mon_habe" + item).focus();
			$("#mon_habe" + item).select();
		}
	});
	//Al cambior elmonto del Debe
	$(".xtotal_debe").on("blur", function () {
		total_asiento();
	});
	//Al cambior elmonto del Haber
	$(".xtotal_habe").on("blur", function () {
		total_asiento();
	});
});
//Nuevo registro
function dat_form_new() {
	listar_empresas();
	$("#fecha_comp").val(GetTodayDate(0));
	listar_status(1);
}
//Consultar registro
function show_row(id) {
	const url = `${base_url}/Asientos/show_row`;
	//Ajax para
	$.ajax({
		url: url,
		method: "POST",
		dataSrc: "",
		data: { id: id },
		dataType: "json",
		beforeSend: function () {
			loader.show();
		},
		complete: function () {
			loader.hide();
		},
		error: function (PDOException) {
			loader.hide();
			console.log("Ha ocurrido el siguiente error:", PDOException.responseText);
		},
		success: function (data) {
			id_emp = data[0]["id_emp"];
			//Validar Fechas Abiertas
			id_emp_config = get_empresa_config(id_emp);
			gfec_ctb = id_emp_config["fec_ctb"];
			gfec_cierre = id_emp_config["fec_ini_fis"];
			listar_empresas(id_emp, true);
			id_tipcom = data[0]["id_tipcom"];
			listar_tipos_comprobantes(id_tipcom, "id_tipcom", true);
			$("#num_comp").val(data[0]["num_comp"]);
			$(`#num_comp`).css("pointer-events", "none");
			fecha_comp_ori = data[0]["fecha_comp"];
			$("#fecha_comp").val(data[0]["fecha_comp"]);
			$("#fecha_comp").trigger("change");
			id_moneda = data[0]["id_moneda"];
			listar_monedas(id_moneda, true);
			$("#tasa_cambio").val(format_number_with_dec_new(data[0]["tasa_cambio"], 2));
			$(`#tasa_cambio`).css("pointer-events", "none");
			$("#desc_comp").val(data[0]["desc_comp"]);
			estatus = data[0]["status"];
			listar_status(estatus, 'status');
			//Cargar detalles de Comprobantres
			item = 0;
			$.each(data, function (index, row) {
				item++;
				ocultar = (row.id_aux == 0) ? 'hidden' : '';
				var htmlTags = `
				<tr id="fila-${item}">
					<td class="text-right">${item}</td>
					<td><input type="hidden" name="id_ctb[]" id="id_ctb${item}" class="text-xs" value="${row.id_cue}"> <div class="input-group">
					<input type="text" class="form-control text-xs id_ctb c-idcta" id="nom_ctb${item}" name="nom_ctb" value="${row.nom_cue}" readonly required><div class="input-group-append"><span class="input-group-text nom_ctb"><a href="#" data-toggle="modal" data-target="#modal-CuentasCtb" title="Buscar y seleccionar Cuentas Contables"> <i class="fas fa-search text-xs"></i></a></span></div></div></td>
					<td><input type="hidden" name="id_aux[]" id="id_aux${item}" class="text-xs" value="${row.id_aux}"><div class="input-group"><input type="text" class="form-control text-xs id_aux c-idaux" id="nom_aux${item}" name="nom_aux" value="${row.nom_aux}" readonly><div class="input-group-append"><span id="div_aux${item}" class="input-group-text" ${ocultar} ><a href="#" data-toggle="modal" data-target="#modal-AuxiliaresCtb" title="Buscar y seleccionar Auxiliares Contables"><i class="fas fa-search text-xs"></i></a></span>
					</div></div></td>
					<td><input type="text" name="descrip_deta[]" id="descrip_deta${item}'" class="form-control text-xs" value="${row.det_observa}"></td>
					<td><select name="tipo[]" id="tipo${item}" class="form-control tipo text-xs" required></select></td>
					<td><input type="text" name="mon_debe[]" id="mon_debe${item}" class="form-control text-right xtotal_debe text-xs camponumero" onkeyup="total_asiento()" value="${format_number_with_dec_new(row.mon_debe, 2)}"></td>
					<td><input type="text" name="mon_habe[]" id="mon_habe${item}" class="form-control text-right xtotal_habe text-xs camponumero" onkeyup="total_asiento()"value="${format_number_with_dec_new(row.mon_habe, 2)}"></td>
					<td class="text-center"><div class="btn-group"><button type="button" class="btn btn-danger btn-xs borrar text-xs" title="Eliminar item" ><i class="far fa-trash-alt"></i></div></td>
				</tr>`;
				$("#tblAsientoDet").append(htmlTags);
				$("#item").val(item);
				listar_tipoDH(row.det_tipo, `tipo${item}`);
				$(`#mon_habe${item}`).prop("readonly", true);
				$(`#mon_debe${item}`).prop("readonly", true);
				total_asiento();
			});
		},
	});
}
//Total Asiento contable
function total_asiento() {
	xTotDebe = 0;
	xTotHaber = 0;
	// 1. Sumar todos los campos del Debe
	$('.xtotal_debe').each(function () {
		let valor = $(this).val().replace(/\./g, '').replace(',', '.'); // Limpiar formato de miles/decimales si aplica
		xTotDebe += parseFloat(valor) || 0;
	});

	// 2. Sumar todos los campos del Haber
	$('.xtotal_haber').each(function () {
		let valor = $(this).val().replace(/\./g, '').replace(',', '.');
		xTotHaber += parseFloat(valor) || 0;
	});

	// Redondear a 2 decimales para evitar problemas de precisión flotante
	xTotDebe = Math.round(xTotDebe * 100) / 100;
	xTotHaber = Math.round(xTotHaber * 100) / 100;

	let diferencia = Math.abs(xTotDebe - xTotHaber);
	diferencia = Math.round(diferencia * 100) / 100;

	// Identificar si existe una fila previa de diferencia (por ejemplo, asignándole un identificador o clase especial)
	let filaDiferenciaIndex = -1;
	table.rows().every(function (rowIdx, tableLoop, rowNode) {
		let data = this.data();
		// Si tienes una propiedad o bandera en el row que indique que es la fila de diferencia
		if (data.es_diferencia === true) {
			filaDiferenciaIndex = rowIdx;
		}
	});

	// Tolerancia mínima para considerar que están cuadrados (0.01)
	if (diferencia > 0.01) {
		// ¿Dónde colocamos la diferencia? 
		// Si el Debe es mayor, la diferencia va en el Haber para equilibrar (o viceversa según tu regla de negocio)
		let montoDebeDif = xTotDebe < xTotHaber ? diferencia : 0;
		let montoHaberDif = xTotHaber < xTotDebe ? diferencia : 0;

		let datosDiferencia = {
			id_cue: '',
			nombre_cta: 'DIFERENCIA / DESCUADRE',
			id_aux: 0,
			nombre_aux: '',
			det_observa: 'Ajuste automático por diferencia de asiento',
			mon_debe: montoDebeDif,
			mon_habe: montoHaberDif,
			es_diferencia: true // Bandera interna
		};

		if (filaDiferenciaIndex !== -1) {
			// Si ya existe la fila de diferencia, actualizamos sus datos y redibujamos
			table.row(filaDiferenciaIndex).data(datosDiferencia).draw(false);
		} else {
			// Si no existe, la creamos agregándola al DataTable
			table.row.add(datosDiferencia).draw(false);
		}
	} else {
		// Si ya están cuadrados y existía la fila de diferencia, la eliminamos
		if (filaDiferenciaIndex !== -1) {
			table.row(filaDiferenciaIndex).remove().draw(false);
		}
	}

	// Opcional: Si tienes elementos en tu HTML para mostrar los totales visuales abajo de la tabla
	$('#lblxTotDebe').text(format_number_with_dec_new(xTotDebe, 2));
	$('#lblxTotHaber').text(format_number_with_dec_new(xTotHaber, 2));
}