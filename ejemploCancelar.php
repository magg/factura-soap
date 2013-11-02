<?php

include("FacturacionDiverza.php");

pruebaCancelacion();

function pruebaCancelacion(){
	$debug = 1;
	// pagina demo no funciona arroja: 
	//https://demonegocios.buzonfiscal.com/CorporativoWS3.0.xsd): failed to open stream: HTTP request failed! HTTP/1.1 404 Not Found
	//$url = "https://demonegocios.buzonfiscal.com/bfcorpcfdiws?wsdl";
	$url = "files/cancela/CorporativoWS3.0.wsdl";
	$cert = "keys/AAA010101AAA_2014.pem";
	$pass = "AAA010101AAA";
	
	
	$cliente = new FacturacionDiverza($url, $cert, $pass, $debug);
	
	$cliente->cancelar("d5b69e4a-62e8-4ec8-aaa3-61cde4493313");

	
}

?>