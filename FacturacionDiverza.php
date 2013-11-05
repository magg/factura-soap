<?php

$GLOBALS['debug_diverza'] = 0;

class TimbrarSoapClient extends SoapClient {
	
	public $log = 'FacturacionDiverza.log';

    function __doRequest($request, $location, $action, $version) {	 
	
		$dom = new DOMDocument('1.0', 'UTF-8');
	    $dom->preserveWhiteSpace = true;
	    $dom->loadXML($request);
	    $dom->documentElement->setAttribute('xmlns:tim', 'http://www.buzonfiscal.com/ns/xsd/bf/TimbradoCFD');
	    $request = $dom->saveXML();
		$request = preg_replace("/SOAP-ENV/", "S", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:req=", $request);
		$request = preg_replace("/\/TimbradoCFD\"\s/", "/RequestTimbraCFDI\" ", $request );
		
		
		if($GLOBALS['debug_diverza'] == 1){
			$this->log("SOAP request:\t".$request);
		    //$this->log("SOAP response:\t".$client->__getLastResponse());
		}
		
        return parent::__doRequest($request, $location, $action, $version);
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

class CancelarSoapClient extends SoapClient {
	
	public $log = 'FacturacionDiverza.log';

    function __doRequest($request, $location, $action, $version) {	 
	
		$request = preg_replace("/SOAP-ENV/", "soapenv", $request);		
		$request = preg_replace("/xmlns:ns1=/", "xmlns:ns=", $request);
		
		if($GLOBALS['debug_diverza'] == 1){
			$this->log("SOAP request:\t".$request);
		    //$this->log("SOAP response:\t".$client->__getLastResponse());
		}
						
        return parent::__doRequest($request, $location, $action, $version);
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

class FacturacionDiverza {
	public $log = 'FacturacionDiverza.log';
	public $url; 
	public $cert;
	public $passphrase;
	public $rfcreceptor;
	public $rfcemisor;
	public $UUID;
	public $server_code; // success = 0, failed = 18, repeated = 19
	
	public function __construct($url, $cert, $pass, $debug = 0) {
	    $GLOBALS['debug_diverza'] = (int) $debug;
	    $this->url = $url;     
		$this->cert = $cert;
		$this->passphrase = $pass;
	}
	
	
	public function timbrar($factura,$refid){
	
		try {	

			$client = new TimbrarSoapClient($this->url, array(
					'trace' => 1, 
					'wsdl_cache' => 0,
					'soap_version'   => SOAP_1_1,
					'style'    => SOAP_DOCUMENT,
					'local_cert' => $this->cert,
					'passphrase'=>$this->passphrase,
					"encoding"=>"UTF-8","exceptions" => 0,
					"connection_timeout"=>1000));


			//esto no debe de ir
			$this->rfcemisor = "AAA010101AAA";
			$this->rfcreceptor = "DIA031002LZ2";
			
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
		    $data->writeAttribute('RfcReceptor',  $this->rfcreceptor );
		    $data->writeAttribute('RfcEmisor',  $this->rfcemisor );
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
		
		
		if($GLOBALS['debug_diverza'] == 1){
			//$this->log("SOAP request:\t".$client->__getLastRequest());
		    $this->log("SOAP response:\t".$client->__getLastResponse());
		}
	
		
	}
	
	public function cancelar($uuid){
		
		try {
			$client = new CancelarSoapClient($this->url, array(
						'trace' => true, 
						'local_cert' => $this->cert,
						'passphrase'=>$this->passphrase,
						'soap_version'   => SOAP_1_1,
						'style'    => SOAP_DOCUMENT,
						"encoding"=>"UTF-8","exceptions" => 0,
						"connection_timeout"=>1000));
			
			//esto no debe de ir
			$this->rfcemisor = "AAA010101AAA";
			$this->rfcreceptor = "DIA031002LZ2";
						

			$data = new XMLWriter();
		  	$data->openMemory();
		  	$data->startElementNS('ns', 'RequestCancelaCFDi', NULL);
		    $data->writeAttribute('rfcEmisor',  $this->rfcemisor );
		    $data->writeAttribute('rfcReceptor',  $this->rfcreceptor );
		    $data->writeAttribute('uuid', $uuid );
			$data->endElement();

		  	//Convert it to a valid SoapVar
		  	$args = new SoapVar($data->outputMemory(), XSD_ANYXML);

			//Get reponse from WS
			$response = $client->__soapCall("cancelaCFDi", array($args));
			
			$this->server_code = $response->Result->Message->code;

		 } catch (Exception $e) {
		            echo "<h2>Exception Error!</h2>";
		            var_dump($e->getMessage());
		}
		
		if($GLOBALS['debug_diverza'] == 1){
			//$this->log("SOAP request:\t".$client->__getLastRequest());
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