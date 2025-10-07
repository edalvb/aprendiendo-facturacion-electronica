<?php

// Crear el contenido del mensaje SOAP
$soapRequest = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe">
   <soapenv:Header/>
   <soapenv:Body>
      <ser:sendBill>
         <fileName>factura_123456.zip</fileName>
         <contentFile>' . base64_encode($zipContent) . '</contentFile>
      </ser:sendBill>
   </soapenv:Body>
</soapenv:Envelope>';

// Inicializar cURL para enviar la solicitud SOAP
$ch = curl_init($url);

// Configurar las cabeceras y opciones para la solicitud SOAP
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: ' . strlen($soapRequest),
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Verificar si hubo algún error
if (curl_errno($ch)) {
    echo 'Error en cURL: ' . curl_error($ch);
} else {
    echo 'Respuesta de SUNAT: ' . $response;
}

// Cerrar la sesión cURL
curl_close($ch);