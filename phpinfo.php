<?php

class ABRSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version) {	 
	
			$dom = new DOMDocument('1.0', 'UTF-8');
	        $dom->preserveWhiteSpace = true;
	        $dom->loadXML($request);

	        $dom->documentElement->setAttribute('xmlns:tim', 'http://www.buzonfiscal.com/ns/xsd/bf/TimbradoCFD');

	        $request = $dom->saveXML();


		$request = preg_replace("/SOAP-ENV/", "S", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:req=", $request);
		$request = 	preg_replace("/\/TimbradoCFD\"\s/", "/RequestTimbraCFDI\" ", $request );
				
		print "<pre>\n";
	    print "<br />\n Request : ".htmlspecialchars($request);
	    print "</pre>";
		
		
        return parent::__doRequest($request, $location, $action, $version);
    }
}

$url = "https://demotf.buzonfiscal.com/timbrado?wsdl";
$cert = "keys/AAA010101AAA_2014.pem";
$passphrase = "AAA010101AAA";
$xml = file_get_contents('AAA010101AAA_FAC_62e8_20120108.xml');

try {	
	
	$client = new ABRSoapClient($url, array(
						'trace' => 1, 
						'wsdl_cache' => 0,
						'soap_version'   => SOAP_1_1,
						'style'    => SOAP_DOCUMENT,
						'local_cert' => $cert,
						'passphrase'=>$passphrase,
						"encoding"=>"UTF-8","exceptions" => 0,
						"connection_timeout"=>1000));
						
	
	$data = new XMLWriter();
  	$data->openMemory();
  	$data->startElementNS('tim', 'RequestTimbradoCFD', NULL);
	$data->writeAttribute('req:RefID',"COD0109-MTZ-EP29112-0309");

    // Send NULL to not specify the NameSpace on every call.
    $data->startElementNS('req','Documento', NULL);
      //Attribute on a Namespaced node!
    $data->writeAttribute('Archivo',  base64_encode($xml) );
    $data->writeAttribute('Tipo',  "XML" );
    $data->writeAttribute('Version',  "3.2" );
	$data->endElement();
    $data->startElementNS('req', 'InfoBasica', NULL);
    $data->writeAttribute('RfcReceptor',  "DIA031002LZ2" );
    $data->writeAttribute('RfcEmisor',  "AAA010101AAA" );
    $data->endElement();
  	$data->endElement();

  //Convert it to a valid SoapVar
  	$args = new SoapVar($data->outputMemory(), XSD_ANYXML);
	
	
	$response = $client->__soapCall("timbradoCFD", array($args));
	
	   
 } catch (Exception $e) {
            echo "<h2>Exception Error!</h2>";
			var_dump($e->getMessage());
}

	print "<pre>\n";
    print "<br />\n Response: ".htmlspecialchars($client->__getLastResponse());
    print "</pre>";

?>