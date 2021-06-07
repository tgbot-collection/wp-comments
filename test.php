<?php
require "IPLocation/IpLocation.php";

function convertIP2Location($ip_addr)
{
    $data = IpLocation::getLocation($ip_addr);
    if (isset($data["area"])) {
        return $data["area"];
    } else {
        return "";
    }
}


$a = convertIP2Location("191.3.3.1");
var_dump($a);