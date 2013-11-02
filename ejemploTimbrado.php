<?php

include("FacturacionDiverza.php");


pruebaTimbrado();

function pruebaTimbrado(){

	$debug = 1;
	$url = "https://demotf.buzonfiscal.com/timbrado?wsdl";
	//$url = "files/envio/TimbradoCFDI.wsdl";
	//$url = "https://tf.buzonfiscal.com/timbrado?wsdl";
	$cert = "keys/AAA010101AAA_2014.pem";
	$pass= "AAA010101AAA";
	$xml = file_get_contents('AAA010101AAA_FAC_62e8_20120108.xml');	
	
	
	$cliente = new FacturacionDiverza($url, $cert, $pass, $debug);
	
	
	$cliente->timbrar($xml, "COD0109-MTZ-EP29112-0309");
	
}
?>