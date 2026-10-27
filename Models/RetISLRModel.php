<?php
class RetISLRModel extends DB {
    public function __construct() {
        parent::__construct();
    }
    static function all() {
        return $r = DB::query('SELECT * FROM f0021');
    }
    static function guardar(array $data, int $row) {        
        //                
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        //
        $cols = "";
        $placeholders = "";
        $params = [];
        try {
            $link->beginTransaction();            
            if($row === 0){
                $cols = array_keys($data);
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);
                foreach($data as $key => $value){
                    $params[$key] = $value;
                }
                $colsStr = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql = "INSERT INTO f0021 ({$colsStr}) VALUES ({$placeholdersStr})";
                
            }else{
                $setClauses = [];
                foreach($data as $key => $value){
                    $setClauses[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
                $params['id'] = $row;
                $sql = "UPDATE f0021 SET " . implode(", ", $setClauses) . " WHERE id = :id";
            }
            $stmt = $link->prepare($sql);
            $stmt->execute($params);
            $link->commit();
            $title = "Registro gurdado satisfactoriamente";
            $msg = "Se ha guardado el Cocnepto de Retención de ISLR, satisfactoriamente";            
            return [
                "title" => $title,
                "icon" => 'success',
                "msg" => $msg
            ];
        } catch (\PDOException $e) {
            $link->rollback();            
            return [
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'])
            ];
        }
    }
    static function actualizar(int $id, array $data) {
        return $r = DB::update('f0021', $data, ['id' => $id]);
    }
    static function destroy(int $id) {        
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        $title = "";
        $msg = "";
        $icon = "";
        try {
            $link->beginTransaction();
            //Verificar si existen registros relacionados
            $sql = "SELECT COUNT(*) tot_rows FROM f3003 WHERE id_retislr = :id_retislr" ;
            $stmt = $link->prepare($sql);
            $stmt->execute([':id_retislr' => $id]);
            $rows = $stmt->fetchColumn();
            $stmt->closeCursor();
            if($rows > 0){
                $title = "Error al eliminar el registro";
                $msg = "No se pudo eliminar el registro ya que se encuentra aociado a un Concepto de Cuentas por Pagar";
                $icon = "error";
            }else{
                $sql = "DELETE FROM f0021 WHERE id = :id";
                $stmt = $link->prepare($sql);
                $stmt->execute([':id' => $id]);
                $title = "Registro eliminado";
                $msg = "El registro se ha eliminada satisfactoriamente";
                $icon = "success";
            }
            $link->commit();
            return [
                'success' => true,
                "title" => $title,
                "icon" => $icon,
                "msg" =>  $msg
            ];
        } catch (\PDOException $e) {
            $link->rollback();            
            return [
                "success" => false,
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s al guardar la %s, por favor verifique.', $e->getMessage(), $_SESSION['page_name'])
            ];
        }     
    }
    static function edit(int $id){
        $r = DB::query("SELECT * FROM f0021 WHERE id = {$id}");
        return $r[0];
    }
    static function listar_retislr(){
        return $r = DB::query("SELECT id, CONCAT(descrip, ' ', FORMAT(por_reten, '2', 'de_DE'), ' %.') descrip FROM f0021 WHERE status = 1");
    }
    static function report_retislr(int $id_emp, string $fecha_ini, string $fecha_fin){
        return $r = DB::query("SELECT d.id_emp, d.cod_emp, d.nombre_emp, d.rif_empresa, e.rif_ent, e.nom_ent, b.id_cot, b.num_tdo, b.num_control, b.fecha_comp, a.total_monto, a.total_base, a.por_reten, a.total_retenido, d.logo, IFNULL(f.code_seniat, 'Por Definir') code_seniat FROM f3007 a INNER JOIN f3004 b ON b.id_cot = a.id_cot INNER JOIN f0011 d ON d.id_emp = a.id_emp INNER JOIN f0014 e ON e.id_ent = b.id_cli LEFT OUTER JOIN f0021 f ON f.id = a.id_retislr WHERE d.id_emp = {$id_emp} AND b.fecha_comp BETWEEN '$fecha_ini' AND '$fecha_fin'");
    }
}