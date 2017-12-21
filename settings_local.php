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


// AWS connection - diff from forum as we want to SSL this traffic
$aws_mysqli = mysqli_init();
if (!$aws_mysqli) {
    exit('mysqli_init failed');
}

if (!$aws_mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
    die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
}

// set SSL using AWS CA -- hostname is too long, so using ec2 one fails SSL check
$aws_mysqli->ssl_set(null,null,'rds-combined-ca-bundle.pem',null,null);

if (!$aws_mysqli->real_connect($dbhost, $dbuser, $dbpassword, $dbdatabase)) {
  echo ("Failed to connect to AWS MySQL $dbhost/$dbdatabase: " . mysqli_connect_error());
	exit("Failed to connect to AWS MySQL $dbhost/$dbdatabase: " . mysqli_connect_error());
} else {
		echo ("Connected to AWS database: $dbhost/$dbdatabase \n" ) ; 
}

$res = $aws_mysqli->query("SHOW STATUS LIKE 'Ssl_cipher';");
while($row = $res->fetch_array()) {
	$sslCipher = $row['Value'];
	$sslExpected = 'DHE-RSA-AES128-SHA';
	if($sslCipher != $sslExpected) { 
		echo ("Error. SSL cipher incorrect or missing, expected $sslExpected, got: $sslCipher. Exiting."); 
		exit();
	} else {
		echo("Using SSL: $sslCipher \n");
	}
} // end new AWS conn


?>
