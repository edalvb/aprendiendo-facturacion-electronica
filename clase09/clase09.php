<?php 
require_once("cantidad_en_letras.php");

$emisor = 	array(
			'tipodoc'		=> '6',//catalogo 06
			'ruc' 			=> '12342814425', //ruc segun ficha de ruc
			'razon_social'	=> 'EMPRESA1 SAC', //razon social ficha  ruc
			'nombre_comercial'	=> 'EMPRESA SAC', //nombre comercial ficha ruc
			'direccion'		=> '8 DE OCTUBRE N 123 - LIMA - LIMA - LIMA', //direccion ficha ruc
			'pais'			=> 'PE', //ficha ruc
			'departamento'  => 'LIMA',//fciha ruc
			'provincia'		=> 'LIMA',//ficha ruc
			'distrito'		=> 'LIMA', //ficha ruc
			'ubigeo'		=> '150101', //segun anexo de catalgo de codigos
			'usuario_sol_sec'	=> 'MODDATOS', //se adquiere del PORTAL SUNAT
			'clave_sol_sec'		=> 'MODDATOS' //se adquiere del PORTAL SUNAT
			);


$cliente = array(
			'tipodoc'		=> '6',//catalgo 6
			'ruc'			=> '20412331286', //del cliente
			'razon_social'  => 'EMPRESA 3', //del cliente 
			'direccion'		=> 'Jr. paz 32 . lima',//del cliente 
			'pais'			=> 'PE'//delcliente
			);	

$comprobante =	array(
			'tipodoc'		=> '07', //catalogo 1
			'serie'			=> 'FC04', 
			'correlativo'	=> '777',
			'fecha_emision' => '2023-12-19',
			'moneda'		=> 'PEN', 
			'total_opgravadas'=> 0, 
			'total_opexoneradas'=>0,
			'total_opinafectas'=>0,
			'igv'			=> 0,
			'total'			=> 0,
			'total_texto'	=> '',
			'tipodoc_ref'	=> '01', //factura
			'serie_ref'		=> 'F021',
			'correlativo_ref'=> '333',
			'codmotivo'		=> '01',
			'descripcion'	=> 'ANULACION DE LA OPERACION'//¿prque?
		);

$detalle = 
			array(
				array(
					'item' 				=> 1,
					'codigo'			=> '12',
					'descripcion'		=> 'arroz',
					'cantidad'			=> 1,
					'valor_unitario'	=> 50,
					'precio_unitario'	=> 59,
					'tipo_precio'		=> "01", //ya inlcuye igv
					'igv'				=> 9,
					'porcentaje_igv'	=> 18, // de 0 a 100
					'valor_total'		=> 50, 
					'importe_total'		=> 59,
					'unidad'			=> 'NIU',//unidad
					'codigo_afectacion_alt'	=> '10',
					'codigo_afectacion'	=> 1000,
					'nombre_afectacion'	=>'IGV',
					'tipo_afectacion'	=> 'VAT' //gravadas			 
				)
				
										
			);

$op_gravadas = 0;
$op_inafectas = 0;
$op_exoneradas = 0;
$igv = 0;
$total = 0; //peracion gravadas

foreach ($detalle as $k => $v) {
	if($v['codigo_afectacion_alt']=='10'){
		$op_gravadas = $op_gravadas + $v['valor_total'];
	}

	if($v['codigo_afectacion_alt']=='20'){
		$op_exoneradas = $op_exoneradas + $v['valor_total'];
	}

	if($v['codigo_afectacion_alt']=='30'){
		$op_inafectas = $op_inafectas + $v['valor_total'];
	}

	$igv = $igv + $v['igv'];
	$total = $total + $v['importe_total'];
}

$comprobante['total_opgravadas'] = $op_gravadas;
$comprobante['total_opexoneradas'] = $op_exoneradas;
$comprobante['total_opinafectas'] = $op_inafectas;
$comprobante['igv'] = $igv;
$comprobante['total'] = $total;
$comprobante['total_texto'] = CantidadEnLetra($total);//ejecuta las letras de monto

require_once("xml.php");

$xml = new GeneradorXML();


$nombrexml = $emisor['ruc'].'-'.$comprobante['tipodoc'].'-'.$comprobante['serie'].'-'.$comprobante['correlativo'];//nmbre del archivo segun a), 1.2 , 1  manual de programador
// 22300066603-01-FC04-777.xml

$ruta = "xml/".$nombrexml;
$xml->CrearXMLNotaCredito($ruta, $emisor, $cliente, $comprobante, $detalle);

require_once("apifactura.php");

$apiF = new ApiFacturacion();

$apiF->EnviarComprobanteElectronico($emisor,$nombrexml);

?>
