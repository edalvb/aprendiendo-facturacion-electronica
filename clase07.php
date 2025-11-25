<?php

class GeneradorXML
{
    function CrearXMLFactura($nombrexml, $emisor, $cliente, $comprobante, $detalle)
    {
        // Crear el DOMDocument
        $doc = new DOMDocument('1.0', 'UTF-8'); // clase que permite crear documentos XML
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = true;
        $doc->encoding = 'UTF-8';

        // Namespaces UBL
        $nsInvoice = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
        $nsCac     = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
        $nsCbc     = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
        $nsExt     = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';
        $nsDs      = 'http://www.w3.org/2000/09/xmldsig#';

        // Elemento raíz <Invoice> con namespace por defecto
        $invoice = $doc->createElementNS($nsInvoice, 'Invoice');
        $doc->appendChild($invoice);

        // Declarar namespaces adicionales
        $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', $nsCac);
        $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', $nsCbc);
        $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ext', $nsExt);
        $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds',  $nsDs);

        /*
         * ext:UBLExtensions
         */
        $extUBLExtensions = $doc->createElementNS($nsExt, 'ext:UBLExtensions');
        $invoice->appendChild($extUBLExtensions);

        $extUBLExtension = $doc->createElementNS($nsExt, 'ext:UBLExtension');
        $extUBLExtensions->appendChild($extUBLExtension);

        $extExtensionContent = $doc->createElementNS($nsExt, 'ext:ExtensionContent');
        // En tu código estaba vacío: <ext:ExtensionContent />
        $extUBLExtension->appendChild($extExtensionContent);

        /*
         * Datos básicos de la factura
         */
        $ublVersion = $doc->createElementNS($nsCbc, 'cbc:UBLVersionID', '2.1');
        $invoice->appendChild($ublVersion);

        $customizationID = $doc->createElementNS($nsCbc, 'cbc:CustomizationID', '2.0');
        $invoice->appendChild($customizationID);

        $id = $doc->createElementNS(
            $nsCbc,
            'cbc:ID',
            $comprobante['serie'] . '-' . $comprobante['correlativo']
        );
        $invoice->appendChild($id);

        $issueDate = $doc->createElementNS($nsCbc, 'cbc:IssueDate', $comprobante['fecha_emision']);
        $invoice->appendChild($issueDate);

        $issueTime = $doc->createElementNS($nsCbc, 'cbc:IssueTime', '00:00:00');
        $invoice->appendChild($issueTime);

        $dueDate = $doc->createElementNS($nsCbc, 'cbc:DueDate', $comprobante['fecha_emision']);
        $invoice->appendChild($dueDate);

        $invoiceTypeCode = $doc->createElementNS($nsCbc, 'cbc:InvoiceTypeCode', $comprobante['tipodoc']);
        $invoiceTypeCode->setAttribute('listID', '0101');
        $invoice->appendChild($invoiceTypeCode);

        // cbc:Note con CDATA
        $note = $doc->createElementNS($nsCbc, 'cbc:Note');
        $note->setAttribute('languageLocaleID', '1000');
        $note->appendChild($doc->createCDATASection($comprobante['total_texto']));
        $invoice->appendChild($note);

        $currencyCode = $doc->createElementNS($nsCbc, 'cbc:DocumentCurrencyCode', $comprobante['moneda']);
        $invoice->appendChild($currencyCode);

        /*
         * cac:Signature
         */
        $signature = $doc->createElementNS($nsCac, 'cac:Signature');
        $invoice->appendChild($signature);

        $sigID = $doc->createElementNS($nsCbc, 'cbc:ID', $emisor['ruc']);
        $signature->appendChild($sigID);

        $sigNote = $doc->createElementNS($nsCbc, 'cbc:Note');
        $sigNote->appendChild($doc->createCDATASection($emisor['nombre_comercial']));
        $signature->appendChild($sigNote);

        $signatoryParty = $doc->createElementNS($nsCac, 'cac:SignatoryParty');
        $signature->appendChild($signatoryParty);

        $partyIdentification = $doc->createElementNS($nsCac, 'cac:PartyIdentification');
        $signatoryParty->appendChild($partyIdentification);

        $partyID = $doc->createElementNS($nsCbc, 'cbc:ID', $emisor['ruc']);
        $partyIdentification->appendChild($partyID);

        $partyName = $doc->createElementNS($nsCac, 'cac:PartyName');
        $signatoryParty->appendChild($partyName);

        $name = $doc->createElementNS($nsCbc, 'cbc:Name');
        $name->appendChild($doc->createCDATASection($emisor['razon_social']));
        $partyName->appendChild($name);

        $digitalSignatureAttachment = $doc->createElementNS($nsCac, 'cac:DigitalSignatureAttachment');
        $signature->appendChild($digitalSignatureAttachment);

        $externalReference = $doc->createElementNS($nsCac, 'cac:ExternalReference');
        $digitalSignatureAttachment->appendChild($externalReference);

        $uri = $doc->createElementNS($nsCbc, 'cbc:URI', '#SIGN-EMPRESA');
        $externalReference->appendChild($uri);

        /*
         * cac:AccountingSupplierParty (EMISOR)
         */
        $accSupplierParty = $doc->createElementNS($nsCac, 'cac:AccountingSupplierParty');
        $invoice->appendChild($accSupplierParty);

        $supplierParty = $doc->createElementNS($nsCac, 'cac:Party');
        $accSupplierParty->appendChild($supplierParty);

        $supPartyId = $doc->createElementNS($nsCac, 'cac:PartyIdentification');
        $supplierParty->appendChild($supPartyId);

        $supID = $doc->createElementNS($nsCbc, 'cbc:ID', $emisor['ruc']);
        $supID->setAttribute('schemeID', $emisor['tipodoc']);
        $supPartyId->appendChild($supID);

        $supPartyName = $doc->createElementNS($nsCac, 'cac:PartyName');
        $supplierParty->appendChild($supPartyName);

        $supName = $doc->createElementNS($nsCbc, 'cbc:Name');
        $supName->appendChild($doc->createCDATASection($emisor['nombre_comercial']));
        $supPartyName->appendChild($supName);

        $supLegalEntity = $doc->createElementNS($nsCac, 'cac:PartyLegalEntity');
        $supplierParty->appendChild($supLegalEntity);

        $supRegName = $doc->createElementNS($nsCbc, 'cbc:RegistrationName');
        $supRegName->appendChild($doc->createCDATASection($emisor['razon_social']));
        $supLegalEntity->appendChild($supRegName);

        $supRegAddress = $doc->createElementNS($nsCac, 'cac:RegistrationAddress');
        $supLegalEntity->appendChild($supRegAddress);

        $supUbigeo = $doc->createElementNS($nsCbc, 'cbc:ID', $emisor['ubigeo']);
        $supRegAddress->appendChild($supUbigeo);

        $addrTypeCode = $doc->createElementNS($nsCbc, 'cbc:AddressTypeCode', '0000');
        $supRegAddress->appendChild($addrTypeCode);

        $citySubdivisionName = $doc->createElementNS($nsCbc, 'cbc:CitySubdivisionName', 'NONE');
        $supRegAddress->appendChild($citySubdivisionName);

        $cityName = $doc->createElementNS($nsCbc, 'cbc:CityName', $emisor['provincia']);
        $supRegAddress->appendChild($cityName);

        $countrySubentity = $doc->createElementNS($nsCbc, 'cbc:CountrySubentity', $emisor['departamento']);
        $supRegAddress->appendChild($countrySubentity);

        $district = $doc->createElementNS($nsCbc, 'cbc:District', $emisor['distrito']);
        $supRegAddress->appendChild($district);

        $addrLine = $doc->createElementNS($nsCac, 'cac:AddressLine');
        $supRegAddress->appendChild($addrLine);

        $line = $doc->createElementNS($nsCbc, 'cbc:Line');
        $line->appendChild($doc->createCDATASection($emisor['direccion']));
        $addrLine->appendChild($line);

        $country = $doc->createElementNS($nsCac, 'cac:Country');
        $supRegAddress->appendChild($country);

        $countryCode = $doc->createElementNS($nsCbc, 'cbc:IdentificationCode', $emisor['pais']);
        $country->appendChild($countryCode);

        /*
         * cac:AccountingCustomerParty (CLIENTE)
         */
        $accCustomerParty = $doc->createElementNS($nsCac, 'cac:AccountingCustomerParty');
        $invoice->appendChild($accCustomerParty);

        $customerParty = $doc->createElementNS($nsCac, 'cac:Party');
        $accCustomerParty->appendChild($customerParty);

        $custPartyIdentification = $doc->createElementNS($nsCac, 'cac:PartyIdentification');
        $customerParty->appendChild($custPartyIdentification);

        $custID = $doc->createElementNS($nsCbc, 'cbc:ID', $cliente['ruc']);
        $custID->setAttribute('schemeID', $cliente['tipodoc']);
        $custPartyIdentification->appendChild($custID);

        $custLegalEntity = $doc->createElementNS($nsCac, 'cac:PartyLegalEntity');
        $customerParty->appendChild($custLegalEntity);

        $custRegName = $doc->createElementNS($nsCbc, 'cbc:RegistrationName');
        $custRegName->appendChild($doc->createCDATASection($cliente['razon_social']));
        $custLegalEntity->appendChild($custRegName);

        $custRegAddress = $doc->createElementNS($nsCac, 'cac:RegistrationAddress');
        $custLegalEntity->appendChild($custRegAddress);

        $custAddrLine = $doc->createElementNS($nsCac, 'cac:AddressLine');
        $custRegAddress->appendChild($custAddrLine);

        $custLine = $doc->createElementNS($nsCbc, 'cbc:Line');
        $custLine->appendChild($doc->createCDATASection($cliente['direccion']));
        $custAddrLine->appendChild($custLine);

        $custCountry = $doc->createElementNS($nsCac, 'cac:Country');
        $custRegAddress->appendChild($custCountry);

        $custCountryCode = $doc->createElementNS($nsCbc, 'cbc:IdentificationCode', $cliente['pais']);
        $custCountry->appendChild($custCountryCode);

        /*
         * cac:PaymentTerms
         */
        $paymentTerms = $doc->createElementNS($nsCac, 'cac:PaymentTerms');
        $invoice->appendChild($paymentTerms);

        $pID = $doc->createElementNS($nsCbc, 'cbc:ID', 'FormaPago');
        $paymentTerms->appendChild($pID);

        $pMeans = $doc->createElementNS($nsCbc, 'cbc:PaymentMeansID', 'Contado');
        $paymentTerms->appendChild($pMeans);

        /*
         * cac:TaxTotal (NIVEL DOCUMENTO)
         */
        $taxTotal = $doc->createElementNS($nsCac, 'cac:TaxTotal');
        $invoice->appendChild($taxTotal);

        $taxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', $comprobante['igv']);
        $taxAmount->setAttribute('currencyID', $comprobante['moneda']);
        $taxTotal->appendChild($taxAmount);

        // Subtotal GRAVADAS
        if ($comprobante['total_opgravadas'] > 0) {
            $taxSubtotal = $doc->createElementNS($nsCac, 'cac:TaxSubtotal');
            $taxTotal->appendChild($taxSubtotal);

            $taxableAmount = $doc->createElementNS(
                $nsCbc,
                'cbc:TaxableAmount',
                $comprobante['total_opgravadas']
            );
            $taxableAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($taxableAmount);

            $subTaxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', $comprobante['igv']);
            $subTaxAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($subTaxAmount);

            $taxCategory = $doc->createElementNS($nsCac, 'cac:TaxCategory');
            $taxSubtotal->appendChild($taxCategory);

            $taxScheme = $doc->createElementNS($nsCac, 'cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $tsID = $doc->createElementNS($nsCbc, 'cbc:ID', '1000');
            $taxScheme->appendChild($tsID);

            $tsName = $doc->createElementNS($nsCbc, 'cbc:Name', 'IGV');
            $taxScheme->appendChild($tsName);

            $tsTypeCode = $doc->createElementNS($nsCbc, 'cbc:TaxTypeCode', 'VAT');
            $taxScheme->appendChild($tsTypeCode);
        }

        // Subtotal EXONERADAS
        if ($comprobante['total_opexoneradas'] > 0) {
            $taxSubtotal = $doc->createElementNS($nsCac, 'cac:TaxSubtotal');
            $taxTotal->appendChild($taxSubtotal);

            $taxableAmount = $doc->createElementNS(
                $nsCbc,
                'cbc:TaxableAmount',
                $comprobante['total_opexoneradas']
            );
            $taxableAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($taxableAmount);

            $subTaxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', '0.00');
            $subTaxAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($subTaxAmount);

            $taxCategory = $doc->createElementNS($nsCac, 'cac:TaxCategory');
            $taxSubtotal->appendChild($taxCategory);

            $taxCatID = $doc->createElementNS($nsCbc, 'cbc:ID', 'E');
            $taxCatID->setAttribute('schemeID', 'UN/ECE 5305');
            $taxCatID->setAttribute('schemeName', 'Tax Category Identifier');
            $taxCatID->setAttribute('schemeAgencyName', 'United Nations Economic Commission for Europe');
            $taxCategory->appendChild($taxCatID);

            $taxScheme = $doc->createElementNS($nsCac, 'cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $tsID = $doc->createElementNS($nsCbc, 'cbc:ID', '9997');
            $tsID->setAttribute('schemeID', 'UN/ECE 5153');
            $tsID->setAttribute('schemeAgencyID', '6');
            $taxScheme->appendChild($tsID);

            $tsName = $doc->createElementNS($nsCbc, 'cbc:Name', 'EXO');
            $taxScheme->appendChild($tsName);

            $tsTypeCode = $doc->createElementNS($nsCbc, 'cbc:TaxTypeCode', 'VAT');
            $taxScheme->appendChild($tsTypeCode);
        }

        // Subtotal INAFECTAS
        if ($comprobante['total_opinafectas'] > 0) {
            $taxSubtotal = $doc->createElementNS($nsCac, 'cac:TaxSubtotal');
            $taxTotal->appendChild($taxSubtotal);

            $taxableAmount = $doc->createElementNS(
                $nsCbc,
                'cbc:TaxableAmount',
                $comprobante['total_opinafectas']
            );
            $taxableAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($taxableAmount);

            $subTaxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', '0.00');
            $subTaxAmount->setAttribute('currencyID', $comprobante['moneda']);
            $taxSubtotal->appendChild($subTaxAmount);

            $taxCategory = $doc->createElementNS($nsCac, 'cac:TaxCategory');
            $taxSubtotal->appendChild($taxCategory);

            $taxCatID = $doc->createElementNS($nsCbc, 'cbc:ID', 'E');
            $taxCatID->setAttribute('schemeID', 'UN/ECE 5305');
            $taxCatID->setAttribute('schemeName', 'Tax Category Identifier');
            $taxCatID->setAttribute('schemeAgencyName', 'United Nations Economic Commission for Europe');
            $taxCategory->appendChild($taxCatID);

            $taxScheme = $doc->createElementNS($nsCac, 'cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $tsID = $doc->createElementNS($nsCbc, 'cbc:ID', '9998');
            $tsID->setAttribute('schemeID', 'UN/ECE 5153');
            $tsID->setAttribute('schemeAgencyID', '6');
            $taxScheme->appendChild($tsID);

            $tsName = $doc->createElementNS($nsCbc, 'cbc:Name', 'INA');
            $taxScheme->appendChild($tsName);

            $tsTypeCode = $doc->createElementNS($nsCbc, 'cbc:TaxTypeCode', 'FRE');
            $taxScheme->appendChild($tsTypeCode);
        }

        /*
         * cac:LegalMonetaryTotal
         */
        $total_antes_de_impuestos =
            $comprobante['total_opgravadas'] +
            $comprobante['total_opexoneradas'] +
            $comprobante['total_opinafectas'];

        $legalMonetaryTotal = $doc->createElementNS($nsCac, 'cac:LegalMonetaryTotal');
        $invoice->appendChild($legalMonetaryTotal);

        $lineExtAmount = $doc->createElementNS($nsCbc, 'cbc:LineExtensionAmount', $total_antes_de_impuestos);
        $lineExtAmount->setAttribute('currencyID', $comprobante['moneda']);
        $legalMonetaryTotal->appendChild($lineExtAmount);

        $taxInclusiveAmount = $doc->createElementNS($nsCbc, 'cbc:TaxInclusiveAmount', $comprobante['total']);
        $taxInclusiveAmount->setAttribute('currencyID', $comprobante['moneda']);
        $legalMonetaryTotal->appendChild($taxInclusiveAmount);

        $payableAmount = $doc->createElementNS($nsCbc, 'cbc:PayableAmount', $comprobante['total']);
        $payableAmount->setAttribute('currencyID', $comprobante['moneda']);
        $legalMonetaryTotal->appendChild($payableAmount);

        /*
         * Líneas de detalle: cac:InvoiceLine
         */
        foreach ($detalle as $k => $v) {

            $invoiceLine = $doc->createElementNS($nsCac, 'cac:InvoiceLine');
            $invoice->appendChild($invoiceLine);

            $lineID = $doc->createElementNS($nsCbc, 'cbc:ID', $v['item']);
            $invoiceLine->appendChild($lineID);

            $invoicedQty = $doc->createElementNS($nsCbc, 'cbc:InvoicedQuantity', $v['cantidad']);
            $invoicedQty->setAttribute('unitCode', $v['unidad']);
            $invoiceLine->appendChild($invoicedQty);

            $lineExtAmountLine = $doc->createElementNS($nsCbc, 'cbc:LineExtensionAmount', $v['valor_total']);
            $lineExtAmountLine->setAttribute('currencyID', $comprobante['moneda']);
            $invoiceLine->appendChild($lineExtAmountLine);

            // PricingReference
            $pricingRef = $doc->createElementNS($nsCac, 'cac:PricingReference');
            $invoiceLine->appendChild($pricingRef);

            $altCondPrice = $doc->createElementNS($nsCac, 'cac:AlternativeConditionPrice');
            $pricingRef->appendChild($altCondPrice);

            $priceAmountRef = $doc->createElementNS($nsCbc, 'cbc:PriceAmount', $v['precio_unitario']);
            $priceAmountRef->setAttribute('currencyID', $comprobante['moneda']);
            $altCondPrice->appendChild($priceAmountRef);

            $priceTypeCode = $doc->createElementNS($nsCbc, 'cbc:PriceTypeCode', $v['tipo_precio']);
            $altCondPrice->appendChild($priceTypeCode);

            // TaxTotal por línea
            $lineTaxTotal = $doc->createElementNS($nsCac, 'cac:TaxTotal');
            $invoiceLine->appendChild($lineTaxTotal);

            $lineTaxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', $v['igv']);
            $lineTaxAmount->setAttribute('currencyID', $comprobante['moneda']);
            $lineTaxTotal->appendChild($lineTaxAmount);

            $lineTaxSubtotal = $doc->createElementNS($nsCac, 'cac:TaxSubtotal');
            $lineTaxTotal->appendChild($lineTaxSubtotal);

            $lineTaxableAmount = $doc->createElementNS($nsCbc, 'cbc:TaxableAmount', $v['valor_total']);
            $lineTaxableAmount->setAttribute('currencyID', $comprobante['moneda']);
            $lineTaxSubtotal->appendChild($lineTaxableAmount);

            $lineSubTaxAmount = $doc->createElementNS($nsCbc, 'cbc:TaxAmount', $v['igv']);
            $lineSubTaxAmount->setAttribute('currencyID', $comprobante['moneda']);
            $lineTaxSubtotal->appendChild($lineSubTaxAmount);

            $lineTaxCategory = $doc->createElementNS($nsCac, 'cac:TaxCategory');
            $lineTaxSubtotal->appendChild($lineTaxCategory);

            $percent = $doc->createElementNS($nsCbc, 'cbc:Percent', $v['porcentaje_igv']);
            $lineTaxCategory->appendChild($percent);

            $taxExReason = $doc->createElementNS($nsCbc, 'cbc:TaxExemptionReasonCode', $v['codigo_afectacion_alt']);
            $lineTaxCategory->appendChild($taxExReason);

            $lineTaxScheme = $doc->createElementNS($nsCac, 'cac:TaxScheme');
            $lineTaxCategory->appendChild($lineTaxScheme);

            $lineTsID = $doc->createElementNS($nsCbc, 'cbc:ID', $v['codigo_afectacion']);
            $lineTaxScheme->appendChild($lineTsID);

            $lineTsName = $doc->createElementNS($nsCbc, 'cbc:Name', $v['nombre_afectacion']);
            $lineTaxScheme->appendChild($lineTsName);

            $lineTsTypeCode = $doc->createElementNS($nsCbc, 'cbc:TaxTypeCode', $v['tipo_afectacion']);
            $lineTaxScheme->appendChild($lineTsTypeCode);

            // Item
            $item = $doc->createElementNS($nsCac, 'cac:Item');
            $invoiceLine->appendChild($item);

            $description = $doc->createElementNS($nsCbc, 'cbc:Description');
            $description->appendChild($doc->createCDATASection($v['descripcion']));
            $item->appendChild($description);

            $sellerItemId = $doc->createElementNS($nsCac, 'cac:SellersItemIdentification');
            $item->appendChild($sellerItemId);

            $sellerID = $doc->createElementNS($nsCbc, 'cbc:ID', $v['codigo']);
            $sellerItemId->appendChild($sellerID);

            // Price
            $price = $doc->createElementNS($nsCac, 'cac:Price');
            $invoiceLine->appendChild($price);

            $priceAmount = $doc->createElementNS($nsCbc, 'cbc:PriceAmount', $v['valor_unitario']);
            $priceAmount->setAttribute('currencyID', $comprobante['moneda']);
            $price->appendChild($priceAmount);
        }

        // Guardar XML
        $doc->save($nombrexml . '.XML');
    }
}

?>
