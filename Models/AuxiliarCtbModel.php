<?php class AuxiliarCtbModel extends DB
{
    public function __construct(){
        parent::__construct();
    }
    static function all(){
        return $r = DB::query("SELECT * FROM f0009");
    }
    static function guardar($data){
        return $id = DB::insert('f0009', $data);
    }
    static function actualizar($id, $data){
        return $res = DB::update('f0009', $data, ['id_aux' => $id]);
    }
    static function borrar(int $id, string $aux){
        $db = new Conexion();
        $link = (object)$db->conect();
        try {
            $link->beginTransaction();
            // 1. Primero, obtenemos el registro que se quiere eliminar para conocer su código y si es agrupador
            $sqlinfo = "SELECT cod_aux, agrupa_aux FROM f0009 WHERE id_aux = :id";
            $stmtInfo = $link->prepare($sqlinfo);
            $stmtInfo->execute([':id' => $id]);
            $auxiliar = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if(!$auxiliar){
                return [
                    'title' => "Error al eliminar el registro",
                    'msg' => "Auxiliar Contable no existe.",
                    'icon' => 'error'
                ];
            }
            // 2. VALIDACIÓN DE HIJOS: Si es agrupador ('S'), validamos que no tenga sub-auxiliares dependientes
            if($auxiliar['agrupa_aux'] === 'S'){
                // Buscamos si existen códigos que comiencen con el código actual + un punto (ej: 'BAN.' o 'BAN.NAC.')
                $patronHijos = $auxiliar['cod_aux'] . '.';
                $sqlHijos = "SELECT COUNT(*) FROM f0009 WHERE cod_aux like CONCAT(:patron, '%')";
                $stmtHijos = $link->prepare($sqlHijos);
                $stmtHijos->execute([':patron' => $patronHijos]);
                $totalHijos = $stmtHijos->fetchColumn();
                if($totalHijos > 0){
                    return [
                    'title' => "Error al eliminar el registro",
                    'msg' => "Auxiliar Contable no se puede eliminar ya que es agrupador '{$auxiliar['cod_aux']}' porque tiene sub-auxiliares (hijos) asociados.",
                    'icon' => 'error'
                ];
                }
            }
            // 3. Array asociativo: 'nombre_tabla' => 'nombre_de_la_columna_foranea'
            $tablasAValidar = [
                'f5004' =>  ['modulo' => 'Bancos', 'columna' => 'id_aux' ],
                'f5005' =>  ['modulo' => 'Bancos', 'columna' => 'id_aux'],
                'f50061' => ['modulo' => 'Bancos', 'columna' => 'id_aux'],
                'f6001' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
                'f6002' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_ctbaux'],
                'f6004' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
                'f60032' => ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
                'f3001' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
                'f3002' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
                'f3003' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
                'f3005' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
                'f30041' => ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
                'f00122' => ['modulo' => 'Contabilidad', 'columna' => 'id_aux'],
            ];
            // 2. Recorrer el array extrayendo la tabla (clave) y la columna (valor)
            foreach( $tablasAValidar as $tabla => $info){
                $columna = $info['columna'];
                $modulo  = $info['modulo'];
                // Usamos la variable $columna dinámicamente en la consulta
                $sqlCheck = "SELECT COUNT(*) FROM {$tabla} WHERE {$columna} = :id";
                $stmtCheck = $link->prepare($sqlCheck);
                $stmtCheck->execute([':id' => $id]);
                $contador = $stmtCheck->fetchColumn();
                //Si encuentra registros asociados, detenemos el proceso
                if($contador > 0){
                    return [
                        'title' => "Error al eliminar el registro",
                        'msg' => "Auxiliar Contable no se puede eliminar porque tiene registros asociados en el Módulo de **{$modulo} ** en la tabla {$tabla} (columna {$columna}).",
                        'icon' => 'error'
                    ];
                }
            }
            // 4. Si pasa todas las validaciones, se ejecuta el borrado en la principal
            $sqlDelete = "DELETE FROM f0009 WHERE id_aux = :id";
            $stmtDelete = $link->prepare($sqlDelete);
            $stmtDelete->execute([':id' => $id]);           
            $link->commit();
            return [
                'title' => "Registro eliminado",
                'msg' => "El Auxiliar Contable ha sido eliminado satisfactoriamente",
                'icon' => 'success'
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
    static function edit($id){
        $r = DB::query("SELECT * FROM f0009 WHERE id_aux = {$id}");
        return $r[0];
    }
    static function show_row(int $id){
        $db = new Conexion();
        $link = (object)$db->conect();   
        // 1. Obtener los datos principales del auxiliar
        $sql = "SELECT * FROM f0009 WHERE id_aux = :id";
        $stmt = $link->prepare($sql);
        $stmt->execute([':id' => $id]);
        $auxiliar = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$auxiliar){
            return [
                'title' => "Error al eliminar el registro",
                'msg' => "Auxiliar Contable no existe.",
                'icon' => 'error'
            ];
        }        
        // 2. Verificar si tiene hijos (si su código sirve de padre)
        $patronHijos = $auxiliar['cod_aux'] . '.';
        $sqlHijos = "SELECT COUNT(*) FROM f0009 WHERE cod_aux LIKE CONCAT(:patron, '%')";
        $stmtHijos = $link->prepare($sqlHijos);
        $stmtHijos->execute([':patron' => $patronHijos]);
        $totalHijos = $stmtHijos->fetchColumn();
        // 3. Verificar si tiene movimientos en otras tablas
        $tieneMovimientos = false;
        $tablasAValidar = [
            'f5004' =>  ['modulo' => 'Bancos', 'columna' => 'id_aux' ],
            'f5005' =>  ['modulo' => 'Bancos', 'columna' => 'id_aux'],
            'f50061' => ['modulo' => 'Bancos', 'columna' => 'id_aux'],
            'f6001' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
            'f6002' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_ctbaux'],
            'f6004' =>  ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
            'f60032' => ['modulo' => 'Cuentas por Cobrar', 'columna' => 'id_aux'],
            'f3001' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
            'f3002' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
            'f3003' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
            'f3005' =>  ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
            'f30041' => ['modulo' => 'Cuentas por Pagar', 'columna' => 'id_aux'],
            'f00122' => ['modulo' => 'Contabilidad', 'columna' => 'id_aux'],
        ];
        foreach ($tablasAValidar as $tabla => $info) {
            $columna = $info['columna'];
            $sqlCheck = "SELECT COUNT(*) FROM {$tabla} WHERE {$columna} = :id";
            $stmtCheck = $link->prepare($sqlCheck);
            $stmtCheck->execute([':id' => $id]);
            if($stmtCheck->fetchColumn() >0){
                $tieneMovimientos = true;
                break;
            }
        }
        // 4. Determinamos si se puede modificar el campo agrupador
        // Si tiene hijos o movimientos, bloqueamos la edición del agrupador (false)
        $bloquearAgrupador = ($totalHijos > 0 || $tieneMovimientos);
        // Devolvemos los datos del auxiliar junto con la bandera para el formulario
        return [
            'status' => true,
            'data' => $auxiliar,
            'bloquear_agrupa' => $bloquearAgrupador
        ];
    }
    static function validar_cod_aux($codigo){
        $r = DB::query("SELECT count(*) totrows FROM f0009 WHERE cod_aux = '".$codigo."'");
        return $r;
    }
    static function validar_cod_cta($codigo){
        $r = DB::query("SELECT * FROM f0010 WHERE cod_cta = '".$codigo."'");
        return $r[0];
    }
    static function listar_aux_ctbles(){
        return $r = DB::query("SELECT * FROM f0009 WHERE status_aux = 1 AND agrupa_aux = 'N'");
    }
    static function listar_aux_ctbles_mod($id, $mod){
        if($mod == "CXC"){
            $r = DB::query("SELECT c.id_aux, c.cod_aux, c.nombre_aux FROM f6002 a INNER JOIN f0010 b on b.id_cta = a.id_ctbcue LEFT OUTER JOIN f0009 c on c.agrupa_aux = 'N' WHERE a.id_emp = {$id} AND b.aux_cta = 'S' ORDER BY 3");
        }
        return $r;
    }
    static function modal_AuxiliarCtb(){
        return $r = DB::query("SELECT * FROM f0009 WHERE status_aux = 1 AND agrupa_aux = 'N' ORDER BY cod_aux");
    }
    static function nom_aux($id){
        $r = DB::query("SELECT cod_aux, nombre_aux FROM f0009 WHERE id_aux = {$id}");
        return $r[0];
    }
}
