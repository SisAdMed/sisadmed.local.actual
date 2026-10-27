<?php 
headerAdmin($data ?? []); 
$origen = $_GET['ori'];
if($origen == 'C'){
    $date_close = 'Fecha de Cierre';
    $titulo_proceso = 'Cierre Contable de Módulos';
    $desc_proceso = 'Este proceso consiste en actualizar saldos, generar asientos contables y reservar la información procesada solamente para consultas.';
    $btn_texto = 'Iniciar Cierre de Módulos';
}else{
    $titulo_proceso = 'Apertura Contable de Módulos';
    $desc_proceso = 'Este proceso consiste en reversar asientos contables y actualizar saldos de los módulos.';
    $date_close = 'Nueva Fecha de Cierre / Apertura';
    $btn_texto = 'Iniciar Apertura de Módulos';
}
?>

<div class='content-wrapper'>
    <section class='content-header'>
        <div class='container-fluid'>
            <div class='row mb-2'>
                <div class='col-sm-12'>
                    <h1><i class="fas fa-calendar-check mr-2"></i><?php echo $data['page_name'] ?? ''; ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class='content'>
        <div class='container-fluid'>
            <div class="row justify-content-center">
                <div class="col-md-8 col-sm-12">
                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold text-secondary">
                                <i class="fas fa-info-circle mr-1"></i> Información del Proceso
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Alerta informativa con diseño moderno -->
                            <div class="alert alert-light border-left-primary text-dark p-3 mb-4 shadow-sm" style="border-left: 4px solid #007bff;">
                                <h5 class="text-primary font-weight-bold mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> Aviso Importante</h5>
                                <p class="mb-1"><?= $desc_proceso ?></p>
                                <small class="text-muted font-italic">La duración de este proceso dependerá del volumen de la información a procesar, pudiendo tardar varios minutos. No cierre la pestaña ni recargue la página.</small>
                            </div>

                            <form name="my_form" id="my_form" method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="id_emp"><i class="fas fa-building mr-1"></i> Empresa <span class="text-danger">*</span></label>
                                        <select name="id_emp" id="id_emp" class="form-control text-xs select2" style="width: 100%;">                                            
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="date_close"><i class="fas fa-calendar-alt mr-1"></i> <?= $date_close ?> <span class="text-danger">*</span></label>
                                        <input type="date" id="date_close" name="date_close" class="form-control text-xs">
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" id="btn_close" name="btn_close" class="btn btn-primary btn-lg px-5 shadow">
                                        <i class="fas fa-play-circle mr-2"></i><?php echo $btn_texto; ?>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Card Footer con la Barra de Progreso Mejorada -->
                        <div class="card-footer bg-white border-top">
                            <div class="row align-items-center mb-2">
                                <div class="col-6">
                                    <span id="progressText" class="text-bold text-muted"><i class="fas fa-clock mr-1"></i> Esperando inicio...</span>
                                </div>
                                <div class="col-6 text-right">
                                    <span id="progressPercent" class="text-bold text-primary" style="font-size: 1.1rem;">0%</span>
                                </div>
                            </div>
                            <div class="progress progress-sm rounded shadow-inner" style="height: 12px; background-color: #e9ecef;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                    role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php footerAdmin($data ?? []); ?>