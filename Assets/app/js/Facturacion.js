//Variables
let item = 0;
let id_prod_img = 0;
let nameid = "id_pro";
let selectFile = "";
let itemSelected = 0;
let subTotal = 0;
let subTotaliva = 0;
let total = 0;
let monto = 0;
let board = [];
let id_ven_cli = 0;
let id_vend2 = [];
let tasa_fac = 0;
id_clir = $("#id_clir").val();
id_empr = $("#id_empr").val();
fecha_comp = $("#fecha_comp").val();
id_moneda = $("#id_monedar").val();
id_vend = $("#id_vendr").val();
id_des = $("#id_des").val();
id_cot = $("#id").val();
t_fuente = false;
let id_tdo_cfg;
let id_tdo_val;
let id_cli_glb;
let xtasa_coti = "";
let tasa_cambio = 0;
id_tip_doc = "";
let alltext = [];
//Validar si requiere autorizacíon el descuento a aplicar
let appreq = 0;
let disapp = "";
handling_conver = 0;
let id_ent;
mov_inv = true;
let ytasa_cambio = 0;
almSalPpal = false;
tip_doc_fuente = "";
xtasa_ori = 0;
let tasa_fac_cli = false;
let invoice = 0;
let xTasaCambio;
tabla = "tblDetalle";
tipo_fac = GetURLParameter("tipo");
error_msg = "";
//
let fac_notas = false;
//
$(document).ready(async function () {
	//Validaciones
	$("form#my_form").validate({
		ignore: null,
		rules: {
			id_emp: "required",
			id_tdo: "required",
			fecha_comp: "required",
			id_cli: "required",
			id_moneda: "required",
			tasa_cambio: "required",
			id_vend: "required",
			item: "required",

		},
		messages: {
			id_emp: "Debe especifiar una Empresa",
			id_tdo: "Debes especificar un Tipo de Documento",
			fecha_comp: "Debe especificar uan Fecha de Emisión",
			id_cli: "Debe especificar un cliente",
			id_moneda: "Debe especificar un Tipo de Moneda",
			tasa_cambio: "Debe especificar una Tasa de Cambio",
			id_vend: "Debe especificar un Vendedor",
			item: "El documento debe tener al menos un detalle"
		},
	})
	//Cargar el index
	form = $("form").attr("id");
	if (form === undefined) {
		initFacturacion();
	} else {
		id = $("#id").val();
		if (id_cot) {
			tipo_fac = $("#tipo_fact").val();
			show_row(id, tipo_fac);
		} else {
			dat_form_new();
		}
	}
	//Ocultar el campo nro de control se es una Nota de Entrega No fiscal
	if (tipo_fac == "Z") {
		$(".nro_control").hide();
	}
	if ($("#tipo_fact").val() != undefined) {
		tipo_fac = $("#tipo_fact").val();
	}
	//Validar Empresa Seleccionada
	$(document).on("change", "#id_emp", async function (e) {
		e.preventDefault();
		table.clear().draw();
		id_emp = $(this).val();
		tipo_fac = $("#tipo_fact").val();
		$("#id_cli").empty();
		id = $("#id").val();
		$("#id_tdo").val("");
		id_tdo_val = "";
		id_tdo_cfg = "";
		id_tdo_cfg = await tip_doc_fac(id_emp);
		id_alm_ppal = id_tdo_cfg["id_alm"];
		loc_pri_inv = id_tdo_cfg["loc_pri_inv"];
		if (!id) {
			if (id_tdo_cfg) {
				if (tipo_fac == "F") {
					id_tdo_val = id_tdo_cfg["id_tdoc_fac"];
				} else if (tipo_fac == "C") {
					id_tdo_val = id_tdo_cfg["id_tdoc_cre"];
				} else if (tipo_fac == "N") {
					id_tdo_val = id_tdo_cfg["id_tdoc_not"];
				} else if (tipo_fac == "Z") {
					id_tdo_val = id_tdo_cfg["id_tdoc_not_no_fis"];
				}

				listar_tipos_documentos(id_emp, tipo_fac, id_tdo_val);
				$("#id_tdo").css("pointer-events", "none");
				//ALmacen y Ubicacion segun el consignado
				id_alm_def = id_tdo_cfg["id_alm"];
				id_ubi_def = id_tdo_cfg["id_ubi"];
				$("#id_alm_def").val(id_alm_def);
				$("#id_ubi_def").val(id_ubi_def);
				$("#id_tdo").trigger("change");
			}
		}
		stock = id_tdo_cfg["fac_stock"];
	});
	//Validar si el Tipo de Documento usa consecutivo o no para poder aisgnar el número del documento
	$(document).on("change", "#id_tdo", async function (e) {
		e.preventDefault()
		const datos = new FormData();
		datos.append("id", id_tdo_val);
		try {
			const url = `${base_url}/CXCDocument/val_tdo`;
			const respuesta = await fetch(url, {
				method: "POST",
				body: datos,
			});
			const resultado = await respuesta.json();
			if (resultado) {
				if (resultado["con_tdoc"] == 0) {
					$("#num_tdo").prop("readonly", false);
				} else {
					$("#num_tdo").prop("readonly", true);
				}
			}
		} catch (error) {
			console.log(error);
		}
	});
	//Al cambiar la fecha de emisión 
	$(document).on('change', "#fecha_comp", async function (e) {
		e.preventDefault();
		id_emp = $("#id_emp").val();
		//Validar Fechas de Contabilidad
		id_emp_cfg = await get_empresa_config(id_emp);
		fec_ctb = id_emp_cfg["fec_cxc"];
		fecha_comp = $(this).val();
		if (fecha_comp <= fec_ctb) {
			$("#fecha_comp").addClass("is-invalid");
			$("#fecha_comp").attr("title", "La fecha del documento no puede ser menor a la fecha de contabilidad de la empresa");
			$("#btnok").prop("disabled", true);
		} else {
			$("#fecha_comp").removeClass("is-invalid");
			$("#btnok").prop("disabled", false);
		}
		id_cli = $("#id_cli").val();
		id_moneda = $("#id_moneda").val();
		if (id_cli) {
			const datosProvee = await tid_vend(id_cli);
			cod_diascre = datosProvee["cod_diascre"] + 1;
			fecha_venci = GetTodayDate(cod_diascre, fecha_comp);
			$("#fecha_venci").val(fecha_venci);
			$("#id_moneda").val(id_moneda);
			$("#id_moneda").trigger("change");
		}
		$("#id_cli").trigger('change');
	})
	//Combo de monedas
	$(document).on("change", "#id_moneda", async function (event) {
		event.preventDefault();
		id_moneda = $("#id_moneda").val();
		fecha_comp = $("#fecha_comp").val();
		xtasa = await getExchangerate(fecha_comp, id_moneda);
		$("#tasa_cambio").val(xtasa);
		tasa_fac = financial(xtasa, 8);
		tasa_cambio = xtasa;
		show_tasa();
	});
	//Seleccioanr por defecto el vendedor al momento de escoger el cliente
	$(document).on("change", "#id_cli", function (e) {
		id_cli = $(this).val();
		e.preventDefault();
		fetcingData(id_cli);
	});
	//Al abrir modal para mostrar las fotos de los productos
	$(document).on("click", ".show-picture", function (event) {
		//Datos para mostrar las fotos
		show_picture();
	});
	//Lleno el combo de origen de facturacion por la fuente seleccionada
	$(document).on("change", "#fuente", function (e) {
		e.preventDefault();
		table.clear().draw();
		$("#origen").empty();
		id_empr = $("#id_emp").val();
		id_emp = id_empr;
		id_cli = $("#id_cli").val();
		id_fuente = $("#fuente").val();
		url = `${base_url}/TipoDocCXC/show_row`;
		if (id_fuente != 0) {
			$.ajax({
				url: url,
				method: 'POST',
				data: { id: id_fuente },
				dataType: 'json',
				beforeSend: function () {
					loader.show();
				},
				complete: function () {
					loader.hide();
				},
				success: function (resulta) {
					fac_notas = false;
					if (resulta) {
						xori_tipo_doc = resulta.tipo_tdoc
						if (xori_tipo_doc == 'P') {
							listar_cotizacones(id_emp, "0", "origen", id_cli);
						} else if (xori_tipo_doc == 'F') {
							listar_facturas_clientes(id_emp, '0', 'origen', id_cli);
						} else {
							fac_notas = true;
							listar_notas(id_emp, '0', 'origen', id_cli, 'N');
						}
						$("#origen").trigger('change');
					}
				},
				error: function (xhr, status, error) {
					loader.hide();
					console.error(xhr.responseText);
				},

			})
		}
	});
	//Completar datos del encabezado y detalle de la factura al seleccioanr un origen
	$(document).on("change", "#origen", function (e) {		
		e.preventDefault();
		table.clear().draw();
		id_cotiza = $(this).val();
		if (id_cotiza) {
			show_data_origen(id_cotiza, xori_tipo_doc);
		}
	});
	//Aplicar descuentos general
	$(document).on("change", "#id_des_enca", async function (e) {
		id_des = $(this).val();
		xdesglb = await getRateDes(id_des);
		desdes = "";
		$("#cuerpoTablaDetalle").html("");
		if (id_des) {
			show_data_cotiza(id_cotiza, parseFloat(xdesglb["valor_tipdes"]));
		} else {
			show_data_cotiza(id_cotiza);
		}
	});
	//Cargar prodcutos desde el archivo cargado a la tabla
	$("#pdf_file").change(function (e) {
		//Actualizar MOneda con de la Empresa
		//listar_monedas(id_moneda_cia);
		//$("#tasa_cambio").val(format_number_with_dec_new(1, 4));
		//tasa_cambio = 1
		show_tasa();
		e.preventDefault();
		//Variables
		var archivo = $(this).val();
		if (archivo && archivo != "") {
			var extensiones = archivo.substring(archivo.lastIndexOf("."));
			//Validar que posea una extension valida para excel
			if (extensiones != ".pdf") {
				Swal.fire({
					icon: "error",
					title: "Error...",
					text: "El archivo de tipo " + extensiones + " no es válido",
				});
				$("#loadinvconsig").val("");
			} else {
				$("#cuerpoTablaDetalle").empty();
				loadAndRenderPDF(e);
			}
		} else {
			$("#cuerpoTablaDetalle").empty();
		}
		$(".loader").hide();
	});
	//Guardar y/o Actualizarregistro
	$("#my_form").on("submit", function (e) {
		e.preventDefault();
		if (!$(this).valid()) {
			return false;
		}
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
				pre_unit: pre_unit,
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
		formData.append('fac_notas', fac_notas);
		formData.append('detalle', JSON.stringify(detalle));		
		const $btnOk = $("#btnok");
		const url = `${base_url}/Facturacion/store`;
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
						window.location.href = `${base_url}/Facturacion?tipo=${tipo_fac}`;
					} else {
						// Solo se reactiva el botón si el backend devolvió un error lógico
						$btnOk.prop("disabled", false);
					}
				})
			},
		});
		$(this).prop("disabled", false);
	});
	$(document).on('click', '.btn_printer_doc', async function () {
		const id = this; // Definimos correctamente el elemento clickeado

		const id_cot = id.dataset.id;
		const id_moneda = id.dataset.moneda;
		const print_special = id.dataset.print;
		const nombre = id.dataset.name;
		const tasa = id.dataset.tasa;
		const code = id.dataset.code;

		const title = "¿Está seguro que desea imprimir la " + nombre + " Nnúmero " + code + "?";
		const title2 = "¿Desea imprimir solo cantidades de la " + nombre + " Número " + code + "?"
		const title3 = "¿Desea imprimir solo cantidades la " + nombre + " Número " + code + "?"

		if (print_special == 1 && id_moneda == 1) {
			if (id_moneda == 1 && tasa == 1) {
				let ytasa_cambio = ""; // Variable controlada localmente
				const resultado = await Swal.fire({
					title: "Indique la fecha para obtener el cambio a facturar",
					input: "date",
					inputValidator: (fecha) => {
						if (!fecha) {
							return "Debe indicar una fecha válida";
						} else {
							return undefined;
						}
					},
					html: `
                <h1>Cambio: <small id="cambio" style="color:#007bff;"></small></h1>
                `,
					didOpen: async () => {
						const input = Swal.getInput();
						const $cambio = Swal.getHtmlContainer().querySelector("#cambio");

						input.oninput = async () => {
							$cambio.textContent = "";
							const cambio = await getExchangerate(input.value, 2);
							if (cambio != "") {
								ytasa_cambio = `${cambio}`;
								$cambio.textContent = `${cambio}`;
							}
						};
					},
					showCancelButton: true,
					confirmButtonText: "Ok",
					cancelButtonText: "Cancelar",
				});

				if (resultado.isConfirmed && resultado.value) {
					const tasa_cambio = ytasa_cambio.replace(",", ".");
					try {
						var datos = new FormData();
						datos.append("id_cot", id_cot);
						datos.append("tasa_cambio", tasa_cambio);

						let url = `${base_url}/Facturacion/update_tasa`;
						let respuesta = await fetch(url, {
							method: "POST",
							body: datos,
						});
						await respuesta.json(); // Consumimos la respuesta

						window.open(`${base_url}/Facturacion/print_factura_metro/` + id_cot, "_blank");
					} catch (error) {
						console.log(error);
					}
				}
			} else {
				window.open(`${base_url}/Facturacion/print_factura_metro/` + id_cot, "_blank");
			}
		} else {
			// Corregido el operador de asignación (= a ==)
			if (tipo_fac == 'F' || tipo_fac == "C") {
				window.open(`${base_url}/Facturacion/print_factura/` + id_cot, "_blank");
			} else if (tipo_fac == "Z") {
				Swal.fire({
					icon: "question",
					title: title,
					showDenyButton: true,
					showCancelButton: true,
					confirmButtonText: "Con Logo ",
					denyButtonText: "Sin Logo",
				}).then((result) => {
					if (result.isConfirmed) {
						Swal.fire({
							icon: "question",
							title: title3,
							showCancelButton: true,
							confirmButtonText: "Si solo cantidades",
						}).then((subResult) => {
							if (subResult.isConfirmed) {
								window.open(`${base_url}/Delnotnotfis/print_Delnotfis_cant_con/` + id_cot, "_blank");
							} else {
								window.open(`${base_url}/Delnotnotfis/print_Delnotfis_con/` + id_cot, "_blank");
							}
						});
					} else if (result.isDenied) {
						Swal.fire({
							icon: "question",
							title: title2,
							showCancelButton: true,
							confirmButtonText: "Si solo cantidades",
						}).then((subResult) => {
							if (subResult.isConfirmed) {
								window.open(
									`${base_url}/Delnotnotfis/print_Delnotfis_cant_sin/` + id_cot, "_blank");
							} else {
								window.open(`${base_url}/Delnotnotfis/print_Delnotfis_sin/` + id_cot, "_blank");
							}
						});
					}
				});
			} else if (tipo_fac == "N") {
				window.open(`${base_url}/Delnot/print_factura/` + id_cot, "_blank");
			}
		}
	});
});
/**Nuevo Registro */
function dat_form_new() {
	$(".foranea").hide();
	$(".local").hide();
	$(".consignado").hide();
	if ($("#tipo_fac").val() != undefined) {
		tipo_fac = $("#tipo_fac").val();
	}
	listar_empresas();
	listar_descuentos(0, "id_des_enca");
	$("#fecha_comp").val(GetTodayDate(0));
	$("#fecha_venci").val(GetTodayDate(0));
}
/**
 * Description Consultar registro
 *
 * @param {*} id 
 * @param {*} tipo_fac 
 */
function show_row(id, tipo_fac) {
	let url = "", onlyread = "";
	url = `${base_url}/Facturacion/consultar_factura`;
	tipo = (tipo_fac == "N") ? 'NOL' : 'NOF';
	$btnOk = $("#btnok");
	$.ajax({
		url: url,
		method: 'POST',
		data: { id_cot: id_cot, tipo: tipo },
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
		success: function (data) {			
			enca = data[0];
			//
			//Cargar detalle         			
			var detalleLimpio = data.map(function (item, index) {
				var $row = $('#tblDetalle').DataTable().row(index).node();
				// Capturamos el valor dinámico actual del input de cantidad
				var cantidadReal = $($row).find("input[id^='cant']").val() || item.cant;
				return {
					id_prod: item.id_prod,
					nom_prod: item.nom_prod,
					cantidad: cantidadReal,
					stock: 0,
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
			//
			id_emp = enca['id_emp'];
			id_tdo = enca['id_tdo'];
			id_cli = enca['id_cli'];
			id_moneda = enca['id_moneda'];
			id_alm_cli = enca['id_alm_cli'];
			id_ubi_cli = enca['id_ubi_cli'];
			listar_empresas(id_emp, true);
			listar_tipos_documentos(id_emp, tipo_fac, id_tdo, true);
			$("#num_tdo").val(enca['num_tdo']);
			$("#fecha_comp").val(enca['fecha_comp']);
			$("#fecha_venci").val(enca['fecha_venci']);
			$("#id_cli").val(id_cli);
			$("#nom_cli").val(enca['nom_ent']);
			listar_monedas(id_moneda, true); 2
			$("#tasa_cambio").val(format_number_with_dec_new(enca['tasa_cambio'], 4));
			listar_vendedores(enca['id_vend'], true);
			$("#oc_cliente").val(enca['oc_cliente']);
			$("#descrip_cot").val(enca['descrip_cot']);
			id_motcam = enca['id_motcam'];
			listar_motivo_cambio(id_motcam, true);
			show_tasa(id_emp, id_moneda, formatoMoneda(enca['tasa_cambio']));
			//Mostrar el Modal de Almacenes y Ubicaciones si es consignado y no tiene ubicacion asignada
			if (enca["c_consig"] == 1) {
				if (enca["alm_out"]) {

					alm_def = enca["alm_out"].split('|');
					id_alm_def = alm_def[0];
					id_ubi_def = alm_def[1];
					$("#id_alm_sal").val(id_alm_def);
					$("#id_ubi_sal").val(id_ubi_def);
				}
				if (enca["alm_in"]) {
					alm_cli = enca["alm_in"].split('|');
					id_alm_cli = alm_cli[0];
					id_ubi_cli = alm_cli[1];
					$("#id_alm_ent").val(id_alm_cli);
					$("#id_ubi_ent").val(id_ubi_cli);
				}
				mostar_data_inv();
			}
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
	})
}
async function fetcingData(id_cli) {
	const datosFetched = await tid_vend(id_cli);
	$(".consignado").hide();
	cod_diascre = datosFetched["cod_diascre"] ?? 0;
	$("#fecha_venci").val($("#fecha_comp").val());
	if (cod_diascre) {
		fecha_venci = GetTodayDate(cod_diascre);
		$("#fecha_venci").val(fecha_venci);
	}
	req_exc_rat = datosFetched["req_exc_rat"] ?? 0;
	if (req_exc_rat == 1 && !id) {
		tasa_fac_cli = true;
	}
	id_ven_cli = datosFetched["id_vend"];
	id_mon_cli = datosFetched["id_moneda"];
	nom_cli = datosFetched["nom_ent"];
	handling_conver = datosFetched["handling_conver"];
	c_consig = datosFetched["c_consig"];
	id_ubi_cli = datosFetched["id_ubi"];
	id_alm_cli = datosFetched["id_alm"];

	id_ent = id_cli;
	$("#nom_cli").val(nom_cli);
	listar_vendedores(id_ven_cli);
	listar_monedas(id_mon_cli);
	fecha_comp = $("#fecha_comp").val();
	xTasaCambio = await getExchangerate(fecha_comp, id_mon_cli);
	if (!id) {
		$("#tasa_cambio").val(xTasaCambio);
		tasa_fac = financial(xTasaCambio, 8);
	}

	id_motcam = datosFetched["id_motcam"];
	listar_motivo_cambio(id_motcam);
	$("#id_motcam").css("pointer-events", "none");

	tasa_cambio = xTasaCambio;
	//Validar ubicación deli clietne si es consignado y si esta vacia mostrar el modal de Ubicacion
	if (id_alm_cli && c_consig == 1) {
		if (!id) {
			if (id_alm_cli) {
				$("#id_alm_cli").val(id_alm_cli);
				id_alm = id_alm_cli;
			}
			if (id_ubi_cli) {
				$("#id_ubi_cli").val(id_ubi_cli);
				id_ubi_consig = id_ubi_cli;
			}
			$(".consignado").hide();
			if (c_consig == 1) {
				$(".consignado").show();
			}
			$("#modal-ubicaciones").modal('show');
			mostar_data_inv();
		}
	}
	if (tipo_fac == "F") {
		id_tdoc_pre = id_tdo_cfg["id_tdoc_pre"];
		listar_tipos_documentos(id_emp, "P,Z", 0, true, "fuente");
		$("#origen").empty();
	} else if (tipo_fac == "C") {
		id_tdoc_pre = id_tdo_cfg["id_tdoc_pre"];
		listar_tipos_documentos(id_emp, "F", 0, true, "fuente");
		$("#origen").empty();
	}
	id_emp = $("#id_emp").val();
	id_moneda = $("#id_moneda").val();
	tasa_cambio = formatoMoneda($("#tasa_cambio").val());
	show_tasa(id_emp, id_moneda, tasa_cambio);
	table.clear().draw();
}
//Solicitar aprobación para aplicar el descuento solicitado
async function ReqApp(desdes) {
	title = "Aprobación aplicación de descuentos";
	message =
		"Se solicita aprobación de descuentos para facturar la cotizacion del " +
		desdes;
	const datos = new FormData();
	datos.append("title", title);
	datos.append("message", desdes);
	try {
		const url = `${base_url}/Facturacion/aprobacion`;
		var respuesta = await fetch(url, {
			method: "POST",
			body: datos,
		});
		var resultado = await respuesta.json();
		return new Promise((resolve, reject) => {
			setTimeout(() => {
				resolve(resultado);
			}, 200);
		});
	} catch (error) {
		console.log(error);
	}
}
//Busqueda del valor del porcentaje de descuento
async function getRateDes(id_des) {
	var datos = new FormData();
	datos.append("id", id_des);
	try {
		const url = `${base_url}/TipoDcto/show_row`;
		var respuesta = await fetch(url, {
			method: "POST",
			body: datos,
		});
		var resultado = await respuesta.json();
		return new Promise((resolve, reject) => {
			setTimeout(() => {
				resolve(resultado);
			}, 200);
		});
	} catch (err) {
		console.log(err);
	}
}
async function show_picture() {
	try {
		var datos = new FormData();
		datos.append("id_prod_img", id_prod_img);
		let url = `${base_url}/Productos/showImg`;
		let repuesta = await fetch(url, {
			method: "POST",
			body: datos,
		});
		const resultado = await repuesta.json();
		$("#imgPreview").empty();
		if (resultado) {
			$("#title-product").text(resultado[0]["nom_prod"]);
			var show_photo = "";
			$.each(resultado, function (i, item) {
				show_photo =
					'<img width="200px" height="200px" title="' +
					item["filename"] +
					'" src="' +
					base_url +
					item["url_photo"] +
					'">' +
					"</img> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				$("#imgPreview").append(show_photo);
			});
		}
		id_prod_img = 0;
	} catch (err) {
		console.log(err);
	}
}
function loadAndRenderPDF(e) {
	$(".loader").show();
	var file = e.target.files[0];
	let fr = new FileReader();
	fr.readAsDataURL(file);
	fr.onload = () => {
		let res = fr.result;
		extractText(res);
	};
}
async function extractText(url) {
	$(".loader").show();
	let pdf;
	pdfjsLib.GlobalWorkerOptions.workerSrc = `${base_url}/Assets/js/pdf.worker.js`;
	pdf = await pdfjsLib.getDocument(url).promise;
	let pages = pdf.numPages;
	for (let i = 1; i <= pages; i++) {
		let page = await pdf.getPage(i);
		let txt = await page.getTextContent();
		let text = txt.items.map((s) => s.str).join("");
		alltext.push(txt);
	}
	readpdf(alltext);
}
async function readpdf(text) {
	$(".loader").show();
	//Variables
	var arrayCodCli = new Array();
	var arrayIdProd = new Array();
	var arrayNombre = new Array();
	var arrayCant = new Array();
	var arrayCosto = new Array();
	var arrayIva = new Array();
	var arrayUniVta = new Array();
	var arraySaldo = new Array();
	var arrayConver = new Array();
	var arrayNomFab = new Array();
	var list = {
		datos: [],
	};
	var tlistarray = 0;
	var titem = 0;
	var tencontre = false;
	id_ent = $("#id_cli").val();
	id_emp = $("#id_emp").val();
	//Recorrer Paginas
	for (i = 0; i < text.length; i++) {
		c = text[i].items.length;
		//Rrecorrer cada item
		nom_prod_cli = "";
		for (x = 0; x < c; x++) {
			$(".loader").show();

			val = $.trim(text[i].items[x]["str"]);
			if (val == "CORPORACION MMQ, C.A.") {
				titem = 0;
				tlistarray++;
				tencontre = true;
			}
			titem++;
			if (tencontre && titem != 0) {
				if (titem == 2) {
					//console.log('titem == 2 ' + val);
					arrayCodCli[tlistarray] = val;
					url = `${base_url}/Facturacion/equivale`;
					datos = new FormData();
					datos.append("id_prod", val);
					datos.append("id_ent", id_ent);
					datos.append("id_emp", id_emp);
					datos.append("format", 1);
					datos.append("id_alm", id_alm);
					datos.append("id_ubi", id_ubi_consig);
					datos.append("fecha", $("#fecha_comp").val());

					const respuesta = await fetch(url, {
						method: "POST",
						body: datos,
					});
					const resultado = await respuesta.json();
					if (resultado) {
						arrayIdProd[tlistarray] = resultado[0]["id_prod"];
						arrayNombre[tlistarray] = resultado[0]["nom_prod"];
						arrayUniVta[tlistarray] = resultado[0]["uni_ven_prod"];
						arraySaldo[tlistarray] = resultado[0]["stock"];
						arrayConver[tlistarray] = resultado[0]["conv_prod_cons"];
						arrayNomFab[tlistarray] = resultado[0]["nom_fab"];
					} else {
						//console.log('Producto no existe', val);
						error_msg += "El producto " + nom_prod_cli + " con código de cliente " + val + " no existe en el las tabla de equivalenicas del sistema.\n";
					}
				} else if (titem == 3) {
					nom_prod_cli = val;
				} else if (titem == 7) {
					if (val.indexOf("-") >= 0) {
						val1 = format_number_with_out_dec(val) * -1;
					} else {
						val1 = format_number_with_out_dec(val);
					}
					arrayCant[tlistarray] = val1;
				} else if (titem == 8) {
					if (val.indexOf("-") >= 0) {
						val1 = format_number_with_out_dec(val) * -1;
					} else {
						val1 = format_number_with_out_dec(val);
					}
					arrayCosto[tlistarray] = val1;
				} else if (titem == 12) {
					arrayIva[tlistarray] = format_number_with_out_dec(val);
					tencontre = false;
				}
				//
				if (titem == 12) {
					list.datos.push({
						codcli: arrayCodCli[tlistarray],
						nombre: arrayNombre[tlistarray],
						id_prod: arrayIdProd[tlistarray],
						saldo: arraySaldo[tlistarray],
						cant: arrayCant[tlistarray],
						conver: arrayConver[tlistarray],
						nom_fab: arrayNomFab[tlistarray],
						costo: arrayCosto[tlistarray],
						iva: arrayIva[tlistarray],
					});
				}
			}
		}
	}
	json = JSON.stringify(list);
	var obj = JSON.parse(json);

	//Recorrer JSON y llenar tabla
	//Tasa IVA
	item = 0;
	tasa_iva = await xvatTax(fecha_comp, "IVA");
	xtasa_iva = parseFloat(tasa_iva[0]["txr1_iva"]);
	//Tasa Cambio
	if (tipo_fac == "F") {
		$("#id_moneda").trigger("change");
	}
	tasa_cambio = $("#tasa_cambio").val();
	tasa_cambio = formatoMoneda(tasa_cambio);
	tcontador = 0;
	var aggregatedObject = Enumerable.From(obj.datos)
		.GroupBy(
			"{codcli: $.codcli, id_prod: $.id_prod, nombre: $.nombre, saldo: $.saldo, conver: $.conver, nom_fab: $.nom_fab}",
			null,
			function (key, g) {
				var aggregatedObject = {
					item: ++tcontador,
					codcli: key.codcli,
					id_prod: key.id_prod,
					nombre: key.nombre,
					saldo: key.saldo,
					conver: key.conver,
					nom_fab: key.nom_fab,
					costo: g.Sum("$.costo"),
					iva: g.Sum("$.iva"),
					cant: g.Sum("$.cant"),
				};
				return aggregatedObject;
			},
			function (x) {
				return x.id_prod + ":" + x.nombre;
			}
		)
		.ToArray();

	//Ordernar el objeto agregado por nombre

	//aggregatedObject.toSorted((a, b) => a.item + b.item);
	aggregatedObject.sort((a, b) => a.codcli.localeCompare(b.codcli));
	let dinamico = [];
	$.each(aggregatedObject, async function (i, xitem) {
		$(".loader").show();
		id_prod = xitem.id_prod;
		cant_det = xitem.cant;
		//Se descomento, para optimizar el proceso de la Facturación de la Metropolita
		//Jose Vargas 15-01-2026 11:09:00
		//xdat_id_pro_rel = await xdat_id_pro(id_prod);  
		//if (xdat_id_pro_rel && id_prod != undefined) { 
		id_prod = xitem.id_prod;
		if (xitem.conver != 1 && handling_conver == 1) {
			cant_det = xitem.cant / xitem.conver;
		}

		nom_prod = xitem.nombre;
		nom_prod_title = xitem.nombre + " Marca: " + xitem.nom_fab + " Cód. Cliente: " + xitem.codcli;
		saldo = xitem.saldo;
		xcolor = "";
		if (xitem.saldo <= 0) {
			saldo = 0;
		}
		if (cant_det > saldo) {
			xcolor = " style=background-color:red; ";
		}
		uni_vta = 1;
		iva_prod = "N";
		xcosto = xitem.costo;
		if (xcosto != 0) {
			if (xitem.iva != 0) {
				iva_prod = "S";
				xcosto = xcosto * (100 / (xtasa_iva + 100));
			}
			pre_unit = xcosto / cant_det;
			pre_vta = xcosto / cant_det;
			sub_total = xcosto;
			//Crear array opara luego insertar en el detalle de la factura
			dinamico.push({
				id_prod: id_prod,
				nom_prod: nom_prod,
				cantidad: cant_det,
				stock: saldo,
				uni_ven_prod: uni_vta,
				ventas_prod: pre_unit,
				ventas_prod1: pre_vta,
				id_des_item: '',
				nom_des: '',
				iva_prod: iva_prod,
				total: sub_total
			});			
		}
	});
	//Cargar el detalle de la factura
	var detalleLimpio = dinamico.map(function (item, index) {
		var $row = $('#tblDetalle').DataTable().row(index).node();
		// Capturamos el valor dinámico actual del input de cantidad
		var cantidadReal = $($row).find("input[id^='cant']").val() || item.cantidad;
		return {
			id_prod: item.id_prod,
			nom_prod: item.nom_prod,
			cantidad: cantidadReal,
			stock: 0,
			uni_ven_prod: item.uni_ven_prod,
			ventas_prod: item.ventas_prod,
			ventas_prod1: item.ventas_prod1,
			id_des_item: '',
			nom_des: '',
			iva_prod: item.iva_prod,
			total: item.total
		}
	});
	table.clear();
	table.rows.add(detalleLimpio);
	table.draw();
	if (error_msg != "") {
		Swal.fire({
			icon: "error",	
			title: "Error al cargar productos",
			text: error_msg,
		});
	}
			
	$(".loader").hide();
}