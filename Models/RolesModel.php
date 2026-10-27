<?php
class RolesModel extends DB
{
    public function __construct()
    {
        parent::__construct();
    }
    static function cargar_screen_main()
    {
        $sql = "SELECT * FROM f0007";
        $r = DB::query($sql);
        return $r;
    }
    static  function deleteRol(int $id)
    {
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        try {
            $link->beginTransaction();
            $sql = "DELETE FROM f0007 WHERE id_rol = :id_rol";
            $stmt = $link->prepare($sql);
            $stmt->bindParam(':id_rol', $id, PDO::PARAM_INT);
            $stmt->execute();
            $link->commit();
            return [
                "success" => true,
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
    static function guardar(array $data, int $id)
    {
        //                 
        $db = new Conexion(); //Obtener la instancia del PDO
        $link = (object)$db->conect();
        $title = '';
        $msg = '';
        $sql = '';
        $cols = "";
        $placeholders = "";
        $params = [];
        try {
            $link->beginTransaction();
            if (empty($id)) {
                $title = 'Registro guardado satisfactoriamente';
                $msg = 'El registro se ha guardado correctamente.';
                $cols = array_keys($data);
                // 2. Crear placeholders (:campo1, :campo2, ...)
                $placeholders = array_map(function ($col) {
                    return ":{$col}";
                }, $cols);

                // 3. Llenar el array de parámetros sin los dos puntos en la clave
                foreach ($data as $key => $value) {
                    $params[$key] = $value;
                }
                // 4. Armar la sentencia SQL
                $colsStr         = implode(", ", $cols);
                $placeholdersStr = implode(", ", $placeholders);
                $sql             = "INSERT INTO f0007 ({$colsStr}) VALUES ({$placeholdersStr})";
            } else {
                $title = 'Registro actualizado satisfactoriamente';
                $msg = 'El registro se ha actualizado correctamente.';
                $setClauses = [];
                foreach ($data as $key => $value) {
                    $setClauses[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
                $setStr = implode(", ", $setClauses);
                $sql    = "UPDATE f0007 SET {$setStr} WHERE id_rol = :id_rol";
                $params['id_rol'] = $id;
            }
            //            
            $stmt = $link->prepare($sql);
            $stmt->execute($params);
            $link->commit();
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
    static function editRol(int $id)
    {
        $rol = DB::query("SELECT * FROM f0007 WHERE id_rol = {$id}");
        return $rol[0];
    }
    static function actualizar(int $id, array $data)
    {
        return $res = DB::update('f0007', $data, ['id_rol' => $id]);
    }
    //Listar Roles. Creado por José Vargas el 09-03-2026 a las 11:26:00
    static function listar_roles()
    {
        $sql = "SELECT * FROM f0007 WHERE status_rol = 1 ORDER BY nombre_rol";
        $r = DB::query($sql);
        return $r;
    }
    
}
