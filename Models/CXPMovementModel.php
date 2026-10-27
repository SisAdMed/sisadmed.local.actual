<?php
class CXPMovementModel extends DB
{
    public function __construct()
    {
        parent::__construct();
    }
    static function all()
    {
        return $r = DB::query("SELECT * FROM f3008 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3002 c ON c.id_tmocxc = a.id_tmocxp INNER JOIN f0014 d ON d.id_ent = a.id_ent INNER JOIN f0005 e on e.id_moneda = a.id_moneda");
    }
    static function listar_tipos_mov(string $efecto)
    {
        $filter = '';
        if ($efecto) {
            $efecto = " AND acc_tmocxc = 'D' ";
        }
        return $r = DB::query("SELECT * from f3002 WHERE status = 1" . $efecto);
    }
    static function val_tmo(int $id)
    {
        return $r = DB::query("SELECT * FROM f3002 WHERE id_tmocxc = {$id}");
    }
    static function nextNumber(int $id_emp, int $id)
    {
        $nextNumber = DB::query("SELECT * FROM f3002 WHERE id_emp = {$id_emp} AND id_tmocxc = {$id} LIMIT 1");
        return $nextNumber[0];
    }
    static function guardar(array $enca, array $deta, int $row)
    {
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        $id_tmocxc = $enca["id_tmocxp"];
        $next_number_flag = false;
        $next_number = $enca['movem_number'];
        $newID = '';
        $msg = '';
        $monto_enca = 0;
        try {
            $link->beginTransaction();
            //Verificar si Usa consecutivo el Tipo de Movimiento
            $sql = "SELECT * FROM f3002 WHERE id_tmocxc = :id_tmocxc";
            $stmt = $link->prepare($sql);
            $stmt->execute([':id_tmocxc' => $id_tmocxc]);
            $con_doc = $stmt->fetch(PDO::FETCH_ASSOC);
            $nom_doc = $con_doc['des_tmocxc'];
            $tipo_tdoc = $con_doc['cod_tmocxc'];
            $stmt->closeCursor();
            //Verificar si usa consecutivo
            $msg = "Se ha actualizado satisfactoriamente el Movimiento $tipo_tdoc $nom_doc Nro. $next_number"  . PHP_EOL;
            if ($row == 0) {
                if ($con_doc['con_tmocxc'] == "S") {
                    $next_number_flag = true;
                    $next_number = floatval($con_doc['next_tmocxc']);
                    $enca['movem_number'] = $next_number;
                }
                $msg = "Se ha guardado satisfactoriamente el Movimiento $tipo_tdoc $nom_doc Nro. $next_number" . PHP_EOL;
            }

            //
            $cols = "";
            $placeholders = "";
            $params = [];
            // 1 Crear el Encabezado de Movimientos de Cuentas por Pagar
            if ($row == 0) {
                // 1 Obtener nombres de columnas
                $cols = array_keys($enca);
                // 2 Crer placeholders
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);
                // 3 Llenar el array de parametros son los dos puntos en la clave
                foreach ($enca as $key => $value) {
                    $params[$key] = $value;
                };
                // 4 Armar la sentencia SQL
                $colsStr = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql = "INSERT INTO f3008 ({$colsStr}) VALUES ({$placeholdersStr})";
                $stmt = $link->prepare($sql);
                $stmt->execute($params);
                $newID =  $link->lastInsertId();
                $stmt->CloseCursor();
            } else {
                $newID = $row;
                //Para el actualizar
                $setClauses = [];
                foreach ($enca as $key => $value) {
                    if ($key !== 'id_movement') { // Excluir la llave primaria del SET
                        $setClauses[] = "{$key} = :{$key}";
                        $params[$key] = $value;
                    }
                }
                // Parámetro para la condición WHERE
                $params['id_movement'] = $newID;
                $sql = "UPDATE f3008 SET " . implode(", ", $setClauses) . " WHERE id_movement = :id_movement";
                $stmt = $link->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();
            }
            //Borrar detalles
            $sql = "DELETE FROM f30081 WHERE movem_id = :movem_id";
            $stmt = $link->prepare($sql);
            $stmt->execute([':movem_id' => $row]);
            $stmt->closeCursor();
            //Guardar detalles
            // RELLENAR EL CAMPO EN EL DETALLE
            // Usamos el signo "&" antes de $fila para modificar el array original directamente            
            if ($row == 0) {
                if (is_array($deta)) {
                    foreach ($deta as &$fila) {
                        if (is_array($fila)) {
                            $fila['movem_id'] = $newID;
                        } elseif (is_object($fila)) {
                            $fila->movem_id = $newID;
                        }
                    }
                    unset($fila); // Rompemos la referencia por seguridad
                }
            }
            $monto_enca = 0;
            foreach ($deta as $item) {
                $cols = "";
                $placeholders = "";
                $params = [];
                // 1. Crear titulos de columnas (campo1, campo2, ...)
                $cols = array_keys($item);
                // 2. Crear placeholders (:campo1, :campo2, ...)
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);
                // 4. Armar la sentencia SQL                    
                $colsStr         = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql         = "INSERT INTO f30081 ({$colsStr}) VALUES ({$placeholdersStr})";
                $stmt = $link->prepare($sql);
                $params = [
                    ':movem_id' => $newID,
                    ':id_tdo' => $item['id_tdo'],
                    ':num_tdo' => $item['num_tdo'],
                    ':id_cot' => $item['id_cot'],
                    ':monto_doc' => convert_string_to_number($item['monto_doc']),
                    ':create_user' => $_SESSION['id_user'],
                    ':create_date' => getAuditoria()
                ];
                //Acumulador Monto de Encabezado
                $monto_enca += convert_string_to_number($item['monto_doc']);
                $stmt->execute($params);
                $stmt->closeCursor();
            }
            //Actualizar monto del encabezado
            $sql = "UPDATE f3008 SET movem_amount = :movem_amount WHERE id_movement = :id_movement ";
            $stmt = $link->prepare($sql);
            $stmt->execute([':movem_amount' => $monto_enca, ':id_movement' => $newID]);
            $stmt->closeCursor();
            //Actualizar el proximo Número de Movimiento
            if ($next_number_flag) {
                $sql = "UPDATE f3002 SET next_tmocxc = :next_tmocxc WHERE id_tmocxc = :id_tmocxc";
                $stmt = $link->prepare($sql);
                $stmt->execute([':id_tmocxc' => $id_tmocxc, ':next_tmocxc' => $next_number + 1]);
                $stmt->closeCursor();
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
    static function setNextNumber(int $id_emp, int $id, array $data)
    {
        return $r = DB::update("f3002", $data, ['id_tmocxc' => $id]);
    }
    static function actualizar(int $id, array $data)
    {
        return $r = DB::update('f3008', $data, ['id_movement' => $id]);
    }
    static function borrarDetCXPMovement(int $id)
    {
        return $r = DB::delete('f30081', ['movem_id' => $id]);
    }
    static function guardarDetMovement(array $data)
    {
        return $r = DB::insert('f30081', $data);
    }
    static function updateDocBalance(int $id, float $monto)
    {
        return $r = DB::query("UPDATE f3004 SET sal_doc = sal_doc - '" . $monto . "' WHERE id_cot = {$id}");
    }
    static function edit(int $id)
    {
        return $r = DB::query("SELECT * FROM f3008 a INNER JOIN f0011 b ON b.id_emp = a.id_emp INNER JOIN f3002 c ON c.id_tmocxc = a.id_tmocxp INNER JOIN f0014 d ON d.id_ent = a.id_ent INNER JOIN f0005 e ON e.id_moneda = a.id_moneda WHERE a.id_movement = {$id}");
    }
    static function show_row(int $id)
    {
        $r = DB::query("SELECT a.id_emp, a.id_tmocxp, a.movem_number, a.id_ent, a.fecha_comp, a.id_moneda, a.tasa_cambio, a.movem_descrip, a.status, b.id_tdo, c.num_tdo, c.fecha_comp fecha_emi, c.fecha_venci, c.id_cot, b.monto_doc, DATEDIFF(a.fecha_comp, c.fecha_comp) dias_venci, ROUND(c.mon_doc - (SELECT SUM(IFNULL(monto_doc, 0)) FROM f30081 WHERE id_cot = c.id_cot AND a.status = 1), 2) saldo, d.tipo_codigo, d.nom_tdoc, e.codigo_moneda, c.mon_doc, c.sal_doc, f.id_ent id_cli, f.nom_ent, a.movem_origen FROM f3008 a INNER JOIN f30081 b ON b.movem_id = a.id_movement INNER JOIN f3004 c ON c.id_cot = b.id_cot INNER JOIN f3001 d ON d.id_tdoc = c.id_tdo INNER JOIN f0005 e ON e.id_moneda = a.id_moneda INNER JOIN f0014 f ON f.id_ent = a.id_ent WHERE a.id_movement = {$id}");
        return $r;
    }
    static function destroy(int $id)
    {
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        try {
            $link->beginTransaction();
            //Borrar Detalle de Movimiento de Cuentas por Pagar
            $sql = "DELETE FROM f30081 WHERE movem_id = :movem_id";
            $stmt = $link->prepare($sql);
            $stmt->execute([':movem_id' => $id]);
            $stmt->closeCursor();
            //Borrar Encabezado de Movimiento de Cuentas por Pagar
            $sql = "DELETE FROM f3008 WHERE id_movement = :movem_id";
            $stmt = $link->prepare($sql);
            $stmt->execute([':movem_id' => $id]);
            $stmt->closeCursor();
            $link->commit();
            return [
                'success' => true,
                "title" => 'El movimiento eliminado',
                "icon" => 'success',
                "msg" => 'Se ha eliminado el Movimiento satisfactoriamente'
            ];
        } catch (\PDOException $e) {
            $link->rollback();
            debug($e->getMessage());
            return [
                "success" => false,
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'])
            ];
        }
    }
    static function print_movement(int $id)
    {
        return $r = DB::query("SELECT c.id_emp, c.nombre_emp, c.rif_empresa, a.fecha_comp, d.des_tmocxc, a.movem_number, a.id_ent, e.nom_ent, f.tipo_codigo, f.nom_tdoc, b.monto_doc, g.sal_doc, CONCAT(h.name_user, ' ', h.last_user) creado, IFNULL(CONCAT(i.name_user, ' ', i.last_user), ' ') modificado, c.logo, g.num_tdo, d.cod_tmocxc, a.movem_descrip FROM f3008 a INNER JOIN f30081 b ON b.movem_id = a.id_movement INNER JOIN f0011 c ON c.id_emp = a.id_emp INNER JOIN f3002 d ON d.id_tmocxc = a.id_tmocxp INNER JOIN f0014 e ON e.id_ent = a.id_ent INNER JOIN f3001 f ON f.id_tdoc = b.id_tdo INNER JOIN f3004 g ON g.id_cot = b.id_cot INNER JOIN f0002 h ON h.id_user = a.create_user LEFT OUTER JOIN f0002 i ON i.id_user = a.modify_user WHERE a.id_movement = {$id}");
    }
    static function show_row_det(int $id)
    {
        $sql = "SELECT row_number() OVER (ORDER BY a.fecha_comp) item, c.id_cot, c.id_tdo, e.tipo_codigo, e.nom_tdoc, c.num_tdo, c.fecha_comp fecha_emi, c.fecha_venci, f.codigo_moneda, f.id_moneda, a.tasa_cambio, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN c.mon_doc ELSE (c.mon_doc * a.tasa_cambio) END, 2) mon_doc_dom, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN c.sal_doc ELSE (c.sal_doc * a.tasa_cambio) END, 2) sal_doc_dom, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN (c.mon_doc / GetExchangeRateVal(c.fecha_comp, 2)) ELSE c.mon_doc END, 2) mon_doc_for, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN (c.sal_doc / GetExchangeRateVal(c.fecha_comp, 2)) ELSE c.sal_doc END, 2) sal_doc_for, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN b.monto_doc ELSE (b.monto_doc * a.tasa_cambio) END, 2) mon_can_dom, ROUND(CASE WHEN a.id_moneda = g.id_moneda THEN (b.monto_doc / GetExchangeRateVal(c.fecha_comp, 2)) ELSE b.monto_doc END, 2) mon_can_for, c.mon_doc monto_doc, c.sal_doc FROM f3008 a INNER JOIN f30081 b ON b.movem_id = a.id_movement INNER JOIN f3004 c ON c.id_cot = b.id_cot INNER JOIN f0014 d ON d.id_ent = c.id_cli INNER JOIN f3001 e ON e.id_tdoc = c.id_tdo INNER JOIN f0005 f ON f.id_moneda = c.id_moneda INNER JOIN f0011 g ON g.id_emp = a.id_emp WHERE a.movem_origen = '$id'";
        return $r = DB::query($sql);
    }
}
