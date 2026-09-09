<?php
class FacturacionModel extends DB
{
    public function __construct()
    {
        parent::__construct();
    }
    static function all(string $tipo)
    {
        return $r = DB::query("SELECT c.id_cot, e.nombre_emp, t.nom_tdoc, c.num_tdo, a.nom_ent, c.fecha_comp, m.codigo_moneda, CASE WHEN c.imp_crcd = 0 THEN c.tasa_cambio ELSE c.imp_tasa_cambio END tasa_cambio, v.nom_vend, c.status, c.id_cont fuente, ap.status penapro, c.nro_control, a.print_special, c.id_moneda, CONCAT(uc.name_user, ' ', uc.last_user) creado_por, IFNULL(CONCAT(um.name_user, ' ', um.last_user), ' ') modificado_por FROM f6003 c INNER JOIN f0011 e ON e.id_emp = c.id_emp INNER JOIN f6001 t ON t.id_tdoc = c.id_tdo INNER JOIN f0014 a ON a.id_ent = c.id_cli INNER JOIN f0005 m ON m.id_moneda = c.id_moneda INNER JOIN f0016 v ON v.id_vend = c.id_vend LEFT OUTER JOIN f4008 co ON co.id_cot = SUBSTRING(c.id_cont,1,LOCATE('-', c.id_cont) - 1) LEFT OUTER JOIN f6001 td ON td.id_tdoc = co.id_tdo LEFT OUTER JOIN fgenmsg ap ON ap.id_cot = c.id_cot AND ap.status = 1 AND ap.tipo_fgenmsgcol = 1 INNER JOIN f4999 cfg ON cfg.id_emp = c.id_emp INNER JOIN f0002 uc ON uc.id_user = c.create_user LEFT OUTER JOIN f0002 um ON um.id_user = c.modify_user WHERE t.tipo_tdoc = '" . $tipo . "' ");
    }
    static function guardar(array $enca, array $deta, $row = null, int $limite)
    {
        //Validar si genero movimiento bancario
        $fac_notas = true;
        if($_POST['fac_notas'] == 'false') $fac_notas = false;
        //                
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        $next_number_flag = false;
        $next_number_flag_movin = false;
        $id_tdo  = $enca['id_tdo'];
        $num_tdo = $enca['num_tdo'];
        $fac_limit = array_chunk($deta, $limite);
        $origen = '';
        $msg = '';
        $id_alm_ent = '';
        $id_ubi_ent = '';
        $c_consig_ent = false;
        try {
            $link->beginTransaction();
            //Verificar si Usa consecutivo el Tipo de Documeto
            $sql_con_doc = "SELECT * FROM f6001 WHERE id_tdoc = :id_tdoc";
            $stmt_con_doc = $link->prepare($sql_con_doc);
            $stmt_con_doc->execute([':id_tdoc' => $id_tdo]);
            $con_doc = $stmt_con_doc->fetch(PDO::FETCH_ASSOC);
            $nom_doc = $con_doc['nom_tdoc'];
            $tipo_tdoc = $con_doc['tipo_tdoc'];
            $tipo_codigo = $con_doc['tipo_codigo'];
            $next_number = 0;
            $next_number_movin = 0;
            $next_movim_ent = 0;
            $idMov = '';
            $idMovEnt = '';
            $nro_control = 0;
            $next_number_flag_movin_ent = false;
            //Verificar si usa consecutivo
            if (!$row) {
                if ($con_doc['con_tdoc'] == "1") {
                    $next_number_flag = true;
                    $next_number = floatval($con_doc['num_tdoc']);
                }
            }
            //Verificar Confgiuración de Empresa
            $sql_emp_config = "SELECT * FROM f4999 WHERE id_emp = :id_emp";
            $stmt_emp_config = $link->prepare($sql_emp_config);
            $stmt_emp_config->execute([':id_emp' => $_POST['id_emp']]);
            $row_emp_config = $stmt_emp_config->fetch(PDO::FETCH_ASSOC);
            $id_alm = $row_emp_config['id_alm'];
            $id_ubi = $row_emp_config['id_ubi'];
            $tmov_fac_sal = '';
            $tmov_fac_ent = '';
            if ($tipo_tdoc == 'F' || $tipo_tdoc == 'N' || $tipo_tdoc == 'Z') {
                $tmov_fac_sal = $row_emp_config['tmov_fac'];
                $tmov_fac_ent = $row_emp_config['tmov_noc'];
            } else {
                $tmov_fac_sal = $row_emp_config['tmov_noc'];
                $tmov_fac_ent = $row_emp_config['tmov_fac'];
            }
            $sql_con_cli = "SELECT * FROM f0014 WHERE id_ent = :id_ent";
            $stmt_con_cli = $link->prepare($sql_con_cli);
            $stmt_con_cli->execute([':id_ent' => $_POST['id_cli']]);
            $row_con_cli = $stmt_con_cli->fetch(PDO::FETCH_ASSOC);
            $c_consig = $row_con_cli['c_consig'];
            if ($c_consig == 1) {
                $id_alm = $_POST['id_alm_sal'];
                $id_ubi = $_POST['id_ubi_sal'];
                if ($_POST['id_alm_ent'] > 0 && $_POST['id_alm_ent'] > 0) {
                    $c_consig_ent = true;
                    $id_alm_ent = $_POST['id_alm_ent'];
                    $id_ubi_ent = $_POST['id_ubi_ent'];
                    if (empty($tmov_fac_ent)) {
                        $tmov_fac_ent = $row_emp_config['tmov_noc'];
                    }
                }
            }
            //Verificar si usa consecutivo el Tipo de Movimiento de Salida
            $sql_con_mov = "SELECT * FROM f4006 WHERE id_tmoinv = :id_tmoinv";
            $stmt_con_mov = $link->prepare($sql_con_mov);
            $stmt_con_mov->execute([':id_tmoinv' => $tmov_fac_sal]);
            $con_mov = $stmt_con_mov->fetch(PDO::FETCH_ASSOC);
            $proximo_tmoinv = $con_mov['proximo_tmoinv'];
            $next_movim = 0;
            //Verificar si usa consecutivo
            if (!$row) {
                if ($con_mov['consecutiv__tmoinv'] == "1") {
                    $next_number_flag_movin = true;
                    $next_number_movin = floatval($con_mov['proximo_tmoinv']);
                }
            }
            //Verificar si usa consecutivo el Tipo de Movimiento de Entrada
            if ($c_consig_ent) {
                $sql_con_mov_ent = "SELECT * FROM f4006 WHERE id_tmoinv = :id_tmoinv";
                $stmt_con_mov_ent = $link->prepare($sql_con_mov_ent);
                $stmt_con_mov_ent->execute([':id_tmoinv' => $tmov_fac_ent]);
                $con_mov_ent = $stmt_con_mov_ent->fetch(PDO::FETCH_ASSOC);
                $proximo_tmoinv_ent = $con_mov_ent['proximo_tmoinv'];
                $next_movim_ent = 0;
                //Verificar si usa consecutivo
                if (!$row) {
                    if ($con_mov_ent['consecutiv__tmoinv'] == "1") {
                        $next_number_flag_movin_ent = true;
                        $next_movim_ent = floatval($con_mov_ent['proximo_tmoinv']);
                    }
                }
            }
            //Asignar Nro de Control                 
            if (!$row && ($tipo_tdoc == 'F' || $tipo_tdoc == 'N' || $tipo_tdoc == 'C')) {
                $sql_ctrl = "SELECT next_nroControl FROM f6007 WHERE id_emp = :id_emp AND status = 1 LIMIT 1";
                $stmt_ctrl = $link->prepare($sql_ctrl);
                $stmt_ctrl->execute([':id_emp' => $_POST['id_emp']]);
                $nro_control = floatval($stmt_ctrl->fetchColumn());
            }
            foreach ($fac_limit as $index => $document) {
                //Numero de Documento
                if ($next_number_flag) {
                    $next_number = $next_number + $index;
                    $enca['num_tdo'] = $next_number;
                }
                if (empty($row)) {
                    $msg .= "Se ha guardado satisfactoriamente la " . $nom_doc . " Nro. " . $next_number . PHP_EOL;
                } else {
                    $next_number = $enca['num_tdo'];
                    $msg = "Se ha actualizado satisfactoriamente la " . $nom_doc . " Nro. " . $next_number  . PHP_EOL;
                }
                if (!$row && ($tipo_tdoc == 'F' || $tipo_tdoc == 'N' || $tipo_tdoc == 'C')) {
                    $nro_control = $nro_control + $index;
                }
                if ($tipo_tdoc == 'F') {
                    $origen = 'FAC-' . $tipo_codigo . '-' . $_POST['id_emp'] . '-' . $next_number;
                    $enca['nro_control'] = $nro_control;
                } else if ($tipo_tdoc == 'N') {
                    $origen = 'NOL-' . $tipo_codigo . '-' . $_POST['id_emp'] . '-' . $next_number;
                    $enca['nro_control'] = $nro_control;
                } else if ($tipo_tdoc == 'Z') {
                    $origen = 'NOF-' . $tipo_codigo . '-' . $_POST['id_emp'] . '-' . $next_number;
                } else {
                    $origen = 'FNC-' . $tipo_codigo . '-' . $_POST['id_emp'] . '-' . $next_number;
                    $enca['nro_control'] = $nro_control;
                }
                //Numero de Movimiento de Inventario de Salida del Almacen
                if ($next_number_flag_movin) {
                    $next_number_movin = $next_number_movin + $index;
                }
                //Numero de Movimiento de Inventario de Entrada al Almacen de Consignado
                if ($c_consig_ent && $next_number_flag_movin_ent) {
                    $next_movim_ent = $next_movim_ent + $index;
                }
                $cols = "";
                $placeholders = "";
                $params = [];
                // =======================================================================
                // PASO 1: INSERTAR Y/O ACTUALIZAR EN EL MAESTRO (ENCABEZADO DE DOCUMENTOS)
                // ENCABEZADO
                // =======================================================================                           
                if (empty($row)) {
                    // 1. Obtener nombres de columnas
                    $cols = array_keys($enca);
                    // 2. Crear placeholders (:campo1, :campo2, ...)
                    $placeholders = array_map(function ($col) {
                        return ":{$col}";
                    }, $cols);

                    // 3. Llenar el array de parámetros sin los dos puntos en la clave
                    foreach ($enca as $key => $value) {
                        $params[$key] = $value;
                    }
                    // 4. Armar la sentencia SQL
                    $colsStr         = implode(", ", $cols);
                    $placeholdersStr = implode(", ", $placeholders);
                    $sql             = "INSERT INTO f6003 ({$colsStr}) VALUES ({$placeholdersStr})";
                    $stmt = $link->prepare($sql);
                    $stmt->execute($params);
                    $idNE = $link->lastInsertId();
                    //Creaer encabezado de Movimiento de Inventario                      
                    if ($fac_notas === false) {                           
                        $sql_mov_enca = "INSERT INTO f4009 (id_emp, id_tmovinv, num_movinv, fecha_comp, id_moneda, tasa_cambio, id_alm, descrip_movinv, status, id_cot, id_cli, id_vend, id_fab, origen, create_user, create_date) VALUES (:id_emp, :id_tmovinv, :num_movinv, :fecha_comp, :id_moneda, :tasa_cambio, :id_alm, :descrip_movinv, :status, :id_cot, :id_cli, :id_vend, :id_fab, :origen, :create_user, :create_date)";
                        $stmt_det = $link->prepare($sql_mov_enca);
                        $params = [
                            ':id_emp' => $_POST['id_emp'],
                            ':id_tmovinv' => $tmov_fac_sal,
                            ':num_movinv' => $next_number_movin,
                            ':fecha_comp' => $_POST['fecha_comp'],
                            ':id_moneda' => $_POST['id_moneda'],
                            ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                            ':id_alm' => $id_alm,
                            ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_doc . ' ' . $origen,
                            ':status' => 1,
                            ':id_cot' => $idNE,
                            ':id_cli' => $_POST['id_cli'],
                            ':id_vend' => $_POST['id_vend'],
                            ':id_fab' => null,
                            ':origen' => $origen,
                            ':create_user' => $_SESSION['id_user'],
                            ':create_date' => getAuditoria(),
                        ];

                        $stmt_det->execute($params);
                        $stmt_det->closeCursor();
                        $idMov = $link->lastInsertId();
                        //Crear Movimiento de Entrada al Almacen de Consignado si es consignado y entrega a almacen de consignado
                        if ($c_consig_ent) {
                            $params = [
                                ':id_emp' => $_POST['id_emp'],
                                ':id_tmovinv' => $tmov_fac_ent,
                                ':num_movinv' => $next_movim_ent,
                                ':fecha_comp' => $_POST['fecha_comp'],
                                ':id_moneda' => $_POST['id_moneda'],
                                ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                                ':id_alm' => $id_alm_ent,
                                ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_doc . ' ' . $origen,
                                ':status' => 1,
                                ':id_cot' => $idNE,
                                ':id_cli' => $_POST['id_cli'],
                                ':id_vend' => $_POST['id_vend'],
                                ':id_fab' => null,
                                ':origen' => $origen,
                                ':create_user' => $_SESSION['id_user'],
                                ':create_date' => getAuditoria(),
                            ];
                            $stmt_det->execute($params);
                            $stmt_det->closeCursor();
                            $idMovEnt = $link->lastInsertId();
                        }
                    }
                } else {
                    // Para el UPDATE de Documento
                    $idNE = $row;
                    $setClauses = [];
                    foreach ($enca as $key => $value) {
                        if ($key !== 'id_cot') { // Excluir la llave primaria del SET
                            $setClauses[] = "{$key} = :{$key}";
                            $params[$key] = $value;
                        }
                    }
                    // Parámetro para la condición WHERE
                    $params['id_cot'] = $row;
                    $sql = "UPDATE f6003 SET " . implode(", ", $setClauses) . " WHERE id_cot = :id_cot";
                    $stmt = $link->prepare($sql);
                    $stmt->execute($params);
                    // para el UPDATE del Movimiento del Inventario
                    // Buscar el ID del Movimiento generado
                    if ($fac_notas === false) {
                        $sql_mov_gen = "SELECT id_movinv FROM f4009 WHERE id_cot = :id_cot";
                        $stmt_mov_gen = $link->prepare($sql_mov_gen);
                        $stmt_mov_gen->execute([':id_cot' => $idNE]);
                        $idMov = $stmt_mov_gen->fetchColumn();
                        $sql_mov_enca = "UPDATE f4009 SET id_emp = :id_emp, id_tmovinv = :id_tmovinv, fecha_comp = :fecha_comp, id_moneda = :id_moneda, tasa_cambio = :tasa_cambio, id_alm = :id_alm, descrip_movinv = :descrip_movinv, status = :status, id_cot = :id_cot, id_cli = :id_cli, id_vend = :id_vend, id_fab = :id_fab, origen = :origen, modify_user = :modify_user, modify_date = :modify_date WHERE id_movinv = :id_movinv";
                        $stmt_det = $link->prepare($sql_mov_enca);
                        $params = [
                            ':id_emp' => $_POST['id_emp'],
                            ':id_tmovinv' => $tmov_fac_sal,
                            ':fecha_comp' => $_POST['fecha_comp'],
                            ':id_moneda' => $_POST['id_moneda'],
                            ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                            ':id_alm' => $id_alm,
                            ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_doc . ' ' . $origen,
                            ':status' => 1,
                            ':id_cot' => $idNE,
                            ':id_cli' => $_POST['id_cli'],
                            ':id_vend' => $_POST['id_vend'],
                            ':id_fab' => null,
                            ':origen' => $origen,
                            ':modify_user' => $_SESSION['id_user'],
                            ':modify_date' => getAuditoria(),
                            ':id_movinv' => $idMov
                        ];
                        $stmt_det->execute($params);
                        //Borrar Detalle
                        $sql_del_det = "DELETE FROM f40091 WHERE id_movinv = :id_movinv";
                        $stmt_del_det = $link->prepare($sql_del_det);
                        $stmt_del_det->execute([':id_movinv' => $idMov]);
                        $stmt_del_det->closeCursor();
                        //Modificar Movimiento de Entrada al Almacen de Consignado si es consignado y entrega a almacen de consignado
                        if ($c_consig_ent) {
                            // Buscar el ID del Movimiento generado
                            $sql_mov_gen_ent = "SELECT id_movinv FROM f4009 WHERE id_cot = :id_cot AND id_tmovinv = :id_tmovinv";
                            $stmt_mov_gen_ent = $link->prepare($sql_mov_gen_ent);
                            $stmt_mov_gen_ent->execute([':id_cot' => $idNE, ':id_tmovinv' => $tmov_fac_ent]);
                            $idMovEnt = $stmt_mov_gen_ent->fetchColumn();
                            $sql_mov_enca_ent = "UPDATE f4009 SET id_emp = :id_emp, id_tmovinv = :id_tmovinv, fecha_comp = :fecha_comp, id_moneda = :id_moneda, tasa_cambio = :tasa_cambio, id_alm = :id_alm, descrip_movinv = :descrip_movinv, status = :status, id_cot = :id_cot, id_cli = :id_cli, id_vend = :id_vend, id_fab = :id_fab, origen = :origen, modify_user = :modify_user, modify_date = :modify_date WHERE id_movinv = :id_movinv";
                            $stmt_det_ent = $link->prepare($sql_mov_enca_ent);
                            $params_ent = [
                                ':id_emp' => $_POST['id_emp'],
                                ':id_tmovinv' => $tmov_fac_ent,
                                ':fecha_comp' => $_POST['fecha_comp'],
                                ':id_moneda' => $_POST['id_moneda'],
                                ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                                ':id_alm' => $id_alm_ent,
                                ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_doc . ' ' . $origen,
                                ':status' => 1,
                                ':id_cot' => $idNE,
                                ':id_cli' => $_POST['id_cli'],
                                ':id_vend' => $_POST['id_vend'],
                                ':id_fab' => null,
                                ':origen' => $origen,
                                ':modify_user' => $_SESSION['id_user'],
                                ':modify_date' => getAuditoria(),
                                ':id_movinv' => $idMovEnt
                            ];
                            $stmt_det_ent->execute($params_ent);
                            //Borrar Detalle
                            $sql_del_det = "DELETE FROM f40091 WHERE id_movinv = :id_movinv";
                            $stmt_del_det = $link->prepare($sql_del_det);
                            $stmt_del_det->execute([':id_movinv' => $idMovEnt]);
                            $stmt_del_det->closeCursor();
                        }
                    }
                }
                // RELLENAR EL CAMPO EN EL DETALLE
                // Usamos el signo "&" antes de $fila para modificar el array original directamente            
                if (empty($row)) {
                    if (is_array($deta)) {
                        foreach ($deta as &$fila) {
                            if (is_array($fila)) {
                                $fila['id_cot'] = $idNE;
                            } elseif (is_object($fila)) {
                                $fila->id_cot = $idNE;
                            }
                        }
                        unset($fila); // Rompemos la referencia por seguridad
                    }
                }
                // =======================================================================
                // PASO 2: INSERTAR Y/O ACTUALIZAR EN EL MAESTRO (DETALLE DE DOCUMENTOS)
                // DETALLE
                // =======================================================================    
                //Eliminar detalle en caso de que exista
                $params = [];
                //Detalle de productos
                $sql_del_1 = "DELETE FROM f60031 WHERE id_cot = :id_cot";
                $stmt_del_1 = $link->prepare($sql_del_1);
                $stmt_del_1->execute([':id_cot' => $idNE]);
                //Detalle de Cuentas por Cobrar
                $sql_del_2 = "DELETE FROM f60032 WHERE id_cot = :id_cot";
                $stmt_del_2 = $link->prepare($sql_del_2);
                $stmt_del_2->execute([':id_cot' => $idNE]);
                //Gaurdar detalle de productos
                foreach ($document as $detalle) {
                    $cols = "";
                    $placeholders = "";
                    $params = [];
                    $cols = array_keys($detalle);
                    // 2. Crear placeholders (:campo1, :campo2, ...)
                    $placeholders = array_map(function ($col) {
                        return ":{$col}";
                    }, $cols);
                    // 4. Armar la sentencia SQL                    
                    $colsStr         = implode(", ", $cols);
                    $placeholdersStr = implode(", ", $placeholders);
                    $sql_det             = "INSERT INTO f60031 ({$colsStr}) VALUES ({$placeholdersStr})";
                    $stmt_det = $link->prepare($sql_det);
                    $params = [
                        ':id_cot' => $idNE,
                        ':id_prod' => $detalle['id_prod'],
                        ':can_det' => $detalle['can_det'],
                        ':uni_vta' => $detalle['uni_vta'],
                        ':pre_unit'     => $detalle['pre_unit'],
                        ':pre_vta' => $detalle['pre_vta'],
                        ':iva_prod' => $detalle['iva_prod'],
                        ':sub_total' => $detalle['sub_total'],
                        ':mon_iva' => $detalle['mon_iva'],
                        ':tota_prod' => $detalle['tota_prod'],
                        ':create_user' => $_SESSION['id_user'],
                        ':create_date' => getAuditoria(),
                        ':id_des' => '',
                        ':utilidad' => $detalle['utilidad'],
                        ':adicional' => $detalle['adicional'],
                        ':costo' => $detalle['adicional'],
                        ':dif_cambio' => $detalle['dif_cambio']
                    ];
                    $stmt_det->execute($params);
                    //Guardar Detalle de Movimientos de Inventarios                       
                    if ($fac_notas === false) {
                        $sql_mov_det = "INSERT f40091 (id_movinv, id_prod, id_ubi, cantidad, costo, flete, otros_cargos, door_cargos, costo1, lote, fec_venc, create_user, create_date) VALUES (:id_movinv, :id_prod, :id_ubi, :cantidad, :costo, :flete, :otros_cargos, :door_cargos, :costo1, :lote, :fec_venc, :create_user, :create_date)";
                        $stmt_mov_det = $link->prepare($sql_mov_det);
                        $sql_id_prod = "SELECT costo_prod costo, flete_prod flete, otros_prod otros_cargos, door_costo door_cargos, costo1 FROM f4005 WHERE id_prod = :id_prod";
                        $stmt_id_prod = $link->prepare($sql_id_prod);
                        $stmt_id_prod->execute([':id_prod' => $detalle['id_prod']]);
                        $row_id_prod = $stmt_id_prod->fetch(PDO::FETCH_ASSOC);
                        $params = [
                            ':id_movinv' => $idMov,
                            ':id_prod' => $detalle['id_prod'],
                            ':id_ubi' => $id_ubi,
                            ':cantidad' => $detalle['can_det'],
                            ':costo' => $row_id_prod['costo'],
                            ':flete' => $row_id_prod['flete'],
                            ':otros_cargos' => $row_id_prod['otros_cargos'],
                            ':door_cargos' => $row_id_prod['door_cargos'],
                            ':costo1' => $row_id_prod['costo1'],
                            ':lote' => 'SL',
                            ':fec_venc' => '0000-00-00',
                            ':create_user' => $_SESSION['id_user'],
                            ':create_date' => getAuditoria()
                        ];
                        $stmt_mov_det->execute($params);
                        $stmt_mov_det->closeCursor();
                        //Guardar Detalle de Movimientos de Inventarios de Entrada al Almacen de Consignado si es consignado y entrega a almacen de consignado
                        if ($c_consig_ent) {
                            $params = [
                                ':id_movinv' => $idMovEnt,
                                ':id_prod' => $detalle['id_prod'],
                                ':id_ubi' => $id_ubi_ent,
                                ':cantidad' => $detalle['can_det'],
                                ':costo' => $row_id_prod['costo'],
                                ':flete' => $row_id_prod['flete'],
                                ':otros_cargos' => $row_id_prod['otros_cargos'],
                                ':door_cargos' => $row_id_prod['door_cargos'],
                                ':costo1' => $row_id_prod['costo1'],
                                ':lote' => 'SL',
                                ':fec_venc' => '0000-00-00',
                                ':create_user' => $_SESSION['id_user'],
                                ':create_date' => getAuditoria()
                            ];
                            $stmt_mov_det->execute($params);
                            $stmt_mov_det->closeCursor();
                        }
                    }
                }
                // =======================================================================
                // PASO 3: INSERTAR Y/O ACTUALIZAR EN EL MAESTRO (DETALLE DE DOCUMENTOS CONCEPTOS)
                // DETALLE DE CONCEPTOS
                // =======================================================================  
                if ($tipo_tdoc == 'F') {
                    $sql_con_ventas = "SELECT a.id_con_sales, b.id_ctbaux FROM f4999 a INNER JOIN f6002 b ON b.id_emp = a.id_emp and a.id_con_sales = b.id WHERE a.id_emp = :id_emp AND a.status = 1";
                    $stmt_con_ventas = $link->prepare($sql_con_ventas);
                    $stmt_con_ventas->execute(['id_emp' => $_POST['id_emp']]);
                    $id_con = $stmt_con_ventas->fetchAll(PDO::FETCH_ASSOC);
                    //Detalle de Ventas
                    $sql_ventas = "SELECT a.id_cot, a.iva_prod, SUM(a.sub_total) monto, SUM(a.mon_iva) mon_iva FROM f60031 a WHERE a.id_cot = :id_cot GROUP BY a.id_cot, a.iva_prod";
                    $stmt_det_ventas = $link->prepare($sql_ventas);
                    $stmt_det_ventas->execute([':id_cot' => $idNE]);
                    $det_ventas = $stmt_det_ventas->fetchAll(PDO::FETCH_ASSOC);
                    $sql_det_ventas = "INSERT INTO f60032 (id_cot, id_concxc, iva, id_aux, monto, mon_iva, create_user, create_date) VALUES (:id_cot, :id_concxc, :iva, :id_aux, :monto, :mon_iva, :create_user, :create_date)";
                    $stmt_det_ins = $link->prepare($sql_det_ventas);
                    foreach ($det_ventas as $ventas) {;
                        $params = [
                            ':id_cot' => $idNE,
                            ':id_concxc' => $id_con[0]['id_con_sales'],
                            ':iva' => $ventas['iva_prod'],
                            ':id_aux' => $id_con[0]['id_ctbaux'],
                            ':monto' => $ventas['monto'],
                            ':mon_iva' => $ventas['mon_iva'],
                            ':create_user' => $_SESSION['id_user'],
                            ':create_date' => getAuditoria()
                        ];
                        $stmt_det_ins->execute($params);
                    }
                }
            }
            //Actualizar proximo numero del Documento
            if ($next_number_flag) {
                $sql_upd_doc = "UPDATE f6001 SET num_tdoc = :num_tdoc, modify_user = :modify_user, create_date = :create_date WHERE id_tdoc = :id_tdoc";
                $stmt_upd_con = $link->prepare($sql_upd_doc);
                $stmt_upd_con->execute([
                    ':num_tdoc' => $next_number + 1,
                    ':modify_user' => $_SESSION['id_user'],
                    ':create_date' => getAuditoria(),
                    ':id_tdoc' => $id_tdo
                ]);
            }
            //Actualizar proximo numero del Tipo de Movimiento de Salida
            if ($next_number_flag_movin && $fac_notas === false) {
                $sql_upd_mov = "UPDATE f4006 SET proximo_tmoinv = :proximo_tmoinv, modify_user = :modify_user, create_date = :create_date WHERE id_tmoinv = :id_tmoinv";
                $stmt_upd_mov = $link->prepare($sql_upd_mov);
                $stmt_upd_mov->execute([
                    ':proximo_tmoinv' => $next_number_movin + 1,
                    ':modify_user' => $_SESSION['id_user'],
                    ':create_date' => getAuditoria(),
                    ':id_tmoinv' => $tmov_fac_sal
                ]);
            }
            //Actualizar proximo numero del Tipo de Movimiento de Entrada si es consignado y entrega a almacen de consignado
            if ($c_consig_ent && $next_number_flag_movin_ent  && $fac_notas === false) {
                $sql_upd_mov_ent = "UPDATE f4006 SET proximo_tmoinv = :proximo_tmoinv, modify_user = :modify_user, create_date = :create_date WHERE id_tmoinv = :id_tmoinv";
                $stmt_upd_mov_ent = $link->prepare($sql_upd_mov_ent);
                $stmt_upd_mov_ent->execute([
                    ':proximo_tmoinv' => $next_movim_ent + 1,
                    ':modify_user' => $_SESSION['id_user'],
                    ':create_date' => getAuditoria(),
                    ':id_tmoinv' => $tmov_fac_ent
                ]);
            }
            //Actualizar proximo Número de Control
            if (!$row && ($tipo_tdoc == 'F' || $tipo_tdoc == 'N' || $tipo_tdoc == 'C')) {
                $sql_upd_ctrl = "UPDATE f6007 SET next_nroControl = :next_nroControl WHERE id_emp = :id_emp";
                $stmt_upd_ctrl = $link->prepare($sql_upd_ctrl);
                $stmt_upd_ctrl->execute([
                    ':id_emp' => $_POST['id_emp'],
                    ':next_nroControl' => $nro_control + 1
                ]);
            }

            $link->commit();
            $title = 'Registro guardado satisfactoriamente';
            return [
                "title" => $title,
                "icon" => 'success',
                "msg" => $msg
            ];
        } catch (\PDOException $e) {
            $link->rollback();
            debug($e->getMessage());
            return [
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'])
            ];
        }
    }
    static function actualizar($id, $data)
    {
        return $r = DB::update('f6003', $data, ['id_cot' => $id]);
    }
    static function nextNumber($id_emp, $id_tdoc)
    {
        $nextNumber = DB::query("SELECT * FROM f6001 WHERE id_emp = {$id_emp} AND id_tdoc = {$id_tdoc} LIMIT 1");
        return $nextNumber[0];
    }
    static function setNextNumber($id_emp, $id_tdoc, $data)
    {
        return $r = DB::update("f6001", $data, ['id_emp' => $id_emp, 'id_tdoc' => $id_tdoc]);
    }
    static function borrarDetfactura($id_cot)
    {
        return $id = DB::query("DELETE FROM f60031 WHERE id_cot = {$id_cot}");
    }
    static function selectEncyDetmovinv($id)
    {
        return $r = DB::query("SELECT c.id_movinv FROM f6003 a INNER JOIN f6001 b ON b.id_tdoc = a.id_tdo INNER JOIN f4009 c ON c.origen = CONCAT(a.id_cont, '-', b.tipo_codigo, '-', a.id_emp, '-', a.num_tdo ) WHERE a.id_cot = {$id}");
    }
    static function borrarEncyDetmovinv($id)
    {
        $r = DB::delete('f40091', ['id_movinv' => $id], 1000);
        return $r = DB::delete('f4009', ['id_movinv' => $id], 1000);
    }
    static function guardarDetfactura($data)
    {
        return $id = DB::insert('f60031', $data);
    }
    static function guardarDetfactura_CXC($data)
    {
        return $id = DB::insert('f60032', $data);
    }
    static function edit(int $id)
    {
        $r = DB::query("SELECT c.id_cot id_cot, c.id_emp id_emp, c.id_tdo id_tdo, c.num_tdo num_tdo, c.id_cli id_cli, c.fecha_comp fecha_comp, c.id_moneda id_moneda, c.tasa_cambio tasa_cambio, c.id_vend id_vend, e.nombre_emp nombre_emp, en.nom_ent nom_ent, concat(v.nom_vend,' ',v.ape_vend) vendedor, dc.id_prod id_prod, dc.can_det cantidad, dc.uni_vta uni_vta, dc.pre_unit pre_unit, dc.pre_vta pre_vta, dc.iva_prod iva_prod, dc.sub_total sub_total, dc.mon_iva mon_iva, dc.tota_prod tota_prod, pr.cod_prod cod_prod, c.nro_control  FROM f6003 c INNER JOIN f0011 e ON e.id_emp = c.id_emp INNER JOIN f0014 en ON en.id_ent = c.id_cli INNER JOIN f0005 m ON m.id_moneda = c.id_moneda INNER JOIN f0016 v ON v.id_vend = c.id_vend INNER JOIN f6001 t ON t.id_tdoc = c.id_tdo INNER JOIN f60031 dc ON dc.id_cot = c.id_cot INNER JOIN f4005 pr ON pr.id_prod = dc.id_prod WHERE c.id_cot = {$id}");
        return $r[0];
    }
    static function edit_deta(int $id, string $tipo)
    {
        $x = DB::query("SELECT GROUP_CONCAT(id_alm) id_alm FROM f4999;");
        $id_alm = $x[0]['id_alm'];
        if ($tipo === 'NOF') {
            $xtipo = 'NOF';
        } else if ($tipo == 'NOL') {
            $xtipo = 'NOL';
        } else {
            $xtipo = 'NOF';
        }
        $sql = "SELECT a.id_emp, a.id_tdo, a.num_tdo, a.fecha_comp, a.fecha_venci, a.id_cli, d.nom_ent, a.id_moneda, a.tasa_cambio, a.id_vend, e.id_prod, f.nom_prod, e.can_det cant, e.uni_vta, e.pre_unit, e.pre_vta,  e.iva_prod, e.sub_total, a.nro_control, a.oc_cliente, fn_alm_in_out(a.id_emp, 'S', c.tipo_codigo, '$xtipo', a.num_tdo) alm_out, fn_alm_in_out(a.id_emp, 'E', c.tipo_codigo, '$xtipo', a.num_tdo) alm_in, fn_saldo_act_inv(a.id_emp, f.id_prod, IFNULL( fn_alm_in_out(a.id_emp, 'S', c.tipo_codigo, '$xtipo', a.num_tdo),'$id_alm'), a.fecha_comp) stock, a.descrip_cot, d.c_consig, a.id_motcam,   IFNULL(a.id_motcam, g.id_motcam) id_motcam, CASE WHEN a.adic_01 = 0 THEN g.adic_01 ELSE a.adic_01 END adic_01, CASE WHEN a.adic_02 = 0 THEN g.adic_02 ELSE a.adic_02 END adic_02 FROM f6003 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f6001 c ON c.id_tdoc = a.id_tdo INNER JOIN f0014 d ON d.id_ent = a.id_cli INNER JOIN f60031 e ON e.id_cot = a.id_cot INNER JOIN f4005 f ON f.id_prod = e.id_prod INNER JOIN f0012a g ON g.id_motcam = d.id_motcam WHERE a.id_cot = {$id}";
        return $r = DB::query($sql);
    }
    static function consulta_adic01($id_emp, $fecha_precio)
    {
        $r = DB::query("SELECT * FROM f0006 p INNER JOIN f0011 e ON e.id_emp = p.id_emp WHERE p.id_emp = {$id_emp} AND fecha_precio <= '" . $fecha_precio . "' ORDER BY fecha_precio DESC LIMIT 1");
        return $r[0];
    }
    static function consulta_adic02($id_cli)
    {
        $r = DB::query("SELECT m.adic_01, m.adic_02 FROM f0014 e INNER JOIN f0012a m ON m.id_motcam = e.id_motcam WHERE e.id_ent = {$id_cli} LIMIT 1");
        return $r[0];
    }
    static function print_factura($id_cot)
    {
        $r = DB::query("SELECT en.id_ent, pro.lote_prod, c.id_cot, IFNULL(en.print_lote, 0) print_lote, c.id_emp, em.nombre_emp nombre_emp, em.rif_empresa rif_empresa, em.dir_emp dir_emp, em.tel_emp tel_emp, em.email_emp email_emp, c.num_tdo num_tdo, tdo.nom_tdoc nom_tdoc, c.fecha_comp fecha_comp, en.nom_ent nom_ent, en.rif_ent rif_ent, en.dir_ent dir_ent, en.postal_ent postal_ent, pa.nombre_pais nombre_pais, es.nombre_edo nombre_edo, ci.nombre_ciudad nombre_ciudad, ve.nom_vend nom_vend, ve.ape_vend ape_vend, pro.cod_prod cod_prod, pro.cod2_prod cod2_prod, pro.nom_prod nom_prod, dc.iva_prod iva_prod, dc.can_det can_det, dc.pre_vta pre_vta, dc.sub_total sub_total, c.id_cot id_cot, em.logo logo, moc.codigo_moneda codigo_moneda, moe.codigo_moneda moneda_emp, CASE WHEN c.imp_crcd = 0 THEN c.tasa_cambio ELSE c.imp_tasa_cambio END tasa_cambio, cfg.id_tdoc_fac, cfg.note_fac, cfg.id_tdoc_cre, cfg.note_cre, cfg.id_tdoc_pre, cfg.note_pre, cfg.id_tdoc_not, cfg.note_not, cfg.id_tdoc_dev, cfg.note_dev, en.note_fac note_fac_custom, fab.nom_fab, pro.ref_prod, IFNULL(diascre.des_diascre, 'CONTADO') diascre, c.oc_cliente, c.descrip_cot, dc.id_prod, CASE WHEN c.id_doc_afec IS NOT NULL THEN CONCAT('Documento Afectado: ', tdoda.tipo_codigo, ' ', tdoda.nom_tdoc, ' ', da.num_tdo, ' de fecha ', DATE_FORMAT(da.fecha_comp, '%d-%m-%Y'), ' por un monto de ', FORMAT(da.mon_doc,2, 'de_DE')) ELSE ' ' END doc_afe, fab_fac.nom_fab nom_fab_fac FROM f6003 c INNER JOIN f6001 tdo ON tdo.id_tdoc = c.id_tdo INNER JOIN f60031 dc ON dc.id_cot = c.id_cot INNER JOIN f0011 em ON em.id_emp = c.id_emp INNER JOIN f0014 en ON en.id_ent = c.id_cli INNER JOIN f0004 pa ON pa.id_pais = en.id_pais INNER JOIN f00041 es ON es.id_edo = en.id_edo INNER JOIN f00042 ci ON ci.id_ciudad = en.id_ciudad INNER JOIN f0016 ve ON ve.id_vend = en.id_vend INNER JOIN f4005 pro ON pro.id_prod = dc.id_prod INNER JOIN f0005 moc ON moc.id_moneda = c.id_moneda INNER JOIN f0005 moe ON moe.id_moneda = em.id_moneda INNER JOIN f4003 fab ON fab.id_fab = pro.id_fab INNER JOIN f4999 cfg ON cfg.id_emp = c.id_emp LEFT OUTER JOIN f6005 diascre ON diascre.id_diascre = en.id_diascre LEFT OUTER JOIN f6003 da ON da.id_cot = c.id_doc_afec LEFT OUTER JOIN f6001 tdoda on tdoda.id_tdoc = da.id_tdo LEFT OUTER JOIN f4003 fab_fac ON fab_fac.id_fab = pro.id_fab_fac WHERE c.id_cot = {$id_cot}");
        return $r;
    }
    static function borrar($id)
    {
        $r = DB::query("UPDATE f6003 SET status = 0 WHERE id_cot = {$id}");
        return $r;
    }
    static function create_express($id_fab)
    {
        $r = DB::query("SELECT * FROM f4005 WHERE id_fab IN ($id_fab) AND status = 1");
        return $r;
    }
    static function listar_factura($id_emp)
    {
        $r = DB::query("SELECT id_cot, concat(num_tdo, ' - ', id_cli, ' - ', nom_ent) cliente FROM f6003 INNER JOIN f0014 on f0014.id_ent = f6003.id_cli WHERE f6003.id_emp = {$id_emp} AND f6003.status = 1");
        return $r;
    }
    static function con_ventas()
    {
        $r = DB::query("SELECT * FROM f6002 WHERE id = 7");
        return $r[0];
    }
    static function detalle_venta(int $id)
    {
        return $r = DB::query("SELECT a.id_cot, a.iva_prod, SUM(a.sub_total) monto, SUM(a.mon_iva) mon_iva FROM f60031 a WHERE a.id_cot = {$id} GROUP BY a.id_cot, a.iva_prod");
    }
    static function borrarDetCXCDocument($id)
    {
        return $r = DB::delete('f60032', ['id_cot' => $id], 100);
    }
    static function tip_doc_fac(int $id)
    {
        $sql = "SELECT * FROM f4999 a INNER JOIN f6002 b ON b.id_emp = a.id_emp and a.id_con_sales = b.id INNER JOIN f4001 c ON c.id_emp = a.id_emp AND c.id_ubi = a.id_ubi  WHERE a.id_emp = {$id} AND a.status = 1";
        $r = DB::query($sql);
        return $r[0];
    }
    static function set_cotiza($id, $data, $origen)
    {
        if ($origen == 'P') {
            return $r = DB::update('f4008', $data, ['id_cot' => $id]);
        } else {
            return $r = DB::update('f6003', $data, ['id_cot' => $id]);
        }
    }
    static function getNextNumer($id)
    {
        $r = DB::query("SELECT proximo_tmoinv FROM f4006 WHERE id_tmoinv = {$id}");
        return $r[0];
    }
    static function guardar_mov_inv($data)
    {
        return $r = DB::insert('f4009', $data);
    }
    static function actualizar_mov_inv($id, $data)
    {
        return $r = DB::update('f4009', $data, ['id_movinv' => $id]);
    }
    static function guardar_Det_Movin($data)
    {
        return $r = DB::insert('f40091', $data);
    }
    static function borrarDetInvMov($id)
    {
        DB::delete('f40091', ['id_movinv' => $id]);
        return $r = DB::delete('f4009', ['id_movinv' => $id]);
    }
    static function setNextNumber_Mov_Inv($id)
    {
        return $r = DB::query("UPDATE f4006 SET proximo_tmoinv = proximo_tmoinv + 1 WHERE id_tmoinv = {$id}");
    }
    static function consulta_prod($id)
    {
        $x = DB::query("SELECT GROUP_CONCAT(id_alm) id_alm FROM f4999;");
        $id_alm = $x[0]['id_alm'];
        $r = DB::query("SELECT * FROM f4010 WHERE id_prod = {$id} AND id_ent = 0 AND id_alm IN ($id_alm) AND stock > 0 ORDER BY fec_ven");
        //$r = DB::query("SELECT * FROM f4010 WHERE id_prod = {$id}");
        return $r;
    }
    static function aprobacion($data)
    {
        return $r = DB::insert('fgenmsg', $data);
    }
    static function show_row_des($id)
    {
        $r = DB::query("SELECT * FROM f7001 WHERE id = {$id}");
        return $r[0];
    }
    static function origen($id, $tipo)
    {
        if ($tipo == 'C') {
            $r = DB::query("SELECT a.num_tdo, b.tipo_codigo FROM f4008 a INNER JOIN f6001 b ON b.id_tdoc = a.id_tdo WHERE a.id_cot = {$id}");
        } else {
            $r = DB::query("SELECT a.num_tdo, b.tipo_codigo FROM f6003 a INNER JOIN f6001 b ON b.id_tdoc = a.id_tdo WHERE a.id_cot = {$id}");
        }
        return $r[0];
    }
    static function getnro_control($id_emp)
    {
        $r = DB::query("SELECT next_nroControl FROM f6007 WHERE id_emp = {$id_emp} AND status = 1 LIMIT 1");
        return $r[0];
    }
    static function nextnro_control($id_emp)
    {
        $r = DB::query("UPDATE f6007 SET next_nroControl = next_nroControl + 1 WHERE id_emp = {$id_emp} AND status = 1 LIMIT 1");
        return $r;
    }
    static function getNumberMovim($origen)
    {
        $r = DB::query("SELECT * FROM f4009 WHERE origen = '$origen'");
        return $r;
    }
    static function consulta_vend($id_ent)
    {
        $sql = "SELECT DISTINCT a.id_vend, a.id_moneda, case when ISNULL(b.cod_diascre) THEN 0 ELSE b.cod_diascre END cod_diascre, a.nom_ent, IFNULL(a.handling_conver, 0) handling_conver, a.id_alm, a.id_ubi, a.req_exc_rat, a.c_consig FROM f0014 a LEFT OUTER JOIN f6005 b on b.id_diascre = a.id_diascre WHERE a.id_ent = {$id_ent}";
        $r = DB::query($sql);
        return $r[0];
    }
    static function consulta_prod_consig($id, $id_cli, $id_alm, $id_ubi = '', $id_emp = 2)
    {
        $filter = '';
        if ($id_ubi) {
            $filter .= " AND id_ubi = {$id_ubi} ";
        }
        if ($id_cli) {
            $filter .= " AND id_ent = {$id_cli} ";
        }
        $sql = "SELECT * FROM f4010 WHERE id_prod = {$id} AND id_alm  = {$id_alm} " . $filter;
        $r = DB::query($sql);
        return $r;
    }
    static function lote($id_cot, $id_prod)
    {
        $r = DB::query("SELECT c.id_movinv , d.lote, d.fec_venc FROM f6003 a INNER JOIN f6001 b ON b.id_tdoc = a.id_tdo INNER JOIN f4009 c ON c.origen = CONCAT(a.id_cont, '-', b.tipo_codigo, '-', a.id_emp, '-', a.num_tdo ) INNER JOIN f40091 d ON d.id_movinv = c.id_movinv WHERE a.id_cot = {$id_cot} AND d.id_prod = {$id_prod}");
        return $r[0];
    }
    static function equivale($id_emp, $id_ent, $id_prod, $format, $id_ubi, $id_alm, $fecha_comp)
    {
        $sql = "SELECT a.id_emp, a.id_ent, a.id_prod, p.nom_prod, p.uni_ven_prod, p.iva_prod, p.costo1, p.recar_prod, p.ventas_prod, e.id_motcam, m.adic_01, m.adic_02, p.id_fab, IFNULL(f.adicional01, 0) adi_fab, fn_saldo_ant_inv_consig( a.id_prod, $id_alm, '$id_ubi', '$fecha_comp') stock, p.conv_prod_cons, f.nom_fab FROM f4013 a INNER JOIN f4005 p ON p.id_prod = a.id_prod INNER JOIN f0014 e ON e.id_ent = a.id_ent INNER JOIN f0012a m ON m.id_motcam = e.id_motcam INNER JOIN f4003 f ON f.id_fab = p.id_fab WHERE a.id_emp = {$id_emp} AND a.id_ent = {$id_ent} AND a.cod_prod_ent = '$id_prod' AND a.format = '$format'  ";
        $r = DB::query($sql);
        return $r;
    }
    /**Nuevos cambios */
    static function listar_cotizacones(int $id_emp, int $id_cli)
    {
        $r = DB::query("SELECT id_cot, concat('Cotización: ', num_tdo, ' - Cliente: ', id_cli, ' - ', nom_ent) cliente FROM f4008 INNER JOIN f0014 on f0014.id_ent = f4008.id_cli WHERE f4008.id_emp = {$id_emp} AND f4008.id_cli = {$id_cli} AND f4008.id_cont is NULL AND f4008.status = 1");
        return $r;
    }
    static function listar_notas(int $id_emp, int $id_cli, string $fuente)
    {
        if ($fuente == 0) {
            $sql0 = "SELECT a.id_cot, concat('Nota de Entrega: ', a.num_tdo, ' - Cliente: ', a.id_cli, ' - ', c.nom_ent) cliente FROM f6003 a INNER JOIN f4999 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_cli WHERE a.id_emp = {$id_emp} AND a.id_tdo = b.id_tdoc_not_no_fis AND a.invoice IS NULL AND a.id_cli = {$id_cli} AND a.status = 1 ORDER BY a.id_cot";
        } elseif ($fuente == "N") {
            $sql0 = "SELECT a.id_cot, concat('Nota de Entrega: ', a.num_tdo, ' - Cliente: ', a.id_cli, ' - ', c.nom_ent) cliente FROM f6003 a INNER JOIN f4999 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_cli WHERE a.id_emp = {$id_emp} AND a.id_tdo = b.id_tdoc_not_no_fis AND a.invoice IS NULL AND a.id_cli = {$id_cli} AND a.status = 1 ORDER BY a.id_cot";
        } else {
            $sql0 = "SELECT a.id_cot, concat('Nota de Entrega: ', a.num_tdo, ' - Cliente: ', a.id_cli, ' - ', c.nom_ent) cliente FROM f6003 a INNER JOIN f4999 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_cli WHERE a.id_emp = {$id_emp} AND a.id_tdo = b.id_tdoc_not_no_fis AND a.invoice = '$fuente' AND a.id_cli = {$id_cli} AND a.status = 1 ORDER BY a.id_cot";
        }
        $r = DB::query($sql0);
        return $r;
    }
    static function consultar_nota(int $id)
    {
        $sql = "SELECT b.id_prod, c.nom_prod, b.can_det cant, 0 stock, b.uni_vta, b.pre_unit, b.pre_vta, b.iva_prod, b.sub_total, a.tasa_cambio, a.id_moneda, a.num_tdo FROM `f6003`  a INNER JOIN f60031 b ON b.id_cot = a.id_cot INNER JOIN f4005 c ON c.id_prod = b.id_prod WHERE a.id_cot = {$id}";
        $r = DB::query($sql);
        return $r;
    }
}
