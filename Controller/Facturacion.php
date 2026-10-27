<?php
include_once VARTAX;
include PRODUCTOMODEL;
include EMPRESAMODEL;
include CAMBIOMODEL;
include $_SERVER['DOCUMENT_ROOT'] . '/Models/TipoDocCXCModel.php';
$page = '';
if (isset($_GET['tipo'])) {
    $ori = htmlspecialchars($_GET['tipo']);
    if (isset($_SESSION['tipo_fact'])) {
        unset($_SESSION['tipo_fact']);
        $_SESSION['tipo_fact'] = $ori;
    }
    $xori = '?ori=' . $ori;
    if ($_SESSION['tipo_fact'] == 'B') {
        $page = 'Notas de Débito';
    } else if ($_SESSION['tipo_fact'] == 'C') {
        $page = 'Notas de Crédito';
    } else if ($_SESSION['tipo_fact'] == 'D') {
        $page = 'Notas de Devolución';
    } else if ($_SESSION['tipo_fact'] == 'F') {
        $page = 'Facturación';
    } else if ($_SESSION['tipo_fact'] == 'N') {
        $page = 'Nota de Entrega';
    } else if ($_SESSION['tipo_fact'] == 'Z') {
        $page = 'Nota de Entrega No Fiscal';
    }
    $_SESSION['page_name'] = $page;
}
class Facturacion extends Controller
{
    public function __construct(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(72);
    }    
    public function edit(int $id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = FacturacionModel::edit($id);
                if (empty($r)) {                    
                    header('Location:' . base_url . '/Facturacion');
                }
                $this->views->getView($this, "edit", [
                    'page_name' => "Editando la " . $_SESSION['page_name'] . " Nro. " . $r['num_tdo'],
                    'function_js' => 'Facturacion.js?v=' . SITE_VERSION,
                    'function_js_mod' => 'FACFun.js?v=' . SITE_VERSION,
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/Facturacion');
            }
            return;
        }        
        header('Location:' . base_url . '/Facturacion');
    }
    public function consultar_factura(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_cot'];   
            $tipo = $_POST['tipo'];         
            $r = FacturacionModel::edit_deta($id, $tipo);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function consulta_adic01(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $fecha_precio = $_POST['fecha_precio'];
            $objeto = FacturacionModel::consulta_adic01($id_emp, $fecha_precio);
            echo json_encode($objeto);
        }
    }
    public function consulta_adic02(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_cli = $_POST['id_cli'];
            $objeto = FacturacionModel::consulta_adic02($id_cli);
            echo json_encode($objeto);
        }
    }
    public function print_factura($id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = FacturacionModel::print_factura($id);
                if (empty($r)) {                    
                    header('Location:' . base_url . '/Facturacion');
                }
                $this->views->getView($this, "print_factura", ['r' => to_obj($r)]);
            } else {
                header('Location:' . base_url . '/Facturacion');
            }
            return;
        }        
        header('Location:' . base_url . '/Facturacion');
    }
    public function print_factura_metro($id){
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = FacturacionModel::print_factura($id);
                if (empty($r)) {                    
                    header('Location:' . base_url . '/Facturacion');
                }
                $this->views->getView($this, "print_factura_metro", [
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/Facturacion');
            }
            return;
        }        
        header('Location:' . base_url . '/Facturacion');
    }
    public function destroy(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){            
            $dataJson = [];
            $id = $_POST['id'];        
            $r = FacturacionModel::destroy($id);        
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function create_express(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_fab = $_POST['id_fab'];
            $objeto = FacturacionModel::create_express($id_fab);
            echo json_encode($objeto);
        }
    }
    public function listar_factura(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $r = FacturacionModel::listar_Factura($id_emp);
            echo json_encode($r);
        }
    }
    public function tip_doc_fac(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_emp'];
            $r = FacturacionModel::tip_doc_fac($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function aprobacion(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'tipo_fgenmsgcol' => 1,
                'title' => $_POST['title'],
                'message' => $_POST['message'],
                'status' => 1,
                'create_user' => $_SESSION['id_user'],
            ];
            $r = FacturacionModel::aprobacion($data);
            $dataJson = [
                'status' => true,
                'type' => 'success',
                'msg' => 'Registro en espera de aprobación'
            ];
            echo json_encode($dataJson, JSON_UNESCAPED_UNICODE);
        }
    }
    public function update_tasa(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array();
            $id_cot = $_POST['id_cot'];
            $tasa_cambio = $_POST['tasa_cambio'];
            $data = ['tasa_cambio' => $tasa_cambio];
            $r = FacturacionModel::actualizar($id_cot, $data);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function consultar_factura_nc(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_cot'];
            $tipo = $_POST['tipo'];
            $r = FacturacionModel::edit_deta($id, $tipo);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function readpdf(){
        $this->views->getView($this, 'readpdf', [
            'page_name' => 'Nueva '  . $_SESSION['page_name'],
            'function_js' => 'Facturacion.js',
        ]);
    }
    public function equivale(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $id_ent = $_POST['id_ent'];
            $id_prod = $_POST['id_prod'];
            $format = $_POST['format'];
            $id_ubi = $_POST['id_ubi'];
            $id_alm = $_POST['id_alm'];
            $fecha_comp = $_POST['fecha'];
            $r = FacturacionModel::equivale($id_emp, $id_ent, $id_prod, $format, $id_ubi, $id_alm, $fecha_comp);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function ventas_unidades(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(176);
        if (Permisos::read()) {
            $this->views->getView($this, 'ventas_unidades', [
                'page_name' => 'Ventas por Unidades',
                'function_js' => 'Ventas_unidades.js'
            ]);
        }
    }
    public function store(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){                    
            $detalle = json_decode($_POST['detalle']);                
            $enca = [];
            $deta = [] ;            
            $modu = 'modify_user';
            $modd = 'modify_date';
            if(empty($_POST['id'])){
                $modu = 'create_user';
                $modd = 'create_date';                
            }
            if ($_POST['id_emp'] == 1) {
                $limitePorFactura = 40;
            } else {
                $limitePorFactura = 30;
            }
            if ($_POST['id_cli'] == 11) {
                $limitePorFactura = 30;
            }                      
            //Crear date de encabezado
            $xtasa = convert_string_to_number($_POST['tasa_cambio']);            
            $mod_doc = 0;    
            if($xtasa == 1) {
                $mod_doc = convert_string_to_number($_POST['total_frmDom']);
            } else {
                $mod_doc = convert_string_to_number($_POST['total_frml']);
            }
            $id_cont = '';
            $signo = 1;
            if($_POST['tipo_fact'] =='F'){
                $id_cont = 'FAC';
            } else if ($_POST['tipo_fact'] =='N') {            
                $id_cont = 'NOL';
            }else if ($_POST['tipo_fact'] =='Z') {
                $id_cont = 'NOF';
            }else if ($_POST['tipo_fact'] =='C') {
                $id_cont = 'FNC';                
            }
            if (isset($_POST['origen']) && $_POST['origen']) {                
                if ($_POST['tipo_fact'] == 'F') {                    
                    if ($_POST['fuente'] == 6) {                        
                        $fuente = FacturacionModel::origen($_POST['origen'], 'C');
                    } else {
                        $fuente = FacturacionModel::origen($_POST['origen'], 'NOF');
                    }
                } else {
                    $fuente = FacturacionModel::origen($_POST['origen'], 'NC');
                    }
                $id_cont = $id_cont . '-' . $fuente['tipo_codigo'] . '-' . $fuente['num_tdo'];
            }
            $id_des = 0;
            $id_des_val = false;
            if (isset($_POST['id_des_enca']) && $_POST['id_des_enca']) {
                $id_des = $_POST['id_des_enca'];
                $id_des_reqapp = FacturacionModel::show_row_des($id_des);
                if ($id_des_reqapp['appreq'] == 1) {
                    $id_des_val = true;
                }
            }
            $nro_control = $_POST['nro_control'];
            $adic_01 = 0;
            $adic_02  = 0;
            //Buscar personalizaciones
            //Cambio 09-05-2005 Solicitado por Nelson Guerra a las 11:30:00
            //Para buscar el Motivo de Cambio del Cliente y guardarlo en el documento para que se muestre en el EDC CXC
            $id_cli = limpiar($_POST['id_cli']);
            $imp_crcd = null;
            $imp_tasa_cambio = null;
            if($id_cli == '11'){
                $imp_crcd = 2;
                $imp_tasa_cambio = 0;
            }
            $id_motcam = 0;  
            $adic_01 = 0;          
            $adic_02 = 0;
            $id_motcam = $_POST['id_motcam'];
            if($id_motcam){
                $mot_cam = FacturacionModel::query("SELECT * FROM f0012a WHERE id_motcam = {$id_motcam}");
                if ($mot_cam) {                    
                    //
                    $adic_01 = $mot_cam[0]['adic_01'];
                    $adic_02 = $mot_cam[0]['adic_02'];                   
                }
            }            
            //Encabezado de Factura           
            $enca = [                
                'id_emp' => $_POST['id_emp'],
                'id_tdo' => $_POST['id_tdo'],
                'num_tdo' => $_POST['num_tdo'] ?? '',
                'id_cli' => $id_cli,
                'id_fab' => $_POST['id_fab'] ?? '',
                'fecha_comp' => $_POST['fecha_comp'],
                'fecha_venci' => $_POST['fecha_venci'],
                'id_moneda' => $_POST['id_moneda'],
                'tasa_cambio' => number_format($xtasa, 8),
                'id_vend' => $_POST['id_vend'],
                'descrip_cot' => htmlspecialchars(limpiar($_POST['descrip_cot'])),
                'mon_doc' => $mod_doc * $signo,
                'sal_doc' => $mod_doc * $signo,
                'status' => 1,
                $modu => $_SESSION['id_user'],
                $modd => getAuditoria(),
                'id_cont' => $id_cont,
                'id_des' => $id_des,
                'oc_cliente' => limpiar($_POST['oc_cliente'] ?? ''),                
                'id_doc_afec' => $_POST['origen'] ?? '',
                'doc_afectado' => '',
                'id_motcam' => $id_motcam,
                'invoice' => null,
                'adic_01' => $adic_01,
                'adic_02' => $adic_02,
                'imp_crcd' => '',
                'imp_tasa_cambio' => '',
                'motivo' =>  null,
                'nro_control' => $nro_control,
            ];               
            //Detalle de FActura
            $xvatTax = xvatTax($_POST['fecha_comp'], 'IVA');            
            $xTasaIva = $xvatTax[0]['txr1_iva'];           
            foreach( $detalle as $item){        
                $mon_iva = 0;                               
                $id_prod = $item->id_prod;                
                if(!$id_prod){
                    continue;
                }
                $iva_prod = $item->iva_prod;
                $sub_total = convert_string_to_number($item->sub_total) * $signo;
                if($iva_prod == 'S'){                                        
                    $mon_iva = $sub_total * ($xTasaIva / 100);
                }                
                //
                $sql = "SELECT costo1 costo, a.ventas_prod - costo1 utilidad, a.ventas_prod, a.uni_ven_prod, c.adic_01, c.adic_02, ((a.ventas_prod / c.adic_01) - a.ventas_prod) adicional FROM f4005 a LEFT OUTER JOIN f0014 b ON b.id_ent = {$_POST['id_cli']} INNER JOIN f0012a c ON c.id_motcam = b.id_motcam WHERE a.id_prod = {$id_prod}";                
                $val_cod_prod = ProductosModel::query($sql);
                //Inicializar variabels por producto
                $dif_cambio = 0;
                $costo = 0;
                $utilidad = 0;
                $adicional = 0;
                //asignacion de valores desde la consulta                
                $costo = $val_cod_prod[0]['costo'] * $signo;                
                $utilidad = $val_cod_prod[0]['utilidad'] * $signo;
                $xuni_venta = $val_cod_prod[0]['uni_ven_prod'];
                $adicional = $val_cod_prod[0]['adicional'] * $signo;
                
                $deta[] = [                    
                    'id_cot' => '',
                    'id_prod' => $id_prod ,
                    'can_det' => $item->can_det,
                    'uni_vta' => $item->uni_vta,
                    'pre_unit' => convert_string_to_number($item->pre_unit),
                    'pre_vta' => convert_string_to_number($item->pre_vta),
                    'iva_prod' => $iva_prod,
                    'sub_total' => $sub_total,
                    'mon_iva' => $mon_iva,
                    'tota_prod' => $sub_total + $mon_iva,
                    'create_user' => $_SESSION['id_user'],
                    'create_date' => getAuditoria(),                    
                    'id_des' => '',
                    'utilidad' => $utilidad,
                    'adicional' => $adicional,
                    'costo' => $costo,
                    'dif_cambio' => '',
                ];              
            }                                            
            IF(isset($_POST['id']) && $_POST['id'] > 0){
                $r = FacturacionModel::guardar($enca, $deta, $_POST['id'], $limitePorFactura);                                        
            } else {
                $r = FacturacionModel::guardar($enca, $deta, 0, $limitePorFactura);                                        
            }                                
            echo json_encode($r, JSON_UNESCAPED_UNICODE);         
        }
    }
     /**Nuevos Cambios */
     public function index(){
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }        
        $this->views->getView($this, 'index', [
            'page_name' => 'Consulta de ' . $_SESSION['page_name'] ?? ' ',
            'function_js' => 'Facturacion.js?v=' . SITE_VERSION,
            'function_js_mod' => 'FACFun.js?v=' . SITE_VERSION,            
        ]);
    }
     public function nuevo(){
        $this->views->getView($this, 'nuevo', [
            'page_name' => 'Nueva '  . $_SESSION['page_name'],
            'function_js' => 'Facturacion.js?v=' . SITE_VERSION,
            'function_js_mod' => 'FACFun.js?v=' . SITE_VERSION,
        ]);
    }
    public function cargar_screen_main() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos_tabla = [];
            $ori = $_SESSION['tipo_fact'];
            $r = FacturacionModel::all($ori);
            //Crear tokens
            foreach ($r as $p) {
                $datos_tabla[] = array_merge($p, [
                    "token_edit" => encriptar_url(json_encode(['accion' => 'edit', 'id_cot' => $p['id_cot']]))
                ]);
            }
            echo json_encode($datos_tabla, JSON_UNESCAPED_UNICODE);
        }
    }
    public function listar_cotizacones(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){            
            $id_emp = $_POST['id_emp'];
            $id_cli = $_POST['id_cli'];
            $r = FacturacionModel::listar_cotizacones($id_emp, $id_cli);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
   }
     public function listar_notas()    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $id_cli = $_POST['id_cli'];
            $fuente = $_POST['fuente'];
            $r = FacturacionModel::listar_notas($id_emp, $id_cli, $fuente);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function gestion($token = null)  {
        if (!$token) {
            return;
        }
        $datos = desencriptar_url($token);
        switch ($datos['accion']) {
            case 'edit':
                $this->edit($datos['id_cot']);
                break;
            default:
                // Acción no permitida
                break;
        }
    }
    public function consultar_nota() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_cot = $_POST['id_cot'];
            $r = FacturacionModel::consultar_nota($id_cot);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
}
