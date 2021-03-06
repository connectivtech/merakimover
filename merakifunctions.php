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

function secondsSinceMidnight () {
  $startdate = new DateTime();
  date_time_set($startdate, 00, 00);
  $date = new DateTime();
  $datediff = date_diff($date, $startdate);

  // var_dump($datediff);

  $seconds = $datediff->h * 60 * 60;
  $seconds = $datediff->i * 60 + $seconds;
  $seconds = $datediff->s + $seconds;

  return $seconds;
}


function newline() {
	echo nl2br ("\n");
};

function logEvent($message) {
  global $logPath, $logType, $environment;

    if ($message != '') {
        // Add a timestamp to the start of the $message
        $message = gmDate(DATE_ATOM) . ': ' . $logType . $environment . ' ' . $message;
        // todo: move this logic to settings.php ? 
    
    if (is_writeable($logPath)) {
      $fp = fopen($logPath, 'a');
          fwrite($fp, $message."\n");
          fclose($fp);
          echo ("\n");
          return "$message";
    } else {
      echo("Error $logPath is not writable");
      exit();
    }
    
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
