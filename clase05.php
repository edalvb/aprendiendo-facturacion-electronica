<?php

require_once('cantidad_en_letras.php');

date_default_timezone_set("America/Lima"); 


$emisor = array(
    'tipodoc'           => '6', 
    'ruc'               => '20123456123',
    'razon_social'      => 'EMPRESA PERÑITA SAC',
    'nombre_comercial'  => 'PERLITA SAC',
    'direccion'         => 'AV. SR MANUELA -LIMA-LIMA',
    'pais'              => 'PE', 
    'departamento'      => 'LIMA',
    'provincia'         => 'LIMA',
    'distrito'          => 'LIMA',
    'ubigeo'            => '150101',
    'usuario_sol_sec'   => 'MODDATOS', 
    'clave_sol_sec'     => 'MODDATOS', 
);

//echo $emisor['tipodoc'] . '-' . $emisor['ruc'] . '-' . $emisor['razon_social'];

$cliente = array(
    'tipodoc'       => '6', 
    'ruc'           => '20480631286',
    'razon_social'  => 'EMPRESA2 EIRL',
    'direccion'     => 'Cal. panameño- lima',
    'pais'          => 'PE', 
);

$comprobante = array(
    'tipodoc'       => '01', 
    'serie'         => 'F001',
    'correlativo'   => '00000005', 
    'fecha_emision' => '2023-02-28', 
    'moneda'        => 'PEN', 
    'total_opgravadas'      => 0, //
    'total_opexoneradas'    => 0, //
    'total_opinafectas'     => 0, //
    'igv'                   => 0,
    'total'                 => 0,
    'total_texto'           => 0,
);

$detalle = array(
    array(
        'item'              => 1,
        'codigo'            => 'CODSIS01', 
        'descripcion'       => 'ACEITE OLIVA',
        'cantidad'          => 1, 
        'valor_unitario'    => 50, 
        'precio_unitario'   => 59, 
        'tipo_precio'       => '01', 
        'igv'               => 9,
        'porcentaje_igv'    => 18, 
        'valor_total'       => 50,
        'importe_total'     => 59,
        'unidad'            => 'NIU', 
        'codigo_afectacion_alt' => '10', 
        'codigo_afectacion' => 1000, 
        'nombre_afectacion' =>  'IGV', 
        'tipo_afectacion'   =>  'VAT' 
    ),
    array(
        'item' 				=> 2,
        'codigo'			=> 'CODSIS02',
        'descripcion'		=> 'LIBRO ABC',
        'cantidad'			=> 1,
        'valor_unitario'	=> 50,
        'precio_unitario'	=> 50,
        'tipo_precio'		=> "01", 
        'igv'				=> 0,
        'porcentaje_igv'	=> 18,
        'valor_total'		=> 50,
        'importe_total'		=> 50,
        'unidad'			=> 'NIU',
        'codigo_afectacion_alt'	=> '20', 
        'codigo_afectacion'	=> 9997,
        'nombre_afectacion'	=>	'EXO',  
        'tipo_afectacion'	=> 'VAT'
    ),
    array(
        'item' 				=> 3,
        'codigo'			=> 'CODSIS03',
        'descripcion'		=> 'TOMATE',
        'cantidad'			=> 1,
        'valor_unitario'	=> 50,
        'precio_unitario'	=> 50,
        'tipo_precio'		=> "01",
        'igv'				=> 0,
        'porcentaje_igv'	=> 18,
        'valor_total'		=> 50,
        'importe_total'		=> 50,
        'unidad'			=> 'NIU',
        'codigo_afectacion_alt'	=> '30', 
        'codigo_afectacion'	=> 9998,
        'nombre_afectacion'	=>	'INA', 
        'tipo_afectacion'	=> 'FRE'  
    )
);

$op_gravadas = 0;
$op_inafectas = 0;
$op_exoneradas = 0;
$igv = 0;
$total = 0;


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
$comprobante['total_texto'] = CantidadEnLetra($total);

require_once("xml.php");

$xml = new GeneradorXML(); 


$nombrexml= $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];
$ruta = 'xml/' . $nombrexml;
$xml->CrearXMLFactura($ruta, $emisor, $cliente, $comprobante, $detalle);

echo 'XML CREADO CON EXITO';


require_once('ApiFacturacion.php');

$objApi = new ApiFacturacion();
$objApi->EnviarComprobanteElectronico($emisor, $nombrexml);



?>