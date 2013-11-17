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
	public $uuid;
	public $server_code; // success = 0, failed = 18, repeated = 19
	public $server_fault;
	public $xmlFile;
	
	public function __construct($url, $cert, $pass, $debug = 0, $file) {
	    $GLOBALS['debug_diverza'] = (int) $debug;
	    $this->url = $url;     
		$this->cert = $cert;
		$this->passphrase = $pass;
		$this->xmlFile = simplexml_load_file("$file");
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
		
			//Get all namepaces in the XML
			$ns = $this->xmlFile->getNamespaces(true);

			//Get all childrens with CDFI namespace
			$cf = $this->xmlFile->children($ns["cfdi"]);

			//Node Emisor
			$emisor = $cf->Emisor;

			//Node Receptor
			$receptor = $cf->Receptor;

			//Complemento with namespace TFD
			$complemento = $cf->Complemento->children($ns["tfd"]);

			//esto no debe de ir
			$this->rfcemisor = $this->getAttributes($emisor,'rfc');
			$this->rfcreceptor = $this->getAttributes($receptor,'rfc');
			
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

			if (array_key_exists("faultstring",$response)){
				$this->server_fault = $response->faultstring;
			} else{
				$this->uuid = $response->TimbreFiscalDigital->uuid;
			}

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
			
			//Get all namepaces in the XML
			$ns = $this->xmlFile->getNamespaces(true);

			//Get all childrens with CDFI namespace
			$cf = $this->xmlFile->children($ns["cfdi"]);

			//Node Emisor
			$emisor = $cf->Emisor;

			//Node Receptor
			$receptor = $cf->Receptor;

			//Complemento with namespace TFD
			$complemento = $cf->Complemento->children($ns["tfd"]);

			//esto no debe de ir
			$this->rfcemisor = $this->getAttributes($emisor,'rfc');
			$this->rfcreceptor = $this->getAttributes($receptor,'rfc');
			$this->uuid = getAttributes($complemento, "UUID");	

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
	  
	private function getAttributes($node, $name) {
		foreach($node as $attr) {
			foreach($attr->attributes() as $key => $value) {
				if ((string) $key == $name) {
    				return $value;
    			}
			}
		}
	}
		
}
?>