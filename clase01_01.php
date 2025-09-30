<?php

// Crear un nuevo documento XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true; // Formato legible

// Crear el elemento raíz del XML
$invoice = $xml->createElement('Invoice');
$invoice->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');

// Agregar los elementos de cabecera
$cbc = $xml->createElement('cbc:CustomizationID', '2.1');
$invoice->appendChild($cbc);

$cbc = $xml->createElement('cbc:ID', 'F001-12345');
$invoice->appendChild($cbc);

$cbc = $xml->createElement('cbc:IssueDate', '2024-09-16');
$invoice->appendChild($cbc);

// Información del emisor (SupplierParty)
$supplierParty = $xml->createElement('cac:AccountingSupplierParty');
$party = $xml->createElement('cac:Party');
$supplierParty->appendChild($party);

$partyName = $xml->createElement('cbc:Name', 'Empresa XYZ S.A.C.');
$party->appendChild($partyName);

// Información del receptor (CustomerParty)
$customerParty = $xml->createElement('cac:AccountingCustomerParty');
$party = $xml->createElement('cac:Party');
$customerParty->appendChild($party);

$partyName = $xml->createElement('cbc:Name', 'Cliente ABC S.A.');
$party->appendChild($partyName);

// Agregar las partes al XML
$invoice->appendChild($supplierParty);
$invoice->appendChild($customerParty);

// Detalles de la factura
$invoiceLine = $xml->createElement('cac:InvoiceLine');
$cbc = $xml->createElement('cbc:ID', '1');
$invoiceLine->appendChild($cbc);

$cbc = $xml->createElement('cbc:LineExtensionAmount', '100.00');
$cbc->setAttribute('currencyID', 'PEN');
$invoiceLine->appendChild($cbc);

$invoice->appendChild($invoiceLine);

// Agregar el nodo raíz al documento
$xml->appendChild($invoice);

// Guardar el archivo XML en un directorio
$xml->save('factura-electronica.xml');

echo "Factura electrónica UBL generada correctamente.";