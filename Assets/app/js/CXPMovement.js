//Variables
efe_bantmo = "P";
efecto = "P";
//Validar campos del formulario
$().ready(function () {
	$.validator.setDefaults({
		ignore: [],
	});
	$("#my_form").validate({
		rules: {
			id_emp: "required",
			id_tmocxp: "required",
			id_ent: "required",
			fecha_comp: "required",
			id_moneda: "required",
			movem_descrip: "required",
			status: "required",
		},
		messages: {
			id_emp: "Debe especificar una empresa",
			id_tmocxp: "Debe especificar un tipo de Movimiento",
			id_ent: "Debe especificar un Proveedor",
			fecha_comp: "Debe especificar una Fecha válida",
			id_moneda: "Debe especificar una Moneda",
			movem_descrip: "Debe especificar una Descripción",
			status: "Debe especificar un Status",
		},
	});
	//Cargar el Index
	form = $("form").attr("id");
	if (form === undefined) {
		initCXPMovement();
	} else {
		//Cuando es un registro nuevo
		id = $("#id").val();
		if (id) {
			show_row(id);
		} else {
			dat_form_new();
		}
	}
	//al seleccionar Empresa
	$("#id_emp").on("change", async function (e) {
		e.preventDefault();
		id_emp = $(this).val();
		id_tmocxp = $("#id_tmocxp");
		id_tmocxp.empty();
		$("#tbody").empty();
		if (id_emp) {
			listar_tipos_mov_CXP(0, "id_tmocxp");
			id_tdo_cfg = await tip_doc_com(id_emp);
			id_moneda_cia = id_tdo_cfg["id_moneda"];
		}
	});
	//Validar Tipo de Movimiento
	$("#id_tmocxp").on("change", async function (e) {
		id_tmocxp = $(this).val();
		$("#tbody").empty();
		const datos = new FormData();
		datos.append("id", id_tmocxp);
		$("#movem_number").prop("readonly", false);
		if (id_tmocxp) {
			try {
				const url = `${base_url}/CXPMovement/val_tmo`;
				const respuesta = await fetch(url, {
					method: "POST",
					body: datos,
				});
				const resultado = await respuesta.json();
				if (resultado) {
					if (resultado[0]["con_tmocxp"] == "N") {
						$("#movem_number").prop("readonly", false);
					} else {
						$("#movem_number").prop("readonly", true);
					}
				}
			} catch (error) {
				console.log("Ha ocurrido el siguiente error: " + error);
			}
		}
	});
	//Seleccionar proveedor
	$("#id_ent").on("change", async function (e) {
		e.preventDefault();
		id_ent = $(this).val();
		$("#tbody").empty();
		if (id_ent) {
			const datosFetched = await tid_vend(id_ent);
			nom_cli = datosFetched["nom_ent"];
			$("#nom_ent").val(nom_cli);
		}
	});
	//Nuevo detalle de documentos a cancelar
	$("#newdetail").on("click", function () {
		$("#modal_doc_pen_cxp").modal("show");
	});
	$(document).on('click', '.btn-delete-index', function (e) {
		e.preventDefault();
		const id = this;
		const id_cot = id.dataset.id;
		const code = id.dataset.code;
		const name = id.dataset.name;
		const num_tdo = id.dataset.number;
		const title = `¿Está seguro que desea eliminar el Movimiento ${code} ${name} Número ${num_tdo}?.`;
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
					url: `${base_url}/CXPMovement/destroy`,
					method: 'POST',
					data: { id: id_cot },
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							Swal.fire({
								title: "¡Eliminado!",
								text: `El Movimiento ${code} ${name} Número ${num_tdo} ha sido eliminado correctamente`,
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
	// Guardar y/o Actualizar
	$("#my_form").on("submit", function (e) {
		e.preventDefault();
		if (!$(this).valid()) {
			return false;
		}
		const $btnOk = $("#btnok");

		// 1. Extraer los datos recorriendo directamente las filas del tbody de la tabla
		var detalle = [];

		$('#tblDetalleMovCxp tbody tr').each(function () {
			var $row = $(this);

			// Si la tabla está vacía, DataTables suele poner una fila de "Ningún dato disponible". La ignoramos:
			if ($row.find('.dataTables_empty').length > 0) {
				return;
			}

			var dataRow = $('#tblDetalleMovCxp').DataTable().row($row).data();

			if (dataRow) {
				var mon_can = $row.find('input[name="mon_can[]"]').val() || '0';

				var itemDetalle = {
					id_cot: dataRow.id_cot || '',
					tipo_codigo: dataRow.tipo_codigo || '',
					nom_tdoc: dataRow.nom_tdoc || '',
					num_tdo: dataRow.num_tdo || '',
					fecha_emi: dataRow.fecha_emi || '',
					fecha_ven: dataRow.fecha_ven || '',
					id_moneda_doc: dataRow.id_moneda_doc || '',
					tasa_cambio: dataRow.tasa_cambio || '',
					monto_doc: dataRow.monto_doc || '',
					sal_doc: dataRow.sal_doc || '',
					mon_can: mon_can
				};

				detalle.push(itemDetalle);
			}
		});

		// Validar que exista al menos un artículo en el detalle
		if (detalle.length === 0) {
			Swal.fire('Error', 'Debe agregar al menos un documento válido al detalle.', 'warning');
			return false;
		}

		// 2. Empaquetamos el detalle en el formData
		var formData = $(this).serializeArray();
		formData.push({ name: 'detalle_movimiento', value: JSON.stringify(detalle) });

		const url = `${base_url}/CXPMovement/store`;
		$.ajax({
			url: url,
			type: "POST",
			data: formData,
			dataType: "json",
			beforeSend: function () {
				$btnOk.prop("disabled", true);
				Swal.fire({
					title: 'Guardando...',
					text: `Guardando el Movimiento de Cuentas por Pagar, por favor espere.`,
					allowOutsideClick: false,
					allowEscapeKey: false,
					showConfirmButton: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});
			},
			// CORREGIDO: Se reciben los parámetros correctos (xhr, textStatus, errorThrown)
			error: function (xhr, textStatus, errorThrown) {
				$btnOk.prop("disabled", false);

				let serverErrorMsg = 'No se pudo procesar la solicitud en el servidor.';

				if (xhr.responseJSON && xhr.responseJSON.msg) {
					serverErrorMsg = xhr.responseJSON.msg;
				} else if (xhr.responseText) {
					serverErrorMsg = xhr.statusText || errorThrown;
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
						window.location.href = `${base_url}/CXPMovement`;
					} else {
						$btnOk.prop("disabled", false);
					}
				});
			},
		});
	});
});
/**Nuevo Registro */
function dat_form_new() {
	listar_empresas();
	listar_status(1);
	$("#fecha_comp").val(GetTodayDate(0));
	listar_monedas();
}

/**
 * Description Consutar un Movimiento de Cuentas por Pagar
 *
 * @param {*} id Id del Movimiento
 */
function show_row(id) {
	url = `${base_url}/CXPMovement/show_row`;
	$.ajax({
		url: url,
		method: 'POST',
		data: { id: id },
		dataType: 'json',
		beforeSend: function () {
			// Muestra una ventana de SweetAlert2 con spinner activo
			Swal.fire({
				title: 'Consultando...',
				text: `Consultando el Movimiento, por favor espere.`,
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
			var enca = data[0];
			id_emp = enca['id_emp'];
			listar_empresas(id_emp, true);
			listar_tipos_mov_CXP(enca['id_tmocxp'], 'id_tmocxp', true);
			$("#movem_number").val(enca['movem_number']).attr('readonly', true);
			$("#id_ent").val(enca['id_cli']);
			$("#nom_ent").val(enca['nom_ent']);
			$("#fecha_comp").val(enca['fecha_comp']);
			listar_monedas(enca['id_moneda'], true);
			$("#tasa_cambio").val(format_number_with_dec_new(enca['tasa_cambio'], 4)).attr('readonly', true);
			$("#movem_descrip").val(enca['movem_descrip']);
			listar_status(enca['status']);
			//Poblar Datatable de Detalle
			var detalleLimpio = data.map(function (item, index) {
				return {
					id_cot: item.id_cot,
					tipo_codigo: item.tipo_codigo,
					nom_tdoc: item.nom_tdoc,
					num_tdo: item.num_tdo,
					fecha_emi: item.fecha_emi,
					fecha_ven: item.fecha_venci,
					id_moneda_doc: item.codigo_moneda,
					tasa_cambio: format_number_with_dec_new(item.tasa_cambio, 4),
					monto_doc: format_number_with_dec_new(item.mon_doc, 2),
					sal_doc: format_number_with_dec_new(item.sal_doc, 2),
					mon_can: format_number_with_dec_new(item.monto_doc, 2),
				}
			});
			table.clear();
			table.rows.add(detalleLimpio);
			table.draw();
			//Total items
			$("#item").val(detalleLimpio.length);
			// 2. LLAMADA DIRECTA: Ejecutamos tu función de validación de inmediato asegurando que los datos ya están en pantalla
			if (typeof validarYObtenerTasa === 'function') {
				validarYObtenerTasa(enca['id_emp'], enca['id_moneda'], enca['fecha_comp']);
			} else {
				console.error("La función validarYObtenerTasa no está definida o accesible en este ámbito.");
			}
		}
	});
}

/**
 * Description Imprimir Movimiento de Cuentas por Pagar
 *
 * @param {*} e 
 */
function print_mov(e) {
	id = e.dataset.id;
	tipo = e.dataset.tipo;
	des = e.dataset.name;
	number = e.dataset.code;
	const url = `${base_url}/CXPMovement/print_movement`;
	title = "¿Está seguro que desea Imprimir el Movimiento " + des + "?";
	Swal.fire({
		icon: "question",
		title: title,
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
	}).then((result) => {
		if (result.isConfirmed) {
			window.open(
				`${base_url}/CXPMovement/print_movement/` + id,
				"_blank"
			);
		}
	});
}