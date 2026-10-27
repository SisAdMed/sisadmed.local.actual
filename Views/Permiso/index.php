<?php headerAdmin($data ?? []); ?>
<div class="content-wrapper">
    <!-- Título superior con el nombre del rol y botón de retorno -->
    <div class="row mb-3 align-items-center">
        <div class="col-sm-6">
            <h4 class="m-0 text-dark font-weight-bold">
                Configurando Permisos para el Rol: <span class="text-primary"><?= htmlspecialchars(html_entity_decode($modulos['nombreRol'] ?? 'Rol', ENT_QUOTES, 'UTF-8')) ?></span>
            </h4>
        </div>
        <div class="col-sm-6 text-right">
            <!-- Botón para devolverse a la pantalla de roles -->
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="<?= base_url ?>/Roles" title="Lista de roles"><i class="fa fa-reply"></i></a></li>
            </ol>
        </div>
    </div>

    <!-- INICIO DEL FORMULARIO DE PERMISOS -->
    <form method="POST" id="formPermisos" name="formPermisos">
        <!-- Input oculto para enviar el ID del rol que estamos configurando -->
        <input type="hidden" name="id_rol" id="id_rol" value="<?= $id_rol ?? '' ?>">

        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="permisos-tab" role="tablist">
                    <?php
                    $listaModulos = $modulos['arbolPermisos'] ?? [];
                    if (!empty($listaModulos)):
                        $i = 0;
                        foreach ($listaModulos as $mod):
                            $nombreMod = html_entity_decode($mod['nombre_menu'], ENT_QUOTES, 'UTF-8');
                            $slugMod = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreMod)));
                            $active = ($i === 0) ? 'active' : '';
                    ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $active ?>" id="tab-<?= $slugMod ?>" data-toggle="pill" href="#content-<?= $slugMod ?>" role="tab">
                                    <?= htmlspecialchars($nombreMod, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php
                            $i++;
                        endforeach;
                    else:
                        ?>
                        <li class="nav-item"><span class="nav-link text-danger">No se encontraron módulos principales.</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="permisos-tabContent">
                    <?php
                    if (!empty($listaModulos)):
                        $j = 0;
                        foreach ($listaModulos as $mod):
                            $nombreMod = html_entity_decode($mod['nombre_menu'], ENT_QUOTES, 'UTF-8');
                            $slugMod = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreMod)));
                            $showActive = ($j === 0) ? 'show active' : '';
                            $idModulo = $mod['id_menu'];
                            // Si la base de datos tiene un ícono registrado lo usamos, sino ponemos uno por defecto
                            $iconoModulo = !empty($mod['icono_menu']) ? $mod['icono_menu'] : 'fas fa-cubes';
                    ?>
                            <div class="tab-pane fade <?= $showActive ?>" id="content-<?= $slugMod ?>" role="tabpanel">

                                <!-- Cabecera del Módulo con sus checkboxes fijos -->
                                <div class="card card-primary card-outline mb-3">
                                    <div class="card-header bg-gradient-primary py-2">
                                        <h3 class="card-title font-weight-bold m-0" style="font-size: 1rem;">
                                            <i class="<?= htmlspecialchars($iconoModulo, ENT_QUOTES, 'UTF-8') ?> mr-2"></i> Módulo: <?= htmlspecialchars($nombreMod, ENT_QUOTES, 'UTF-8') ?>
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool text-white p-0 check-all-module" data-target="<?= $slugMod ?>" title="Seleccionar todo">
                                                <i class="fas fa-check-double"></i> Seleccionar todo
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body bg-light py-2">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <span class="font-weight-bold text-dark text-sm">Permiso general sobre el Módulo (Acceso raíz):</span>
                                            <div class="d-flex align-items-center">
                                                <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="mod_c_<?= $idModulo ?>" name="permiso[<?= $idModulo ?>][C]" value="1" class="<?= $slugMod ?>-chk" <?= ($mod['crear'] == 1) ? 'checked' : '' ?>><label for="mod_c_<?= $idModulo ?>" class="text-sm font-weight-normal ml-1 mb-0">Crear</label></div>
                                                <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="mod_r_<?= $idModulo ?>" name="permiso[<?= $idModulo ?>][R]" value="1" class="<?= $slugMod ?>-chk" <?= ($mod['leer'] == 1) ? 'checked' : '' ?>><label for="mod_r_<?= $idModulo ?>" class="text-sm font-weight-normal ml-1 mb-0">Leer</label></div>
                                                <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="mod_u_<?= $idModulo ?>" name="permiso[<?= $idModulo ?>][U]" value="1" class="<?= $slugMod ?>-chk" <?= ($mod['actualizar'] == 1) ? 'checked' : '' ?>><label for="mod_u_<?= $idModulo ?>" class="text-sm font-weight-normal ml-1 mb-0">Actualizar</label></div>
                                                <div class="icheck-primary d-inline"><input type="checkbox" id="mod_d_<?= $idModulo ?>" name="permiso[<?= $idModulo ?>][D]" value="1" class="<?= $slugMod ?>-chk" <?= ($mod['borrar'] == 1) ? 'checked' : '' ?>><label for="mod_d_<?= $idModulo ?>" class="text-sm font-weight-normal ml-1 mb-0">Borrar</label></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CATEGORÍAS (Nivel 2) -->
                                <?php
                                if (!empty($mod['hijos'])):
                                    foreach ($mod['hijos'] as $categoria):
                                        $nombreCat = html_entity_decode($categoria['nombre_menu'], ENT_QUOTES, 'UTF-8');
                                        $idCat = $categoria['id_menu'];
                                        $slugCat = $slugMod . '-' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreCat)));
                                ?>
                                        <div class="card card-secondary card-outline">
                                            <div class="card-header bg-light py-2">
                                                <h3 class="card-title font-weight-bold text-dark m-0" style="font-size: 0.95rem;">
                                                    <i class="fas fa-folder-open mr-1 text-secondary"></i> <?= htmlspecialchars($nombreCat, ENT_QUOTES, 'UTF-8') ?>
                                                </h3>
                                                <div class="card-tools">
                                                    <button type="button" class="btn btn-tool btn-sm text-primary font-weight-bold check-all-category mr-2" data-target="<?= $slugCat ?>" title="Seleccionar todo en esta categoría">
                                                        <i class="fas fa-check-double"></i> Seleccionar categoría
                                                    </button>
                                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                                </div>
                                            </div>

                                            <!-- Permiso general de la Categoría Nivel 2 -->
                                            <div class="card-body bg-white py-1 px-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                    <span class="text-muted text-xs font-weight-bold">Permiso general sobre (<?= htmlspecialchars($nombreCat, ENT_QUOTES, 'UTF-8') ?>):</span>
                                                    <div class="d-flex align-items-center">
                                                        <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="cat_c_<?= $idCat ?>" name="permiso[<?= $idCat ?>][C]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($categoria['crear'] == 1) ? 'checked' : '' ?>><label for="cat_c_<?= $idCat ?>" class="text-xs font-weight-normal ml-1 mb-0">Crear</label></div>
                                                        <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="cat_r_<?= $idCat ?>" name="permiso[<?= $idCat ?>][R]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($categoria['leer'] == 1) ? 'checked' : '' ?>><label for="cat_r_<?= $idCat ?>" class="text-xs font-weight-normal ml-1 mb-0">Leer</label></div>
                                                        <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="cat_u_<?= $idCat ?>" name="permiso[<?= $idCat ?>][U]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($categoria['actualizar'] == 1) ? 'checked' : '' ?>><label for="cat_u_<?= $idCat ?>" class="text-xs font-weight-normal ml-1 mb-0">Actualizar</label></div>
                                                        <div class="icheck-primary d-inline"><input type="checkbox" id="cat_d_<?= $idCat ?>" name="permiso[<?= $idCat ?>][D]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($categoria['borrar'] == 1) ? 'checked' : '' ?>><label for="cat_d_<?= $idCat ?>" class="text-xs font-weight-normal ml-1 mb-0">Borrar</label></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body p-0">
                                                <?php 
                                                if (!empty($categoria['hijos'])):
                                                    $esMultinivel = false;
                                                    foreach($categoria['hijos'] as $hijoPrueba) {
                                                        if (!empty($hijoPrueba['hijos'])) {
                                                            $esMultinivel = true;
                                                            break;
                                                        }
                                                    }

                                                    if ($esMultinivel):
                                                        foreach ($categoria['hijos'] as $subcat):
                                                            $nombreSub = html_entity_decode($subcat['nombre_menu'], ENT_QUOTES, 'UTF-8');
                                                            $idSub = $subcat['id_menu'];
                                                            $slugSub = $slugCat . '-' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreSub)));
                                                ?>
                                                            <div class="bg-light px-3 py-2 border-bottom font-weight-bold text-sm text-secondary d-flex justify-content-between align-items-center">
                                                                <span><i class="fas fa-angle-right mr-1"></i> <?= htmlspecialchars($nombreSub, ENT_QUOTES, 'UTF-8') ?></span>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="sub_c_<?= $idSub ?>" name="permiso[<?= $idSub ?>][C]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($subcat['crear'] == 1) ? 'checked' : '' ?>><label for="sub_c_<?= $idSub ?>" class="text-xs font-weight-normal ml-1 mb-0">Crear</label></div>
                                                                    <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="sub_r_<?= $idSub ?>" name="permiso[<?= $idSub ?>][R]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($subcat['leer'] == 1) ? 'checked' : '' ?>><label for="sub_r_<?= $idSub ?>" class="text-xs font-weight-normal ml-1 mb-0">Leer</label></div>
                                                                    <div class="icheck-primary d-inline mr-3"><input type="checkbox" id="sub_u_<?= $idSub ?>" name="permiso[<?= $idSub ?>][U]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($subcat['actualizar'] == 1) ? 'checked' : '' ?>><label for="sub_u_<?= $idSub ?>" class="text-xs font-weight-normal ml-1 mb-0">Actualizar</label></div>
                                                                    <div class="icheck-primary d-inline"><input type="checkbox" id="sub_d_<?= $idSub ?>" name="permiso[<?= $idSub ?>][D]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($subcat['borrar'] == 1) ? 'checked' : '' ?>><label for="sub_d_<?= $idSub ?>" class="text-xs font-weight-normal ml-1 mb-0">Borrar</label></div>
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($subcat['hijos'])): ?>
                                                                <table class="table table-hover text-nowrap table-sm m-0">
                                                                    <!-- AQUI RESTAURAMOS LOS TÍTULOS DE LAS COLUMNAS -->
                                                                    <thead>
                                                                        <tr>
                                                                            <th style="width: 52%;" class="pl-5 text-xs">Opción / Pantalla</th>
                                                                            <th class="text-center text-xs" style="width: 12%;">Crear (C)</th>
                                                                            <th class="text-center text-xs" style="width: 12%;">Leer (R)</th>
                                                                            <th class="text-center text-xs" style="width: 12%;">Actualizar (U)</th>
                                                                            <th class="text-center text-xs" style="width: 12%;">Borrar (D)</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($subcat['hijos'] as $accion): 
                                                                            $nombreAccion = html_entity_decode($accion['nombre_menu'], ENT_QUOTES, 'UTF-8');
                                                                        ?>
                                                                            <tr>
                                                                                <td class="pl-5" style="width: 52%;"><?= htmlspecialchars($nombreAccion, ENT_QUOTES, 'UTF-8') ?></td>
                                                                                <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][C]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($accion['crear'] == 1) ? 'checked' : '' ?>></td>
                                                                                <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][R]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($accion['leer'] == 1) ? 'checked' : '' ?>></td>
                                                                                <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][U]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($accion['actualizar'] == 1) ? 'checked' : '' ?>></td>
                                                                                <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][D]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk <?= $slugSub ?>-chk" <?= ($accion['borrar'] == 1) ? 'checked' : '' ?>></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php endif; ?>
                                                        <?php 
                                                        endforeach;
                                                    else:
                                                    ?>
                                                        <table class="table table-hover text-nowrap table-sm m-0">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 50%;">Opción / Pantalla</th>
                                                                    <th class="text-center" style="width: 12%;">Crear (C)</th>
                                                                    <th class="text-center" style="width: 12%;">Leer (R)</th>
                                                                    <th class="text-center" style="width: 12%;">Actualizar (U)</th>
                                                                    <th class="text-center" style="width: 12%;">Borrar (D)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($categoria['hijos'] as $accion): 
                                                                    $nombreAccion = html_entity_decode($accion['nombre_menu'], ENT_QUOTES, 'UTF-8');
                                                                ?>
                                                                    <tr>
                                                                        <td class="pl-4" style="width: 50%;"><?= htmlspecialchars($nombreAccion, ENT_QUOTES, 'UTF-8') ?></td>
                                                                        <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][C]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($accion['crear'] == 1) ? 'checked' : '' ?>></td>
                                                                        <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][R]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($accion['leer'] == 1) ? 'checked' : '' ?>></td>
                                                                        <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][U]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($accion['actualizar'] == 1) ? 'checked' : '' ?>></td>
                                                                        <td class="text-center" style="width: 12%;"><input type="checkbox" name="permiso[<?= $accion['id_menu'] ?>][D]" value="1" class="<?= $slugMod ?>-chk <?= $slugCat ?>-chk" <?= ($accion['borrar'] == 1) ? 'checked' : '' ?>></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>

                            </div>
                    <?php
                            $j++;
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Botón de Guardado General al pie de la tarjeta -->
            <div class="card-footer text-center bg-white">
                <a href="<?= base_url ?>/Roles" class="btn btn-default btn-xs mr-2">
                    <i class="fas fa-arrow-left mr-1"></i> Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-primary btn-xs">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios de Permisos
                </button>
            </div>
        </div>
    </form>
</div>
<?php footerAdmin($data ?? []); ?>