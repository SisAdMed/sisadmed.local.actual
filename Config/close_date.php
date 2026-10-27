<?php
/**Ciere de Fechas de Procesos de SisAdmed
*Creado el 28-09-2026 a las 09:24 a.m. por José Vargas
*Modificado el 259-09-2026 a las 09:08 a.m. por José Vargas
*   Se agrego la siguiente linea motivado a que la guardaba con la fecha del dia, debiendo ser el dia anterior
*   3. Restar un día a la fecha actual
*   $fechaActual->modify('-1 day');
*/
//Conexión a la Base de Datos
require_once('../Config/Config.php');
require_once('../Config/App/Conexion.php');
require_once('../Helpers/Helpers.php');

//Obtener fecha del dia
// 1. Establecer la zona horaria deseada (ejemplo: América/Caracas)
$zonaHoraria = new DateTimeZone('America/Caracas');

// 2. Crear el objeto DateTime con esa zona horaria
$fechaActual = new DateTime('now', $zonaHoraria);

// 3. Restar un día a la fecha actual
$fechaActual->modify('-1 day');

// 4. Formatear la fecha como string y guardarla en una variable
$fechaFormateada = $fechaActual->format('Y-m-d');

/**Guardar fecha en la Base de Datos */
//Variables 
$id_user = 1; $db = new Conexion(); //Obtener la instancia del PDO
$link = (object)$db->conect();

try {
    //Inicio transaccion
    $link->beginTransaction();

    //realizar actualización de fechas
    $sqldate = "UPDATE f0011 SET fec_ctb = :fec_ctb, fec_ban = :fec_ban, fec_cxc = :fec_cxc, fec_cxp = :fec_cxp, fec_nom = :fec_nom";
    $stmt = $link->prepare($sqldate);
    $stmt->execute([
        ':fec_ctb' => $fechaFormateada,
        ':fec_ban' => $fechaFormateada,
        ':fec_cxc' => $fechaFormateada,
        ':fec_cxp' => $fechaFormateada,
        ':fec_nom' => $fechaFormateada
    ]);

    //Finalización transaccion
    $link->commit();

    // Si todo sale bien
    registrarLog("[" .date('Y-m-d H:i:s')  ." El script se ejecutó de forma satisfactoria.]", "INFO");
    echo "Fechas de cierre de procesos actualziada satisfactoriamente en la base de datos.\n";

} catch (PDOException $e) {
    debug($e->getMessage());
    $link->rollback();
    registrarLog("[" . date('Y-m-d H:i:s') . "] Error BD: " . $e->getMessage(), 'ERROR');
    echo "Error al actualizar la fecha de cierre de procesos en la base de datos.\n";
}