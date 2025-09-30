<?php
// Pequeño generador de XML Factura SUNAT (UBL 2.1) — ejemplo mínimo sin firma

// ==== Datos de ejemplo ====
$emisor = [
  'ruc' => '20123456789',
  'razon' => 'EMPRESA DEMO S.A.C.',
  'comercial' => 'EMPRESA DEMO',
  'ubigeo' => '150101',
  'direccion' => 'Av. Siempre Viva 123',
  'provincia' => 'LIMA',
  'departamento' => 'LIMA',
  'distrito' => 'LIMA',
  'pais' => 'PE'
];

$cliente = [
  'tipo_doc' => '6', // 6=RUC, 1=DNI
  'num_doc' => '20654321098',
  'razon'   => 'CLIENTE DEMO S.A.'
];

$items = [
  // cantidad, descripcion, precio_unitario_sin_igv, codigo
  ['qty'=>2, 'desc'=>'Producto A', 'pu'=>100.00, 'cod'=>'P001'],
  ['qty'=>1, 'desc'=>'Servicio B', 'pu'=>50.00,  'cod'=>'S002'],
];

$serie  = 'F001';
$numero = '00000001';
$moneda = 'PEN';
$igvPct = 0.18; // IGV 18%

// ==== Generación del XML ====
function generarFacturaXML($emisor, $cliente, $items, $serie, $numero, $moneda, $igvPct){
  $ns_ubl = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
  $ns_cac = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
  $ns_cbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
  $ns_ext = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';
  $ns_ds  = 'http://www.w3.org/2000/09/xmldsig#';

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->formatOutput = true;

  // Helper para crear nodos con prefijo
  $mk = function($prefix, $name, $value = null) use ($doc, $ns_ubl, $ns_cac, $ns_cbc, $ns_ext, $ns_ds){
    $map = ['cbc'=>$ns_cbc,'cac'=>$ns_cac,'ext'=>$ns_ext,'ds'=>$ns_ds,'inv'=>$ns_ubl];
    $el = $doc->createElementNS($map[$prefix], "$prefix:$name");
    if($value !== null) $el->appendChild($doc->createTextNode($value));
    return $el;
  };

  // Raíz
  $Invoice = $doc->createElementNS($ns_ubl, 'Invoice');
  $Invoice->setAttribute('xmlns:cac', $ns_cac);
  $Invoice->setAttribute('xmlns:cbc', $ns_cbc);
  $Invoice->setAttribute('xmlns:ext', $ns_ext);
  $Invoice->setAttribute('xmlns:ds',  $ns_ds);
  $doc->appendChild($Invoice);

  // UBLExtensions (reservado para firma)
  $exts = $mk('ext','UBLExtensions');
  $ext  = $mk('ext','UBLExtension');
  $extContent = $mk('ext','ExtensionContent'); // vacío (aquí irá la firma XAdES-BES)
  $ext->appendChild($extContent);
  $exts->appendChild($ext);
  $Invoice->appendChild($exts);

  // Encabezados mínimos
  $Invoice->appendChild($mk('cbc','UBLVersionID','2.1'));
  // En Perú se usa CustomizationID "2.0" (perfil SUNAT para UBL 2.1)
  $Invoice->appendChild($mk('cbc','CustomizationID','2.0'));
  // Tipo de operación (Catálogo 51). 0101 = venta interna
  $Invoice->appendChild($mk('cbc','ProfileID','0101'));
  $Invoice->appendChild($mk('cbc','ID', $serie.'-'.$numero));     // F001-00000001
  $Invoice->appendChild($mk('cbc','IssueDate', date('Y-m-d')));    // Fecha emisión
  $Invoice->appendChild($mk('cbc','InvoiceTypeCode','01'));        // 01 = Factura
  $Invoice->appendChild($mk('cbc','DocumentCurrencyCode', $moneda));

  // Firma (referencia) — solo estructura mínima
  $sig = $mk('cac','Signature');
  $sig->appendChild($mk('cbc','ID','IDSignKG'));
  $signParty = $mk('cac','SignatoryParty');
  $partyName = $mk('cac','PartyName');
  $partyName->appendChild($mk('cbc','Name', $emisor['comercial']));
  $signParty->appendChild($partyName);
  $sig->appendChild($signParty);
  $attach = $mk('cac','DigitalSignatureAttachment');
  $extRef = $mk('cac','ExternalReference');
  $extRef->appendChild($mk('cbc','URI','#signature')); // placeholder
  $attach->appendChild($extRef);
  $sig->appendChild($attach);
  $Invoice->appendChild($sig);

  // Emisor
  $acctSupp = $mk('cac','AccountingSupplierParty');
  $party = $mk('cac','Party');

  $partyId = $mk('cac','PartyIdentification');
  $id = $mk('cbc','ID', $emisor['ruc']);
  $id->setAttribute('schemeID','6'); // 6 = RUC
  $partyId->appendChild($id);
  $party->appendChild($partyId);

  $partyName2 = $mk('cac','PartyName');
  $partyName2->appendChild($mk('cbc','Name', $emisor['comercial']));
  $party->appendChild($partyName2);

  $legal = $mk('cac','PartyLegalEntity');
  $legal->appendChild($mk('cbc','RegistrationName', $emisor['razon']));
  $regAddr = $mk('cac','RegistrationAddress');
  $regAddr->appendChild($mk('cbc','ID', $emisor['ubigeo']));
  $regAddr->appendChild($mk('cbc','AddressLine'))
          ->appendChild($mk('cbc','Line', $emisor['direccion']));
  $regAddr->appendChild($mk('cbc','Province',   $emisor['provincia']));
  $regAddr->appendChild($mk('cbc','Department', $emisor['departamento']));
  $regAddr->appendChild($mk('cbc','District',   $emisor['distrito']));
  $regAddr->appendChild($mk('cac','Country'))->appendChild($mk('cbc','IdentificationCode', $emisor['pais']));
  $legal->appendChild($regAddr);
  $party->appendChild($legal);

  $acctSupp->appendChild($party);
  $Invoice->appendChild($acctSupp);

  // Cliente
  $acctCust = $mk('cac','AccountingCustomerParty');
  $cParty = $mk('cac','Party');
  $cId = $mk('cac','PartyIdentification');
  $cIdVal = $mk('cbc','ID', $cliente['num_doc']);
  $cIdVal->setAttribute('schemeID', $cliente['tipo_doc']);
  $cId->appendChild($cIdVal);
  $cParty->appendChild($cId);
  $cLegal = $mk('cac','PartyLegalEntity');
  $cLegal->appendChild($mk('cbc','RegistrationName', $cliente['razon']));
  $cParty->appendChild($cLegal);
  $acctCust->appendChild($cParty);
  $Invoice->appendChild($acctCust);

  // Cálculos de totales
  $opGravada = 0.0;
  foreach($items as $it){ $opGravada += $it['qty'] * $it['pu']; }
  $igv = round($opGravada * $igvPct, 2);
  $total = round($opGravada + $igv, 2);

  // Totales de impuestos
  $taxTotal = $mk('cac','TaxTotal');
  $taxTotal->appendChild($mk('cbc','TaxAmount', number_format($igv,2,'.','')))
           ->setAttribute('currencyID',$moneda);

  $taxSub = $mk('cac','TaxSubtotal');
  $taxSub->appendChild($mk('cbc','TaxableAmount', number_format($opGravada,2,'.','')))
         ->setAttribute('currencyID',$moneda);
  $taxSub->appendChild($mk('cbc','TaxAmount', number_format($igv,2,'.','')))
         ->setAttribute('currencyID',$moneda);

  $taxCat = $mk('cac','TaxCategory');
  $taxScheme = $mk('cac','TaxScheme');
  $taxScheme->appendChild($mk('cbc','ID','1000'));     // 1000 = IGV
  $taxScheme->appendChild($mk('cbc','Name','IGV'));
  $taxScheme->appendChild($mk('cbc','TaxTypeCode','VAT'));
  $taxCat->appendChild($taxScheme);
  $taxSub->appendChild($taxCat);

  $taxTotal->appendChild($taxSub);
  $Invoice->appendChild($taxTotal);

  // Totales monetarios
  $legalMon = $mk('cac','LegalMonetaryTotal');
  $legalMon->appendChild($mk('cbc','LineExtensionAmount', number_format($opGravada,2,'.','')))
           ->setAttribute('currencyID',$moneda);
  $legalMon->appendChild($mk('cbc','TaxInclusiveAmount', number_format($total,2,'.','')))
           ->setAttribute('currencyID',$moneda);
  $legalMon->appendChild($mk('cbc','PayableAmount', number_format($total,2,'.','')))
           ->setAttribute('currencyID',$moneda);
  $Invoice->appendChild($legalMon);

  // Líneas
  $i = 1;
  foreach($items as $it){
    $qty   = $it['qty'];
    $pu    = $it['pu'];           // sin IGV
    $base  = round($qty * $pu, 2);
    $igvL  = round($base * $igvPct, 2);

    $line = $mk('cac','InvoiceLine');
    $line->appendChild($mk('cbc','ID', strval($i)));
    $line->appendChild($mk('cbc','InvoicedQuantity', (string)$qty))
         ->setAttribute('unitCode','NIU');
    $line->appendChild($mk('cbc','LineExtensionAmount', number_format($base,2,'.','')))
         ->setAttribute('currencyID',$moneda);

    // Impuesto por línea
    $lTaxTotal = $mk('cac','TaxTotal');
    $lTaxTotal->appendChild($mk('cbc','TaxAmount', number_format($igvL,2,'.','')))
              ->setAttribute('currencyID',$moneda);

    $lTaxSub = $mk('cac','TaxSubtotal');
    $lTaxSub->appendChild($mk('cbc','TaxableAmount', number_format($base,2,'.','')))
            ->setAttribute('currencyID',$moneda);
    $lTaxSub->appendChild($mk('cbc','TaxAmount', number_format($igvL,2,'.','')))
            ->setAttribute('currencyID',$moneda);

    $lTaxCat = $mk('cac','TaxCategory');
    $lTaxCat->appendChild($mk('cbc','Percent', strval($igvPct*100)));
    $lTaxCat->appendChild($mk('cbc','TaxExemptionReasonCode','10')); // 10 = Gravado - Operación Onerosa
    $lTaxScheme = $mk('cac','TaxScheme');
    $lTaxScheme->appendChild($mk('cbc','ID','1000'));
    $lTaxScheme->appendChild($mk('cbc','Name','IGV'));
    $lTaxScheme->appendChild($mk('cbc','TaxTypeCode','VAT'));
    $lTaxCat->appendChild($lTaxScheme);

    $lTaxSub->appendChild($lTaxCat);
    $lTaxTotal->appendChild($lTaxSub);
    $line->appendChild($lTaxTotal);

    // Ítem
    $item = $mk('cac','Item');
    $item->appendChild($mk('cbc','Description', $it['desc']));
    $item->appendChild($mk('cac','SellersItemIdentification'))
         ->appendChild($mk('cbc','ID', $it['cod']));
    $line->appendChild($item);

    // Precio (valor unitario con y sin IGV — aquí ponemos PrecioAmount = sin IGV)
    $price = $mk('cac','Price');
    $price->appendChild($mk('cbc','PriceAmount', number_format($pu,2,'.','')))
          ->setAttribute('currencyID',$moneda);
    $line->appendChild($price);

    $Invoice->appendChild($line);
    $i++;
  }

  return $doc->saveXML();
}

$xml = generarFacturaXML($emisor, $cliente, $items, $serie, $numero, $moneda, $igvPct);

// Guardar a archivo: 20123456789-01-F001-00000001.xml (formato usual)
$nombre = "{$emisor['ruc']}-01-{$serie}-{$numero}.xml";
file_put_contents($nombre, $xml);

header('Content-Type: application/xml; charset=UTF-8');
echo $xml;