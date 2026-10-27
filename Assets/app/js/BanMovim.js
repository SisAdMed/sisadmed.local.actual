/**Bancos Movimientos
 * Creado por José Vargas el 29-09-2026 a las 03:44 p.m. 
 */
$(document).ready(function () {
	//Validaciones del formulario
	$("form#my_form").validate({
		ignore: null,
		rules: {
			id_emp: "required",
			id_bantmo: "required",
			id_bancue: "required",
			fecha_comp: "required",
			num_banmov: "required",
			id_moneda: "required",
			des_banmov: "required",
			item: "required",
		},
		messages: {
			id_emp: "Debe especificar una Empresa",
			id_bantmo: "Debe especificar uan Tipo de Movimiento",
			id_bancue: "Debe especificar una Cuenta Bancaria",
			fecha_comp: "Debe indicar una Fecha",
			num_banmov: "Debe indicar un Número de Movimiento",
			id_moneda: "Debe indicar un tipo de Moneda",
			des_banmov: "Debe especificar una descripción",
			item: "Debe especificar al menos un concepto",
		},
	});
	//Cargar el index
	form = $("form").attr('id');
	if (form === undefined) {
		initBanMovim();
	} else {
		id = $("#id").val();
		if (id) {
			show_row(id)
		} else {
			dat_form_new();
		}
	}
	/**Al seleccionar la Empresa */
	$(document).on("change", "#id_emp", async function (e) {
		e.preventDefault();
		id_emp = $(this).val();
		if (id_emp) {
			$("#id_emp").val(id_emp)
			listar_tipomov_bancos();
			listar_cuentas_ban(id_emp);
			id_tdo_cfg = await tip_doc_com(id_emp);
			id_moneda_cia = id_tdo_cfg["id_moneda"];
			listar_monedas(id_moneda_cia);
			//Obtener Tasa de Cambio
			fecha_comp = GetTodayDate(0);
			$("#fecha_comp").val(fecha_comp);
			validarYObtenerTasa(id_emp, id_moneda_cia, fecha_comp);
		} else {
			dat_form_new();
		}
	})
	/**Validar el efecto del Tipo de Movimiento */
	$(document).on("change", "#id_bantmo", function (e) {
		e.preventDefault();
		var id_bantmo = $(this).val();
		var id_emp = $("#id_emp").val();
		// Si la limpian, reseteamos el concepto predeterminado
		conceptoProveedorDefault = null
		// Si deseleccionan el tipo de movimiento, limpiamos todo de una vez
		if (!id_bantmo) {
			hide_show_tabs("0"); // Ocultamos las pestañas de CXC/CXP
			return;
		}

		const url = `${base_url}/BanTipoMov/showrow`;
		$.ajax({
			url: url,
			type: "POST",
			data: { id: id_bantmo, id_emp: id_emp },
			dataType: "json",
			success: function (resultado) {
				// Validar que el resultado traiga registros
				if (!resultado || resultado.length === 0) return;

				var acc_bantmo = resultado[0]["acc_bantmo"];
				var efe_bantmo = resultado[0]["efe_bantmo"];

				efecto = efe_bantmo;

				//Obtner configuracion en caso de ser Cobranza y/o Pagos
				id_emp = $("#id_emp").val();
				cargarConfiguracionBanco(id_emp, efecto);

				if (efe_bantmo) {
					// Efecto y Movimiento de CXC y/o CXP                    
					$("#efecto").val(efe_bantmo);
					$("#cxtmo").val(resultado[0]["id_cxtmo"]);

					// Aquí ejecutas tu función para mostrar/ocultar las pestañas según 'C' o 'P'
					hide_show_tabs(efe_bantmo);

					var id_bancon_efec = resultado[0]["id_bancon"];
					var nom_bancon_efec = resultado[0]["nom_bancon"];

					var id_bancon_RETIVA = resultado[0]["id_bancon_RETIVA"];
					var nom_bancon_RETIVA = resultado[0]["nom_bancon_RETIVA"];
				} else {
					// Si no tiene efecto CXC/CXP, aseguramos ocultar las pestañas
					hide_show_tabs("0");
				}

				if (acc_bantmo == "D") {
					$(".benef_banmov").show();
				} else {
					$(".benef_banmov").hide();
				}
			},
			error: function (xhr, ajaxOptions, thrownError) {
				console.log(xhr.responseText);
			},
		});
	});
	//Eliminar registro
	$(document).on('click', '.btn-delete', function (e) {
		e.preventDefault();
		const id = this;
		const id_banmov = id.dataset.id;
		const name = id.dataset.name;
		const number = id.dataset.number;

		const title = `¿Está seguro que desea eliminar el Movimiento Bancario ${name} Número ${number}?.`;
		const fila = $(this).closest('tr');
		Swal.fire({
			title: title,
			icon: 'question',
			text: '¡No podrás revertir esta acción!',
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Si, elimiar registro!",
			cancelButtonText: "Cancelar"
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: `${base_url}/BanMovim/destroy`,
					method: 'POST',
					data: { id: id_banmov },
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							Swal.fire({
								title: "¡Eliminado!",
								text: `El Movimiento Bancario ${name} Número ${number} ha sido eliminado correctamente`,
								icon: 'success'
							}).then((result) => {
								if ($.fn.DataTable.isDataTable('#tblIndexMain')) {
									$('#tblIndexMain').DataTable().ajax.reload(null, false);
								}
							});
						} else {
							Swal.fire({
								title: "Error",
								text: response.msg || "No se pudo eliminar el registro",
								icon: error
							});
						}
					},
					error: function (xhr, status, error) {
						Swal.fire({
							title: 'Error de servidor',
							text: 'Ocurrió un problema al procesar la solicitud',
							icon: 'error'
						});
					}
				});
			}
		});
	});
	//Imprimir Movimiento
	$(document).on('click', '.btn_printer_doc', function (e) {
		let id = $(this).attr('data-id');
		let name = $(this).attr('data-name');
		let number = $(this).attr('data-code');
		Swal.fire({
			icon: "question",
			title: `¿Está seguro que desea imprimir el Movimiento Bancario ${name} número ${number}?`,
			showCancelButton: true,
			confirmButtonText: "Imprimir",
		}).then((result) => {
			if (result.isConfirmed) {
				window.open(`${base_url}/BanMovim/print_movement/` + id, "_blank");
			}
		});
	});
	//Seleccioanr proveedor
	$(document).on("change", "#id_ent", async function (e) {
		e.preventDefault();
		id_ent = $(this).val();
		if (id_ent) {
			fecha_comp = $("#fecha_comp").val();
			id_moneda = $("#id_moneda").val();
			const datosFetched = await tid_vend(id_ent);
			nom_cli = decodeHTMLEntities(datosFetched["nom_ent"]);
			$("#nom_ent").val(nom_cli);
			//Validar si se muestran los documentos pendientes
			id_config_cxp = await show_config_cxp(id_emp);
			show_doc = id_config_cxp["show_doc"];
			$("#tbody").empty();
			if (show_doc == "S" && id) {
				//Mostra los documentos cancelados
			} else if (show_doc == "S") {
				//Mostra los documentos pendientes del cliente
				//show_doc_cli(id_ent, "P");
				totalizar_tabla_mov();
			}
		}
	});
	//Nuevo detalle de documentos a cancelar
	$(".newdetail").on("click", function () {
		if (efecto == "C") {
			$("#modal_doc_pen_cxc").modal("show");
			cargar_documentos_pendientes_cxc();
		} else {
			$("#modal_doc_pen_cxp").modal("show");
			cargar_documentos_pendientes_cxp();
		}
	});
	//Guardar y/o Actualizar
	$("#my_form").on("submit", function (e) {
		e.preventDefault();
		if (!$(this).valid()) {
			return false;
		}
		const $btnOk = $("#btnok");
		// Extraer los datos del detalle de movimiento (recorriendo cada fila)
		var tblDetalleMovBan = [];
		var table = $('#tblDetalleMovBan').DataTable();
		// Usamos table.$('tr') para incluir todas las filas (incluso con paginación)
		table.$('tbody tr').each(function () {
			var $row = $(this);
			var id_bancon = $row.find("input[id^='id_bancon']").val();
			var id_aux = $row.find("input[id^='id_aux']").val();
			var monto = $row.find("input[id^='monto']").val();
			tblDetalleMovBan.push({
				id_bancon: id_bancon,
				id_aux: id_aux,
				monto: monto,
			})
		});
		// Validar que exista al menos un artículo en el detalle
		if (tblDetalleMovBan.length === 0) {
			Swal.fire('Error', 'Debe agregar al menos un detalle al Movimiento Bancario.', 'warning');
			return false;
		}
		if (efecto == 'C') {
			if(!validarComprobantesRetencion()){
				return;
			}
			// Extraer los datos del detalle de CXC
			var tblDetalleMovCxc = [];
			var table = $("#tblSeatDetail_cxc").DataTable();
			// Recorremos cada fila utilizando la API de DataTables
			table.rows().every(function (rowIdx, tableLoop, rowLoop) {
				var data = this.data(); // Objeto de datos original de la fila (contiene id_doc, tipo_codigo, etc.)
				var $node = $(this.node()); // El elemento DOM <tr> de la fila

				// Capturamos el valor actual del input de cancelar dentro de esta fila específica
				var inputMonto = $node.find('input[data-campo="mon_can"]');
				var montoCancelarStr = inputMonto.val() || '0';
				// Capturamos el valor actual del input de Retencion dentro de esta fila específica
				var inputMontoRet = $node.find('input[data-campo="mon_ret"]');
				var montoRetencionrStr = inputMontoRet.val() || '0';
				// Capturamos el valor actual del input de Número de Retencion dentro de esta fila específica
				var inputNumRet = $node.find('input[data-campo="num_ret"]');
				var NumRetrStr = inputNumRet.val() || ' ';

				// Limpiamos el formato numérico para enviarlo puro al backend
				var montoCancelarNum = parseFloat(montoCancelarStr.replace(/\./g, '').replace(',', '.')) || 0;
				var montoRetencionNum = parseFloat(montoRetencionrStr.replace(/\./g, '').replace(',', '.')) || 0;
				// Solo lo agregamos al arreglo si el monto a cancelar es mayor a 0
				if (montoCancelarNum != 0) {
					tblDetalleMovCxc.push({
						id_tdo: data.id_tdo,
						id_moneda: data.id_moneda,
						num_tdo: data.num_tdo,             // Número de documento
						id_cot: data.id_doc,
						tasa_cambio: data.tasa_cambio,
						monto: montoCancelarNum,  // Monto que el usuario decidió abonar/cancelar
						mon_ret: montoRetencionNum,
						num_ret: NumRetrStr
					});
				}
			});
			// Validar que exista al menos un artículo en el detalle
			if (tblDetalleMovCxc.length === 0) {
				Swal.fire('Error', 'Debe agregar al menos un detalle en las facturas a cancelar.', 'warning');
				return false;
			}
		} else if (efecto == 'P') {
			// Extraer los datos del detalle de CXP		
			var tblDetalleMovCxp = [];
			var table = $("#tblSeatDetail_cxp").DataTable();
			// Recorremos cada fila utilizando la API de DataTables
			table.rows().every(function (rowIdx, tableLoop, rowLoop) {
				var data = this.data(); // Objeto de datos original de la fila (contiene id_doc, tipo_codigo, etc.)
				var $node = $(this.node()); // El elemento DOM <tr> de la fila

				// Capturamos el valor actual del input de cancelar dentro de esta fila específica
				var inputMonto = $node.find('input[data-campo="mon_can"]');
				var montoCancelarStr = inputMonto.val() || '0';

				// Limpiamos el formato numérico para enviarlo puro al backend
				var montoCancelarNum = parseFloat(montoCancelarStr.replace(/\./g, '').replace(',', '.')) || 0;

				// Solo lo agregamos al arreglo si el monto a cancelar es mayor a 0
				if (montoCancelarNum != 0) {
					tblDetalleMovCxp.push({
						id_tdo: data.id_tdo,
						id_moneda: data.id_moneda,
						num_tdo: data.num_tdo,             // Número de documento
						id_cot: data.id_doc,
						tasa_cambio: data.tasa_cambio,
						monto: montoCancelarNum   // Monto que el usuario decidió abonar/cancelar
					});
				}
			});
			// Validar que exista al menos un artículo en el detalle
			if (tblDetalleMovCxp.length === 0) {
				Swal.fire('Error', 'Debe agregar al menos un detalle en las facturas a pagar.', 'warning');
				return false;
			}
		}


		var formData = new FormData(this);
		formData.append('tblDetalleMovBan', JSON.stringify(tblDetalleMovBan));
		formData.append('tblDetalleMovCxp', JSON.stringify(tblDetalleMovCxp));
		formData.append('tblDetalleMovCxc', JSON.stringify(tblDetalleMovCxc));
		const url = `${base_url}/BanMovim/store`;
		$.ajax({
			url: url,
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				$btnOk.prop("disabled", true);
				Swal.fire({
					title: 'Guardando...',
					text: `Guardando el Movimiento Bancario, por favor espere.`,
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
						window.location.href = `${base_url}/BanMovim`;
					} else {
						// Solo se reactiva el botón si el backend devolvió un error lógico
						$btnOk.prop("disabled", false);
					}
				});
			},
		});
	});
});
/**Nuevo registro */
function dat_form_new() {
	// 1. Resetear el formulario HTML nativo si existe
	var form = document.getElementById('my_form');
	if (form) {
		form.reset();
	}
	$("#id_bantmo").empty();
	$("#id_bancue").empty();
	$("#id_moneda").empty();
	listar_empresas();
	$("#fecha_comp").val(GetTodayDate(0));
	listar_status(1);
}
/**
 * Mostrar u ocultar TABS de CXC o CXP o ambas
 * @param {*} mode 
 */
function hide_show_tabs(mode = "0") {
	// Seleccionamos los contenedores <li> completos
	var tabCxc = $("#customer-bancxc-tab").parent(".nav-item");
	var tabCxp = $("#customer-bancxp-tab").parent(".nav-item");

	if (mode == "0") {
		// Ocultar y volver a colocar el atributo hidden
		tabCxc.attr("hidden", true).hide();
		tabCxp.attr("hidden", true).hide();

		// Si el usuario estaba en alguna de estas pestañas, lo regresamos a la Principal
		if ($("#customer-bancxc-tab").hasClass("active") || $("#customer-bancxp-tab").hasClass("active")) {
			$('#customer-banmovim-tab').tab('show');
		}
	} else if (mode == "C") {
		// Remover el hidden y mostrar CXC, ocultar CXP
		tabCxc.removeAttr("hidden").show();
		tabCxp.attr("hidden", true).hide();
	} else if (mode == "P") {
		// Remover el hidden y mostrar CXP, ocultar CXC
		tabCxp.removeAttr("hidden").show();
		tabCxc.attr("hidden", true).hide();
	}
}
/**
 * Mostrar el registro seleccionado
 * @param {*} id 
 */
function show_row(id) {
	url = `${base_url}/BanMovim/show_row`;
	$.ajax({
		url: url,
		method: 'POST',
		data: { id: id },
		dataType: 'json',
		beforeSend: function () {
			// Muestra una ventana de SweetAlert2 con spinner activo
			Swal.fire({
				title: 'Consultando...',
				text: `Consultando el Movimiento Bancario, por favor espere.`,
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
			//Encabezado de Movimiento
			id_emp = enca['id_emp'];
			id_bantmo = enca['id_bantmo'];
			id_bancue = enca['id_bancue'];
			id_moneda = enca['id_moneda'];
			fecha_comp = enca['fecha_comp']
			cargarConfiguracionBanco(id_emp);
			status = enca['status'];
			//
			validarYObtenerTasa(id_emp, id_moneda, fecha_comp);
			listar_empresas(id_emp, true);
			listar_tipomov_bancos(id_bantmo, true);
			listar_cuentas_ban(id_emp, id_bancue, "id_bancue");
			listar_monedas(id_moneda);
			listar_status(status);
			//			
			$("#fecha_comp").val(fecha_comp);
			$("#num_banmov").val(enca['num_banmov']);
			tasa_cambio = enca['tasa_cambio'];
			$("#tasa_cambio").val(format_number_with_dec_new(tasa_cambio, 4));
			$("#benef_banmov").val(enca['benef_banmov']);
			$("#des_banmov").val(enca['des_banmov']);
			$("#cxtmo").val(enca['id_cxtmo']);
			efecto = enca['efe_bantmo'];
			$("#efecto").val(efecto);			
			// ==========================================
			// EVALUACIÓN SEGÚN EL EFECTO (P o C)
			// ==========================================
			cargarConfiguracionBanco(id_emp, efecto);
			if (efecto == 'P') {
				$("#id_ent").val(enca['id_ent']);
				$("#id_ent").trigger('change');
				$.ajax({
					url: `${base_url}/CXPMovement/show_row_det`,
					method: 'POST',
					data: { id: id },
					dataType: 'json',
					beforeSend: function () {
						// Muestra una ventana de SweetAlert2 con spinner activo
						Swal.fire({
							title: 'Consultando...',
							text: `Consultando el Movimiento Bancario, por favor espere.`,
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
					success: function (datcxp) {
						//Cargar Detalle del Movimiento				
						total_mov = 0;
						var detalleLimpio = datcxp.map(function (item, index) {
							// Capturamos el valor dinámico actual del input de cantidad								
							// Tomamos directamente el monto_doc que ya viene convertido y guardado desde f30081
							var montoConvertido = parseFloat(item.monto_doc);
							var saldoConvertido = parseFloat(item.sal_doc);
							var mon_doc_dom = montoConvertido;
							var mon_doc_for = montoConvertido;
							var sal_doc_dom = saldoConvertido;
							var sal_doc_for = saldoConvertido;
							if (id_moneda != item.id_moneda) {
								mon_doc_dom = montoConvertido * item.tasa_cambio;
								mon_doc_for = montoConvertido * item.tasa_cambio;
								sal_doc_dom = saldoConvertido * item.tasa_cambio;
								sal_doc_for = saldoConvertido * item.tasa_cambio;
							}
							var saldoConvertido = parseFloat(item.sal_doc);
							var montoCancelar = parseFloat(item.mon_can_dom);
							total_mov += monto;
							return {
								id_doc: item.id_cot,
								tipo_codigo: item.tipo_codigo,
								id_tdo: item.id_tdo,
								nom_tdoc: item.nom_tdoc,
								num_tdo: item.num_tdo,
								fec_emi: item.fecha_emi,
								fecha_venci: item.fecha_venci,
								codigo_moneda: item.codigo_moneda,
								id_moneda: item.id_moneda,
								tasa_cambio: item.tasa_cambio,
								// Asignamos el valor ya convertido a las variables domésticas para que la tabla 
								// los muestre siempre en la moneda principal del movimiento sin alterarse
								mon_doc_dom: mon_doc_dom,
								mon_doc_for: mon_doc_for,
								sal_doc_dom: saldoConvertido,
								sal_doc_for: saldoConvertido,
								mon_can_dom: montoCancelar,
								mon_can_for: montoCancelar
							}
						});
						table_cxp.clear();
						table_cxp.rows.add(detalleLimpio);
						table_cxp.draw();
					}
				});
			} else if (efecto == 'C') {
				$("#id_cli").val(enca['id_ent']);
				$("#nom_cli").val(enca['nom_ent']);
				$(".benef_banmov").hide();
				$.ajax({
					url: `${base_url}/CXCMovement/show_row_det`,
					method: 'POST',
					data: { id: id },
					dataType: 'json',
					beforeSend: function () {
						// Muestra una ventana de SweetAlert2 con spinner activo
						Swal.fire({
							title: 'Consultando...',
							text: `Consultando el Movimiento Bancario, por favor espere.`,
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
					success: function (datcxc) {
						//Cargar Detalle del Movimiento				
						total_mov = 0;
						var detalleLimpio = datcxc.map(function (item, index) {
							// Capturamos el valor dinámico actual del input de cantidad	
							// Tomamos directamente el monto_doc que ya viene convertido y guardado desde f30081
							var montoConvertido = parseFloat(item.monto_doc);
							var saldoConvertido = parseFloat(item.sal_doc);
							if (id_moneda != item.id_moneda) {
								mon_doc_dom = montoConvertido * item.tasa_cambio;
								mon_doc_for = montoConvertido * item.tasa_cambio;
								sal_doc_dom = saldoConvertido * item.tasa_cambio;
								sal_doc_for = saldoConvertido * item.tasa_cambio;
							}
							var saldoConvertido = parseFloat(item.sal_doc);
							var montoCancelar = parseFloat(item.mont_doc_dom);
							var monRetenido = parseFloat(item.mon_ret_dom);
							total_mov += monto;
							return {
								id_doc: item.id_cot,
								tipo_codigo: item.tipo_codigo,
								id_tdo: item.id_tdo,
								nom_tdoc: item.nom_tdoc,
								num_tdo: item.num_tdo,
								fec_emi: item.fecha_emi,
								fecha_venci: item.fecha_venci,
								codigo_moneda: item.codigo_moneda,
								id_moneda: item.id_moneda,
								tasa_cambio: item.tasa_cambio,
								// Asignamos el valor ya convertido a las variables domésticas para que la tabla 
								// los muestre siempre en la moneda principal del movimiento sin alterarse
								mon_doc_dom: mon_doc_dom,
								mon_doc_for: mon_doc_for,
								sal_doc_dom: saldoConvertido,
								sal_doc_for: saldoConvertido,
								mon_can_dom: montoCancelar,
								mon_can_for: montoCancelar,
								mon_ret_dom: monRetenido,
								num_ret: item.num_ret || ''
							}
						});
						table_cxc.clear();
						table_cxc.rows.add(detalleLimpio);
						table_cxc.draw();
					}
				});
			}
			//Cargar Detalle del Movimiento				
			total_mov = 0;
			var detalleLimpio = data.map(function (item, index) {
				// Capturamos el valor dinámico actual del input de cantidad	
				if (tasa_cambio == 1) {
					monto = item.monto_nac
				} else {
					monto = item.monto_for
				}
				total_mov += monto;
				return {
					id_bancon: item.id_bancon,
					nom_bancon: item.nom_bancon,
					id_aux: item.id_aux,
					nombre_aux: item.nom_aux,
					monto_nac: monto,
				}
			});
			table.clear();
			table.rows.add(detalleLimpio);
			table.draw();
			$("#footer_total_input").val(format_number_with_dec_new(total_mov, 2));
			//Mostar información de CXP
			hide_show_tabs(efecto);

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

			console.error('Detalle del error:', serverErrorMsg);

			Swal.fire({
				title: `Error (${xhr.status || 'Desconocido'})`,
				text: serverErrorMsg,
				icon: 'error',
				confirmButtonText: 'Aceptar'
			});
		}
	});
}