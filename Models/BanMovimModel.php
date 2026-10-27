<?php

/**
 * Clase creada por José Vargas
 * Fecha 20-12-2024 Hora 11:35 a.m.
 * Transacciones de Movimientos Bancarios
 */
class BanMovimModel extends DB
{
    function __construct()
    {
        parent::__construct();
    }
    static function all()
    {
        $sql = "SELECT DISTINCT a.id_banmov, c.nombre_emp, d.nom_bantmo, CONCAT( g.nombre_banco, ' - ' , e.cuenta_bancue) bancue, a.num_banmov, a.fecha_comp, f.codigo_moneda id_moneda, a.status, a.tasa_cambio, CONCAT(uc.name_user, ' ', uc.last_user, ' el ', DATE_FORMAT(a.create_date, '%d-%m-%Y'), ' a las ', DATE_FORMAT(a.create_date, '%h:%i:%s %p')) create_for, IFNULL(CONCAT(uu.name_user, ' ', uu.last_user, ' el ', DATE_FORMAT(a.modify_date, '%d-%m-%Y'), ' a las ', DATE_FORMAT(uu.modify_date, '%h:%i:%s %p')), ' ') update_for, CASE WHEN a.fecha_comp < em.fec_ban THEN 0 ELSE 1 END borrable FROM f5006 a INNER JOIN f50061 b ON b.id_banmov = a.id_banmov INNER JOIN f0011 c ON c.id_emp = a.id_emp INNER JOIN f5002 d ON d.id_bantmo = a.id_bantmo INNER JOIN f5004 e ON e.id_bancue = a.id_bancue INNER JOIN f0005 f ON f.id_moneda = a.id_moneda INNER JOIN f5003 g ON e.id_banco = g.id_banco LEFT OUTER JOIN f6006 h ON d.efe_bantmo = 'C' AND h.movem_origen != 'CXC' AND h.movem_origen = a.id_banmov LEFT OUTER JOIN f6004 i ON i.id_tmocxc = h.id_tmocxc LEFT OUTER JOIN f3008 j ON d.efe_bantmo = 'P' AND j.movem_origen != 'CXP' AND j.movem_origen = a.id_banmov LEFT OUTER JOIN f3002 k ON k.id_tmocxc = j.id_tmocxp INNER JOIN f0002 uc ON uc.id_user = a.create_user LEFT OUTER JOIN f0002 uu ON uu.id_user = a.modify_user INNER JOIN f0011 em ON em.id_emp = a.id_emp";
        $r = DB::query($sql);
        return $r;
    }
    static function guardar(array $encaban, array $detaban, array $movimCXC, array $movimCXP, int $row)
    {
        //Variables
        $title = '';
        $msg = '';
        $icon = '';
        //Conexión
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        try {
            $link->beginTransaction();
            //            
            $title = "Registro guardado con éxito";
            $icon = "success";
            if ($row == 0) {
                $msg = "Se ha guardado satisfactoriamente el Movimiento Bancario {$encaban['num_banmov']}";
            } else {
                $msg = "Se ha actualizado satisfactoriamente el Movimiento Bancario {$encaban['num_banmov']}";
            }
            //  
            $cols = "";
            $placeholders = "";
            $params = [];
            if ($row == 0) {
                //Nuevo
                // 1. Obtener nombres de columnas
                $cols = array_keys($encaban);
                // 2. Crear placeholders (:campo1, :campo2, ...)
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);
                // 3. Llenar el array de parámetros sin los dos puntos en la clave
                foreach ($encaban as $key => $value) {
                    $params[$key] = $value;
                }
                $colsStr         = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql             = "INSERT INTO f5006 ({$colsStr}) VALUES ({$placeholdersStr})";
                $stmt = $link->prepare($sql);
                $stmt->execute($params);
                $row = $link->lastInsertId();
                $stmt->closeCursor();
                // RELLENAR EL CAMPO EN EL DETALLE
                // Usamos el signo "&" antes de $fila para modificar el array original directamente 
                if (is_array($detaban)) {
                    foreach ($detaban as &$fila) {
                        if (is_array($fila)) {
                            $fila['id_banmov'] = $row;
                        } elseif (is_object($fila)) {
                            $fila->id_banmov = $row;
                        }
                    }
                    unset($fila); // Rompemos la referencia por seguridad
                }
            } else {
                //Update
            
                $setClauses = [];
                foreach ($encaban as $key => $value) {
                    if ($key !== 'id_banmov') { // Excluir la llave primaria del SET
                        $setClauses[] = "{$key} = :{$key}";
                        $params[$key] = $value;
                    }
                }
                // Parámetro para la condición WHERE
                $params['id_banmov'] = $row;
                $sql = "UPDATE f5006 SET " . implode(", ", $setClauses) . " WHERE id_banmov = :id_banmov";
                $stmt = $link->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();
            }
            //Eliminar detalle en caso de que exista            
            $sql = "DELETE FROM f50061 WHERE id_banmov = :id_banmov";
            $stmt = $link->prepare($sql);
            $stmt->execute([':id_banmov' => $row]);
            $stmt->closeCursor();
            //Guardar Detalle de Movimiento Bancario            
            foreach ($detaban as $item) {
                $cols = "";
                $placeholders = "";
                $params = [];
                $cols = array_keys($item);
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);
                $colsStr         = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql             = "INSERT INTO f50061 ({$colsStr}) VALUES ({$placeholdersStr})";
                $stmt = $link->prepare($sql);
                $params = [
                    'id_banmov' => $row,
                    'id_bancon' => $item['id_bancon'],
                    'monto_nac' => $item['monto_nac'],
                    'monto_for' => $item['monto_for'],
                    'id_aux' => $item['id_aux'],
                    'create_user' => $_SESSION['id_user'],
                    'create_date' => getAuditoria(),
                ];
                $stmt->execute($params);
                $stmt->closeCursor();
            }
            //Actualizar Total del Movimiento Bancario, con la suma de los detalles
            $sql = "SELECT SUM(monto_nac) monto_nac, SUM(monto_for) monto_for FROM f50061 WHERE id_banmov = :id_banmov";
            $stmt = $link->prepare($sql);
            $stmt->execute([':id_banmov' => $row]);
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $monto_nac = $rows['monto_nac'];
            $monto_for = $rows['monto_for'];
            $sql = "UPDATE f5006 SET monto_nac = :monto_nac, monto_for = :monto_for WHERE id_banmov = :id_banmov";
            $stmt = $link->prepare($sql);
            $stmt->execute([':monto_nac' => $monto_nac, ':monto_for' => $monto_for, ':id_banmov' => $row]);
            $stmt->closeCursor();
            //Validar si existe el Movimiento par crear la CXC
            if ($movimCXC) {
            }
            //Validar si existe el Movimiento par crear la CXP
            $cols = "";
            $placeholders = "";
            $params = [];            
            if ($movimCXP) {                                
                $next_tmocxc = 0;                
                //Eliminar tanto el encabezado como los detalles de Movimientos de CXP en caso de que existan
                $sql_del = "SELECT id_movement FROM f3008 WHERE movem_origen = :movem_origen";
                $stmt_del = $link->prepare($sql_del);
                $stmt_del->execute([':movem_origen' => $row]);
                $rows_del = $stmt_del->fetchAll(PDO::FETCH_ASSOC);                                
                $stmt_del->closeCursor();
                if (!empty($rows_del)) {
                    // Preparamos las consultas de eliminación una sola vez fuera del ciclo
                    $sql_del_det = "DELETE FROM f30081 WHERE movem_id = :id_movement";
                    $stmt_del_det = $link->prepare($sql_del_det);
                    //
                    $sql_del_enc = "DELETE FROM f3008 WHERE id_movement = :id_movement";
                    $stmt_del_enc = $link->prepare($sql_del_enc);
                    //
                    foreach ($rows_del as $del) {
                        $id_movement = $del['id_movement'];
                        // 2. Primero eliminamos los detalles asociados en f30081
                        $stmt_del_det->execute([':id_movement' => $id_movement]);
                        $stmt_del_det->closeCursor();
                        // 3. Luego eliminamos el encabezado en f3008
                        $stmt_del_enc->execute([':id_movement' => $id_movement]);
                        $stmt_del_enc->closeCursor();
                    }
                }
                foreach ($movimCXP as $grupo) {
                    $encacxp = $grupo['encabezado'];
                    $detacxp = $grupo['detalles'];
                    // Armar query encabezado
                    $id_tmocxp = $encacxp['id_tmocxp'];
                    $idNewMov = null; // Limpiamos la variable por cada grupo                
                    // Buscar el consecutivo del Movimiento
                    if($next_tmocxc == 0){
                        $sql = "SELECT next_tmocxc FROM f3002 WHERE id_tmocxc = :id_tmocxp";
                        $stmt = $link->prepare($sql);
                        $stmt->execute([':id_tmocxp' => $id_tmocxp]);
                        $next_tmocxc = ($stmt->fetchColumn());
                    }else{
                        $next_tmocxc++;
                    }

                    $cols = array_keys($encacxp);
                    $placeholders = array_map(function ($col) {
                        return ":{$col}";
                    }, $cols);
                    //Descripción del Movimiento
                    $sql_des = "SELECT CONCAT(b.cod_bantmo, '-', c.cuenta_bancue, '-', a.num_banmov) descrip, CONCAT(b.cod_bantmo, c.cuenta_bancue, a.num_banmov) cont FROM f5006 a INNER JOIN f5002 b ON b.id_bantmo = a.id_bantmo INNER JOIN f5004 c ON c.id_bancue = a.id_bancue WHERE a.id_banmov = :id_banmov";
                    $stmt_des = $link->prepare($sql_des);
                    $stmt_des->execute([':id_banmov' => $row]);
                    $descrip = $stmt_des->fetch();
                    $stmt_des->closeCursor();
                    //
                    $descrip = "PAGO Según {$descrip['descrip']}";
                    $encacxp['movem_origen'] = $row;
                    $encacxp['movem_number'] = $next_tmocxc; // Usamos el consecutivo directo
                    $encacxp['movem_descrip'] = $descrip;
                    $params = [];
                    foreach ($encacxp as $key => $value) {
                        $params[$key] = $value;
                    }

                    $colsStr         = implode(", ", $cols);
                    $placeholdersStr = implode(", ", $placeholders);
                    $sql             = "INSERT INTO f3008 ({$colsStr}) VALUES ({$placeholdersStr})";

                    // Guardar encabezado de este grupo específico   
                    $stmt = $link->prepare($sql);
                    $stmt->execute($params);
                    $idNewMov = $link->lastInsertId(); // <--- AQUÍ OBTENEMOS EL ID ÚNICO PARA ESTE ENCABEZADO
                    $stmt->closeCursor();
                    // Guardar los detalles correspondientes a ESTE encabezado específico
                    foreach ($detacxp as $item) {
                        $params_det = [
                            'movem_id'     => $idNewMov, // Vinculado al ID correcto del encabezado actual
                            'id_tdo'       => $item['id_tdo'],
                            'num_tdo'      => $item['num_tdo'],
                            'id_cot'       => $item['id_cot'],
                            'monto_doc'    => $item['monto_doc'],
                            'create_user'  => $_SESSION['id_user'],
                            'create_date'  => getAuditoria()
                        ];

                        $sql_det = "INSERT INTO f30081 
                    (movem_id, id_tdo, num_tdo, id_cot, monto_doc, create_user, create_date) 
                    VALUES 
                    (:movem_id, :id_tdo, :num_tdo, :id_cot, :monto_doc, :create_user, :create_date)";

                        $stmt_det = $link->prepare($sql_det);
                        $stmt_det->execute($params_det);
                        $stmt_det->closeCursor();
                    }
                }                
                $sql_up = "UPDATE f3002 SET next_tmocxc = :next_tmocxc, modify_user = :modify_user, modify_date = :modify_date WHERE id_tmocxc = :id_tmocxp";
                $stmt_up = $link->prepare($sql_up);
                $stmt_up->execute([
                    ':next_tmocxc' => $next_tmocxc + 1,
                    ':modify_user' => $_SESSION['id_user'],
                    ':modify_date' => getAuditoria(),
                    ':id_tmocxp' => $id_tmocxp
                ]);
            }
            if ($movimCXC) {                  
                $next_tmocxc = 0;                
                //Eliminar tanto el encabezado como los detalles de Movimientos de CXP en caso de que existan
                $sql_del = "SELECT id_movement FROM f6006 WHERE movem_origen = :movem_origen";
                $stmt_del = $link->prepare($sql_del);
                $stmt_del->execute([':movem_origen' => $row]);
                $rows_del = $stmt_del->fetchAll(PDO::FETCH_ASSOC);                                
                $stmt_del->closeCursor();
                if (!empty($rows_del)) {
                    // Preparamos las consultas de eliminación una sola vez fuera del ciclo
                    $sql_del_det = "DELETE FROM f60061 WHERE movem_id = :id_movement";
                    $stmt_del_det = $link->prepare($sql_del_det);
                    //
                    $sql_del_enc = "DELETE FROM f6006 WHERE id_movement = :id_movement";
                    $stmt_del_enc = $link->prepare($sql_del_enc);
                    //
                    foreach ($rows_del as $del) {
                        $id_movement = $del['id_movement'];
                        // 2. Primero eliminamos los detalles asociados en f60061
                        $stmt_del_det->execute([':id_movement' => $id_movement]);
                        $stmt_del_det->closeCursor();
                        // 3. Luego eliminamos el encabezado en f6006
                        $stmt_del_enc->execute([':id_movement' => $id_movement]);
                        $stmt_del_enc->closeCursor();
                    }
                }                
                $next_tmocxc = 0;
                foreach ($movimCXC as $grupo) {                    
                    $encacxc = $grupo['encabezado'];
                    $detacxc = $grupo['detalles'];
                    // Armar query encabezado                    
                    $id_tmocxc = $encacxc['id_tmocxc'];
                    $idNewMov = null; // Limpiamos la variable por cada grupo                         
                    // Buscar el consecutivo del Movimiento                    
                    if($next_tmocxc == 0){                        
                        $sql = "SELECT next_tmocxc FROM f6004 WHERE id_tmocxc = :id_tmocxc";
                        $stmt = $link->prepare($sql);
                        $stmt->execute([':id_tmocxc' => $id_tmocxc]);
                        $next_tmocxc = ($stmt->fetchColumn());
                    }else{
                        $next_tmocxc++;
                    }                         
                    $cols = array_keys($encacxc);
                    $placeholders = array_map(function ($col) {
                        return ":{$col}";
                    }, $cols);
                    //Descripción del Movimiento
                    $sql_des = "SELECT CONCAT(b.cod_bantmo, '-', c.cuenta_bancue, '-', a.num_banmov) descrip, CONCAT(b.cod_bantmo, c.cuenta_bancue, a.num_banmov) cont FROM f5006 a INNER JOIN f5002 b ON b.id_bantmo = a.id_bantmo INNER JOIN f5004 c ON c.id_bancue = a.id_bancue WHERE a.id_banmov = :id_banmov";
                    $stmt_des = $link->prepare($sql_des);
                    $stmt_des->execute([':id_banmov' => $row]);
                    $descrip = $stmt_des->fetch();
                    $stmt_des->closeCursor();
                    //
                    $descrip = "COBRO Según {$descrip['descrip']}";
                    //
                    $encacxc['movem_descrip'] = $descrip;
                    $encacxc['movem_origen'] = $row;
                    $encacxc['movem_number'] = $next_tmocxc; // Usamos el consecutivo directo    
                   
                    $params = [];
                    foreach ($encacxc as $key => $value) {
                        $params[$key] = $value;
                    }

                    $colsStr         = implode(", ", $cols);
                    $placeholdersStr = implode(", ", $placeholders);
                    $sql             = "INSERT INTO f6006 ({$colsStr}) VALUES ({$placeholdersStr})";

                    // Guardar encabezado de este grupo específico   
                    $stmt = $link->prepare($sql);
                    $stmt->execute($params);
                    $idNewMov = $link->lastInsertId(); // <--- AQUÍ OBTENEMOS EL ID ÚNICO PARA ESTE ENCABEZADO
                    $stmt->closeCursor();                    
                    // Guardar los detalles correspondientes a ESTE encabezado específico
                    foreach ($detacxc as $item) {
                        $params_det = [
                            'movem_id'     => $idNewMov, // Vinculado al ID correcto del encabezado actual
                            'id_tdo'       => $item['id_tdo'],
                            'num_tdo'      => $item['num_tdo'],
                            'id_cot'       => $item['id_cot'],
                            'monto_doc'    => $item['monto_doc'],
                            'create_user'  => $_SESSION['id_user'],
                            'create_date'  => getAuditoria(),
                            'mon_ret'      => $item['mon_ret'],
                            'num_ret'      => $item['num_ret'],
                            'monto_doc_for'=> $item['monto_doc_for'],
                            'monto_ret_for'=> $item['monto_ret_for']
                        ];

                        $sql_det = "INSERT INTO f60061 
                    (movem_id, id_tdo, num_tdo, id_cot, monto_doc, create_user, create_date, mon_ret, num_ret, monto_doc_for, monto_ret_for) 
                    VALUES 
                    (:movem_id, :id_tdo, :num_tdo, :id_cot, :monto_doc, :create_user, :create_date, :mon_ret, :num_ret, :monto_doc_for, :monto_ret_for)";

                        $stmt_det = $link->prepare($sql_det);
                        $stmt_det->execute($params_det);
                        $stmt_det->closeCursor();
                    }                    
                }            
                $sql_up = "UPDATE f6004 SET next_tmocxc = :next_tmocxc, modify_user = :modify_user, modify_date = :modify_date WHERE id_tmocxc = :id_tmocxc";
                $stmt_up = $link->prepare($sql_up);
                $stmt_up->execute([
                    ':next_tmocxc' => $next_tmocxc + 1,
                    ':modify_user' => $_SESSION['id_user'],
                    ':modify_date' => getAuditoria(),
                    ':id_tmocxc' => $id_tmocxc
                ]);
            }
            //
            $link->commit();
            return [
                "title" => $title,
                "icon" => $icon,
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
    static function actualizar($id, $data){
        return $r = DB::update('f5006', $data, ['id_banmov' => $id]);
    }
    static function borrar_det_mov($id){
        return $r = DB::delete('f50061', ['id_banmov' => $id]);
    }
    static function guardar_det($data){
        return $r = DB::insert('f50061', $data);
    }
    static function edit(int $id){
        $r = DB::query("SELECT a.id_banmov, b.cod_bantmo, b.nom_bantmo, a.num_banmov FROM f5006 a INNER JOIN f5002 b ON b.id_bantmo = a.id_bantmo WHERE a.id_banmov = {$id}");
        return $r[0];
    }
    static function show_row(int $id){
        //$sql = "SELECT a.id_emp, a.id_bantmo, e.id_bancue, a.fecha_comp, a.num_banmov, a.id_moneda, a.tasa_cambio, a.des_banmov, a.status, b.id_bancon, CONCAT(h.cod_bancon, ' - ', h.nom_bancon) nom_bancon, IFNULL(b.id_aux, ' ') id_aux, IFNULL(CASE WHEN IFNULL(b.id_aux, ' ')  = ' ' THEN ' ' ELSE CONCAT(i.cod_aux, ' - ', i.nombre_aux) END, ' ') nom_aux, b.monto_nac, b.monto_for, d.efe_bantmo, a.id_ent, d.acc_bantmo, IFNULL(a.benef_banmov, ' ') benef_banmov FROM f5006 a INNER JOIN f50061 b ON b.id_banmov = a.id_banmov INNER JOIN f0011 c ON c.id_emp = a.id_emp INNER JOIN f5002 d ON d.id_bantmo = a.id_bantmo INNER JOIN f5004 e ON e.id_bancue = a.id_bancue INNER JOIN f5003 f ON f.id_banco = e.id_banco INNER JOIN f0005 g ON g.id_moneda = a.id_moneda INNER JOIN f5005 h ON h.id_bancon = b.id_bancon LEFT OUTER JOIN f0009 i ON i.id_aux = b.id_aux WHERE a.id_banmov = {$id} ORDER BY CONCAT(h.cod_bancon, ' - ', h.nom_bancon)";     
        $sql = "SELECT 
            -- Datos del Encabezado y Detalle del Banco (f5006 / f50061)
            a.id_emp, 
            a.id_bantmo, 
            e.id_bancue, 
            a.fecha_comp AS fecha_comp, 
            a.num_banmov, 
            a.id_moneda AS id_moneda, 
            a.tasa_cambio AS tasa_cambio, 
            a.des_banmov, 
            a.status, 
            b.id_bancon, 
            CONCAT(h.cod_bancon, ' - ', h.nom_bancon) AS nom_bancon, 
            IFNULL(b.id_aux, ' ') AS id_aux, 
            IFNULL(CASE WHEN IFNULL(b.id_aux, ' ') = ' ' THEN ' ' ELSE CONCAT(i.cod_aux, ' - ', i.nombre_aux) END, ' ') AS nom_aux, 
            b.monto_nac, 
            b.monto_for, 
            d.efe_bantmo, 
            a.id_ent, 
            d.acc_bantmo, 
            d.id_cxtmo,
            IFNULL(a.benef_banmov, ' ') AS benef_banmov,
            j.id_bancon_CXP,
            IFNULL(k.nom_ent, '') nom_ent
            FROM f5006 a
            INNER JOIN f50061 b ON b.id_banmov = a.id_banmov
            INNER JOIN f0011 c ON c.id_emp = a.id_emp
            INNER JOIN f5002 d ON d.id_bantmo = a.id_bantmo
            INNER JOIN f5004 e ON e.id_bancue = a.id_bancue
            INNER JOIN f5003 f ON f.id_banco = e.id_banco
            INNER JOIN f0005 g ON g.id_moneda = a.id_moneda
            INNER JOIN f5005 h ON h.id_bancon = b.id_bancon
            INNER JOIN f5999 j ON j.id_emp = a.id_emp
            LEFT OUTER JOIN f0009 i ON i.id_aux = b.id_aux
            LEFT OUTER JOIN f0014 k ON k.id_ent = a.id_ent
            WHERE a.id_banmov = {$id}";            
        return $r = DB::query($sql);
    }
    static function det_doc_can($id, $efecto)
    {
        if ($efecto == 'C') {
            $sql = "SELECT row_number() OVER (ORDER BY c.id_cot)     item, d.tipo_codigo, d.nom_tdoc, c.num_tdo, c.fecha_comp, c.fecha_venci, e.codigo_moneda, a.tasa_cambio, c.id_cot id_doc,  b.num_ret, f.cod_tmocxc, f.des_tmocxc, a.movem_number, a.tasa_cambio, g.nom_ent,  CASE WHEN c.id_moneda = h.id_moneda THEN c.mon_doc WHEN c.id_moneda != h.id_moneda THEN c.mon_doc * c.tasa_cambio END mon_doc, CASE WHEN c.id_moneda = h.id_moneda THEN c.sal_doc WHEN c.id_moneda != h.id_moneda THEN c.sal_doc * c.tasa_cambio END sal_doc, CASE WHEN c.id_moneda = h.id_moneda THEN b.monto_doc WHEN c.id_moneda != h.id_moneda THEN b.monto_doc * c.tasa_cambio END mon_can, CASE WHEN c.id_moneda = h.id_moneda THEN b.mon_ret WHEN c.id_moneda != h.id_moneda THEN b.mon_ret * c.tasa_cambio END mon_ret FROM f6006 a INNER JOIN f60061 b ON b.movem_id = a.id_movement INNER JOIN f6003 c ON c.id_cot = b.id_cot  INNER JOIN f6001 d ON d.id_tdoc = c.id_tdo  INNER JOIN f0005 e ON e.id_moneda = a.id_moneda INNER JOIN f6004 f ON f.id_tmocxc = a.id_tmocxc INNER JOIN f0014 g ON g.id_ent = a.id_cli INNER JOIN f5006 h ON h.id_banmov = a.movem_origen WHERE a.movem_origen = {$id}";
        } else {
            $sql = "SELECT row_number() OVER (ORDER BY c.id_cot)  item, d.tipo_codigo, d.nom_tdoc, c.num_tdo, c.fecha_comp, c.fecha_venci, e.codigo_moneda, a.tasa_cambio, c.id_cot id_doc,  f.cod_tmocxc, f.des_tmocxc, a.movem_number, a.tasa_cambio, g.nom_ent, CASE WHEN c.id_moneda = h.id_moneda THEN c.mon_doc WHEN c.id_moneda != h.id_moneda THEN c.mon_doc * c.tasa_cambio END mon_doc, CASE WHEN c.id_moneda = h.id_moneda THEN c.sal_doc WHEN c.id_moneda != h.id_moneda THEN c.sal_doc * c.tasa_cambio END sal_doc, CASE WHEN c.id_moneda = h.id_moneda THEN b.monto_doc WHEN c.id_moneda != h.id_moneda THEN b.monto_doc * c.tasa_cambio END mon_can FROM f3008 a INNER JOIN f30081 b ON b.movem_id = a.id_movement INNER JOIN f3004 c ON c.id_cot = b.id_cot  INNER JOIN f3001 d ON d.id_tdoc = c.id_tdo INNER JOIN f0005 e ON e.id_moneda = a.id_moneda INNER JOIN f3002 f ON f.id_tmocxc = a.id_tmocxp INNER JOIN f0014 g ON g.id_ent = a.id_ent INNER JOIN f5006 h ON h.id_banmov = a.movem_origen WHERE a.movem_origen = {$id}";
        }
        $r = DB::query($sql);
        return $r;
    }
    static function destroy(int $id){
        $db = new Conexion(); // Obtener la instancia del PDO
        $link = (object)$db->conect();

        try {
            $link->beginTransaction();

            // 1. Eliminar detalle y encabezado de Banco (f50061 y f5006)
            $sql_ban_det = "DELETE FROM f50061 WHERE id_banmov = :id_banmov";
            $sql_ban_enc = "DELETE FROM f5006 WHERE id_banmov = :id_banmov";

            $stmt_ban_det = $link->prepare($sql_ban_det);
            $stmt_ban_enc = $link->prepare($sql_ban_enc);

            $stmt_ban_det->execute([':id_banmov' => $id]);
            $stmt_ban_det->closeCursor();

            $stmt_ban_enc->execute([':id_banmov' => $id]);
            $stmt_ban_enc->closeCursor();

            // 2. Obtener los IDs de Movimientos de Cuentas por Pagar asociados
            $sql_get_cxp = "SELECT id_movement FROM f3008 WHERE movem_origen = :movem_origen";
            $stmt_get_cxp = $link->prepare($sql_get_cxp);
            $stmt_get_cxp->execute([':movem_origen' => $id]);
            $rows = $stmt_get_cxp->fetchAll(PDO::FETCH_ASSOC);
            $stmt_get_cxp->closeCursor();

            // 3. Recorrer y eliminar detalles de CXP (f30081)
            if (!empty($rows)) {
                $sql_del_det = "DELETE FROM f30081 WHERE movem_id = :movem_id";
                $stmt_del_det = $link->prepare($sql_del_det);

                foreach ($rows as $item) {
                    $stmt_del_det->execute([':movem_id' => $item['id_movement']]);
                    $stmt_del_det->closeCursor();
                }
            }

            // 4. Eliminar encabezado de Movimientos de CXP (f3008)
            $sql_del_enc = "DELETE FROM f3008 WHERE movem_origen = :movem_origen";
            $stmt_del_enc = $link->prepare($sql_del_enc);
            $stmt_del_enc->execute([':movem_origen' => $id]);
            $stmt_del_enc->closeCursor();

            // 2. Obtener los IDs de Movimientos de Cuentas por Cobrar asociados
            $sql_get_cxp = "SELECT id_movement FROM f6006 WHERE movem_origen = :movem_origen";
            $stmt_get_cxp = $link->prepare($sql_get_cxp);
            $stmt_get_cxp->execute([':movem_origen' => $id]);
            $rows = $stmt_get_cxp->fetchAll(PDO::FETCH_ASSOC);
            $stmt_get_cxp->closeCursor();

            // 3. Recorrer y eliminar detalles de CXP (f30081)
            if (!empty($rows)) {
                $sql_del_det = "DELETE FROM f60061 WHERE movem_id = :movem_id";
                $stmt_del_det = $link->prepare($sql_del_det);

                foreach ($rows as $item) {
                    $stmt_del_det->execute([':movem_id' => $item['id_movement']]);
                    $stmt_del_det->closeCursor();
                }
            }

            // 4. Eliminar encabezado de Movimientos de CXP (f3008)
            $sql_del_enc = "DELETE FROM f6006 WHERE movem_origen = :movem_origen";
            $stmt_del_enc = $link->prepare($sql_del_enc);
            $stmt_del_enc->execute([':movem_origen' => $id]);
            $stmt_del_enc->closeCursor();

            // Confirmar transacción
            $link->commit();

            return [
                'success' => true
            ];
        } catch (\PDOException $e) {
            $link->rollback();
            return [
                "success" => false,
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'] ?? '')
            ];
        }
    }
    static function print_mov($id){
        $sql = "SELECT row_number() OVER (ORDER BY b.id_bandet) item, c.id_emp, c.nombre_emp, d.id_bantmo, d.cod_bantmo, d.nom_bantmo, d.acc_bantmo, f.cod_banco, f.nombre_banco, e.id_bancue, e.cuenta_bancue, a.fecha_comp, a. num_banmov, a.id_moneda, a.tasa_cambio, a.benef_banmov, a.des_banmov, a.monto_nac, a.monto_for, CONCAT(j.name_user, ' ', j.last_user) user_create, h.cod_bancon, h.nom_bancon, i.cod_cta, i.nombre_cta, IFNULL(k.cod_aux, l.cod_aux) cod_aux, IFNULL(k.nombre_aux, l.nombre_aux) nombre_aux, b.monto_nac, b.monto_for, g.codigo_moneda, g.nombre_moneda, c.logo, c.rif_empresa, a.id_banmov, d.efe_bantmo 
        FROM `f5006` a
        INNER JOIN f50061 b ON b.id_banmov = a.id_banmov
        INNER JOIN f0011 c ON c.id_emp = a.id_emp
        INNER JOIN f5002 d ON d.id_bantmo = a.id_bantmo
        INNER JOIN f5004 e ON e.id_bancue = a.id_bancue
        INNER JOIN f5003 f ON f.id_banco = e.id_banco
        INNER JOIN f0005 g ON g.id_moneda = a.id_moneda
        INNER JOIN f5005 h ON h.id_bancon = b.id_bancon
        INNER JOIN f0010 i ON i.id_cta = h.id_ctb
        INNER JOIN f0002 j ON j.id_user = a.create_user
        LEFT OUTER JOIN f0009 k ON k.id_aux = h.id_aux
        LEFT OUTER JOIN f0009 l ON l.id_aux = b.id_aux
        WHERE a.id_banmov = {$id} 
        ORDER BY b.id_bandet";
        $r = DB::query($sql);
        return $r;
    }
    static function banmov_cuentas($id_emp, $fec_ini, $fec_fin, $id_bancue, $id_bancon){
        $filter =  "WHERE a.id_emp = {$id_emp} AND a.fecha_comp BETWEEN '$fec_ini' AND '$fec_fin' ";
        $filter_con = "";
        if ($id_bancue) {
            $filter .= "AND a.id_bancue = {$id_bancue} ";
        }
        if ($id_bancon) {
            $filter .= "AND b.id_bancon = {$id_bancon} ";
            $filter_con = " AND id_bancon = {$id_bancon}";
        }
        $sql = "SELECT row_number() OVER (ORDER BY a.fecha_comp) item, c.id_emp, c.nombre_emp, d.id_bantmo, d.cod_bantmo, CASE WHEN IFNULL(m.id_ent,0) > 0 THEN CONCAT(d.nom_bantmo, ' - ' , m.nom_ent) WHEN IFNULL(a.benef_banmov, '') = '' THEN d.nom_bantmo  ELSE CONCAT(d.nom_bantmo, ' - ' , a.benef_banmov) END  nom_bantmo, d.acc_bantmo, f.cod_banco, f.nombre_banco, e.id_bancue, e.cuenta_bancue, a.fecha_comp, a. num_banmov, a.id_moneda, a.tasa_cambio, a.des_banmov, g.codigo_moneda, g.nombre_moneda, c.logo, c.rif_empresa, fn_saldo_banco(a.id_emp, a.fecha_comp, a.id_bancue) saldo, (SELECT SUM(monto_nac) FROM f50061 WHERE id_banmov = a.id_banmov $filter_con) mon_mov_nac, (SELECT SUM(monto_for) FROM f50061 WHERE id_banmov = a.id_banmov $filter_con) mon_mov_for
        FROM `f5006` a
        INNER JOIN f50061 b ON b.id_banmov = a.id_banmov
        INNER JOIN f0011 c ON c.id_emp = a.id_emp
        INNER JOIN f5002 d ON d.id_bantmo = a.id_bantmo
        INNER JOIN f5004 e ON e.id_bancue = a.id_bancue
        INNER JOIN f5003 f ON f.id_banco = e.id_banco
        INNER JOIN f0005 g ON g.id_moneda = a.id_moneda
        INNER JOIN f5005 h ON h.id_bancon = b.id_bancon
        INNER JOIN f0010 i ON i.id_cta = h.id_ctb
        INNER JOIN f0002 j ON j.id_user = a.create_user
        LEFT OUTER JOIN f0009 k ON k.id_aux = h.id_aux
        LEFT OUTER JOIN f6006 l ON l.movem_origen = a.id_banmov
        LEFT OUTER JOIN f0014 m ON m.id_ent = l.id_cli
        " . $filter . "
        GROUP BY c.id_emp, c.nombre_emp, d.id_bantmo, d.cod_bantmo, CASE WHEN IFNULL(m.id_ent,0) > 0 THEN CONCAT(d.nom_bantmo, ' - ' , m.nom_ent) WHEN IFNULL(a.benef_banmov, '') = '' THEN d.nom_bantmo  ELSE CONCAT(d.nom_bantmo, ' - ' , a.benef_banmov) END, d.acc_bantmo, f.cod_banco, f.nombre_banco, e.id_bancue, e.cuenta_bancue, a.fecha_comp, a. num_banmov, a.id_moneda, a.tasa_cambio, a.des_banmov, g.codigo_moneda, g.nombre_moneda, c.logo, c.rif_empresa, fn_saldo_banco(a.id_emp, a.fecha_comp, a.id_bancue) ORDER BY a.id_emp, a.fecha_comp, d.acc_bantmo";
        $r = DB::query($sql);
        return $r;
    }
    static function getConfigBanco(int $id_emp, $efecto){
        //Conexión
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        $sql = "";
        try {
            if( $efecto == 'P'){
                $sql = "SELECT a.id_bancon_CXP, CONCAT(c.cod_bancon, ' - ', c.nom_bancon) nom_con, IFNULL(d.id_aux, ' ') id_aux, IFNULL(CONCAT(d.cod_aux, ' - ', d.nombre_aux), '') nom_aux FROM `f5999` a INNER JOIN `f0011` b ON b.id_emp = a.id_emp INNER JOIN `f5005` c ON c.id_bancon = a.id_bancon_CXP LEFT OUTER JOIN f0009 d ON d.id_aux = c.id_aux WHERE a.id_emp = :id_emp";
            }else if($efecto == 'C'){
                $sql = "SELECT a.id_bancon_CXC id_bancon_CXP, CONCAT(c.cod_bancon, ' - ', c.nom_bancon) nom_con, IFNULL(d.id_aux, ' ') id_aux, IFNULL(CONCAT(d.cod_aux, ' - ', d.nombre_aux), '') nom_aux, a.id_bancon_RETIVA, CONCAT(cc.cod_bancon, ' - ', cc.nom_bancon) nom_con_ret, IFNULL(dd.id_aux, ' ') id_aux_ret, IFNULL(CONCAT(dd.cod_aux, ' - ', dd.nombre_aux), '') nom_aux_ret FROM `f5999` a INNER JOIN `f0011` b ON b.id_emp = a.id_emp INNER JOIN `f5005` c ON c.id_bancon = a.id_bancon_CXC LEFT OUTER JOIN f0009 d ON d.id_aux = c.id_aux INNER JOIN `f5005` cc ON cc.id_bancon = a.id_bancon_RETIVA LEFT OUTER JOIN f0009 dd ON dd.id_aux = cc.id_aux WHERE a.id_emp = :id_emp";
            }         
            $stmt = $link->prepare($sql);
            $stmt->execute([':id_emp' => $id_emp]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return [
                'status' => true,
                'data' => $row
            ];
        } catch (\Throwable $th) {
            return false;
        }
    }
}

