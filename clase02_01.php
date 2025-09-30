<?php

// Cargar el archivo XML que se desea firmar
$xml = new DOMDocument();
$xml->load('factura_electronica.xml');

// Cargar el certificado .pfx y la clave privada
$pfxPath = 'certificado.pfx'; // Ruta al certificado pfx
$pfxPassword = 'tu_contraseña_pfx'; // Contraseña del archivo pfx
$pfxContent = file_get_contents($pfxPath); // Leer el contenido del pfx

// Extraer la clave privada y el certificado público del archivo pfx
openssl_pkcs12_read($pfxContent, $certificados, $pfxPassword);
$privateKey = openssl_pkey_get_private($certificados['pkey']);
$publicCert = $certificados['cert'];

// Crear la firma digital usando la clave privada
$xmlToSign = $xml->C14N(); // Canonicalizar el XML (C14N)
openssl_sign($xmlToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

// Añadir la firma al XML en formato base64
$signatureElement = $xml->createElement('Signature', base64_encode($signature));
$xml->documentElement->appendChild($signatureElement);

// Guardar el documento XML firmado
$xml->save('factura_firmada.xml');

echo "XML firmado correctamente.";