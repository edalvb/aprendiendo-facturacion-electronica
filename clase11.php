<?php

require 'fpdf/fpdf.php';
require 'phpqrcode/qrlib.php'; // ✅ Librería QR (archivo qrlib.php de phpqrcode)
require 'codefpdf/code128.php'; // (Opcional) si quieres seguir usando Code128

class PDF_Factura_Peru_Directa extends PDF_Code128
{
    
    public $emisor = [
        "razon" => "MI EMPRESA S.A.C.",
        "comercial" => "MI EMPRESA",
        "ruc" => "20123456789",
        "direccion" => "Av. Siempre Viva 123, Lima - Lima - Perú",
        "sucursal" => "Sucursal: Centro Comercial XYZ - Local 10, Lima",
        "ubigeo" => "150101",
        "telefono" => "01 555-5555",
        "email" => "ventas@miempresa.com"
    ];

    public $doc = [
        "tipo" => "FACTURA ELECTRÓNICA",
        "serie" => "F001",
        "numero" => "000000123",
        "fecha_emision" => "11/11/2025 10:30:00",
        "moneda" => "PEN",
        "tipo_operacion" => "0101", // venta interna (ejemplo)
        // Hash / CDR son referencias, no “autorización” como SRI
        "hash" => "A1B2C3D4E5F60123456789ABCDEF000011112222333344445555666677778888",
        "cdr"  => "ACEPTADO (Referencia)",
        "xml"  => "Generado (Referencia)"
    ];

    public $cliente = [
        "nombre" => "CLIENTE DE PRUEBA S.A.C.",
        "tipo_doc" => "RUC",
        "num_doc" => "20456789012",
        "direccion" => "Av. República 456, Lima"
    ];

    // Totales “quemados” (según items demo)
    public $totales = [
        "op_gravada" => 65.00,
        "op_exonerada" => 0.00,
        "op_inafecta" => 0.00,
        "op_gratuita" => 0.00,
        "descuento" => 5.00,
        "igv" => 11.70,   // ojo: ajusta según tu cálculo real
        "total" => 76.70  // ajusta según tu cálculo real
    ];

    public $pago = [
        "forma" => "Contado",
        "medio" => "Efectivo",
        "codigo_sunat" => "01",
        "plazo_dias" => 0
    ];

    function Header()
    {
        // Marco general
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.4);
        $this->Rect(5, 5, 200, 287);

        // ================= EMISOR (IZQUIERDA) =================
        $this->SetFont('Arial','B',10);
        $this->SetXY(10, 10);
        $this->Cell(90, 5, utf8_decode($this->emisor["razon"]), 0, 1, 'L');

        $this->SetFont('Arial','',9);
        $this->SetX(10);
        $this->MultiCell(90, 5, utf8_decode('Nombre comercial: '.$this->emisor["comercial"]), 0, 'L');

        $this->SetX(10);
        $this->MultiCell(90, 5, utf8_decode('RUC: '.$this->emisor["ruc"]), 0, 'L');

        $this->SetX(10);
        $this->MultiCell(90, 5, utf8_decode('Dirección: '.$this->emisor["direccion"]), 0, 'L');

        $this->SetX(10);
        $this->MultiCell(90, 5, utf8_decode($this->emisor["sucursal"]), 0, 'L');

        // (Opcional) UBIGEO
        $this->SetX(10);
        $this->MultiCell(90, 5, utf8_decode('Ubigeo: '.$this->emisor["ubigeo"]), 0, 'L');

        // ================= CUADRO DERECHO (DOCUMENTO) =================
        $this->SetXY(105, 10);
        $this->SetFont('Arial','B',11);
        $this->Cell(100, 6, utf8_decode($this->doc["tipo"]), 0, 1, 'C');

        $this->SetFont('Arial','',9);
        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('RUC: '.$this->emisor["ruc"]), 0, 1, 'L');

        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('N°: '.$this->doc["serie"].'-'.$this->doc["numero"]), 0, 1, 'L');

        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('Fecha emisión: '.$this->doc["fecha_emision"]), 0, 1, 'L');

        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('Moneda: '.$this->doc["moneda"]), 0, 1, 'L');

        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('Tipo de operación: '.$this->doc["tipo_operacion"]), 0, 1, 'L');

        // Referencias SUNAT: HASH / CDR (no “autorización SRI”)
        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('Hash: '), 0, 1, 'L');
        $this->SetFont('Arial','',7);
        $this->SetX(105);
        $this->MultiCell(100, 4, utf8_decode($this->doc["hash"]), 0, 'L');
        $this->SetFont('Arial','',9);

        $this->SetX(105);
        $this->Cell(100, 5, utf8_decode('CDR: '.$this->doc["cdr"]), 0, 1, 'L');

        // ================= QR SUNAT (derecha) =================
        // Estructura típica usada en Perú (QR):
        // RUC|TipoDoc|Serie|Numero|IGV|Total|Fecha|TipoDocCliente|NumDocCliente
        // TipoDoc: 01 factura, 03 boleta (ejemplo)
        $tipoDocSunat = "01";
        $qrText = $this->emisor["ruc"]."|".$tipoDocSunat."|".$this->doc["serie"]."|".$this->doc["numero"]."|".
                  number_format($this->totales["igv"],2,'.','')."|".
                  number_format($this->totales["total"],2,'.','')."|".
                  substr($this->doc["fecha_emision"],0,10)."|".
                  ($this->cliente["tipo_doc"]=="RUC" ? "6" : "1")."|".
                  $this->cliente["num_doc"];

        // Generar QR temporal
        $tmpQr = sys_get_temp_dir()."/qr_sunat_".md5($qrText).".png";
        if (!file_exists($tmpQr)) {
            QRcode::png($qrText, $tmpQr, QR_ECLEVEL_M, 3, 1);
        }

        // Dibujar imagen QR
        $this->Image($tmpQr, 160, 58, 30, 30);

        // Línea divisoria
        $this->SetY(90);
        $this->Line(5, 90, 205, 90);

        // ================= DATOS DEL CLIENTE =================
        $this->SetY(92);
        $this->SetFont('Arial','B',9);
        $this->SetX(10);
        $this->Cell(0, 5, utf8_decode('DATOS DEL CLIENTE'), 0, 1, 'L');

        $this->SetFont('Arial','',9);
        $this->SetX(10);
        $this->Cell(60, 5, utf8_decode('Razón social / Nombres:'), 0, 0, 'L');
        $this->SetX(70);
        $this->Cell(130, 5, utf8_decode($this->cliente["nombre"]), 0, 1, 'L');

        $this->SetX(10);
        $this->Cell(60, 5, utf8_decode('Documento:'), 0, 0, 'L');
        $this->SetX(70);
        $this->Cell(60, 5, utf8_decode($this->cliente["num_doc"]), 0, 0, 'L');
        $this->SetX(130);
        $this->Cell(30, 5, utf8_decode('Tipo:'), 0, 0, 'L');
        $this->SetX(150);
        $this->Cell(50, 5, utf8_decode($this->cliente["tipo_doc"]), 0, 1, 'L');

        $this->SetX(10);
        $this->Cell(60, 5, utf8_decode('Dirección:'), 0, 0, 'L');
        $this->SetX(70);
        $this->MultiCell(130, 5, utf8_decode($this->cliente["direccion"]), 0, 'L');

        $this->SetY(115);
        $this->Line(5, 115, 205, 115);
    }

    function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('Arial','I',7);
        $this->Cell(0, 5, utf8_decode('Representación impresa de la Factura Electrónica. Consulte en SUNAT.'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Este documento puede ser verificado usando el QR.'), 0, 0, 'C');
    }

    function TablaDetalleDirecta()
    {
        // Encabezados de detalle
        $this->SetFont('Arial','B',8);
        $this->SetFillColor(230,230,230);
        $this->SetY(118);
        $this->SetX(5);

        $this->Cell(15, 6, utf8_decode('CANT'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('CÓDIGO'), 1, 0, 'C', true);
        $this->Cell(80, 6, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C', true);
        $this->Cell(20, 6, utf8_decode('P.UNIT'), 1, 0, 'C', true);
        $this->Cell(20, 6, utf8_decode('DSCTO'), 1, 0, 'C', true);
        $this->Cell(20, 6, utf8_decode('IMPORTE'), 1, 1, 'C', true);

        $this->SetFont('Arial','',8);

        // =============== ÍTEM 1 ===============
        $y = 124;
        $this->SetY($y);
        $this->SetX(5);

        $this->Cell(15, 6, '2.00', 1, 0, 'C');                 // cantidad
        $this->Cell(25, 6, utf8_decode('P001'), 1, 0, 'L');    // código

        $xDescr1 = $this->GetX();
        $yDescr1 = $this->GetY();
        $this->MultiCell(80, 6, utf8_decode('PRODUCTO DE PRUEBA 1'), 1, 'L');
        $hDescr1 = $this->GetY() - $yDescr1;

        $this->SetXY($xDescr1 + 80, $yDescr1);
        $this->Cell(20, $hDescr1, '10.00', 1, 0, 'R');         // P.UNIT
        $this->Cell(20, $hDescr1, '0.00', 1, 0, 'R');          // Descuento
        $this->Cell(20, $hDescr1, '20.00', 1, 1, 'R');         // Importe

        // =============== ÍTEM 2 ===============
        $y2 = $yDescr1 + $hDescr1;
        $this->SetY($y2);
        $this->SetX(5);

        $this->Cell(15, 6, '1.00', 1, 0, 'C');
        $this->Cell(25, 6, utf8_decode('P002'), 1, 0, 'L');

        $xDescr2 = $this->GetX();
        $yDescr2 = $this->GetY();
        $this->MultiCell(80, 6, utf8_decode('SERVICIO DE PRUEBA 2'), 1, 'L');
        $hDescr2 = $this->GetY() - $yDescr2;

        $this->SetXY($xDescr2 + 80, $yDescr2);
        $this->Cell(20, $hDescr2, '50.00', 1, 0, 'R');
        $this->Cell(20, $hDescr2, '5.00', 1, 0, 'R');
        $this->Cell(20, $hDescr2, '45.00', 1, 1, 'R');
    }

    function TotalesYFormaPagoDirecta()
    {
        // =============== INFORMACIÓN ADICIONAL (IZQUIERDA) ===============
        $this->SetFont('Arial','',8);
        $this->SetY(210);

        $this->SetX(5);
        $this->SetFont('Arial','B',8);
        $this->Cell(100, 6, utf8_decode('INFORMACIÓN ADICIONAL'), 1, 1, 'L');
        $this->SetFont('Arial','',8);

        $this->SetX(5);
        $this->Cell(30, 5, utf8_decode('Email:'), 0, 0, 'L');
        $this->Cell(70, 5, utf8_decode($this->emisor["email"]), 0, 1, 'L');

        $this->SetX(5);
        $this->Cell(30, 5, utf8_decode('Teléfono:'), 0, 0, 'L');
        $this->Cell(70, 5, utf8_decode($this->emisor["telefono"]), 0, 1, 'L');

        $this->SetX(5);
        $this->Cell(30, 5, utf8_decode('Observación:'), 0, 0, 'L');
        $this->Cell(70, 5, utf8_decode('Gracias por su compra.'), 0, 1, 'L');

        // =============== TOTALES (DERECHA) ===============
        $this->SetY(210);
        $this->SetX(110);
        $this->SetFont('Arial','B',8);
        $this->Cell(95, 6, utf8_decode('RESUMEN DE TOTALES'), 1, 1, 'L');
        $this->SetFont('Arial','',8);

        $this->SetX(110);
        $this->Cell(60, 5, utf8_decode('Op. Gravada:'), 1, 0, 'L');
        $this->Cell(35, 5, number_format($this->totales["op_gravada"],2,'.',''), 1, 1, 'R');

        $this->SetX(110);
        $this->Cell(60, 5, utf8_decode('Op. Exonerada:'), 1, 0, 'L');
        $this->Cell(35, 5, number_format($this->totales["op_exonerada"],2,'.',''), 1, 1, 'R');

        $this->SetX(110);
        $this->Cell(60, 5, utf8_decode('Op. Inafecta:'), 1, 0, 'L');
        $this->Cell(35, 5, number_format($this->totales["op_inafecta"],2,'.',''), 1, 1, 'R');

        $this->SetX(110);
        $this->Cell(60, 5, utf8_decode('Descuento:'), 1, 0, 'L');
        $this->Cell(35, 5, number_format($this->totales["descuento"],2,'.',''), 1, 1, 'R');

        $this->SetX(110);
        $this->Cell(60, 5, utf8_decode('IGV:'), 1, 0, 'L');
        $this->Cell(35, 5, number_format($this->totales["igv"],2,'.',''), 1, 1, 'R');

        $this->SetFont('Arial','B',9);
        $this->SetX(110);
        $this->Cell(60, 6, utf8_decode('IMPORTE TOTAL:'), 1, 0, 'L');
        $this->Cell(35, 6, number_format($this->totales["total"],2,'.',''), 1, 1, 'R');

        // =============== FORMA DE PAGO ===============
        $this->Ln(4);
        $this->SetFont('Arial','B',8);
        $this->SetX(110);
        $this->Cell(95, 6, utf8_decode('FORMA DE PAGO'), 1, 1, 'L');
        $this->SetFont('Arial','',8);

        $this->SetX(110);
        $this->MultiCell(
            95,
            5,
            utf8_decode(
                'Código '.$this->pago["codigo_sunat"].' - '.$this->pago["medio"].
                ' - '.$this->pago["forma"].' - Plazo: '.$this->pago["plazo_dias"].' días'
            ),
            1,
            'L'
        );
    }
}

// =======================
// GENERAR PDF
// =======================
$pdf = new PDF_Factura_Peru_Directa('P','mm','A4');
$pdf->SetMargins(5,5,5);
$pdf->AddPage();
$pdf->TablaDetalleDirecta();
$pdf->TotalesYFormaPagoDirecta();
$pdf->Output('I', 'factura_sunat_datos_directos.pdf');
