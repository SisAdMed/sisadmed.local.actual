<?php

/**
 * Clase creada por José Vargas
 * Fecha: 20-12-2004 Hora 11:22 a.m.
 * Metodos para los Movimientos Bancarios
 */
include $_SERVER['DOCUMENT_ROOT'] . '/Models/EmpresasModel.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Models/CXCMovementModel.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Models/CXPMovementModel.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Models/TipoMovCXCModel.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Models/CambiosModel.php';
//Variables
class BanMovim extends Controller{
    function __construct(){
        Auth::noAuth();
        parent::__construct();
        Permisos::getPermisos(159);
    }
    public function index(){
        if (empty($_SESSION['permisosMod']['r'])) {
            header('Location:' . base_url . '/Perfil');
        }
        $this->views->getView($this, 'index', [
            'page_name' => 'Consulta de Movimientos Bancarios',
            'function_js' => 'BanMovim.js?v=' . SITE_VERSION,
            'function_js_mod' => 'BANFun.js?v=' . SITE_VERSION,
        ]);
    }
    public function cargar_screen_main(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos_tabla = [];
            $r = BanMovimModel::all();
            //Crear tokens
            foreach ($r as $p) {
                $datos_tabla[] = array_merge($p, [
                    "token_edit" => encriptar_url(json_encode(['accion' => 'edit', 'id' => $p['id_banmov']]))
                ]);
            }
            echo json_encode($datos_tabla, JSON_UNESCAPED_UNICODE);
        }
    }
    public function nuevo(){
        $this->views->getView($this, 'nuevo', [
            'page_name' => 'Nuevo Movimiento Bancario',
            'function_js' => 'BanMovim.js?v=' . SITE_VERSION,
            'function_js_mod' => 'BANFun.js?v=' . SITE_VERSION,
        ]);
    }
    public function store(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tblDetalleMovBan = json_decode($_POST['tblDetalleMovBan']);
            $encaban = [];
            $detaban = [];
            $modu = 'modify_user';
            $modd = 'modify_date';
            if (empty($_POST['id'])) {
                $modu = 'create_user';
                $modd = 'create_date';
            }
            //Encabezado de movimiento bancario
            //Tasa
            $val_tasa = convert_string_to_number($_POST['tasa_cambio']);
            //Cliente y/o Proveedor
            $id_ent = '';
            if (isset($_POST['id_cli']) && $_POST['id_cli'] > 0) {
                $id_ent = $_POST['id_cli'];
            }
            if (isset($_POST['id_ent']) && $_POST['id_ent'] > 0) {
                $id_ent = $_POST['id_ent'];
            }
            $encaban = [
                'id_emp' => $_POST['id_emp'],
                'id_bantmo' => $_POST['id_bantmo'],
                'id_bancue' => $_POST['id_bancue'],
                'num_banmov' => $_POST['num_banmov'],
                'fecha_comp' => $_POST['fecha_comp'],
                'id_moneda' => $_POST['id_moneda'],
                'tasa_cambio' => $val_tasa,
                'des_banmov' => $_POST['des_banmov'],
                'benef_banmov' => strtoupper(limpiar($_POST['benef_banmov'])),
                'id_ent' => $id_ent,
                'status' => $_POST['status'],
                $modu => $_SESSION['id_user'],
                $modd => getAuditoria(),
            ];
            //Detalle de Movimiento Bancario
            foreach ($tblDetalleMovBan as $item) {
                $monto = convert_string_to_number($item->monto);
                $monto_nac = 0;
                $monto_for = 0;
                if ($val_tasa == 1) {
                    $monto_nac = $monto;
                } else {
                    $monto_nac = $monto * $val_tasa;
                    $monto_for = $monto;
                }
                $detaban[] = [
                    'id_banmov' => !empty($_POST['id'] && $_POST['id'] > 0 ? $_POST['id'] : ''),
                    'id_bancon' => $item->id_bancon,
                    'monto_nac' => $monto_nac,
                    'monto_for' => $monto_for,
                    'id_aux' => isset($item->id_aux) ? $item->id_aux : null,
                    'create_user' => $_SESSION['id_user'],
                    'create_date' => getAuditoria(),
                ];
            }
            //Encabezado de CXP en caso de Afectar proveedores                       
            if (isset($_POST['tblDetalleMovCxp']) && $_POST['efecto'] == 'P') {
                $tblDetalleMovCxp = json_decode(($_POST['tblDetalleMovCxp']));
                $agrupadoPorTasa = [];
                foreach ($tblDetalleMovCxp as $item) {
                    // Creamos una clave única que combine la moneda y la tasa de cambio exacta
                    // (O solo la tasa, dependiendo de cómo manejes tus factores de conversión)
                    $claveTasa = $item->id_moneda . '_' . $item->tasa_cambio;
                    // Agrupamos el objeto bajo esa tasa específica
                    $agrupadoPorTasa[$claveTasa][] = $item;
                }
                // Arreglo maestro que contendrá todos los movimientos (Encabezado + Detalles)
                $movimCXP = [];                
                foreach ($agrupadoPorTasa as $claveUnica => $movim) {
                    $encacxp = [];
                    $detacxp = [];
                    // Separamos la clave si necesitas recuperar la moneda y la tasa por separado                    
                    list($idMoneda, $tasaCambio) = explode('_', $claveUnica);
                    $movem_amount = 0;
                    foreach ($movim as $det) {
                        $tasaDoc = floatval($det->tasa_cambio ?? 0);
                        // Si la tasa del banco es igual a la tasa del documento, se deja el monto igual.
                        // De lo contrario, se divide el monto entre la tasa del documento (validando que no sea cero).
                        if ($val_tasa == $tasaDoc) {
                            $movem_amount += $det->monto;
                        } else {
                            $movem_amount += ($tasaDoc > 0) ? ($det->monto / $tasaDoc) : $det->monto;
                        }                        
                    }
                    //Crear encabezado de Movimiento de CXP                                            
                    $encacxp = [
                        'id_emp' => $_POST['id_emp'],
                        'id_tmocxp' => $_POST['cxtmo'],
                        'movem_number' => '',
                        'id_ent' => $_POST['id_ent'],
                        'fecha_comp' => $_POST['fecha_comp'],
                        'movem_descrip' => '',
                        'movem_amount' => $movem_amount,
                        'status' => $_POST['status'],
                        'movem_origen' => $_POST['id'] ?? '',
                        'id_moneda' => $idMoneda,
                        'tasa_cambio' => $tasaCambio,
                        'create_user' => $_SESSION['id_user'],
                        'create_date' => getAuditoria(),
                    ];     
                    $detacxp = [];               
                    foreach ($movim as $det) {
                        $tasaDoc = floatval($det->tasa_cambio ?? 0);
                        // Si la tasa del banco es igual a la tasa del documento, se deja el monto igual.
                        // De lo contrario, se divide el monto entre la tasa del documento (validando que no sea cero).
                        if ($val_tasa == $tasaDoc) {
                            $montoDoc = $det->monto;
                        } else {
                            $montoDoc = ($tasaDoc > 0) ? ($det->monto / $tasaDoc) : $det->monto;
                        }
                        $detacxp[] = [
                            'movem_id' => '',
                            'id_tdo' => $det->id_tdo,
                            'num_tdo' => $det->num_tdo,
                            'id_cot' => $det->id_cot,
                            'monto_doc' => $montoDoc,
                            'create_user' => $_SESSION['id_user'],
                            'create_date' => getAuditoria(),
                        ];
                    }
                    // 3. Unificar ambos en un solo elemento dentro del arreglo maestro
                    $movimCXP[] = [
                        'encabezado' => $encacxp,
                        'detalles' => $detacxp
                    ];
                }
            }
            //Encabezado de CXC en caso de Afectar clientes                       
            if (isset($_POST['tblDetalleMovCxc']) && $_POST['efecto'] == 'C') {
                $tblDetalleMovCxc = json_decode(($_POST['tblDetalleMovCxc']));               
                $agrupadoPorTasa = [];
                foreach ($tblDetalleMovCxc as $item) {
                    // Creamos una clave única que combine la moneda y la tasa de cambio exacta
                    // (O solo la tasa, dependiendo de cómo manejes tus factores de conversión)
                    $claveTasa = $item->id_moneda . '_' . $item->tasa_cambio;
                    // Agrupamos el objeto bajo esa tasa específica
                    $agrupadoPorTasa[$claveTasa][] = $item;
                }
                // Arreglo maestro que contendrá todos los movimientos (Encabezado + Detalles)
                $movimCXC = [];                
                foreach ($agrupadoPorTasa as $claveUnica => $movim) {
                    $encacxc = [];
                    $detacxC = [];
                    // Separamos la clave si necesitas recuperar la moneda y la tasa por separado                    
                    list($idMoneda, $tasaCambio) = explode('_', $claveUnica);
                    $movem_amount = 0;
                    foreach ($movim as $det) {
                        $tasaDoc = floatval($det->tasa_cambio ?? 0);
                        // Si la tasa del banco es igual a la tasa del documento, se deja el monto igual.
                        // De lo contrario, se divide el monto entre la tasa del documento (validando que no sea cero).
                        if ($_POST['id_moneda'] == $det->id_moneda) {
                            $movem_amount += $det->monto;
                        } else {
                            $movem_amount += ($tasaDoc > 0) ? ($det->monto / $tasaDoc) : $det->monto;
                        }                        
                    }
                    //Crear encabezado de Movimiento de CXC                                          
                    $encacxc = [
                        'id_emp' => $_POST['id_emp'],
                        'id_tmocxc' => $_POST['cxtmo'],
                        'movem_number' => '',
                        'id_cli' => $_POST['id_cli'],
                        'fecha_comp' => $_POST['fecha_comp'],
                        'movem_descrip' => '',
                        'movem_amount' => $movem_amount,
                        'status' => $_POST['status'],
                        'movem_origen' => $_POST['id'] ?? '',
                        'id_moneda' => $idMoneda,
                        'tasa_cambio' => $tasaCambio,
                        'create_user' => $_SESSION['id_user'],
                        'create_date' => getAuditoria(),
                        'movem_amount_for' => $movem_amount,
                    ];                        
                    $detacxc = [];               
                    foreach ($movim as $det) {                        
                        $tasaDoc = floatval($det->tasa_cambio ?? 0);                        
                        // Si la tasa del banco es igual a la tasa del documento, se deja el monto igual.
                        // De lo contrario, se divide el monto entre la tasa del documento (validando que no sea cero).
                        if ($_POST['id_moneda'] == $det->id_moneda) {
                            $montoDoc = $det->monto;  
                            $monRet = $det->mon_ret;
                        } else {
                            $montoDoc = ($tasaDoc > 0) ? ($det->monto / $tasaDoc) : $det->monto;
                            $monRet = $det->mon_ret / $tasaDoc;
                        }
                        $detacxc[] = [
                            'movem_id' => '',
                            'id_tdo' => $det->id_tdo,
                            'num_tdo' => $det->num_tdo,
                            'id_cot' => $det->id_cot,
                            'monto_doc' => $montoDoc,
                            'monto_doc_for' => $montoDoc,
                            'create_user' => $_SESSION['id_user'],
                            'create_date' => getAuditoria(),
                            'mon_ret' => $monRet,
                            'monto_ret_for' => $det->mon_ret,
                            'num_ret' => $det->num_ret
                        ];                        
                    }
                    // 3. Unificar ambos en un solo elemento dentro del arreglo maestro
                    $movimCXC[] = [
                        'encabezado' => $encacxc,
                        'detalles' => $detacxc
                    ];
                }
            }
            if (isset($_POST['id']) && $_POST['id'] > 0) {
                $r = BanMovimModel::guardar($encaban, $detaban, $movimCXC ?? [], $movimCXP ?? [], $_POST['id']);
            } else {
                $r = BanMovimModel::guardar($encaban, $detaban, $movimCXC ?? [], $movimCXP ?? [], 0);
            }
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function edit(int $id)
    {
        if (Permisos::read()) {
            if ($id > 0) {
                $r = BanMovimModel::edit($id);                                
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/BanMovim');
                }
                $this->views->getView($this, "edit", [
                     'page_name' => "Editar Movimiento Bancario {$r['cod_bantmo']} {$r['nom_bantmo']} número {$r['num_banmov']}",
                    'function_js' => 'BanMovim.js?v=' . SITE_VERSION,
                    'function_js_mod' => 'BANFun.js?v=' . SITE_VERSION,
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/BanMovim');
            }
            return;
        }
        Alertas::new('No tiene permiso para realizar esta acción', 'warning');
        header('Location:' . base_url . '/BanMovim');
    }
    public function show_row($id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = BanMovimModel::show_row($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function destroy()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $r = BanMovimModel::destroy($id);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function det_doc_can()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $efecto = $_POST['efecto'];
            if (isset($_POST['id_moneda'])) {
                $id_moneda = $_POST['id_moneda'];
            }
            $r = BanMovimModel::det_doc_can($id, $efecto);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function print_movement(int $id)
    {
        if (Permisos::read()) {
            $id = intval(limpiar($id));
            if ($id > 0) {
                $r = BanMovimModel::print_mov($id);
                if (empty($r)) {
                    Alertas::new('El registro no existe', 'warning');
                    header('Location:' . base_url . '/BanMovim');
                }
                $this->views->getView($this, "print_movement", [
                    'r' => to_obj($r)
                ]);
            } else {
                header('Location:' . base_url . '/BanMovim');
            }
            return;
        }
        Alertas::new('No tiene permiso para realizar esta acción', 'warning');
        header('Location:' . base_url . '/BanMovim');
    }
    public function movcuentas(){
        if (Permisos::read()) {
            $this->views->getView($this, "movcuentas", [
                'page_name' => 'Movimientos por Cuentas',
                'function_js' => 'BanMovim.js?v=' . SITE_VERSION,
                'function_js_mod' => 'BANFun.js?v=' . SITE_VERSION,
            ]);
        }
    }
    public function banmov_cuentas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_emp = $_POST['id_emp'];
            $fec_ini = $_POST['fec_ini'];
            $fec_fin = $_POST['fec_fin'];
            $id_bancue = $_POST['id_bancue'];
            $id_bancon = $_POST['id_bancon'];
            $r = BanMovimModel::banmov_cuentas($id_emp, $fec_ini, $fec_fin, $id_bancue, $id_bancon);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
    }
    public function gestion($token = null){
    if (!$token) {
        return;
    }    
    $datos = desencriptar_url($token);        
    // Validar que la desencriptación fue exitosa y es un arreglo con las claves necesarias
    if (!is_array($datos) || !isset($datos['accion'], $datos['id'])) {
        // Manejar el error de token inválido o retornar
        return;
    }
    $accion = $datos['accion'];
    $id = intval($datos['id']);
    switch ($accion) {
        case 'edit':
            $this->edit($id);
            break;
        default:
            // Acción no permitida
            break;
    }
}
    public function getConfigBanco()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if(!isset($_POST['efecto'])) exit();
            $id_emp = $_POST['id_emp'];            
            $efecto = $_POST['efecto'];            
            $r = BanMovimModel::getConfigBanco($id_emp, $efecto);
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}
