<div class="container-fluid">
    <div class="card card-primary card-tabs">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs" id="bank-movement" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="customer-banmovim-tab" data-toggle="pill" href="#customer-banmovim" role="tab" aria-controls="customer-banmovim" aria-selected="true">Principal</a>
                </li>
                <li class="nav-item" hidden>
                    <a class="nav-link" id="customer-bancxc-tab" data-toggle="pill" href="#customer-bancxc" role="tab" aria-controls="customer-bancxc" aria-selected="true">Cuentas por Cobrar</a>
                </li>
                <li class="nav-item" hidden>
                    <a class="nav-link" id="customer-bancxp-tab" data-toggle="pill" href="#customer-bancxp" role="tab" aria-controls="customer-bancxp" aria-selected="true">Cuentas por Pagar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="customer-bandet-tab" data-toggle="pill" href="#customer-bandet" role="tab" aria-controls="customer-bandet" aria-selected="true">Detalles</a>
                </li>
            </ul>
        </div>
        <!-- EL CARD-BODY VA DENTRO DE LA CARD -->
        <div class="card-body text-xs">
            <div class="tab-content" id="bank-movement-tabcontent">

                <!-- Pestaña Principal -->
                <div class="tab-pane fade show active" id="customer-banmovim" role="tabpanel" aria-labelledby="customer-banmovim-tab">
                    <input type="hidden" name="id" id="id" value="<?= $r->id_banmov ?? '' ?>">
                    <input type="hidden" name="item" id="item">
                    <input type="hidden" name="efecto" id="efecto">
                    <input type="hidden" name="cxtmo" id="cxtmo">
                    <div class="row">
                        <div class="form-group col-md-12 col-sm-12 col-xs-12 text-xs">
                            <label for="id_emp">Empresa</label>
                            <select name="id_emp" id="id_emp" class="form-control text-xs" title="Empresa"></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 col-sm-3 col-xs-12 text-xs">
                            <label for="id_bantmo">Tipo</label>
                            <select name="id_bantmo" id="id_bantmo" class="form-control text-xs"></select>
                        </div>
                        <div class="form-group col-md-3 col-sm-3 col-xs-12 text-xs">
                            <label for="">Cuenta</label>
                            <select name="id_bancue" id="id_bancue" class="select2 text-xs"></select>
                        </div>
                        <div class="form-group col-md-2 col-sm-2 col-xs-12 text-xs">
                            <label for="fecha_comp">Fecha</label>
                            <input type="date" id="fecha_comp" name="fecha_comp" class="form-control text-xs">
                        </div>
                        <div class="form-group col-md-2 col-sm-2 col-xs-12">
                            <label for="num_banmov">Número</label>
                            <input type="number" id="num_banmov" name="num_banmov" class="form-control text-xs text-right">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2 col-sm-2 col-xs-12">
                            <label for="id_moneda">Moneda</label>
                            <select name="id_moneda" id="id_moneda" class="form-control text-xs"></select>
                        </div>
                        <div class="form-group col-md-2 col-sm-2 col-xs-12">
                            <label for="tasa_cambio">Tasa</label>
                            <input type="text" name="tasa_cambio" id="tasa_cambio" class="form-control text-right text-xs" readonly />
                        </div>
                        <div class="form-group col-md-4 col-sm-4 col-xs-12 benef_banmov">
                            <label for="benef_banmov">Beneficiario</label>
                            <input type="text" name="benef_banmov" id="benef_banmov" class="form-control text-xs" style="text-transform: uppercase;" />
                        </div>
                        <div class="form-group col-sm-4 col-md-4 col-xs-12">
                            <label for="des_banmov">Descripción</label>
                            <textarea id="des_banmov" name="des_banmov" class="form-control text-xs" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 col-sm-3 col-xs-12 text-xs">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control text-xs"></select>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Detalles -->
                <div class="tab-pane fade show" id="customer-bandet" role="tabpanel" aria-labelledby="customer-bandet-tab">
                    <div class="dt-table">
                        <div class="center text-xs">
                            <button type="button" class="btn btn-primary btn-sm text-xs" id="btnAgregarFila">+ Agregar Detalle</button>
                        </div>
                        <table id="tblDetalleMovBan" name="tblDetalleMovBan" class="table table-striped compact table-bordered table-condensed table-hover text-xs" style="width:100%">
                            <thead></thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="text-xs">
                                    <th colspan="3" class="text-right text-xs align-middle">Total General:</th>
                                    <th class="text-right text-xs">
                                        <input type="text" id="footer_total_input" class="form-control text-right text-xs font-weight-bold" value="0,00" disabled>
                                    </th>
                                    <th></th> <!-- Espacio para la columna de Acciones -->
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <!-- Pestaña Cuentas por Cobrar -->
                <div class="tab-pane fade show" id="customer-bancxc" role="tabpanel" aria-labelledby="customer-bancxc-tab">
                    <div class="row">
                        <div class="form-group col-md-4 col-sm-4 col-xs-12">
                            <label for="id_cli">Nombre de Cliente <span class="required">*</span></label>
                            <input type="hidden" id="id_cli" name="id_cli">
                            <div class="input-group">
                                <input type="text" class="form-control text-xs" id="nom_cli" name="nom_cli" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text"><a href="#" data-toggle="modal" data-target="#modal-clientes" title="Buscar y seleccionar cliente"><i class="fas fa-search"></i></a></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12 col-sm-12 col-xs-12">
                            <div class="dt-table">
                                <table id="tblSeatDetail_cxc" class="display responsive compact table-hover text-xs" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Id</th>
                                            <th>Tipo Doc</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Número</th>
                                            <th>Fec.Emis.</th>
                                            <th>Fec.Venc.</th>
                                            <th>Moneda</th>
                                            <th>Tasa</th>
                                            <th>Monto</th>
                                            <th>Saldo</th>
                                            <th>Cancelar</th>
                                            <th>Ret. IVA</th>
                                            <th>Comprobante</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los registros se cargan dinámicamente aquí -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <input type="button" class="btn btn-primary text-xs newdetail" value="Nuevo registro">
                    </div>
                </div>
                <!-- Pestaña Cuentas por Pagar -->
                <div class="tab-pane fade show" id="customer-bancxp" role="tabpanel" aria-labelledby="customer-bancxp-tab">
                    <div class="row">
                        <div class="form-group col-md-4 col-sm-4 col-xs-12">
                            <label for="id_ent">Nombre de Proveedor <span class="required">*</span></label>
                            <input type="hidden" id="id_ent" name="id_ent">
                            <div class="input-group">
                                <input type="text" class="form-control text-xs" id="nom_ent" name="nom_ent" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text"><a href="#" data-toggle="modal" data-target="#modal-Proveedores" title="Buscar y seleccionar Proveedor"><i class="fas fa-search"></i></a></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12 col-sm-12 col-xs-12">
                            <div class="dt-table">
                                <table id="tblSeatDetail_cxp" class="display responsive compact table table-hover text-xs" style="width:100%">                                   
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <input type="button" class="btn btn-primary text-xs newdetail" value="Nuevo registro">
                    </div>
                </div>

            </div>
        </div><!-- FIN DEL CARD-BODY -->
    </div><!-- FIN DE LA CARD -->
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/BanConceptos/modal_BanConceptos.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/Clientes/modal_Clientes.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/Proveedores/modal_Proveedores.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/AuxiliarCtb/modal_AuxiliaresCtb.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/CXCDocument/modal_doc_pen_cxc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Views/CXPDocument/modal_doc_pen_cxp.php';
?>