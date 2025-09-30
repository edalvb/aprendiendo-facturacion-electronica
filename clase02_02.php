// Cargar el documento XML firmado
$xml = new DOMDocument();
$xml->load('factura_firmada.xml');

// Extraer la firma del documento
$signatureElement = $xml->getElementsByTagName('Signature')->item(0);
$signature = base64_decode($signatureElement->nodeValue);

// Obtener el contenido XML que fue firmado
$xmlToVerify = $xml->C14N();

// Verificar la firma usando el certificado público
$publicCert = 'ruta_al_certificado_publico.pem'; // Certificado público en formato PEM
$certContent = file_get_contents($publicCert);
$publicKey = openssl_pkey_get_public($certContent);

// Verificación de la firma
$verification = openssl_verify($xmlToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);

if ($verification === 1) {
    echo "La firma es válida.";
} elseif ($verification === 0) {
    echo "La firma no es válida.";
} else {
    echo "Error en la verificación de la firma.";
}
