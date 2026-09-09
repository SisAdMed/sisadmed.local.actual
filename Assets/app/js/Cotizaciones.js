//Variables
tipo_fac = "P";
item = "";
itemSelected = "";
id_cot = "";
let fecha_comp = "";
$(document).ready(function () {
	//Validar datos del Formulario
	jQuery.validator.setDefaults({
		debug: false,
		success: "valid",
	});
	$("form#my_form").validate({
		rules: {
			id_emp: "required",
			id_tdo: "required",
			fecha_comp: "required",
			nom_cli: "required",
			id_moneda: "required",
			id_vend: "required",
		},
		messages: {
			id_emp: "Debe especificar una Empresa",
			id_tdo: "Debe especificar un Tipo de Documentos",
			fecha_comp: "Desbe especificar una Fecha válida",
			nom_cli: "Debe especificar un Cliente",
			id_moneda: "Debe especificar una Moneda",
			id_vend: "Debe especificar un Vendedor",
		},
	});
	//Cargar el index
	form = $("form").attr("id");
	if (form === undefined) {
		initCotizaciones();
	} else {
		id = $("#id").val();
		if (id) {
			dat_form(id);
		} else {
			dat_form_new();
		}
	}
	//Validar empresa
	$("#id_emp").on("change", async function (e) {
		e.preventDefault();
		onlyread = "";
		id_emp = $(this).val();
		$("#id_tdo").val("");
		id_tdo_cfg = "";
		$("#id_cli").val("");
		$("#nom_cli").val("");
		$("#id_moneda").empty();
		$("#id_vend").empty();
		$("#id_fab").empty();
		$("#tasa_cambio").val("");
		if (id_emp) {
			id_tdo_cfg = await tip_doc_fac(id_emp);
			loc_pri_cot = id_tdo_cfg["loc_pri_cot"];
			locked_invoice = id_tdo_cfg["locked_invoice"];
			stock = id_tdo_cfg["cot_stock"];
			listar_monedas();
			listar_vendedores();
			listar_marcas();
			if (loc_pri_cot == 1) {
				onlyread = " readonly ";
			}
		}
		if (!id) {
			if (id_tdo_cfg) {
				id_tdo_val = id_tdo_cfg["id_tdoc_pre"];
				listar_tipos_documentos(id_emp, tipo_fac, id_tdo_val);
				id_tdo = id_tdo_val;
				$("#id_tdo").trigger("change");
			}
		}
	});
	//Validar si el Tipo de Documento usa consecutivo o no para poder aisgnar el número del documento
	$(document).on("change", "#id_tdo", function (e) {
		const url = `${base_url}/CXCDocument/val_tdo`;
		if (!id_tdo) {
			id_tdo = $(this).val();
			return false;
		}
		try {
			$.ajax({
				url: url,
				method: "POST",
				data: { id: id_tdo },
				dataType: "json",
				dataSrc: "",
				beforeSend: function () {
					loader.show();
				},
				complete: function () {
					loader.hide();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					loader.hide();
					console.error(
						"Error en la solicitud AJAX:",
						textStatus,
						errorThrown
					);
				},
				success: function (data) {
					if (data.con_tdoc == 0) {
						$("#num_tdo").prop("readonly", false);
					} else {
						$("#num_tdo").prop("readonly", true);
					}
					if (data.sol_aprob == 1) {
						sol_aprob = true;
						$("#status").val(9);
						$("#status").css("pointer-events", "none");
					}
					$("#id_tdo").css("pointer-events", "none");
				},
			});
		} catch (error) {
			console.log("Ha ocurrido el siguiente error: " + error);
		}
	});
	//Al cambiar la fecha
	$(document).on("change", "#fecha_comp", function (event) {
		event.preventDefault();
		fecha_comp = $(this).val();
		update_tasa();
	});
	//Combo de monedas
	$(document).on("change", "#id_moneda", function (event) {
		event.preventDefault();
		id_moneda = $(this).val();
		update_tasa();
	});
	//Seleccioanr por defecto el vendedor al momento de escoger el cliente
	$(document).on("change", "#id_cli", function (e) {
		e.preventDefault();
		id_cli = $(this).val();
		fetcingData(id_cli);
	});
	//Guardar y/o Actualizar
	$("#my_form").on("submit", function (e) {
		e.preventDefault();
		// 2. Extraer los datos del detalle (recorriendo cada fila)
		var detalle = [];
		var table = $('#tblDetalle').DataTable();
		// Usamos table.$('tr') para incluir todas las filas (incluso con paginación)
		table.$('tbody tr').each(function () {
			var $row = $(this);
			var id_prod = $row.find("input[id^='id_prod']").val();
			var can_det = $row.find("input[id^='cant']").val();
			var uni_vta = $row.find("input[id^='uni_ven_prod']").val();
			var pre_unit = $row.find("input[id^='ventas_prod']").val();
			var pre_vta = $row.find("input[data-campo='pre_vta']").val();
			var iva_prod = $row.find("select[id^='iva_prod']").val();
			var sub_total = $row.find("input[id^='total']").val();
			detalle.push({
				id_prod: id_prod,
				can_det: can_det,
				uni_vta: uni_vta,
				pre_unit, pre_unit,
				pre_vta: pre_vta,
				iva_prod: iva_prod,
				sub_total: sub_total,
			})
		});
		// Validar que exista al menos un artículo en el detalle
		if (detalle.length === 0) {
			Swal.fire('Error', 'Debe agregar al menos un producto válido al detalle.', 'warning');
			return false;
		}
		var formData = new FormData(this);
		formData.append('detalle', JSON.stringify(detalle));
		//Ajax para Guardar y/o Actualizar        
		const $btnOk = $("#btnok");
		const url = `${base_url}/Cotizaciones/store`;
		$.ajax({
			url: url,
			method: "POST",
			data: formData,
			processData: false,       // Prevent jQuery from trying to convert the FormData to a string
			contentType: false,
			dataType: "json",
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
			},
			success: function (data) {
				Swal.fire({
					title: data.title,
					text: data.msg,
					icon: data.icon,
				}).then((result) => {
					if (data.icon != "error") {
						window.location.href = `${base_url}/Cotizaciones`;
					} else {
						// Solo se reactiva el botón si el backend devolvió un error lógico
						$btnOk.prop("disabled", false);
					}
				});
			},
		});
	});
	//funcion para elimnar una fila de detalle de cotización
	$(document).on("click", ".borrar", function (event) {
		event.preventDefault();
		$(this).closest("tr").remove();
		xtasa = formatoMoneda($("#tasa_cambio").val());
		recorreTable_fac(1, xtasa, "P");
	});
	//Recalcular en caso de cambiar el IVA a si o No
	$(document).on("change", ".reCalcular", function (event) {
		event.preventDefault();
		xtasa = formatoMoneda($("#tasa_cambio").val());
		recorreTable_fac(1, xtasa, "P");
	});
	//
	$("#id_fab").on("change", function (e) {
		e.preventDefault();
		id_fab = $(this).val();
		$("#cuerpoTablaDetalle").html("");
		if (Object.keys(id_fab).length) {
			create_express();
		}
	});
	//Eliminar un registro
	$("#tblIndexMain").on("click", ".btn-delete", function () {
		var recordId = $(this).data("id"); // Obtiene el ID del registro
		var num_tdo = $(this).data("code");
		Swal.fire({
			title: "¿Está usted seguro de eliminar este registro?",
			text: "¡No podrá revertir esta eliminación!",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Si, borrar este registro!",
			cancelButtonText: "Cancelar",
		}).then((result) => {
			if (result.isConfirmed) {
				const url = `${base_url}/Cotizaciones/destroy`;
				$.ajax({
					url: url, // URL de tu script de eliminación en el servidor
					type: "POST",
					data: { id: recordId, num_tdo, num_tdo },
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
								tableIndex.draw(); // El 'false' previene que se reajuste la paginación a la página 1.
								tableIndex.ajax.reload(null, true);
								//window.location.href = `${base_url}/BanMovim`;
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
});
//Formulario Nuevo
function dat_form_new() {
	listar_empresas();
	$("#fecha_comp").val(GetTodayDate(0));
	fecha_comp = $("#fecha_comp").val();
	listar_status(1);
}
//Consultar registro
function dat_form(id_cot) {
	const url = `${base_url}/Cotizaciones/consultar_cotizacion`; 
	$.ajax({
		url: url,
		method: "POST",
		dataSrc: "",
		data: { id_cot, id_cot },
		dataType: "json",
		beforeSend: function () {
			loader.show();
		},
		complete: function () {
			loader.hide();
		},
		error: function (jqXHR, textStatus, errorThrown) {
			loader.hide();
			console.log(
				"Ha ocurrido el siguiente error: ",
				textStatus,
				errorThrown
			);
		},
		success: function (data) {
			//Encabezado
			id_emp = data[0].id_emp;
			id_tdo = data[0].id_tdo;
			listar_empresas(id_emp, true);
			listar_tipos_documentos(id_emp, "P", id_tdo, true);
			$("#num_tdo").val(data[0].num_tdo);
			$("#num_tdo").css("pointer-events", "none");
			fecha_comp = data[0].fecha_comp;
			$("#fecha_comp").val(fecha_comp);
			$("#fecha_comp").css("pointer-events", "none");
			id_cli = data[0].id_cli;
			$("#id_cli").val(id_cli);
			$("#nom_cli").val(data[0].nom_ent);
			id_moneda = data[0].id_moneda;

			listar_monedas(id_moneda, true);
			xtasa = data[0].tasa_cambio;
			$("#tasa_cambio").val(format_number_with_dec_new(xtasa, 2));
			xtasa = data[0].tasa_cambio;
			id_vend = data[0].id_vend;
			listar_vendedores(id_vend, true);
			id_fab = data[0].id_fab;
			listar_marcas(id_fab);
			$("#observa").val(data[0].observa);
			//
			update_tasa();
			//Detalle
			onlyread = "";
			id_emp = data[0].id_emp;
			id_tdo_cfg = tip_doc_fac(id_emp);
			loc_pri_cot = id_tdo_cfg["loc_pri_cot"];
			locked_invoice = id_tdo_cfg["locked_invoice"];
			if (loc_pri_cot == 1) {
				onlyread = " readonly ";
			}
			stock = id_tdo_cfg["cot_stock"];
			item = 0;
			htmlTags = "";
			var detalleLimpio = data.map(function (item, index) {		
				   var $row = $('#tblDetalle').DataTable().row(index).node();
				    // Capturamos el valor dinámico actual del input de cantidad
                    var cantidadReal = $($row).find("input[id^='cant']").val() || item.cant;		
				return {
					id_prod: item.id_prod,
					nom_prod: item.nom_prod,
					cantidad: cantidadReal,
					stock: item.stock,
					uni_ven_prod: item.uni_vta,
					ventas_prod: item.pre_unit,
					ventas_prod1: item.pre_vta,
					id_des_item: '',
					nom_des: '',
					iva_prod: item.iva_prod,
					total: item.sub_total
				}
			});
			table.clear();
			table.rows.add(detalleLimpio);
			table.draw();			
		},
	});
}
//Actualizar tasa y mostrar campos de valores de moneda
async function update_tasa() {
	$("#tasa_cambio").val("");
	$(".local").hide();
	$(".foranea").hide();

	if (id_moneda && fecha_comp) {
		xtasa = await getExchangerate(fecha_comp, id_moneda);
		$("#tasa_cambio").val(xtasa);
		tasa_cambio = formatoMoneda(xtasa);
		show_tasa();
	}
	$("#tasa_cambio").css("pointer-events", "none");
}
async function fetcingData(id_cli) {
	const datosFetched = await tid_vend(id_cli);
	id_ven_cli = datosFetched["id_vend"];
	id_mon_cli = datosFetched["id_moneda"];
	nom_cli = datosFetched["nom_ent"];
	handling_conver = datosFetched["handling_conver"];
	$("#nom_cli").val(nom_cli);
	listar_vendedores(id_ven_cli);
	listar_monedas(id_mon_cli);
	$("#id_moneda").val(id_mon_cli);
	id_moneda = id_mon_cli;
	update_tasa();
}
//Imprimir cotización segun la moneda indicada
function print_cotiza(e, ori) {
	//ori = 1 pdf, ori = 2 excel
	let num_cot = e.dataset.code;
	let id_moneda = e.dataset.name;
	let id_code = e.dataset.id;
	if (ori) {
		id_code = id_code + "|" + ori;
	}
	if (id_moneda == "USD") {
		Swal.fire({
			icon: "question",
			title:
				"¿Está seguro que desea imprimir la Cotización número " +
				num_cot +
				"?",
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: "Solo en " + id_moneda,
			denyButtonText: "Ambas monedas",
		}).then((result) => {
			if (result.isConfirmed) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza_foranea/` + id_code,
					"_blank"
				);
			} else if (result.isDenied) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza/` + id_code,
					"_blank"
				);
			}
		});
	} else {
		Swal.fire({
			icon: "question",
			title:
				"¿Está seguro que desea imprimir la Cotización número " +
				num_cot +
				"?",
			showCancelButton: true,
			confirmButtonText: "¿Imrprimir ?",
		}).then((result) => {
			if (result.isConfirmed) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza/` + id_code,
					"_blank"
				);
			}
		});
	}
}
//Imprimir cotización segun la moneda indicada
function print_cotiza_excel(e, ori) {
	//ori = 1 pdf, ori = 2 excel
	let num_cot = e.dataset.code;
	let id_moneda = e.dataset.name;
	let id_code = e.dataset.id;
	if (ori) {
		id_code = id_code + "|" + ori;
	}
	if (id_moneda == "USD") {
		Swal.fire({
			icon: "question",
			title:
				"¿Está seguro que desea imprimir la Cotización número " +
				num_cot +
				" en Excel?",
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: "Solo en " + id_moneda,
			denyButtonText: "Ambas monedas",
		}).then((result) => {
			if (result.isConfirmed) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza_excel/` + id_code,
					"_blank"
				);
			} else if (result.isDenied) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza_excel/` + id_code,
					"_blank"
				);
			}
		});
	} else {
		Swal.fire({
			icon: "question",
			title:
				"¿Está seguro que desea imprimir la Cotización número " +
				num_cot +
				"en Excel?",
			showCancelButton: true,
			confirmButtonText: "¿Imrprimir ?",
		}).then((result) => {
			if (result.isConfirmed) {
				window.open(
					`${base_url}/Cotizaciones/print_cotiza_excel/` + id_code,
					"_blank"
				);
			}
		});
	}
}
function create_express() {
	const url = `${base_url}/Cotizaciones/create_express`;
	id_ent = $("#id_cli").val();

	$.ajax({
		url: url,
		method: "POST",
		dataSrc: "",
		data: { id_fab: id_fab, id_ent: id_ent },
		dataType: "json",
		beforeSend: function () {
			loader.show();
		},
		complete: function () {
			loader.hide();
		},
		error: function (jqXHR, textStatus, errorThrown) {
			loader.hide();
			console.log(
				"Ha ocurrido el siguiente error: ",
				textStatus,
				errorThrown
			);
		},
		success: function (data) {
			var htmlTags = "";
			item = 0;
			$.each(data, function (i, xitem) {
				var detalleLimpio = data.map(function (item) {
					
					return {
						id_prod: item.id_prod,
						nom_prod: item.nom_prod,
						cant: item.can_det,
						stock: item.stock,
						uni_ven_prod: item.uni_ven_prod,
						ventas_prod1: item.pv1,
						ventas_prod: (item.pv1 / item.uni_ven_prod),
						iva_prod: (item.iva_prod == "0") ? 'N' : 'S',
						id_des_item: '',
						nom_des: '',
						total: item.pv1
					}
				});
				table.clear();
				table.rows.add(detalleLimpio);
				table.draw();
				tasa_cambio = formatoMoneda($("#tasa_cambio").val());
				recorreTable_fac('', tasa_cambio, tipo_fac);
			});
			xtasa = formatoMoneda($("#tasa_cambio").val());
			recorreTable_fac(1, xtasa, "P");
		},
	});
}