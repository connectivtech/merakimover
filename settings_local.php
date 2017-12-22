<?php

include "merakifunctions.php";

$environment = 'dev';
$logPath	= '/dev/null' ; 

$merakiURL =	"https://api.meraki.com/api/v0/";
$merakiHeaders = ['X-Cisco-Meraki-API-Key: yourkeyhere'] ;
$dbOrgTable = 'meraki_orgs'; 
$dbNetworkTable = 'meraki_table'; 

$dbhost     = "rds.amazonaws.com" ;
$dbuser     = "youruser" ;
$dbpassword = "secret" ;
$dbdatabase = "dbname" ;

?>
