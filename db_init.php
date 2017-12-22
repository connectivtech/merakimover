<?php


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

$attemptLimit = 10;
$attempts = 0;
$retryWait = 5;

do {
	if (!$aws_mysqli->real_connect($dbhost, $dbuser, $dbpassword, $dbdatabase)) {
	 	$attempts++;
	 	echo logEvent("Error Failed # $attempts to connect to AWS MySQL $dbhost/$dbdatabase: " . mysqli_connect_error());
	 	sleep($retryWait);
		$retryWait = $retryWait + 5;
		if ($attempts >= $attemptLimit){
			exit("Error could not connect after $attempts tries to AWS MySQL $dbhost/$dbdatabase: " . mysqli_connect_error());	
		} 
	} else {
		echo ("Connected to AWS database: $dbhost/$dbdatabase \n" ) ; 
		break;
	}
} while($attempts < $attemptLimit);


$res = $aws_mysqli->query("SHOW STATUS LIKE 'Ssl_cipher';");
while($row = $res->fetch_array()) {
	$sslCipher = $row['Value'];
	$sslExpected = 'DHE-RSA-AES128-SHA';
	if($sslCipher != $sslExpected) { 
		echo LogEvent("Error. SSL cipher incorrect or missing, expected $sslExpected, got: $sslCipher. Exiting."); 
		exit();
	} else {
		echo("Using SSL: $sslCipher \n");
	}
} // end new AWS conn

?>