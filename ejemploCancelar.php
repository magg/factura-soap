<?php

include("FacturacionDiverza.php");

pruebaCancelacion();

function pruebaCancelacion(){
	$debug = 1;
	//$url = "https://demonegocios.buzonfiscal.com/bfcorpcfdiws?wsdl";
	$url = "files/cancela/CorporativoWS3.0.wsdl";
	$cert = "keys/AAA010101AAA_2014.pem";
	$pass = "AAA010101AAA";
	
	
	$client = new FacturacionDiverza($url, $cert, $pass, $debug);
	
	$client->cancelar("d5b69e4a-62e8-4ec8-aaa3-61cde4493313");

	
}

?>