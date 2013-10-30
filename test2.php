<?php

$url = "https://demonegocios.buzonfiscal.com/bfcorpcfdiws?wsdl";
//archivo del kit
//$url = "files/cancela/CorporativoWS3.0.wsdl";
$cert = "keys/AAA010101AAA_2014.pem";
$passphrase = "AAA010101AAA";

//$opts = array('ssl' => array('ciphers'=> 'TLSv1', 'allow_self_signed' => true, "cafile"=>"keys/20001000000100003992.pem"));


class ABRSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version) {	 



		$request = preg_replace("/SOAP-ENV/", "soapenv", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:ns=", $request);
				
		print "<pre>\n";
	    print "<br />\n Request : ".htmlspecialchars($request);
	    print "</pre>";
		
		
        return parent::__doRequest($request, $location, $action, $version);
    }
}

try {
	$client = new ABRSoapClient($url, array(
						'trace' => true, 
						'local_cert' => $cert,
						'passphrase'=>$passphrase,
						'soap_version'   => SOAP_1_1,
						'style'    => SOAP_DOCUMENT,
						"encoding"=>"UTF-8","exceptions" => 0,
						//'stream_context' => stream_context_create($opts),
						"connection_timeout"=>1000
						
						
	));
	
	$data = new XMLWriter();
  	$data->openMemory();
  	$data->startElementNS('ns', 'RequestCancelaCFDi', NULL);
    $data->writeAttribute('rfcEmisor',  "TERA010101DEM" );
    $data->writeAttribute('rfcReceptor',  "TERA010101DEM" );
    $data->writeAttribute('uuid', "56179390-29c4-40ca-95b1-1fe4a9619dd9" );
	$data->endElement();


  //Convert it to a valid SoapVar
  	$args = new SoapVar($data->outputMemory(), XSD_ANYXML);
	
	$response = $client->__soapCall("cancelaCFDi", array($args));
	
   
 } catch (Exception $e) {
            echo "<h2>Exception Error!</h2>";
            echo $e->getMessage();
}

print "<pre>\n";
//print "<br />\n Request : ".htmlspecialchars($client->__getLastRequest());
print "<br />\n Response: ".htmlspecialchars($client->__getLastResponse());
print "</pre>";

?>
