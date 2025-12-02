<?php
require_once('signature.php'); 

class ApiFacturacion
{
    public function EnviarComprobanteElectronico($emisor, $nombre, $rutacertificado="", $ruta_archivo_xml = "xml/", $ruta_archivo_cdr = "cdr/")
    {
        $objfirma = new Signature();
        $flg_firma = 0; 
      
        $ruta = $ruta_archivo_xml . $nombre . '.XML';

        $ruta_firma = $rutacertificado. 'LLAMA-PE-CERTIFICADO-DEMO-20123456123.pfx'; 
        $pass_firma = '20123456123';
        
        $resp = $objfirma->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);
        print_r($resp);
        echo '</br> XML FIRMADO';
        
       
        
     
        $zip = new ZipArchive();

        $nombrezip = $nombre.".ZIP";
        $rutazip = $ruta_archivo_xml . $nombre.".ZIP";
        
        if($zip->open($rutazip, ZipArchive::CREATE) === TRUE)
        {
            $zip->addFile($ruta, $nombre . '.XML');
            $zip->close();
        }
        
        echo '</br>XML ZIPEADO';//zip
        
      
        
        
      //envia  el zip a la sunat 
        $ws = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'; //conectar con la sunat
        
        $ruta_archivo = $rutazip;
		$nombre_archivo = $nombrezip;

        $contenido_del_zip = base64_encode(file_get_contents($ruta_archivo)); 
        

        $xml_envio ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                        <soapenv:Header>
                        <wsse:Security>
                            <wsse:UsernameToken>
                                <wsse:Username>'.$emisor['ruc'].$emisor['usuario_sol_sec'].'</wsse:Username>
                                <wsse:Password>'.$emisor['clave_sol_sec'].'</wsse:Password>
                            </wsse:UsernameToken>
                        </wsse:Security>
                        </soapenv:Header>
                        <soapenv:Body>
                        <ser:sendBill>
                            <fileName>'.$nombre_archivo.'</fileName>
                            <contentFile>'.$contenido_del_zip.'</contentFile>
                        </ser:sendBill>
                        </soapenv:Body>
                    </soapenv:Envelope>';
        
            $header = array(
                "Content-type: text/xml; charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "SOAPAction: ",
                "Content-lenght: ".strlen($xml_envio)
                );
        
        $ch = curl_init(); //inicia llamada
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch,CURLOPT_URL, $ws);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $xml_envio);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        
       //para ejecutar de forma local
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem"); //solo lcal
        
        $response = curl_exec($ch); //ejecutar llamada y respuesta WS SUNAT
        
        $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE); 
        $estadofe = "0"; //iniciamos la operacion 
        
        if($httpcode == 200)//200 cmunicacion exitosa
        {
            $doc = new DOMDocument();//clase que permite crear documentos XML
            $doc->loadXML($response);  //carga y crea
        
            if( isset( $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue ) ) ///valr de respuesta
            {
                $cdr = $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue; //guardamos la respuesta txt-html
                $cdr = base64_decode($cdr); //decodificando XML
                file_put_contents($ruta_archivo_cdr . 'R-' . $nombrezip, $cdr ); //guarda el CDR
                $zip = new ZipArchive();
                if($zip->open($ruta_archivo_cdr. 'R-' . $nombrezip ) === true )//verficiamos la existencia 
                {
                    $zip->extractTo($ruta_archivo_cdr, 'R-' . $nombre . '.XML');
                    $zip->close();
                }
                $estadofe = '1';
                echo 'Procesado correctamente, OK';
            }
            else {
                $estadofe = '2';
                $codigo = $doc->getElementsByTagName('faultcode')->item(0)->nodeValue;
                $mensaje = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
              
                echo 'Ocurrio un error con código: ' . $codigo . ' Msje:' . $mensaje;
            }
        }
        else { 
            $estadofe = "3";
           
            echo curl_error($ch);
            echo 'Hubo existe un problema de conexión';
        }
        
        curl_close($ch);
        
        
        
    } }
