<?php
$x=20;

function function_1() {
    static $y=10;
    $y++;
    return $y;

}
$a = function_1();
echo $a;

$car = array(
    "Volvo" => array("XC90", "S60", "V60"),
    "BMW" => array("X5", "X3", "M3"),
    "Toyota" => array("Camry", "Corolla", "Prius")
);
echo $car["BMW"][2];


?>