<?php
class Permiso extends Controller
{
    public function __construct()
    {
        Auth::noAuth();
        parent::__construct();
    }
    public function index(int $id)
    {
        $rolId = intval(limpiar($id));
        // Obtenemos el árbol estructurado
        $data['arbolPermisos'] = PermisoModel::modulosAgrupados($rolId);
        $data['id_rol'] = $rolId;
        $data['nombreRol'] = PermisoModel::obtenerNombreRol($rolId);

        $this->views->getView($this, "index", [
            'page_name' => "Accesos por rol",
            'id_rol' => $id,
            'function_js' => "Permiso.js?v=" . SITE_VERSION,
            'function_js_mod' => "Main.js?v=" . SITE_VERSION,
            'modulos' => $data,
        ]);
    }
    public function store(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){            
            $id_rol = intval(limpiar($_POST['id_rol']));
            $permisos = $_POST['permiso'] ?? [];
            $r = PermisoModel::guardarPermisosRol($id_rol, $permisos);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function gestion($token = null){
        if (!$token) {
            return;
        }
        $datos = desencriptar_url($token);           
        switch ($datos['accion']) {
            case 'show':
                $this->index($datos['id']);
                break;            
            default:
                // Acción no permitida
                break;
        }
    }
}
