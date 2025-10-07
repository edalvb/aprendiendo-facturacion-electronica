<?php

// Ruta del archivo XML que se desea comprimir
$xmlFile = 'factura_electronica.xml';

// Ruta y nombre del archivo ZIP que se generará
$zipFile = 'factura_electronica.zip';

// Crear una nueva instancia de ZipArchive
$zip = new ZipArchive();

// Abrir el archivo ZIP para escritura
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    // Añadir el archivo XML al ZIP
    $zip->addFile($xmlFile, basename($xmlFile));
    
    // Cerrar el archivo ZIP
    $zip->close();
    
    echo "Archivo ZIP generado correctamente: $zipFile";
} else {
    echo "Error al crear el archivo ZIP.";
}