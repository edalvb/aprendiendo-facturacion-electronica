<?php

// URL del servicio web de SUNAT (endpoint)
$url = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';

// Archivo ZIP que contiene la factura XML
$zipFile = 'factura_123456.zip';

// Leer el contenido del archivo ZIP
$zipContent = file_get_contents($zipFile);

// Cabeceras para la solicitud HTTP
$headers = [
    'Content-Type: application/soap+xml; charset=utf-8',
    'Content-Length: ' . strlen($zipContent),
];

// Inicializar cURL
$ch = curl_init($url);

// Configurar las opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retornar el resultado
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Configurar las cabeceras
curl_setopt($ch, CURLOPT_POST, true); // Indicar que es una solicitud POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $zipContent); // Enviar el contenido del archivo ZIP

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Comprobar si hubo algún error en la solicitud
if (curl_errno($ch)) {
    echo 'Error en cURL: ' . curl_error($ch);
} else {
    echo 'Respuesta de SUNAT: ' . $response;
}

// Cerrar cURL
curl_close($ch);