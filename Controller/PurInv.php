<?php

/**
 * Controlador de la Factura de Compras 
 * Creado por José Vargas el 20-09-2004
 */
include_once VARTAX;
include_once CXPDOCMODEL;
include_once CXPCONMODEL;
include_once DELNOTFISMODEL;
include_once TIPDOCCXPMODEL;
include_once MOVINMODEL;
include_once PRODUCTOMODEL;
include_once CAMBIOMODEL;
include_once COTIZAMODEL;
include_once ROOT . DS . 'Models' . DS . 'EmpresasModel.php';
$ori = '';
$page = '';
if (isset($_GET['ori'])) {
    $ori = htmlspecialchars($_GET['ori']);
    if (isset($_SESSION['ori'])) {
        unset($_SESSION['or']);
        $_SESSION['ori'] = $ori;
    }
    $xori = '?ori=' . $ori;
    if ($_SESSION['ori'] == 'M') {
        $page = 'Facturas de Compras';
    } else if ($_SESSION['ori'] == 'O') {
        $page = 'Ordenes de Compras';
    } else if ($_SESSION['ori'] == 'A') {
        $page = 'Notas de Crédito';
    } else if ($_SESSION['ori'] == 'T') {
        $page = 'Notas de Entrega';
    } else if ($_SESSION['ori'] == 'V') {
        $page = 'Notas de Devolución';
    }
    $_SESSION['page_name'] = $page;
}
class PurInv extends Controller{
    public function __construct(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(122);
    }
    public function index(){
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }
        global $ori;        
        $this->views->getView($this, 'index', [
            'page_name' => 'Consulta ' . $_SESSION['page_name'],
            'function_js' => 'PurInv.js?v=' . SITE_VERSION,            
            'function_js_mod' => 'COMFun.js?v=' . SITE_VERSION,
        ]);
    }
    public function nuevo(){        
        $this->views->getView($this, 'nuevo', [
            'page_name' => 'Nueva ' . $_SESSION['page_name'],
            'function_js' => 'PurInv.js?v=' . SITE_VERSION,
            'function_js_mod' => 'COMFun.js?v=' . SITE_VERSION,
        ]);
    }
    public function cargar_screen_main(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){            
            $datos_tabla = [];
            $ori = $_SESSION['ori'];
            $r = PurInvModel::all($ori);        
            //Crear Tokens
            foreach ($r as $p){
                $datos_tabla[] = array_merge($p, [
                    "token_edit" => encriptar_url(json_encode(['accion' => 'edit', 'id' => $p['id_cot']]))
                ]);
            }            
            echo json_encode($datos_tabla, JSON_UNESCAPED_UNICODE);
        }
    } 
    public function edit(int $id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = PurInvModel::edit($id);
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/PurInv?ori=' . $_SESSION['ori']);
                }                
                $this->views->getView($this, "edit", [
                    'page_name' => "Editando el documento Número " ,
                    'function_js' => "PurInv.js?v=" . SITE_VERSION,
                    'function_js_mod' => "COMFun.js?v=" . SITE_VERSION,
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/PurInv');
            }
            return;
        }
        Alertas::new('No tiene permiso para realizar esta acción', 'warning');
        header('Location:' . base_url . '/PurInv');
    }
    public function gestion($token = null){
        if(!$token){
            return;
        }
        $datos = desencriptar_url($token);        
        switch($datos['accion']){
            case 'edit':
                $this->edit($datos['id']);
                break;
            default:
                break;
        }
    }
    public function show_row(){        
         if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_cot = $_POST['id'];
            $r = PurInvModel::show_row($id_cot);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function tip_doc_com(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_emp'];
            $r = PurInvModel::tip_doc_com($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function listar_entidad_modal(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tipo = $_POST['tipo'];
            $r = PurInvModel::listar_entidad_modal($tipo);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function store(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $id = $_POST['id'];
            $data = $_POST;
            $r = PurInvModel::guardar($data, $id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function destroy(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $dataJson = [];
            $id = intval(limpiar($_POST['id']));
            $origen = limpiar($_POST['origen']);
            $id_emp = intval($_POST['id_emp']);
            $ori = $_POST['ori'];
            //Buscar y borrar Movimientos de Inventarios
            try {
                $r = PurInvModel::selectEncyDetmovinv($origen, $id_emp);
                if ($r) {
                    $id_movinv = $r['id_movinv'];
                    $ide = PurInvModel::borrarEncyDetmovinv($id_movinv);
                }
                //Buscar y borrar las Cuentas por Pagar

                if ($ori == 'M' || $ori == 'A') {
                    $r = PurInvModel::selectEncyDetCXP($id, $id_emp);
                    $id_cot = $r['id_cot'];
                    $ide = PurInvModel::borrarEncyDetCXP($id_cot);
                }
                //Eliminar Retenciones de IVA
                if ($ori == 'M' || $ori == 'A') {
                    $r = PurInvModel::query("DELETE FROM f3006 WHERE id_cot = {$id_cot}");
                }
                //Borrar Compras
                $ide = PurInvModel::borrar($id);
                if ($ide) {
                    $msg = 'El Registro se ha eliminado satisfactoriamente';
                    $title = 'Eliminado correctamente';
                    $dataJson = [
                        'title' => $title,
                        'icon' => 'success',
                        'msg' => $msg
                    ];
                } else {
                    $title = 'Error al eliminar el registro';
                    $dataJson = [
                        'title' => $title,
                        'icon' => 'error',
                        'msg' => 'No se puede elimianr el registros, se encuetra asociado con otro registro, por favor inente luego',
                    ];
                }
            } catch (PDOException $e) {
                $title = "Se ha presentado un error, intente luego";
                $msg = sprintf("Error código: %s, Descripción del Error: %s", $e->getCode(), $e->getMessage());
                $dataJson = [
                    'title' => $title,
                    'icon' => 'error',
                    'msg' => $msg
                ];
            }
            echo json_encode($dataJson, JSON_UNESCAPED_UNICODE);
        }
    }  
    public function print_Retiva(int $id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = PurInvModel::print_retiva_enca($id, 'O');
                $d = PurInvModel::print_retiva_deta($id, 'O');
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/PurInv');
                }
                $this->views->getView($this, "print_RetIva", [
                    'r' => to_obj($r),
                    'd' => to_obj($d),
                ]);
            } else {
                header('Location:' . base_url . '/PurInv');
            }
            return;
        }
    }
    public function listar_doc_fuentes(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $id = $_POST['id'];
            $tipo  = $_POST['tipo'];
            $id_cli = $_POST['id_cli'];
            $tipo_doc_ori = $_POST['tipo_doc_ori'];
            $r = PurInvModel::listar_doc_fuentes($id_emp, $id, $tipo, $id_cli, $tipo_doc_ori);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
}
