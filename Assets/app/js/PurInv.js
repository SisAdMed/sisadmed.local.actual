//Variables
let item = 0;
let itemSelect;
let c_consig;
origen_COM = 1;
stock = 0;
let id_tdo;
myConsulproduc = {};
ori = "";
//Validar campos
$(document).ready(function () {
	$("form[name='my_form']").validate({
		rules: {
			id_emp: "required",
			id_tdo: "required",
			num_tdo: "required",
			fecha_comp: "required",
			fec_fact: "required",
			id_moneda: "required",
			nom_cli: "required",
			sub_total: "required",
			status: "required",
			num_control: {
				required: function (element) {
					return $("#ori").val() == 'M';
				},
			},
		},
		messages: {
			id_emp: "Debe especificar una empresa",
			id_tdo: "Debe especificar un Tipo de documento",
			num_tdo: "Debe especificar un Número de documento",
			fecha_comp: "Debe especificar un fecha del documento",
			fec_fact: "Debe especificar uan fecha de factura del proveedor",
			id_moneda: "Debe especificar un moneda del documento",
			nom_cli: "Debe especificar un proveedor del documento",
			sub_total: "Debe especificar al menos un item",
			status: "Debe especificar un Status",
			num_control: "Debe especificar un Número de Control"
		},
	});
	//Cargar el index
	$(".especial_contrib").hide();
	tipo_fac = "COM";
	ori = $("#ori").val();
	if (ori == "M") {
		localStorage.setItem("page_name", "Factura de Compra");
	}
	form = $("form").attr("id");
	if (form === undefined) {
		initPurInv();
	} else {
		id = $("#id").val();
		if (id) {
			show_row(id);
		} else {
			dat_form_new()
		}
	}
	//Lleno el combo de proveedores dependiendo de la empresa seleccionada
	$(document).on("change", "#id_emp", async function (e) {
		e.preventDefault();
		id = $("#id").val();
		$("#id_tdo").empty();
		id_tdo_val = "";
		id_tdo_cfg = "";
		id_emp = $(this).val();
		id_tdo_cfg = await tip_doc_com(id_emp);

		//Validar si es contribuyente especial
		especial_contrib = id_tdo_cfg["especial_contrib"];
		$(".especial_contrib").hide();
		if (especial_contrib == "S" && ori != "T") {
			$(".especial_contrib").show();
			listar_retiva(0, "id_retiva");
		}
		if (!id) {
			if (id_tdo_cfg) {
				if (ori == "M") {
					id_tdo_val = id_tdo_cfg["tdoc_pur"];
					id_tdoc_pre = id_tdo_cfg["tdoc_purord"];
					listar_tipos_documentos_CXP(
						id_emp,
						id_tdoc_pre,
						"",
						false,
						"fuente"
					);
				} else if (ori == "O") {
					id_tdo_val = id_tdo_cfg["tdoc_purord"];
					tip_doc_cxp_cfg = await tip_doc_cxp(id_tdo_val);
				} else if (ori == "A") {
					id_tdo_val = id_tdo_cfg["tdoc_purcrenot"];
				} else if (ori == "T") {
					id_tdo_val = id_tdo_cfg["tdoc_purdelnot"];
				} else if (ori == "V") {
					id_tdo_val = id_tdo_cfg["tdoc_purretnot"];
				}
				listar_tipos_documentos_CXP(id_emp, ori, id_tdo_val);

				$("#id_tdo").css("pointer-events", "none");
			}
		}
		stock = 0;
	});
	//Combo de monedas
	$(document).on("change", "#id_moneda", async function (e) {
		e.preventDefault();
		fecha_comp = $("#fecha_comp").val();
		id_moneda = $("#id_moneda").val();
		fecha_comp = $("#fecha_comp").val();
		xtasa = await getExchangerate(fecha_comp, id_moneda);
		$("#tasa_cambio").val(xtasa);
		tasa_fac = financial(xtasa, 8);
		tasa_cambio = xtasa;
		show_tasa();
	});
	//Seleccioanr por defecto el vendedor al momento de escoger el cliente
	$(document).on("change", "#id_cli", function (event) {
		id_cli = $("#id_cli").val();
		event.preventDefault();
		fetcingData(id_cli);
	});
	//Cargar los Numeros de Documentos segun la fuente seleccionada cuando es Nota de Credito
	$("#fuente").on("change", function (e) {
		tipo_doc_ori = $(this).val();
		listar_doc_fuentes_cxp(id_emp, "0", "origen", ori, id_cli, tipo_doc_ori);
	});
	//Mostrar datos del Documento Origen en pantalla
	$("#origen").on("change", function (e) {
		e.preventDefault();
		id_cot_ori = $(this).val();
		dat_form_com(id_cot_ori, true);
	});
	//Guardar y/o Actualizar Registro
	$("#my_form").on('submit', function (e) {
		e.preventDefault();
		// 2. Valdiar el formulario antes de guardar y/o actualizar
		if (!$(this).valid()) {
			return false;
		}
		// 2. Extraer los datos del detalle (recorriendo cada fila)
		var detalle = [];
		var table = $("#tblDetalle").DataTable();
		// Usamos table.$('tr') para incluir todas las filas (incluso con paginación)
		table.$('tbody tr').each(function () {
			var $row = $(this);
			var id_prod = $row.find("input[id^='id_prod']").val();
			var can_det = $row.find("input[id^='cant']").val();
			var uni_vta = $row.find("input[id^='uni_com_prod']").val();
			var pre_unit = $row.find("input[id^='costo_prod']").val();
			var pre_vta = $row.find("input[id^='costo_prod1']").val();
			var iva_prod = $row.find("select[id^='iva_prod']").val();
			var sub_total = $row.find("input[id^='total']").val();
			var lote = $row.find("input[id^='lote']").val();
			var fec_venc = $row.find("input[id^='fec_venc']").val();
			detalle.push({
				id_prod: id_prod,
				cant_det: can_det,
				uni_vta: uni_vta,
				pre_unit: pre_unit,
				pre_vta: pre_vta,
				iva_prod: iva_prod,
				sub_total: sub_total,
				lote: lote,
				fec_venc: fec_venc,
			})
		});
		// Validar que exista al menos un artículo en el detalle
		if (detalle.length === 0) {
			Swal.fire('Error', 'Debe agregar al menos un producto válido al detalle.', 'warning');
			return false;
		}
		var formData = new FormData(this);
		formData.append('detalle', JSON.stringify(detalle));
		const $btnOk = $("#btnok");
		const url = `${base_url}/PurInv/store`;
		let valor = localStorage.getItem('page_name');
		//Ajax para Guardar y/o Actualizar
		$.ajax({
			url: url,
			method: 'POST',
			dataSrc: '',
			data: formData,
			processData: false,       // Prevent jQuery from trying to convert the FormData to a string
			contentType: false,
			dataType: 'json',
			beforeSend: function () {
				$btnOk.prop("disabled", true);
				// Muestra una ventana de SweetAlert2 con spinner activo
				Swal.fire({
					title: 'Guardando...',
					text: `Guardando el documento de ${valor}, por favor espere.`,
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
						window.location.href = `${base_url}/PurInv?ori=${ori}`;
					} else {
						// Solo se reactiva el botón si el backend devolvió un error lógico
						$btnOk.prop("disabled", false);
					}
				})
			},
		});
		$(this).prop("disabled", false);
	})
});
/**Nuevo registro */
function dat_form_new() {
	listar_empresas(0);
	$("#fecha_comp").val(GetTodayDate(0));
	$("#fecha_venci").val(GetTodayDate(0));
	$("#fec_fact").val(GetTodayDate(0));
	listar_InvTipoMov(0, "", "id_tmovinv");
	listar_monedas();
	listar_status(1);
}
/**Consultar registro */
function show_row(id) {
	let url = `${base_url}/PurInv/show_row`;
	$.ajax({
		url: url,
		method: 'POST',
		data: { id: id },
		dataType: 'json',
		beforeSend: function () {
			// Muestra una ventana de SweetAlert2 con spinner activo			
			Swal.fire({
				title: 'Consultando...',
				text: `Consultando el Documento, por favor espere.`,
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
		error: function (xhr, status, error) {
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
			enca = data[0];
			//Cargar detalle
			var detalleLimpio = data.map(function (item, index) {
				var $row = $('#tblDetalle').DataTable().row(index).node();
				// Capturamos el valor dinámico actual del input de cantidad
				var cantidadReal = $($row).find("input[id^='cant']").val() || item.can_det;
				return {
					id_prod: item.id_prod,
					nom_prod: item.nom_prod,
					cant: cantidadReal,
					lote: item.lote,
					fec_venc: item.fec_venc,
					uni_com_prod: item.uni_com_prod,
					costo_prod: item.pre_unit,
					costo_prod1: item.pre_vta,
					id_des_item: '',
					nom_des: '',
					iva_prod: item.iva_prod,
					total: item.sub_total
				}
			});
			table.clear();
			table.rows.add(detalleLimpio);
			table.draw();
			//Cargar encabezado
			id_emp = enca['id_emp'];
			id_tdo = enca['id_tdo'];
			listar_empresas(id_emp, true);
			listar_tipos_documentos_CXP(id_emp, ori, id_tdo, true);
			$("#num_tdo").val(enca['num_tdo']);
			$("#num_control").val(enca['num_control']);
			$("#fecha_comp").val(enca['fecha_comp']);
			$("#fec_fact").val(enca['fec_fact']);
			$("#fecha_venci").val(enca['fecha_venci']);
			id_moneda = enca['id_moneda'];
			listar_monedas(id_moneda);
			tasa_cambio = format_number_with_dec_new(enca['tasa_cambio'], 4);
			$("#tasa_cambio").val(tasa_cambio);
			id_cli = enca['id_cli'];
			$("#id_cli").val(id_cli);
			nom_cli = enca['nom_ent'];
			$("#nom_cli").val(nom_cli);
			id_retiva = enca['id_retiva'];
			if(id_retiva != 0){
				$(".especial_contrib").show();
				listar_retiva(id_retiva, 'id_retiva');
			}
			show_tasa();

		}
	});
}
async function fetcingData(id_cli) {
	const datosFetched = await tid_vend(id_cli);
	cod_diascre = datosFetched["cod_diascre"] ?? 0;
	contr_esp = datosFetched["contr_esp"];
	id_por_ret_iva = datosFetched["id_por_ret_iva"]
	$("#fecha_venci").val($("#fecha_comp").val());
	if (cod_diascre) {
		fecha_venci = GetTodayDate(cod_diascre);
		$("#fecha_venci").val(fecha_venci);
	}
	nom_cli = datosFetched["nom_ent"];
	$("#nom_cli").val(nom_cli);
	show_tasa();
	//Cargar Documentos Fuentes y Numeros para emitir Nota de Credito
	if (ori == "A") {
		load_document_com(id_cli);
	}
	if (contr_esp) {
		//Validar si es contribuyente especial		
		$(".especial_contrib").hide();
		if (especial_contrib == "S" && ori != "T") {
			$(".especial_contrib").show();
			listar_retiva(id_por_ret_iva, "id_retiva");
		}
	}
}
// para agregar un detalle mas, una fila
//Cargar Documentos Fuentes y Numeros para emitir Nota de Credito
function load_document_com(id_cli) {
	listar_tipos_documentos_CXP(id_emp, "M", "", false, "fuente");
}
//Listar Documentos Fuentes para emitir Nota de Crédito
function listar_doc_fuentes_cxp(id_emp, id, tag, tipo, id_cli, tipo_doc_ori, bloquear = false) {
	const url = `${base_url}/PurInv/listar_doc_fuentes`;
	if (tipo_doc_ori) {
		$.ajax({
			url: url,
			method: "POST",
			data: {
				id_emp: id_emp,
				id: id,
				tipo: tipo,
				id_cli: id_cli,
				tipo_doc_ori: tipo_doc_ori,
			},
			dataSrc: "",
			dataType: "json",
			beforeSend: function () {
				loader.show();
			},
			complete: function () {
				loader.hide();
			},
			error: function (jqXHR, textStatus, errorThrown) {
				loader.hide();
				// Handle the error
				console.error("AJAX Error: ", textStatus, errorThrown);
				console.error("Response Text: ", jqXHR.responseText);
			},
			success: function (data) {
				if (data) {
					var htmlTags = `<option>Selecione...</option>`;
					var selecTed = " selected ";
					$.each(data, function (index, item) {
						if (item.id_cot == id) {
							htmlTags += `<option ${selecTed} value=${item.id_cot}>${item.doc}</option>`;
						} else {
							htmlTags += `<option value=${item.id_cot}>${item.doc}</option>`;
						}
					});
					$("#origen").html(htmlTags);
					if (bloquear) {
						$("#origen").css("pointer-events", "none");
					}
				}
			},
		});
	}
}