<?php
    include_once('Config.php');
    // --- CONFIGURACIÓN ---
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASSWORD;
    $to = "";
    $subject = "";
    $message = ""; 
    $headers = "";
    
    try {
        echo PHP_EOL;
        // Carpeta donde se guardarán los respaldos (asegúrate de que tenga permisos de escritura 755 o 777)
        $backup_dir = '/home/cpuj4gbphmxn/sisadmed_backups';
        
        // Crear la carpeta de respaldos si no existe
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Nombre del archivo con fecha y hora
        $fecha = date("Y-m-d_H-i-s");
        echo $fecha;
        $filename = $backup_dir . '/' . $db_name . '_' . $fecha . '.sql';
        $zipFile  = $backup_dir . '/' . $db_name . '_' . $fecha . '.zip';
        $nombre_archivo = basename($zipFile);
        
        // Comando mysqldump
        $command = "mysqldump --host=$db_host --user=$db_user --password=$db_pass $db_name > $filename";
        
        // Ejecutar el comando
        system($command, $output);
        
        // --- RESULTADO ---
        if ($output === 0) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($filename, basename($filename));
                $zip->close();
                unlink($filename);
                
                echo PHP_EOL;
                echo "Respaldo exitoso: " . $zipFile;
                
                // Eliminar archivos zip mayores a 7 días
                $dias = 7;
                $tiempoLimite = time() - ($dias * 24 * 60 * 60);
                
                foreach (glob($backup_dir . '/*.zip') as $file) {
                    if (filemtime($file) < $tiempoLimite) {
                        echo PHP_EOL;
                        if (unlink($file)) {
                            echo "Archivo eliminado: " . $file;
                        } else {
                            echo "No se pudo eliminar: " . $file;
                        }
                        echo PHP_EOL;
                    }
                }
                
                $html_break = '<br>';
                $to      = "jose.vargas@josvarsistemas.com.ve, gerencia@corp-mmq.com";
                $subject = 'Respaldo de SisAdMed al ' . $fecha;
                
                $mensaje = 'Buen día' . $html_break . $html_break; 
                $mensaje .= 'Adjunto le hacemos llegar el respaldo de SisAdMed al ' . $fecha . $html_break . $html_break; 
                $mensaje .= 'Saludos cordiales' . $html_break . $html_break;
                $mensaje .= 'Sistema Administrativo Médico' . $html_break . $html_break;
                $mensaje .= 'PD: Cuenta de correo no monitoreado, por favor no responda este correo.' . $html_break . $html_break;
                
                // Crear un límite único (boundary) limpio
                $boundary = md5(time());
                
                // Encabezados principales corregidos (SIN concatenar el nombre del archivo en el boundary)
                $headers = "From: sisadmed@corp-mmq.com\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
                $headers .= "Cc: nguerra.ekomed@gmail.com\r\n";
                $headers .= "Bcc: cheovargas@gmail.com\r\n";
                
                // Construir el cuerpo del mensaje (multipart)
                $message  = "--" . $boundary . "\r\n";
                $headers_content_type = "Content-Type: text/html; charset=UTF-8\r\n";
                $message .= $headers_content_type;
                $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $message .= $mensaje . "\r\n\r\n";
                
                // Añadir el archivo adjunto de manera robusta
                if (file_exists($zipFile)) {
                    $contenido = chunk_split(base64_encode(file_get_contents($zipFile)));
                    
                    $message .= "--" . $boundary . "\r\n";
                    $message .= "Content-Type: application/zip; name=\"" . $nombre_archivo . "\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"" . $nombre_archivo . "\"\r\n\r\n";
                    $message .= $contenido . "\r\n";
                    $message .= "--" . $boundary . "--";
                }
            }
        }
        
        echo PHP_EOL;
        if (mail($to, $subject, $message, $headers)) {
            echo 'Correo enviado exitosamente desde mail()';    
        } else {
            echo 'Error enviando correo desde mail()';
        }
    } catch (Exception $e) {
        echo PHP_EOL;
        echo "Error al enviar: " . $e->getMessage();
    } finally {
        echo PHP_EOL;
    }
?>