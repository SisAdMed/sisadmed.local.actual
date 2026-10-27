<?php
class Roles extends Controller{
    public function __construct(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(2);
    }
    public function index(){
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }        
        $this->views->getView($this, "index", [
            'page_name' => "Roles",
            'function_js' => "Roles.js?v=" . SITE_VERSION,         
            'function_js_mod' => "Main.js?v=" . SITE_VERSION
        ]);
    }
    public function cargar_screen_main(){        
        if($_SERVER["REQUEST_METHOD"] == "POST"){            
            $datos_tabla = [];
            //Crear tokens
            $r = RolesModel::cargar_screen_main();                        
            foreach($r as $p){
                $datos_tabla[] = array_merge($p, [
                    "token_edit" => encriptar_url(json_encode(['accion' => 'edit', 'id' => $p['id_rol']])),
                    "token_delete" => encriptar_url(json_encode(['accion' => 'delete', 'id' => $p['id_rol']])),
                    "token_show" => encriptar_url(json_encode(['accion' => 'show', 'id' => $p['id_rol']])),
                ]);
            }                     
            echo json_encode($datos_tabla, JSON_UNESCAPED_UNICODE);
        }
    }   
    public function nuevo(){
        $this->views->getView($this, "nuevo", [
            'page_name' => "Nuevo rol",
            'function_js' => "Roles.js?v=" . SITE_VERSION,         
            'function_js_mod' => "Main.js?v=" . SITE_VERSION
        ]);
    }
     public function store(){        
        if($_SERVER['REQUEST_METHOD'] == 'POST'){            
            $id_rol = intval(limpiar($_POST['id']));            
            $modu = 'create_user';
            $modd = 'create_date';
            if(isset($id_rol) && $id_rol > 0){
                $modu = 'modify_user';
                $modd = 'modify_date';
            }
            $nombre_rol = limpiar($_POST['nombre_rol']);
            $status_rol = intval(limpiar($_POST['status_rol'] ?? 1));        
            $data = [
                'nombre_rol' => $nombre_rol,
                'status_rol' => $status_rol,
                $modu => $_SESSION['id_user'],
                $modd => getAuditoria(),
            ];            
            if(empty($id_rol)){
                $r = RolesModel::guardar($data, 0);
            }else{
                $r = RolesModel::guardar($data, $id_rol );
            }                
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function gestion($token = null)  {
        if (!$token) {
            return;
        }
        $datos = desencriptar_url($token);        
        switch ($datos['accion']) {
            case 'edit':
                $this->edit($datos['id']);
                break;            
            default:
                // Acción no permitida
                break;
        }
    }
    public function edit(int $id) {
        if (Permisos::read()) {
            $idRol = intval(limpiar($id));
            if ($idRol > 0) {
                $rol = RolesModel::editRol($idRol);
                if (empty($rol)) {                    
                    header('Location:' . base_url . '/Roles');
                }
                //mostrar registro
                $this->views->getView($this, "edit", [
                    'page_name' => "Editando el registro " . $rol['nombre_rol'],
                    'function_js' => "Roles.js?v=" . SITE_VERSION,         
                    'function_js_mod' => "Main.js?v=" . SITE_VERSION,
                    'rol' => to_obj($rol)
                ]);
            } else {
                header('Location:' . base_url . '/Roles');
            }
            return;
        }
        Alertas::new('No tiene permiso para realizar esta acción', 'warning');
        header('Location:' . base_url . '/Roles');
    }
    public function show_row() {        
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $id = $_POST['id'] ?? null;
            $idRol = intval(limpiar($id));
            if ($idRol > 0) {
                $rol = RolesModel::editRol($idRol);
                if (empty($rol)) {                    
                    header('Location:' . base_url . '/Roles');
                }
                echo json_encode($rol, JSON_UNESCAPED_UNICODE);
            } else {
                header('Location:' . base_url . '/Roles');
            }
        }
    }
    public function delete(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){                     
            $id = intval(limpiar($_POST['id']));
            $r = RolesModel::deleteRol($id);            
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    //Listar Roles
    public function listar_roles(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $r = RolesModel::listar_roles();
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
}
