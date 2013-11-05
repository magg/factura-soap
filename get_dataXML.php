<?php

//Access the attribute value of specified node

function getAttributes($node, $name) {
	foreach($node as $attr) {
		foreach($attr->attributes() as $key => $value) {
			if ((string) $key == $name) {
    			return $value;
    		}
		}
	}
}

//Loads XML file

$xml=simplexml_load_file("factura_prueba.xml");

//Get all namepaces in the XML
$ns = $xml->getNamespaces(true);

//Get all childrens with CDFI namespace
$cf = $xml->children($ns["cfdi"]);

//Node Emisor
$emisor = $cf->Emisor;

//Node Receptor
$receptor = $cf->Receptor;

//Childrens of complemento with namespace TFD
$complemento = $cf->Complemento->children($ns["tfd"]);

//Get attribute rfc of Emisor
$emisorRFC = getAttributes($emisor, "rfc");

//Get attribute rfc of Receptor
$receptorRFC = getAttributes($receptor, "rfc");

//Get attribute uuid of complemento child
$uuid = getAttributes($complemento, "UUID");

echo "RFC del emisor es $emisorRFC <br>";
echo "RFC del receptor es $receptorRFC <br>";
echo "UUID para cancelacion es $uuid <br>";

?>