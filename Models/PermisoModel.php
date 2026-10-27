<?php
class PermisoModel extends DB
{
    public function __construct()
    {
        parent::__construct();
    }
    static function modulosAgrupados(int $id_rol)
    {
        $db = new Conexion();
        $link = (object)$db->conect();

        try {
            $sql = "SELECT 
                    m.id_menu, 
                    m.nombre_menu, 
                    m.padre_menu, 
                    m.orden_menu,
                    m.icono_menu,
                    IFNULL(p.c, 0) AS crear,
                    IFNULL(p.r, 0) AS leer,
                    IFNULL(p.u, 0) AS actualizar,
                    IFNULL(p.d, 0) AS borrar
                FROM f0001 m
                LEFT JOIN f0008 p ON p.id_menu = m.id_menu AND p.id_rol = :id_rol
                ORDER BY m.padre_menu ASC, m.orden_menu ASC";

            $stmt = $link->prepare($sql);
            $stmt->execute([':id_rol' => $id_rol]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar en estructura jerárquica de 3 niveles
            $arbol = [];
            $mapa = [];

            // Primero mapeamos todos por su ID
            foreach ($filas as $fila) {
                $fila['hijos'] = [];
                $mapa[$fila['id_menu']] = $fila;
            }

            // Luego construimos la jerarquía
            foreach ($mapa as $id => &$nodo) {
                if ($nodo['padre_menu'] == 0) {
                    // Nivel 1: Módulos principales
                    $arbol[$id] = &$nodo;
                } else {
                    // Si tiene padre, lo metemos dentro del array 'hijos' de su padre correspondiente
                    if (isset($mapa[$nodo['padre_menu']])) {
                        $mapa[$nodo['padre_menu']]['hijos'][$id] = &$nodo;
                    }
                }
            }
            unset($nodo);

            return $arbol;
        } catch (\PDOException $e) {
            return [];
        }
    }
    static function paginas()
    {
        return $paginas = DB::query("SELECT * FROM f0001 WHERE status_menu <> 0");
    }
    static function permisosbyRoles($idRol)
    {
        return $permisoByRole = DB::query("SELECT * FROM f0008 WHERE id_rol = {$idRol}");
    }
    static function roles($idRol)
    {
        $roles = DB::query("SELECT * FROM f0007 WHERE id_rol = {$idRol}");
        return $roles[0];
    }
    static function deletePermisos($idRol)
    {
        return $roles = DB::query("DELETE FROM f0008 WHERE id_rol = {$idRol}");
    }
    static function insertPermisos($data)
    {
        return DB::insert('f0008', $data);
    }
    static function obtenerNombreRol(int $id_rol)
    {
        $db = new Conexion();
        $link = (object)$db->conect();
        try {
            $stmt = $link->prepare("SELECT nombre_rol FROM f0007 WHERE id_rol = :id_rol");
            $stmt->execute([':id_rol' => $id_rol]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['nombre_rol'] : 'Desconocido';
        } catch (\PDOException $e) {
            return 'Rol';
        }
    }
    static function guardarPermisosRol(int $id_rol, array $permisosEnviados)
    {
        $db = new Conexion();
        $link = (object)$db->conect();

        try {
            $link->beginTransaction();

            // 1. Limpiamos todos los permisos previos de este rol de un solo golpe
            $sqlDelete = "DELETE FROM f0008 WHERE id_rol = :id_rol";
            $stmtDelete = $link->prepare($sqlDelete);
            $stmtDelete->execute([':id_rol' => $id_rol]);

            // 2. Insertamos únicamente los permisos que vienen marcados desde el formulario
            if (!empty($permisosEnviados)) {
                $sqlInsert = "INSERT INTO f0008 (id_rol, id_menu, c, r, u, d, create_user, create_date) 
                         VALUES (:id_rol, :id_menu, :c, :r, :u, :d, :create_user, :create_date)";
                $stmtInsert = $link->prepare($sqlInsert);

                foreach ($permisosEnviados as $id_menu => $acciones) {
                    $id_menu = intval($id_menu);
                    $c = isset($acciones['C']) ? 1 : 0;
                    $r = isset($acciones['R']) ? 1 : 0;
                    $u = isset($acciones['U']) ? 1 : 0;
                    $d = isset($acciones['D']) ? 1 : 0;

                    $stmtInsert->execute([
                        ':id_rol' => $id_rol,
                        ':id_menu' => $id_menu,
                        ':c' => $c,
                        ':r' => $r,
                        ':u' => $u,
                        ':d' => $d,
                        ':create_user' => $_SESSION['id_user'] ?? 1, // Previene error si la sesión no está definida
                        ':create_date' => function_exists('getAuditoria') ? getAuditoria() : date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Confirmamos la transacción
            $link->commit();

            // ¡Importante! Añadimos "status" => "success" para que coincida con tu JS
            return [
                "status" => "success",
                "title" => 'Permisos guardados satisfactoriamente',
                "icon" => 'success',
                "msg" => 'Los permisos se han guardado correctamente.'
            ];
        } catch (\PDOException $e) {
            if ($link->inTransaction()) {
                $link->rollBack();
            }

            // También añadimos "status" => "error" para el manejo de fallos en JS
            return [
                "status" => "error",
                "title" => 'Se ha presentado un error',
                "icon" => 'error',
                "msg" => sprintf('Se ha presentado el error %s, por favor verifique.', $e->getMessage())
            ];
        }
    }
}
