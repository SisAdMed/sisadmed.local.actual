<?php
class CXPMovement extends Controller {
    public function __construct() {
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(110);
    }
    public function index() {
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }
        $r = CXPMovementModel::all();
        $this->views->getView($this, 'index', [
            'page_name' => 'Listado de Movimientos',
            'function_js' => 'CXPMovement.js?v=' . SITE_VERSION,
            'function_js_mod' => 'CXPFun.js?v=' . SITE_VERSION,
            'objeto' => to_obj($r)
        ]);
    }
    public function nuevo() {
        $this->views->getView($this, "new", [
            'page_name' => "Nuevo Movimiento",
            'function_js' => "CXPMovement.js?v=" . SITE_VERSION,
            'function_js_mod' => 'CXPFun.js?v=' . SITE_VERSION,
        ]);
    }
   public function store() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Decodificamos asegurándonos de que devuelva un array asociativo
        $detalle = json_decode($_POST['detalle_movimiento'] ?? '[]', true);            
        
        $enca = [];
        $deta = [];
        $modu = 'modify_user';
        $modd = 'modify_date';
        if(empty($_POST['id'])){
            $modu = 'create_user';
            $modd = 'create_date';                
        }                            
        
        // Asignar valores a variables desde $_POST
        foreach ($_POST as $key => $value) {
            $$key = $value;
        }            
        
        $movem_amount = 0;
        $tasa_cambio = convert_string_to_number($tasa_cambio);            
        $enca = [
            'id_emp' => $id_emp,
            'id_tmocxp' => $id_tmocxp,
            'movem_number' => $movem_number,
            'id_ent' => $id_ent,
            'fecha_comp' => $fecha_comp,
            'movem_descrip' => $movem_descrip,
            'movem_amount' => $movem_amount,
            'status' => $status,
            'movem_origen' => $movem_origen,
            'id_moneda' => $id_moneda,
            'tasa_cambio' => $tasa_cambio, 
            $modu => $_SESSION['id_user'],
            $modd => getAuditoria(),
        ];                    
        // Guardar los detalles del movimiento recorriendo el array correctamente        
        if (is_array($detalle) && !empty($detalle)) {            
            foreach($detalle as $item) {                          
                $xmonto = convert_string_to_number($item['mon_can']);                    
                $id_cot = $item['id_cot'];                
                $tip = CXPMovementModel::query("SELECT a.id_tdo, a.num_tdo FROM f3004 a INNER JOIN f3001 b ON b.id_tdoc = a.id_tdo WHERE a.id_cot = {$id_cot} ");                
                if (!empty($tip)) {
                    $id_tdo = $tip[0]['id_tdo'];
                    $num_tdo = $tip[0]['num_tdo'];
                    $deta[] = [
                        'movem_id' => $id ?? 0,
                        'id_tdo' => $id_tdo,
                        'num_tdo' => $num_tdo,
                        'id_cot' => $id_cot,
                        'monto_doc' => $xmonto,
                        'create_user' => $_SESSION['id_user'],
                        'create_date' => getAuditoria()
                    ];                    
                } 
            }
        }        
        if(isset($_POST['id']) && $_POST['id'] > 0) {            
            $r = CXPMovementModel::guardar($enca, $deta, $_POST['id']);            
        } else {            
            $r = CXPMovementModel::guardar($enca, $deta, 0);
        }        
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
    }
}
    public function listar_tipos_mov() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $efecto = '';
            if (isset($_POST['efecto'])) {
                $efecto = $_POST['efecto'];
            }
            $r = CXPMovementModel::listar_tipos_mov($efecto);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function val_tmo() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = CXPMovementModel::val_tmo($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function edit( int $id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = CXPMovementModel::edit($id);
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/CXPMovement');
                }
                $this->views->getView($this, "edit", [
                    'page_name' => "Editando el Movimiento " . $r[0]['cod_tmocxc'] . ' ' . $r[0]['des_tmocxc'] . ' número ' . $r[0]['movem_number'],
                    'function_js' => "CXPMovement.js?v=" . SITE_VERSION,
                    'function_js_mod' => 'CXPFun.js?v=' . SITE_VERSION,
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/CXPMovement');
            }
            return;
        }
        Alertas::new('No tiene permiso para realizar esta acción', 'warning');
        header('Location:' . base_url . '/CXPMovement');
    }
    public function show_row() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = CXPMovementModel::show_row($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function destroy(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $id = $_POST['id'];
            $r = CXPMovementModel::destroy($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function print_movement(int $id) {
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = CXPMovementModel::print_movement($id);
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/CXPMovement');
                }
                $this->views->getView($this, "print_movement", [
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/CXPMovement');
            }
            return;
        }
    }
    public function cargar_screen_main() {
        if ($_SERVER['REQUEST_METHOD']) {
            $r = CXPMovementModel::all();
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function show_row_det() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = CXPMovementModel::show_row_det($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
}
