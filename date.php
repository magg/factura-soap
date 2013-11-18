<?php
$objDateTime = new DateTime('NOW');
echo $objDateTime->format('c'); 
 echo "<br>";

// este es el tipo de fecha requerida en la factura
echo date('Y-m-d\TH:i:s');

echo "<br>";

$arr['Conceptos'][1]['descripcion'] = "PZA";
$arr['Conceptos'][1]['cantidad'] = "1";
$arr['Conceptos'][1]['unidad']="CANT";
$arr['Conceptos'][1]['valorUnitario']="1000.00";
$arr['Conceptos'][1]['importe']="1000.00";

print_r($arr);

$pkeyid = openssl_pkey_get_private(file_get_contents('keys/csd/emisor.key.pem'), "12345678a");
    if($pkeyid == FALSE) {
        echo '<br/>PRIVATE KEY IS FALSE<br/>';
    }
?>