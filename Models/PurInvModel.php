<?php
    /**
     * Clase del Modelo de Datos de la Factura de Compras
     * Creado por José Vargas el 20-09-2004
     */
    class PurInvModel extends DB{
        public function __construct() {
            parent::__construct();
        }
        static function all(string $ori){
            $sql = "SELECT a.id_cot, a.id_emp, b.nombre_emp, c.tipo_codigo, c.nom_tdoc, a.num_tdo, e.nom_ent, a.fecha_comp, d.codigo_moneda, a.tasa_cambio, a.status, a.origen, CASE WHEN f.id > 0 THEN 1 ELSE 0 END ret_iva FROM f8020 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3001 c on c.id_tdoc = a.id_tdo INNER JOIN f0005 d on d.id_moneda = a.id_moneda INNER JOIN f0014 e on e.id_ent = a.id_cli LEFT OUTER JOIN f3006 f ON f.id_cot = a.id_cot WHERE c.tipo_tdoc = '$ori'
            UNION
            SELECT  a.id_cot, a.id_emp, b.nombre_emp, c.tipo_codigo, c.nom_tdoc, a.num_tdo, e.nom_ent, a.fecha_comp, d.codigo_moneda, a.tasa_cambio, a.status, a.id_cont origen, CASE WHEN f.id > 0 THEN 1 ELSE 0 END ret_iva FROM f3004 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3001 c on c.id_tdoc = a.id_tdo INNER JOIN f0005 d on d.id_moneda = a.id_moneda INNER JOIN f0014 e on e.id_ent = a.id_cli LEFT OUTER JOIN f3006 f ON f.id_cot = a.id_cot WHERE a.id_cont = 'COM' AND c.tipo_tdoc = '$ori'";
            return $r = DB::query($sql);
        }
        static function show_row(int $id){
            $sql = "SELECT a.id_emp, a.id_tdo, a.num_tdo, a.num_control, a.fecha_comp, a.fec_fact, a.fecha_venci, a.id_moneda, a.tasa_cambio, a.id_cli, e.nom_ent, g.id_prod, g.nom_prod, f.can_det, f.lote, f.fec_venc, g.uni_com_prod, f.pre_unit, f.pre_vta, f.iva_prod, f.sub_total, a.id_retiva FROM f3004 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3001 c ON c.id_tdoc = a.id_tdo INNER JOIN f0005 d ON d.id_moneda = a.id_moneda INNER JOIN f0014 e ON e.id_ent = a.id_cli INNER JOIN f80201 f ON f.id_cot = a.id_cot INNER JOIN f4005 g ON g.id_prod = f.id_prod WHERE a.id_cot = {$id}";            
            $r = DB::query($sql);
            return $r;
        }
        static function tip_doc_com(int $id){
            $r = DB::query("SELECT a.tdoc_pur, a.tdoc_purord, a.tdoc_purcrenot, a.tdoc_purdelnot, a.tdoc_purretnot, b.especial_contrib, a.con_purcon, a.id_alm, a.id_ubi, a.id_typmovinwar, a.id_typmovoutwar, b.id_moneda  FROM f8999 a INNER JOIN f0011 b ON b.id_emp = a.id_emp WHERE a.id_emp = {$id} AND a.status = 1;");
            return $r[0];
        }
        static function listar_entidad_modal(string $tipo){
            $r = DB::query("SELECT a.id_ent, a.rif_ent, a.nom_ent FROM f0014 a WHERE a.status = 1 AND tip_ent = '".$tipo."'");
            return $r;
        }
        static function guardar(array $data, $row = null){            
            $db = new Conexion(); //Obtener la instancia del PDO
            $link = (object)$db->conect();    
            //
            $id_emp = $_POST['id_emp'];
            //
            $next_number_doc_flag = false;   
            $next_number_mov_flag = false;   
            $next_number_doc = $_POST['num_tdo'];                        
            $msg = '';
            //
            $cols = "";
            $placeholders = "";
            $params = [];  
            //Tasa de IVA
            $xTasaIVA = 0;
            $xvatTax = xvatTax($_POST['fecha_comp'], 'IVA');
            $msg = '';
            if($xvatTax) $xTasaIVA = $xvatTax[0]['txr1_iva'];    
            //
            $tot_compras = 0; 
            $tot_exento = 0; 
            $tot_base  = 0;
            $usa_ret_iva = false;
            try {
                $link->beginTransaction();
                //Verificar si usa consecutivo el tipo de Documento
                $id_tdoc = $_POST['id_tdo'];
                $sql_tip_doc = "SELECT * FROM f3001 WHERE id_tdoc  = :id_tdoc ";
                $stmt_tipo_doc = $link->prepare($sql_tip_doc);
                $stmt_tipo_doc->execute([':id_tdoc' => $id_tdoc]);
                $con_tip_doc = $stmt_tipo_doc->fetch(PDO::FETCH_ASSOC);
                $nom_tip_doc = $con_tip_doc['nom_tdoc'];
                $tip_tip_doc = $con_tip_doc['tipo_tdoc'];
                $cod_tip_doc = $con_tip_doc['tipo_codigo'];
                $usa_con_tdo = $con_tip_doc['con_tdoc'];
                if(!$row && $usa_con_tdo == 1){
                    $next_number_doc_flag = true;
                    $next_number_doc = floatval($con_tip_doc['num_tdoc']);
                }             
                //Buscar confgiouración de Compras
                $sql_con_com = "SELECT a.tdoc_pur, a.tdoc_purord, a.tdoc_purcrenot, a.tdoc_purdelnot, a.tdoc_purretnot, b.especial_contrib, a.con_purcon, a.id_alm, a.id_ubi, a.id_typmovinwar, a.id_typmovoutwar, b.id_moneda, c.id_aux, d.id_retiva, d.con_ret_iva  FROM f8999 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3003 c ON c.id = a.con_purcon INNER JOIN f3999 d ON d.id_emp = a.id_emp WHERE a.id_emp = :id_emp AND a.status = :status";
                $stmt_con_com = $link->prepare($sql_con_com);
                $stmt_con_com->execute([':id_emp' => $_POST['id_emp'], ':status' => 1]);
                $fila = $stmt_con_com->fetch(PDO::FETCH_ASSOC);
                $id_cxpcon = $fila['con_purcon'];
                $id_aux = $fila['id_aux'];
                $id_alm = $fila['id_alm'];
                $id_ubi = $fila['id_ubi'];
                $id_con_retiva = $fila['id_retiva'];
                $con_ret_iva = floatval($fila['con_ret_iva']);
                if ($_POST['ori'] == 'A') {
                    $id_tip_mov = $fila['id_typmovoutwar'];
                } else {
                    $id_tip_mov = $fila['id_typmovinwar'];
                }           
                //Generar Movimiento de Inventario
                $sql_con_mov = "SELECT * FROM f4006 WHERE id_tmoinv = :id_tmoinv";
                $stmt_con_mov = $link->prepare($sql_con_mov);
                $stmt_con_mov->execute([':id_tmoinv' => $id_tip_mov]);
                $con_mov = $stmt_con_mov->fetch(PDO::FETCH_ASSOC);
                $proximo_tmoinv = $con_mov['proximo_tmoinv'];  
                $next_movim = 0;     
                $next_number_flag_movin = false;
                //Verificar si usa consecutivo
                if(!$row){
                    if($con_mov['consecutiv__tmoinv'] == "1"){
                        $next_number_flag_movin = true;
                        $next_movim = floatval($con_mov['proximo_tmoinv']);                       
                    }   
                }  
                //Total del Documento
                $xtasa = convert_string_to_number($_POST['tasa_cambio']);
                $moon_doc = ($xtasa > 1) ? convert_string_to_number($_POST['total_frml']) : convert_string_to_number($_POST['total_frmDom']);
                //
                $origen = "COM";
                $id_cont = $origen;
                //
                if(!$row){
                    //Guardar Encabezado de Compras y CXP
                    // 1. Obtener nombres de columnas                                
                    $stmt_doc_enca = $link->prepare("SELECT id_emp, id_tdo, num_tdo, id_cli, id_fab, fecha_comp, fecha_venci, id_moneda, tasa_cambio, id_vend, descrip_cot, mon_doc, sal_doc, status, create_user, create_date, origen, id_retiva, num_control, fec_fact, id_cont FROM f3004 LIMIT 1");                
                    $stmt_doc_enca->execute();                
                    $fila = $stmt_doc_enca->fetch(PDO::FETCH_ASSOC);
                    $cols = array_keys($fila);
                    // 2. Crear placeholders (:campo1, :campo2, ...)
                    $placeholders = array_map(function($col) {
                        return ":{$col}";
                    }, $cols);
                    // 4. Armar la sentencia SQL
                    $colsStr         = implode(", ", $cols);
                    $placeholdersStr = implode(", ", $placeholders);                    
                    $sql             = "INSERT INTO f3004 ({$colsStr}) VALUES ({$placeholdersStr})";        
                    $stmt = $link->prepare($sql);                     
                    if($_POST['ori'] == 'A'){
                        if(isset($_POST['fuente']) && isset($_POST['origen'])){
                            $id_cont = $id_cont . '-' . $_POST['fuente'] . '-' . $_POST['origen'];
                        }                        
                    }
                    $params = [
                        ':id_emp' => $_POST['id_emp'], 
                        ':id_tdo' => $_POST['id_tdo'], 
                        ':num_tdo' => $next_number_doc, 
                        ':id_cli' => $_POST['id_cli'], 
                        ':id_fab' => null, 
                        ':fecha_comp' => $_POST['fecha_comp'], 
                        ':fecha_venci' => $_POST['fecha_venci'], 
                        ':id_moneda' => $_POST['id_moneda'], 
                        ':tasa_cambio' => $xtasa,
                        ':id_vend' => null, 
                        ':descrip_cot' => 'Factura generada desde el Modulo de Compras ', 
                        ':mon_doc' => $moon_doc, 
                        ':sal_doc' => $moon_doc, 
                        ':status' => 1, 
                        ':create_user' => $_SESSION['id_user'], 
                        ':create_date' => getAuditoria(), 
                        ':origen' => $origen, 
                        ':id_retiva' => $_POST['id_retiva'], 
                        ':num_control' => $_POST['num_control'], 
                        ':fec_fact' => $_POST['fec_fact'], 
                        ':id_cont' => $id_cont
                    ];
                    $stmt->execute($params);                    
                    $idNE = $link->lastInsertId(); 
                    $msg .= sprintf("Se ha guardado satisfactoriamente el documento %s Número %s", $nom_tip_doc, $next_number_doc);
                    //Crear Movimiento de Inventarios
                    if($_POST['ori'] === 'M' || $_POST['ori'] == 'T' || $_POST['ori'] == 'A'){
                        $origen = 'COM-' . $cod_tip_doc . '-' . $next_number_doc;
                        $sql_mov_enca = "INSERT INTO f4009 (id_emp, id_tmovinv, num_movinv, fecha_comp, id_moneda, tasa_cambio, id_alm, descrip_movinv, status, id_cot, id_cli, id_vend, id_fab, origen, create_user, create_date) VALUES (:id_emp, :id_tmovinv, :num_movinv, :fecha_comp, :id_moneda, :tasa_cambio, :id_alm, :descrip_movinv, :status, :id_cot, :id_cli, :id_vend, :id_fab, :origen, :create_user, :create_date)"; 
                        $stmt_det = $link->prepare($sql_mov_enca);    
                        $params = [                       
                            ':id_emp' => $_POST['id_emp'],
                            ':id_tmovinv' => $id_tip_mov,
                            ':num_movinv' => $next_movim,
                            ':fecha_comp' => $_POST['fecha_comp'],
                            ':id_moneda' => $_POST['id_moneda'],
                            ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                            ':id_alm' => $id_alm,
                            ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_tip_doc . ' ' . $origen,
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
                        $idNEMov = $link->lastInsertId();
                    }
                } else {
                    //Para modificar un Documento de compra
                    $idNE = $row;
                    $setClauses = [];
                    //
                    $stmt_doc_enca = $link->prepare("SELECT id_emp, id_tdo, num_tdo, id_cli, id_fab, fecha_comp, fecha_venci, id_moneda, tasa_cambio, id_vend, descrip_cot, mon_doc, sal_doc, status, modify_user, modify_date, origen, id_retiva, num_control, fec_fact, id_cont FROM f3004 LIMIT 1");
                    $stmt_doc_enca->execute();                
                    $fila = $stmt_doc_enca->fetch(PDO::FETCH_ASSOC);
                    //
                    $setClauses = [];
                    $params = [];
                    //
                    foreach($fila as $key =>$value){
                        if ($key !== 'id_cot') { // Excluir la llave        primaria del SET
                            $setClauses[] = "{$key} = :{$key}";
                            $params[$key] = $value;
                        }
                    }
                    //Parametro para la condicion WHERE                    
                    $sql = "UPDATE f3004 SET " . implode(", ", $setClauses) . " WHERE id_cot = :id_cot";
                    $stmt = $link->prepare($sql);                     
                    $params = [
                        ':id_emp' => $_POST['id_emp'], 
                        ':id_tdo' => $_POST['id_tdo'], 
                        ':num_tdo' => $next_number_doc, 
                        ':id_cli' => $_POST['id_cli'], 
                        ':id_fab' => null, 
                        ':fecha_comp' => $_POST['fecha_comp'], 
                        ':fecha_venci' => $_POST['fecha_venci'], 
                        ':id_moneda' => $_POST['id_moneda'], 
                        ':tasa_cambio' => $xtasa,
                        ':id_vend' => null, 
                        ':descrip_cot' => 'Factura generada desde el Modulo de Compras ', 
                        ':mon_doc' => $moon_doc, 
                        ':sal_doc' => $moon_doc, 
                        ':status' => 1, 
                        ':modify_user' => $_SESSION['id_user'], 
                        ':modify_date' => getAuditoria(), 
                        ':origen' => 0, 
                        ':id_retiva' => $_POST['id_retiva'] ?? 0, 
                        ':num_control' => $_POST['num_control'], 
                        ':fec_fact' => $_POST['fec_fact'], 
                        ':id_cont' => $id_cont,
                        ':id_cot' => $row
                    ];                    
                    $stmt->execute($params);                                        
                    $stmt->closeCursor();
                    //Actualizaro Encabezo Movimiento de Inventarios
                    if($_POST['ori'] === 'M' || $_POST['ori'] == 'T' || $_POST['ori'] == 'A'){   
                        $origen = 'COM-' . $cod_tip_doc . '-' . $next_number_doc;                     
                        $sql = "SELECT * FROM f4009 WHERE id_cot = :id_cot";                        
                        $stmt = $link->prepare($sql);                        
                        $stmt->execute([':id_cot' => $row]);
                        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                        $idNEMov = $fila['id_movinv'];
                        $stmt->closeCursor();                        
                        $sql_mov_enca = "UPDATE f4009 SET id_movinv = :id_movinv, id_emp = :id_emp, id_tmovinv = :id_tmovinv, num_movinv = :num_movinv, fecha_comp = :fecha_comp, id_moneda = :id_moneda, tasa_cambio = :tasa_cambio, id_alm = :id_alm, descrip_movinv = :descrip_movinv, status = :status, id_cli = :id_cli, id_vend = :id_vend, id_fab = :id_fab, origen = :origen, modify_user = :modify_user, modify_date = :modify_date WHERE id_movinv = :id_movinv"; 
                        $stmt_det = $link->prepare($sql_mov_enca);    
                        $params = [                       
                            ':id_emp' => $_POST['id_emp'],
                            ':id_tmovinv' => $fila['id_tmovinv'],
                            ':num_movinv' => $fila['num_movinv'],
                            ':fecha_comp' => $_POST['fecha_comp'],
                            ':id_moneda' => $_POST['id_moneda'],
                            ':tasa_cambio' => convert_string_to_number($_POST['tasa_cambio']),
                            ':id_alm' => $id_alm,
                            ':descrip_movinv' => 'Movimiento automático generado desde ' . $nom_tip_doc . ' ' . $origen,
                            ':status' => 1,
                            ':id_cot' => $idNE,                        
                            ':id_cli' => $_POST['id_cli'],
                            ':id_vend' => $_POST['id_vend'],
                            ':id_fab' => null,
                            ':origen' => $origen,
                            ':modify_user' => $_SESSION['id_user'],
                            ':modify_date' => getAuditoria(),                        
                            'id_movinv' => $idNEMov
                        ];
                        $stmt_det->execute($params);                        
                    }
                    $msg .= sprintf("Se ha actualizado satisfactoriamente el documento %s Número %s", $nom_tip_doc, $next_number_doc);
                    
                }                
                //Guardar detalle de Compras / Eliminar Registro previo
                //Eliminar Detalle de Compras en caso de existir
                $params = [':id_cot' => $idNE];
                $sql_del_det = "DELETE FROM f80201 WHERE id_cot = :id_cot";
                $stmt_del_det = $link->prepare($sql_del_det);
                $stmt_del_det->execute($params);
                $stmt_del_det->closeCursor();
                //Eliminar Detalle de Cuentas por Pagar en caso de existir
                $sql_del_det = "DELETE FROM f30041 WHERE id_cot = :id_cot";
                $stmt_del_det = $link->prepare($sql_del_det);
                $stmt_del_det->execute($params);                
                $stmt_del_det->execute();
                $stmt_del_det->closeCursor();
                //Eliminar Detalle de Movimientos de Inventaros en caso de existir
                $sql_del_det = "DELETE FROM f40091 WHERE id_movinv = :id_movinv";
                $stmt_del_det = $link->prepare($sql_del_det);
                $stmt_del_det->execute([':id_movinv' => $idNEMov]);                
                $stmt_del_det->execute();
                $stmt_del_det->closeCursor();
                //Guardar detalle de Compra                
                $detalle = json_decode($_POST['detalle']);                
                $sql_ins_det = "INSERT INTO f80201 (id_cot, id_prod, can_det, uni_vta, pre_unit, pre_vta, iva_prod, sub_total, mon_iva, tota_prod, create_user, create_date, lote, fec_venc) VALUES (:id_cot, :id_prod, :can_det, :uni_vta, :pre_unit, :pre_vta, :iva_prod, :sub_total, :mon_iva, :tota_prod, :create_user, :create_date, :lote, :fec_venc)";
                $stmt_ins_det = $link->prepare($sql_ins_det);                             
                foreach($detalle as $det_doc){                     
                    $id_prod = $det_doc->id_prod;
                    $cantidad = $det_doc->cant_det;   
                    $uni_vta = $det_doc->uni_vta;
                    $pre_unit = convert_string_to_number($det_doc->pre_unit);
                    $pre_vta = convert_string_to_number($det_doc->pre_vta);
                    $iva_prod = $det_doc->iva_prod;
                    $sub_total = convert_string_to_number($det_doc->sub_total);
                    $mon_iva = 0;                    
                    if($iva_prod == "S"){
                        $mon_iva = $sub_total * ($xTasaIVA / 100);                        
                    }
                    $tota_prod = $sub_total + $mon_iva;                     
                    $params = [
                        ':id_cot' => $idNE, 
                        ':id_prod' => $id_prod,
                        ':can_det' => $cantidad, 
                        ':uni_vta' => $uni_vta, 
                        ':pre_unit' => $pre_unit, 
                        ':pre_vta' => $pre_vta, 
                        ':iva_prod' => $iva_prod, 
                        ':sub_total' => $sub_total, 
                        ':mon_iva' => $mon_iva, 
                        ':tota_prod' => $tota_prod, 
                        ':create_user' => $_SESSION['id_user'], 
                        ':create_date' => getAuditoria(), 
                        ':lote' => $det_doc->lote, 
                        ':fec_venc' => $det_doc->fec_venc
                    ];                      
                    $stmt_ins_det->execute($params);
                    $stmt->closeCursor();
                }                
                //Gaurdar Detalle de Cuentas por Pagar               
                if($_POST['ori'] === 'M' || $_POST['ori'] == 'T' || $_POST['ori'] == 'A'){    
                    $sql_con_sum = "SELECT iva_prod, sum(sub_total) sub_total, sum(mon_iva) mon_iva FROM f80201 WHERE id_cot = :id_cot GROUP by iva_prod";
                    $stmt_con_sum = $link->prepare($sql_con_sum);
                    $stmt_con_sum->execute([':id_cot' => $idNE]);                    
                    $fila = $stmt_con_sum->fetchAll(PDO::FETCH_ASSOC);                    
                    $stmt_con_sum->closeCursor();
                    $sql_con_cxp = "INSERT INTO f30041 (id_cot, id_concxp, iva, id_aux, monto, mon_iva, create_user, create_date) VALUES (:id_cot, :id_concxp, :iva, :id_aux, :monto, :mon_iva, :create_user, :create_date)"; 
                    $stmt_con_cxp = $link->prepare($sql_con_cxp);
                    $mon_iva = 0;                    
                    foreach ($fila as $cxp_con) {                        
                        $params = [
                            ':id_cot' => $idNE, 
                            ':id_concxp' => $id_cxpcon, 
                            ':iva' => $cxp_con['iva_prod'], 
                            ':id_aux' => $id_aux ?? 0, 
                            ':monto' => $cxp_con['sub_total'], 
                            ':mon_iva' => $cxp_con['mon_iva'], 
                            ':create_user' => $_SESSION['id_user'], 
                            ':create_date' => getAuditoria()
                        ];
                        $mon_iva += $cxp_con['mon_iva'];
                        $stmt_con_cxp->execute($params);
                        $stmt_con_cxp->closeCursor();
                        //
                        $tot_compras += ($cxp_con['sub_total'] + $cxp_con['mon_iva']); 
                        if($cxp_con['iva_prod'] === 'S'){
                            $tot_base += $cxp_con['sub_total'];
                        }else{
                            $tot_exento += $cxp_con['sub_total']; 
                        }                        
                        
                    };                    
                } 
                //Guardar detalle de Movimiento de Inventarios
                $sql_mov_det = "INSERT f40091 (id_movinv, id_prod, id_ubi, cantidad, costo, flete, otros_cargos, door_cargos, costo1, lote, fec_venc, create_user, create_date) VALUES (:id_movinv, :id_prod, :id_ubi, :cantidad, :costo, :flete, :otros_cargos, :door_cargos, :costo1, :lote, :fec_venc, :create_user, :create_date)";
                $stmt_mov_det = $link->prepare($sql_mov_det);                
                $xtasa_new = 0;
                if($xtasa == 1){
                    $xtasa_new = convert_string_to_number(CambiosModel::rateExchange(2, $_POST['fecha_comp']));                    
                }                
                foreach( $detalle as $det_doc){                                        
                    $sql_id_prod = "SELECT costo_prod costo, flete_prod flete, otros_prod otros_cargos, door_costo door_cargos, costo1 FROM f4005 WHERE id_prod = :id_prod";
                    $stmt_id_prod = $link->prepare($sql_id_prod);
                    $stmt_id_prod->execute([':id_prod' => $det_doc->id_prod]);                    
                    $row_id_prod = $stmt_id_prod->fetch(PDO::FETCH_ASSOC);                     
                    $costo = convert_string_to_number($row_id_prod['costo']);
                    if($xtasa == 1){                        
                        $pre_unit = convert_string_to_number($det_doc->pre_unit) ;
                        $costo = $pre_unit / $xtasa_new;
                    }
                    $params = [
                        ':id_movinv' => $idNEMov, 
                        ':id_prod' => $det_doc->id_prod, 
                        ':id_ubi' => $id_ubi, 
                        ':cantidad' => $det_doc->cant_det, 
                        ':costo' => $costo,
                        ':flete' => $row_id_prod['flete'], 
                        ':otros_cargos' => $row_id_prod['otros_cargos'], 
                        ':door_cargos' => $row_id_prod['door_cargos'], 
                        ':costo1' => $row_id_prod['costo1'], 
                        ':lote' => $det_doc->lote, 
                        ':fec_venc' => $det_doc->fec_venc, 
                        ':create_user' => $_SESSION['id_user'], 
                        ':create_date' => getAuditoria()
                    ];                    
                    $stmt_mov_det->execute($params);
                    $stmt_mov_det->closeCursor();                
                } 
                  //Calcular la Retención de IVA
                if(isset($_POST['id_retiva']) && $_POST['id_retiva'] > 0 && $mon_iva <> 0){
                    $xmon_retiva = 0;                    
                    $val_retiva = CXPDocumentModel::ret_iva($_POST['id_retiva']);                    
                    $por_retiva = $val_retiva['tasa_retiva'];
                    $min_retiva = $val_retiva['min_retiva'];
                    //
                    $xmon_retiva = $mon_iva * ($por_retiva / 100);                    
                    //
                    if ($min_retiva != 0 && $xmon_retiva < $min_retiva) {
                        $xmon_retiva = $min_retiva;
                    }
                    if($xmon_retiva != 0){
                        $usa_ret_iva = true;
                        $sql_con_cxp = "INSERT INTO f30041 (id_cot, id_concxp, iva, id_aux, monto, mon_iva, create_user, create_date) VALUES (:id_cot, :id_concxp, :iva, :id_aux, :monto, :mon_iva, :create_user, :create_date)"; 
                        $stmt_con_cxp = $link->prepare($sql_con_cxp);
                        $params = [
                            ':id_cot' => $idNE, 
                            ':id_concxp' => $id_con_retiva, 
                            ':iva' => 'N', 
                            ':id_aux' => null, 
                            ':monto' => $xmon_retiva * -1, 
                            ':mon_iva' => 0, 
                            ':create_user' => $_SESSION['id_user'], 
                            ':create_date' => getAuditoria()
                        ];
                        $stmt_con_cxp->execute($params);
                        $stmt_con_cxp->closeCursor;
                        //Sumar Detalle de Documentos
                        $sql = "SELECT SUM(monto + mon_iva) monto FROM f30041 WHERE id_cot = :id_cot";
                        $stmt = $link->prepare($sql);
                        $stmt->execute([':id_cot' => $idNE]);
                        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                        $stmt->closeCursor();
                        $mon_doc = $fila['monto'];
                        //Update MonDoc y SalDoc del Documento
                        $sql = "UPDATE f3004 SET mon_doc = :mon_doc, sal_doc = :sal_doc WHERE id_cot = :id_cot";                        
                        $stmt = $link->prepare($sql);                        
                        $stmt->execute([':mon_doc' => $mon_doc, ':sal_doc' => $mon_doc, ':id_cot' => $idNE]);                        
                        $stmt->closeCursor();
                        //Validar si existe el Comprobante de Retención para creaciíon y o modificacion si existe
                        $sql = "SELECT * FROM f3006 WHERE id_cot = :id_cot AND origen = :origen";
                        $stmt = $link->prepare($sql);
                        $stmt->execute([':id_cot' => $idNE, ':origen' => 'COM']);
                        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                        $stmt->closeCursor();                             
                        if($fila){
                            $con_ret_iva = $fila['num_retiva'];
                            $usa_ret_iva = false;
                            $sql = "UPDATE f3006 SET id_emp = :id_emp, id_cot = :id_cot, id_retiva = :id_retiva, id_ent = :id_ent, fecha_pago = :fecha_pago, num_retiva = :num_retiva, modify_user = :modify_user, modify_date = :modify_date, tot_compras = :tot_compras, tot_exento = :tot_exento, tot_base = :tot_base, tasa_iva = :tasa_iva, tot_iva = :tot_iva, tot_ret = :tot_ret, por_retiva = :por_retiva, origen = :origen WHERE id = :id";
                            $params = [
                                ':modify_user' => $_SESSION['id_user'], 
                                ':modify_date' => getAuditoria(), 
                                ':id' => $fila['id']
                            ];
                        }else{
                            $sql = "INSERT INTO f3006 (id_emp, id_cot, id_retiva, id_ent, fecha_pago, num_retiva, create_user, create_date, tot_compras, tot_exento, tot_base, tasa_iva, tot_iva, tot_ret, por_retiva, origen) VALUES(:id_emp, :id_cot, :id_retiva, :id_ent, :fecha_pago, :num_retiva, :create_user, :create_date, :tot_compras, :tot_exento, :tot_base, :tasa_iva, :tot_iva, :tot_ret, :por_retiva, :origen)";
                            $params = [
                                ':create_user' => $_SESSION['id_user'], 
                                ':create_date' => getAuditoria(), 
                            ];
                        }
                        $params += [
                            ':id_emp' => $_POST['id_emp'], 
                            ':id_cot' => $idNE, 
                            ':id_retiva' => $_POST['id_retiva'], 
                            ':id_ent' => $_POST['id_cli'], 
                            ':fecha_pago' => $_POST['fecha_comp'], 
                            ':num_retiva' => $con_ret_iva,                           
                            ':tot_compras' => $tot_compras, 
                            ':tot_exento' => $tot_exento, 
                            ':tot_base' => $tot_base, 
                            ':tasa_iva' => $xTasaIVA, 
                            ':tot_iva' => $mon_iva, 
                            ':tot_ret' => $xmon_retiva, 
                            ':por_retiva' => $por_retiva,
                            ':origen' => 'COM'
                        ];
                        $stmt = $link->prepare($sql);                        
                        $stmt->execute($params);
                        $stmt->closeCursor();
                    }
                }
                //Actualizar proximo numero de Documento
                if($next_number_doc_flag){
                    $sql_upd_doc = "UPDATE f6001 SET num_tdoc = :num_tdoc, modify_user = :modify_user, create_date = :create_date WHERE id_tdoc = :id_tdoc";
                    $stmt_upd_con = $link->prepare($sql_upd_doc);
                    $stmt_upd_con->execute([
                        ':num_tdoc' => $next_number_doc + 1,
                        ':modify_user' => $_SESSION['id_user'],
                        ':create_date' => getAuditoria(),
                        ':id_tdoc' => $$id_tdoc
                    ]);
                }
                //Actualizar proximo numero del Tipo de Movimiento de Salida
                if($next_number_flag_movin){
                    $sql_upd_mov = "UPDATE f4006 SET proximo_tmoinv = :proximo_tmoinv, modify_user = :modify_user, create_date = :create_date WHERE id_tmoinv = :id_tmoinv";
                    $stmt_upd_mov = $link->prepare($sql_upd_mov);
                    $stmt_upd_mov->execute([
                        ':proximo_tmoinv' => $next_movim + 1,
                        ':modify_user' => $_SESSION['id_user'],
                        ':create_date' => getAuditoria(),
                        ':id_tmoinv' => $id_tip_mov
                    ]);
                }      
                //Actualizar proximo numero de retencion de IVA
                if($usa_ret_iva)          {
                    $sql = "UPDATE f3999 SET con_ret_iva = :con_ret_iva, modify_user = :modify_user, create_date = :create_date";
                    $stmt = $link->prepare($sql);
                    $stmt->execute([
                        ':con_ret_iva' => $con_ret_iva + 1,
                        ':modify_user' => $_SESSION['id_user'],
                        ':create_date' => getAuditoria()
                    ]);
                }              
                //                
                $link->commit();
                $title = 'Registro guardado satisfactoriamente';
                return [
                    "tilte" => $title,
                    "icon" => "success",
                    "msg" => $msg
                ];
            } catch (\PDOException $e) {              
                debug($e->getMessage());
                $link->rollback();                  
                return [
                    "title" => 'Se ha presentado un error',
                    "icon" => 'error',
                    "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'])
                ];            
            }
        }
        static function borrar($id){
            $r = DB::delete('f80201', ['id_cot' => $id]);
            if($r){
                $r = DB::delete('f8020', ['id_cot' => $id]);
                if($r){
                    return true;
                }else{
                    return false;
                }
            }
        }
        static function borrarDet($id){
            return $r= DB::delete('f80201', ['id_cot' => $id]);
        }
        static function guardarDet($data){
            return $r = DB::insert('f80201', $data);
        }
        static function selectEncyDetmovinv($origen, $id_emp){
            $r = DB::query("SELECT * FROM f4009 WHERE id_emp = {$id_emp} AND origen = '$origen'");
            return $r[0];
        }
        static function borrarEncyDetmovinv($id_movinv){
            DB::delete('f40091', ['id_movinv' => $id_movinv]);
            return $r = DB::delete('f4009', ['id_movinv' => $id_movinv]);
        }
        static function selectEncyDetCXP($origen, $id_emp){
            $r = DB::query("SELECT * FROM f3004 WHERE id_emp = {$id_emp} AND origen = '{$origen}'");
            return $r[0];
        }
        static function borrarEncyDetCXP($id){
            DB::delete('f30041', ['id_cot' => $id]);
            return $r = DB::delete('f3004', ['id_cot' => $id]);
        }
        static function edit($id){
            $r = DB::query("SELECT * FROM f3004 WHERE id_cot = {$id}");
            return $r[0];
        }        
        static function actualizar($id, $data){
            return $r = DB::update('f8020', $data, ['id_cot' => $id]);
        }
        static function print_retiva_enca($id, $mod){
            $xorigen = 'CXP';
            $sql = "";
            $table = `f3004`;
            if($mod=='O'){
                $xorigen = 'COM';
                $table = `f8020`;
            }
            if($mod=='O'){
                $sql = "SELECT row_number() OVER (ORDER BY a.fecha_pago) item, b.nombre_emp, b.rif_empresa, a.fecha_pago, Date_format(a.fecha_pago, '%m') mes, Date_format(a.fecha_pago, '%Y') anio, b.dir_emp, c.nom_ent, c.rif_ent, a.por_retiva, d.fec_fact, d.num_tdo, d.num_control, a.tot_compras, a.tot_exento, a.tot_base, a.tasa_iva, a.tot_iva, a.tot_ret, CONCAT(e.name_user, ' ', e.last_user) ela_por, b.logo, a.num_retiva, d.fecha_comp, f.tipo_tdoc FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f8020 d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id} AND a.origen = '".$xorigen."' ";
                return $r = DB::query($sql);
            }else{
                //$sql = "SELECT row_number() OVER (ORDER BY a.fecha_pago) item, b.nombre_emp, b.rif_empresa, a.fecha_pago, Date_format(a.fecha_pago, '%m') mes, Date_format(a.fecha_pago, '%Y') anio, b.dir_emp, c.nom_ent, c.rif_ent, a.por_retiva, d.fec_fact, d.num_tdo, d.num_control, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_compras ELSE a.tot_compras * d.tasa_cambio END tot_compras, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_exento ELSE a.tot_exento * d.tasa_cambio END tot_exento, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_base ELSE a.tot_base * d.tasa_cambio END tot_base, a.tasa_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_iva ELSE a.tot_iva * d.tasa_cambio END tot_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_ret ELSE a.tot_ret * d.tasa_cambio END tot_ret, CONCAT(e.name_user, ' ', e.last_user) ela_por, b.logo, a.num_retiva, d.fecha_comp, f.tipo_tdoc FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f3004 d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id} AND a.origen = '".$xorigen."' ";
                //Codigo nuevo motivado a la integración del Modulos de Compras directamente a Cuentas por pagar. Modificado por Jose Vargas el 10/09/2026
                $sql = "SELECT row_number() OVER (ORDER BY a.fecha_pago) item, b.nombre_emp, b.rif_empresa, a.fecha_pago, Date_format(a.fecha_pago, '%m') mes, Date_format(a.fecha_pago, '%Y') anio, b.dir_emp, c.nom_ent, c.rif_ent, a.por_retiva, d.fec_fact, d.num_tdo, d.num_control, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_compras ELSE a.tot_compras * d.tasa_cambio END tot_compras, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_exento ELSE a.tot_exento * d.tasa_cambio END tot_exento, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_base ELSE a.tot_base * d.tasa_cambio END tot_base, a.tasa_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_iva ELSE a.tot_iva * d.tasa_cambio END tot_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_ret ELSE a.tot_ret * d.tasa_cambio END tot_ret, CONCAT(e.name_user, ' ', e.last_user) ela_por, b.logo, a.num_retiva, d.fecha_comp, f.tipo_tdoc FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f3004 d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id}";
                return $r = DB::query($sql);
            }
        }
        static function print_retiva_deta($id, $mod){
            $xorigen = 'CXP';
            $table = `f3004`;
            if($mod=='O'){
                $xorigen = 'COM';
                $table = `f8020`;
            }
            if($mod=='O'){
                $sql = "SELECT row_number() OVER (ORDER BY a.fecha_pago) item, a.fecha_pago, CASE WHEN f.tipo_tdoc = 'M' THEN d.num_tdo ELSE '' END num_factura, d.num_control, CASE WHEN f.tipo_tdoc = 'B' THEN d.num_tdo ELSE '' END num_debito, CASE WHEN f.tipo_tdoc = 'A' THEN d.num_tdo ELSE '' END num_credito, CASE WHEN f.tipo_tdoc = 'M' THEN '01' WHEN f.tipo_tdoc = 'B' THEN '02' ELSE '03' END tipo_tran, a.tot_compras * d.tasa_cambio tot_compras, a.tot_exento * d.tasa_cambio tot_exento, a.tot_base * d.tasa_cambio tot_base,  a.tasa_iva, a.tot_iva * d.tasa_cambio tot_iva, a.tot_ret * d.tasa_cambio tot_ret FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f8020  d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id} AND a.origen = '".$xorigen."' ";
            }else{
                //$sql = "SELECT DISTINCT row_number() OVER (ORDER BY a.fecha_pago) item, b.nombre_emp, b.rif_empresa, a.fecha_pago, Date_format(a.fecha_pago, '%m') mes, Date_format(a.fecha_pago, '%Y') anio, b.dir_emp, c.nom_ent, c.rif_ent, a.por_retiva, d.fec_fact, d.num_tdo, d.num_control, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_compras ELSE a.tot_compras * d.tasa_cambio END tot_compras, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_exento ELSE a.tot_exento * d.tasa_cambio END tot_exento, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_base ELSE a.tot_base * d.tasa_cambio END tot_base, a.tasa_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_iva ELSE a.tot_iva * d.tasa_cambio END tot_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_ret ELSE a.tot_ret * d.tasa_cambio END tot_ret, CONCAT(e.name_user, ' ', e.last_user) ela_por, b.logo, a.num_retiva, d.fecha_comp, f.tipo_tdoc, CASE WHEN f.tipo_tdoc = 'M' THEN d.num_tdo ELSE '' END num_factura, d.num_control, CASE WHEN f.tipo_tdoc = 'B' THEN d.num_tdo ELSE '' END num_debito, CASE WHEN f.tipo_tdoc = 'A' THEN d.num_tdo ELSE '' END num_credito, CASE WHEN f.tipo_tdoc = 'M' THEN '01' WHEN f.tipo_tdoc = 'B' THEN '02' ELSE '03' END tipo_tran FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f3004 d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id} AND a.origen = '".$xorigen."' ";
                //Codigo nuevo motivado a la integración del Modulos de Compras directamente a Cuentas por pagar. Modificado por Jose Vargas el 10/09/2026
                $sql = "SELECT DISTINCT row_number() OVER (ORDER BY a.fecha_pago) item, b.nombre_emp, b.rif_empresa, a.fecha_pago, Date_format(a.fecha_pago, '%m') mes, Date_format(a.fecha_pago, '%Y') anio, b.dir_emp, c.nom_ent, c.rif_ent, a.por_retiva, d.fec_fact, d.num_tdo, d.num_control, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_compras ELSE a.tot_compras * d.tasa_cambio END tot_compras, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_exento ELSE a.tot_exento * d.tasa_cambio END tot_exento, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_base ELSE a.tot_base * d.tasa_cambio END tot_base, a.tasa_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_iva ELSE a.tot_iva * d.tasa_cambio END tot_iva, CASE WHEN d.id_moneda = b.id_moneda THEN a.tot_ret ELSE a.tot_ret * d.tasa_cambio END tot_ret, CONCAT(e.name_user, ' ', e.last_user) ela_por, b.logo, a.num_retiva, d.fecha_comp, f.tipo_tdoc, CASE WHEN f.tipo_tdoc = 'M' THEN d.num_tdo ELSE '' END num_factura, d.num_control, CASE WHEN f.tipo_tdoc = 'B' THEN d.num_tdo ELSE '' END num_debito, CASE WHEN f.tipo_tdoc = 'A' THEN d.num_tdo ELSE '' END num_credito, CASE WHEN f.tipo_tdoc = 'M' THEN '01' WHEN f.tipo_tdoc = 'B' THEN '02' ELSE '03' END tipo_tran FROM f3006 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f0014 c ON c.id_ent = a.id_ent INNER JOIN f3004 d ON d.id_cot = a.id_cot INNER JOIN f0002 e ON e.id_user = a.create_user INNER JOIN f3001 f ON f.id_tdoc = d.id_tdo WHERE a.id_cot = {$id}";
            }            
            return $r = DB::query($sql);
            
        }
        static function print_retislr(int $id){
            $sql = "SELECT DISTINCT b.cod_emp, b.nombre_emp, b.rif_empresa, b.dir_emp, c.id_ent, c.nom_ent, c.rif_ent, a.fecha_comp, a.num_tdo, a.fec_fact, a.num_control, (SELECT SUM(x1.monto + x1.mon_iva) * xx.tasa_cambio FROM f3004 xx 
            INNER JOIN f30041 x1 ON x1.id_cot = xx.id_cot INNER JOIN f3999 x2 ON x2.id_emp = xx.id_emp INNER JOIN f3003 x3 ON x3.id = x1.id_concxp AND x3.id <> x2.id_retislr AND x3.id <> x2.id_retiva WHERE xx.id_cot = a.id_cot) total_monto, CASE WHEN a.id_moneda = b.id_moneda THEN d.total_base ELSE d.total_base * a.tasa_cambio END total_base, CASE WHEN a.id_moneda = b.id_moneda THEN d.base_imp ELSE d.base_imp * a.tasa_cambio END base_imp, d.por_reten, d.deducible, CASE WHEN a.id_moneda = b.id_moneda THEN d.total_retenido ELSE d.total_retenido * a.tasa_cambio END total_retenido, b.logo, CONCAT(j.name_user, ' ' , j.last_user) ela_por, d.id_retislr, c.dir_ent FROM f3004 a INNER JOIN f3999 i ON i.id_emp = a.id_emp  INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f30041 e ON e.id_cot = a.id_cot AND e.id_concxp <> i.id_retislr AND e.id_concxp <> i.id_retiva INNER JOIN f0014 c ON c.id_ent = a.id_cli INNER JOIN f3007 d ON d.id_cot = a.id_cot INNER JOIN f3003 f ON f.id = e.id_concxp INNER JOIN f0021 g ON g.id = f.id_retislr INNER JOIN f0002 j ON j.id_user = a.create_user WHERE a.id_cot = {$id}";            
            return $r = DB::query($sql);
        }
        static function listar_doc_fuentes($id_emp, $id, $tipo, $id_cli, $tipo_doc_ori){
            $filter = '';
            if($id != 0){
                $filter = " AND a.id_cot = {$id} ";
            }
            $sql = "SELECT a.id_cot, CONCAT(c.nom_tdoc, ': ', a.num_tdo, ' - Proveedor: ', b.nom_ent) doc FROM `f8020` a INNER JOIN f0014 b ON b.id_ent = a.id_cli
            INNER JOIN f3001 c ON c.id_tdoc = a.id_tdo WHERE a.id_emp = {$id_emp} AND a.id_cli = {$id_cli} AND c.tipo_tdoc != '$tipo' AND c.id_tdoc = {$tipo_doc_ori} " . $filter;
            $r = DB::query($sql);
            return $r;
        }
    }
?>