<?php

class TimbrarSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version) {	 
	
		$dom = new DOMDocument('1.0', 'UTF-8');
	    $dom->preserveWhiteSpace = true;
	    $dom->loadXML($request);
	    $dom->documentElement->setAttribute('xmlns:tim', 'http://www.buzonfiscal.com/ns/xsd/bf/TimbradoCFD');
	    $request = $dom->saveXML();
		$request = preg_replace("/SOAP-ENV/", "S", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:req=", $request);
		$request = preg_replace("/\/TimbradoCFD\"\s/", "/RequestTimbraCFDI\" ", $request );
		
        return parent::__doRequest($request, $location, $action, $version);
    }
}

class CancelarSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version) {	 
	
		$request = preg_replace("/SOAP-ENV/", "soapenv", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:ns=", $request);
						
        return parent::__doRequest($request, $location, $action, $version);
    }
}

class FacturacionDiverza {
	public $log = 'FacturacionDiverza.log';
	public $debug;	
	public $url; 
	public $cert;
	public $passphrase;
	public $refid;
	public $UUID;
	public $rfcrecptor;
	public $rfcemisor;
	
	public function __construct($url, $cert, $pass, $debug = 0) {
	    $this->debug = (int) $debug;
	    $this->url = $url;     
		$this->cert = $cert;
		$this->passphrase = $pass;
	}
	
	
	public function timbrar($factura){
	
		try {	

			$client = new TimbrarSoapClient($url, array(
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
			$data->writeAttribute('req:RefID', $refid);
		    // Send NULL to not specify the NameSpace on every call.
		    $data->startElementNS('req','Documento', NULL);
		    //Attribute on a Namespaced node!
		    $data->writeAttribute('Archivo',  base64_encode($factura) );
		    $data->writeAttribute('Tipo',  "XML" );
		    $data->writeAttribute('Version',  "3.2" );
			$data->endElement();
		    $data->startElementNS('req', 'InfoBasica', NULL);
		    $data->writeAttribute('RfcReceptor',  $rfcrecptor );
		    $data->writeAttribute('RfcEmisor',  $rfcemisor );
		    $data->endElement();
		  	$data->endElement();

		  	//Convert it to a valid SoapVar
		  	$args = new SoapVar($data->outputMemory(), XSD_ANYXML);

			//Get reponse from WS
			$response = $client->__soapCall("timbradoCFD", array($args));

		 } catch (Exception $e) {
		            echo "<h2>Exception Error!</h2>";
					var_dump($e->getMessage());
		}
		
		
		if($this->debug == 1){
			$this->log("SOAP request:\t".$client->__getLastRequest());
		    $this->log("SOAP response:\t".$client->__getLastResponse());
		}
	
		
	}
	
	public function cancelar($uuid){
		
		try {
			$client = new CancelarSoapClient($url, array(
						'trace' => true, 
						'local_cert' => $cert,
						'passphrase'=>$passphrase,
						'soap_version'   => SOAP_1_1,
						'style'    => SOAP_DOCUMENT,
						"encoding"=>"UTF-8","exceptions" => 0,
						"connection_timeout"=>1000));

			$data = new XMLWriter();
		  	$data->openMemory();
		  	$data->startElementNS('ns', 'RequestCancelaCFDi', NULL);
		    $data->writeAttribute('rfcEmisor',  $rfcemisor );
		    $data->writeAttribute('rfcReceptor',  $rfcrecptor );
		    $data->writeAttribute('uuid', $UUID );
			$data->endElement();

		  	//Convert it to a valid SoapVar
		  	$args = new SoapVar($data->outputMemory(), XSD_ANYXML);

			//Get reponse from WS
			$response = $client->__soapCall("cancelaCFDi", array($args));

		 } catch (Exception $e) {
		            echo "<h2>Exception Error!</h2>";
		            var_dump($e->getMessage());
		}
		
		if($this->debug == 1){
			$this->log("SOAP request:\t".$client->__getLastRequest());
		    $this->log("SOAP response:\t".$client->__getLastResponse());
		}	
		
	}
	
	
	/**
	* Registra los mensajes SOAP en el archivo, si el
	* archivo no existe lo crea.
	*
	* Sólo se ejecuta si debug tiene 1 como valor
	* @param $str
	* @return void
	*/
	  private function log($str){
	    $f = fopen($this->log, 'a');
	    fwrite($f, date('c')."\t".$str."\n\n");
	    fclose($f);
	  }
		
}
?>