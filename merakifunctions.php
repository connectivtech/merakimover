<?php

// include "stathat.php";

// function logStathat($statName, $statValue, $statType) {
// 	global $environment, $stathatAccount;
// 	$statName = 'pnp.' . $environment . '.' . $statName ; 
// 	// echo("Account $stathatAccount Name $statName Type $statType");
// 	newline();
// 	if ($statType == 'value') {
// 		stathat_ez_value($stathatAccount, $statName, $statValue);
// 		echo("Logged value: $statValue to stathat: $statName");
// 	}
// 	elseif ($statType == 'count') {
// 		stathat_ez_count($stathatAccount, $statName, $statValue);
// 		echo("Logged count: $statValue to stathat: $statName");
// 	}
// 	else {
// 		echo logEvent('Error. Invalid stathat statType. Must be value or count.');
// 	}
// }


function newline() {
	echo nl2br ("\n");
};

function logEvent($message) {
	global $logPath, $logType;

    if ($message != '') {
        // Add a timestamp to the start of the $message
        $message = gmDate(DATE_ATOM) . ': ' . $logType . ' ' . $message;
        // todo: move this logic to settings.php ? 
		$fp = fopen($logPath, 'a');
        fwrite($fp, $message."\n");
        fclose($fp);
        return "$message";
    }
}

function showIP() {
  $ch = curl_init('http://ifconfig.me/ip');
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
  $myIp = curl_exec($ch);
  echo "Server IP: $myIp";
  newLine(); 
}

?>
