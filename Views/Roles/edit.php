<?php headerAdmin($data ?? []); ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1><?php echo $data['page_name'] ?? ''; ?>
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= base_url ?>/Roles" title="Lista de roles"><i class="fa fa-reply"></i></a></li>
                        </ol>
                    </h1>
                </div>
            </div>
        </div>
    </section>    
    <section class="content">                
        <form id="my_form" name="my_form" method="POST" class="form-horizontal form-label-left" >
            <div class="row d-flex justify-content-ceter">
                <?php include_once __DIR__ . "/form.php" ?>
                <div class="col-12 text-center mt-3">
                    <div class="card-footer border-top-0">
                        <input type="submit" id="btnok" name="btnok" class="btn btn-success btn-xs" value="Actualizar" />
                    </div>
                </div>
            </div>
        </form>
    </section>    
</div>
<?php footerAdmin($data ?? []); ?>