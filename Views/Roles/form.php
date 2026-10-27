<div class="card-body">
    <input type="hidden" id="id" name="id" value="<?php echo $rol->id_rol  ?? '' ?>">
    <div class="row">
        <div class="form-group col-md-9 col-sm-9 col-xs-12">
            <label for="nombre_rol">Nombre <span class="required">*</span></label>
            <input type="text" class="form-control text-xs" id="nombre_rol" name="nombre_rol" placeholder="Ingrese nombre">
        </div>
        <div class="form-group col-md-3 col-md-3 col-sm-6 col-xs-12">
            <label for="status">Estado </label>
            <select class="form-control text-xs" id="status" name="status">
            </select>
        </div>        
    </div>
</div>