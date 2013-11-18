<?php

function setAttributeValue(&$nodo, $attr) {
$quitar = array('sello'=>1,'noCertificado'=>1,'certificado'=>1);
foreach ($attr as $key => $val) {
    $val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
    $val = trim($val);                           // Regla 5b
    if (strlen($val)>0) {   // Regla 6
        $val = utf8_encode(str_replace("|","/",$val)); // Regla 1
        $nodo->setAttribute($key,$val);
    }
}
}

function sellarXML($cfdi, $numero_certificado, $archivo_cer, $archivo_pem){
  
  $private = openssl_pkey_get_private(file_get_contents($archivo_pem), "12345678a");
  $certificado = str_replace(array('\n', '\r'), '', base64_encode(file_get_contents($archivo_cer)));
  
  $xdoc = new DomDocument();
  $xdoc->loadXML($cfdi) or die("XML invalido");

  $XSL = new DOMDocument();
  $XSL->load('files/xslt/cadenaoriginal_3_2.xslt');
  
  $proc = new XSLTProcessor;
  $proc->importStyleSheet($XSL);

  $cadena_original = $proc->transformToXML($xdoc);
  openssl_sign($cadena_original, $sig, $private, OPENSSL_ALGO_SHA1);
  openssl_free_key($private);
  $sello = base64_encode($sig);

  $c = $xdoc->getElementsByTagNameNS("http://www.sat.gob.mx/cfd/3", "Comprobante")->item(0);
  $c->setAttribute('sello', $sello);
  $c->setAttribute('certificado', $certificado);
  $c->setAttribute('noCertificado', $numero_certificado);
  return $xdoc->saveXML();

}

$xml = new DOMDocument('1.0', 'UTF-8');
$xml->xmlStandalone = true;
$root = $xml->createElement("cfdi:Comprobante");
$root = $xml->appendChild($root);

$numero_certificado = "20001000000100005867";
$archivo_cer = "keys/csd/emisor.cer.pem";
$archivo_pem = "keys/csd/emisor.key.pem";

// simular arreglo de conceptos
$arr['Conceptos'][1]['descripcion'] = "PZA";
$arr['Conceptos'][1]['cantidad'] = "1";
$arr['Conceptos'][1]['unidad']="CANT";
$arr['Conceptos'][1]['valorUnitario']="1000.00";
$arr['Conceptos'][1]['importe']="1000.00";


setAttributeValue($root, array("xmlns:cfdi"=>"http://www.sat.gob.mx/cfd/3",
	"xmlns:tfd"=>"http://www.sat.gob.mx/TimbreFiscalDigital",
   	"xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
	"xmlns:xs"=>"http://www.w3.org/2001/XMLSchema",
    "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd http://www.sat.gob.mx/TimbreFiscalDigital http://www.sat.gob.mx/sitio_internet/TimbreFiscalDigital/TimbreFiscalDigital.xsd"));


setAttributeValue($root, array("version"=>"3.2",
	                      //"serie"=>$arr['serie'],
	                      "folio"=>"3",
	                      "fecha"=> date('Y-m-d\TH:i:s'),
	                      "sello"=>"@",
	                      //"noAprobacion"=>$arr['noAprobacion'],
	                      //"anoAprobacion"=>$arr['anoAprobacion'],
	                      "formaDePago"=>"PAGO EN UNA SOLA EXHIBICION",
	                      "noCertificado"=>"20001000000100005867",
	                      "certificado"=>"@",
	                      "subTotal"=>"1000.00",
	                      //"descuento"=>"0",
	                      "total"=>"1160.00",
	                      "tipoDeComprobante"=>"ingreso",
	                      "metodoDePago"=>"TARJETA",
	                      "LugarExpedicion"=>"MONTERREY, NL",
	                      "NumCtaPago"=>"2345",
//	                      "FolioFiscalOrig"=>$arr['FolioFiscalOrig'],
//	                      "SerieFolioFiscalOrig"=>$arr['SerieFolioFiscalOrig'],
//	                      "FechaFolioFiscalOrig"=>satxmlsv22_xml_fech($arr['FechaFolioFiscalOrig']),
//	                      "MontoFolioFiscalOrig"=>$arr['MontoFolioFiscalOrig']
						));



$emisor = $xml->createElement("cfdi:Emisor");
$emisor = $root->appendChild($emisor);
setAttributeValue($emisor, array("rfc"=>"AAA010101AAA","nombre"=>"EMPRESA DEMO" ));
$domfis = $xml->createElement("cfdi:DomicilioFiscal");
$domfis = $emisor->appendChild($domfis);
setAttributeValue($domfis, array("calle"=>"CARLOS B. ZETINA",
	                        "noExterior"=>"80",
	                        "noInterior"=>"",
	                        "colonia"=>"PARQUE INDUSTRIAL XALOSTOC",
	                        "municipio"=>"ECATEPEC DE MORELOS",
	                        "estado"=>"MEXICO",
	                        "pais"=>"MEXICO",
	                        "codigoPostal"=>"55348"));

$regimen = $xml->createElement("cfdi:RegimenFiscal");
$expedido = $emisor->appendChild($regimen);
setAttributeValue($regimen, array("Regimen"=>"PERSONA FISCA"));


$receptor = $xml->createElement("cfdi:Receptor");
$receptor = $root->appendChild($receptor);
setAttributeValue($receptor, array("rfc"=>"DIA031002LZ2", "nombre"=> "DIVERZA" ));

$domicilio = $xml->createElement("cfdi:Domicilio");
$domicilio = $receptor->appendChild($domicilio);
setAttributeValue($domicilio, array("calle"=>"PADRE MIER",
                       "noExterior"=>"",
                       "noInterior"=>"",
                       "colonia"=>"",
                       "municipio"=>"",
                       "estado"=>"",
                       "pais"=>"MEXICO",
                       "codigoPostal"=>""));



$conceptos = $xml->createElement("cfdi:Conceptos");
$conceptos = $root->appendChild($conceptos);
for ($i=1; $i<=sizeof($arr['Conceptos']); $i++) {
	$concepto = $xml->createElement("cfdi:Concepto");
	$concepto = $conceptos->appendChild($concepto);
	$prun = $arr['Conceptos'][$i]['valorUnitario'];
	setAttributeValue($concepto, array("cantidad"=>$arr['Conceptos'][$i]['cantidad'],
								"unidad"=>$arr['Conceptos'][$i]['unidad'],
					            "descripcion"=>$arr['Conceptos'][$i]['descripcion'],
					            "valorUnitario"=>$arr['Conceptos'][$i]['valorUnitario'],
					            "importe"=>$arr['Conceptos'][$i]['importe'] ));
}


$impuestos = $xml->createElement("cfdi:Impuestos");
$impuestos = $root->appendChild($impuestos);
//if (isset($arr['Traslados']['importe'])) {
    $traslados = $xml->createElement("cfdi:Traslados");
    $traslados = $impuestos->appendChild($traslados);
    $traslado = $xml->createElement("cfdi:Traslado");
    $traslado = $traslados->appendChild($traslado);
    setAttributeValue($traslado, array("impuesto"=>"IVA",
                              "tasa"=>"160.00",
                              "importe"=>"160.00" ));
//}
$impuestos->SetAttribute("totalImpuestosTrasladados","160.00");

$xml->formatOutput = true;
$cfdi = $xml->saveXML();

$cfdi = sellarXML($cfdi, $numero_certificado, $archivo_cer, $archivo_pem);

print $cfdi; 

?>