<?php
class RetISLR extends Controller{
    public function __construct(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(165);
    }
    public function index(){
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }
        $this->views->getView($this, 'index', [
            'page_name' => 'Consulta de Conceptos de Retención de ISLR',
            'function_js' => 'RetISLR.js?v=' . SITE_VERSION,
        ]);
    }
    public function nuevo(){
        $this->views->getView($this, 'nuevo', [
            'page_name' => 'Nuevo registro de Concepto de Retención de ISLR',
            'function_js' => 'RetISLR.js?v=' . SITE_VERSION,
        ]);
    }
    public function edit( int $id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));            
                $this->views->getView($this, 'edit', [
                    'page_name' => 'Modificando el registro de Concepto de Retención de ISLR',
                    'function_js' => 'RetISLR.js?v=' . SITE_VERSION,
                    'r' => $id,
                ]);        
        }
    }
    public function store(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $modu = 'modify_user';
            $modd = 'modify_date';
            $data = array();
            if (empty($_POST['id'])) {
                $modu = 'create_user';
                $modd = 'create_date';
            }
            $data = [
                'fecha_vigencia' => $_POST['fecha_vigencia'],
                'descrip' => $_POST['descrip'],
                'minimo' => convert_string_to_number($_POST['minimo']),
                'maximo' => convert_string_to_number($_POST['maximo']),
                'por_reten' => convert_string_to_number($_POST['por_reten']),
                'por_imp_suj_ret' => convert_string_to_number($_POST['por_imp_suj_ret']),
                'fac_reten' => convert_string_to_number($_POST['fac_reten']),
                'code_seniat' => $_POST['code_seniat'],
                'status' => $_POST['status'],
                'sustraendo' => convert_string_to_number($_POST['sustraendo']),
                $modu => $_SESSION['id_user'],
                $modd => getAuditoria()
            ];
            if (empty($_POST['id'])) {
                $r = RetISLRModel::guardar($data, 0);
            } else {
                $r = RetISLRModel::guardar($data, $_POST['id']);
            }
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function destroy(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {                
            $id = $_POST['id_con'];
            $r = RetISLRModel::destroy($id);            
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function cargar_screen_main(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos_tabla = [];
            $r = RetISLRModel::all();
            //Crear tokens
            foreach ($r as $p) {
                $datos_tabla[] = array_merge($p, [
                    "token_edit" => encriptar_url(json_encode(['accion' => 'edit', 'id' => $p['id']]))
                ]);
            }
            echo json_encode($datos_tabla, JSON_UNESCAPED_UNICODE);
        }
    }
    public function show_row() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = RetISLRModel::edit($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function listar_retislr(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $r = RetISLRModel::listar_retislr();
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function index2(){
        $this->views->getView($this, 'index2', [
            'page_name' => 'Reporte Retención de I.S.L.R.',
            'function_js' => 'RetISLR.js?v=' . SITE_VERSION,
        ]);
    }
    public function report_retislr(){
        if (Permisos::read()) {
            $id_emp = $_GET['id_emp'];
            $fec_ini = $_GET['fec_ini'];
            $fec_fin = $_GET['fec_fin'];
            $r = RetISLRModel::report_retislr($id_emp, $fec_ini, $fec_fin);
            if (empty($r)) {                
                header('Location:' . base_url . '/RetISLR/index2');
            }
            $this->views->getView($this, "report_retislr", [
                'r' => to_obj($r)
            ]);
            return;            
            header('Location:' . base_url . '/RetISLR');
        }
    }
    public function report_retislr_xml(){
        if (Permisos::read()) {
            $id_emp = $_GET['id_emp'];
            $fec_ini = $_GET['fec_ini'];
            $fec_fin = $_GET['fec_fin'];
            $r = RetISLRModel::report_retislr($id_emp, $fec_ini, $fec_fin);
            if (empty($r)) {                
                header('Location:' . base_url . '/RetISLR/index2');
            }
            $this->views->getView($this, "report_retislr_xml", [
                'r' => to_obj($r)
            ]);
            return;            
            header('Location:' . base_url . '/RetISLR');
        }
    }
    public function gestion($token = null){
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
}
