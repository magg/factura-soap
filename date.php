<?php
$objDateTime = new DateTime('NOW');
echo $objDateTime->format('c'); 
 echo "<br>";

// este es el tipo de fecha requerida en la factura
echo date('Y-m-d\TH:i:s');

?>