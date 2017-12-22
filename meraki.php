<?php

include "settings.php";

echo logEvent("Meraki mover started");

$insertErrorCounter = 0;
$insertSuccessCounter = 0;

date_default_timezone_set('UTC');

//todo: parse into JSON
$decoded = getMerakiOrgs($merakiURL, $merakiHeaders);

if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
    logEvent('Error API curl failed: ' . $decoded->response->errormessage);
    die('error occured: ' . $decoded->response->errormessage);
} else {
	echo logEvent('API successful: ' . count($decoded) ) ;
	$orgs = processMerakiOrgs($decoded);
 	$networks = processMerakiNetworks($orgs);
 	$devices = processMerakiDevices($networks);
 	processMerakiClients($devices);
}

function getMerakiOrgs ($merakiURL, $merakiHeaders) {
	$curl = curl_init($merakiURL . 'organizations/');

	$decoded = curlMeraki('organizations/');

	echo "getMerakiOrgs finished \n";
	return $decoded;

}  // end getMerakiOrgs

function processMerakiOrgs ($decoded) {
	$orgs = array();
	foreach($decoded as $i => $item) {
		echo ("\n");
		$org_id = $decoded[$i]->{'id'};
		$orgs[] = $org_id ;
		$boolOrgExist = doesOrgExist($org_id);
	// check if org ID already exists
		if ($boolOrgExist === true) {
			// dont insert again if already exists, and we can goto next org
			echo ("Meraki org ID exists: $org_id");
			echo "\n";
		} elseif ($boolOrgExist === false) {
			echo logEvent("New org found: $org_id");
			echo $decoded[$i]->{'name'} . "\n" ;
			$org_name = $decoded[$i]->{'name'};
			insertOrg($org_id, $org_name);		
		} else {
			echo logEvent("Error: Meraki org check broke");
			die();
		}

	} // end foreach
	echo "processMerakiOrgs finished \n";
	return($orgs);
} // end processMerakiOrgs

function processMerakiNetworks ($orgs) {
	echo "Start processMerakiNetworks \n";
	foreach($orgs as $i => $item) {
		echo ("\n");
		$org_id = $orgs[$i];

		$networks = curlMeraki('organizations/' . $org_id . '/networks/');
		
		foreach($networks as $i => $item) {
			$network_id = $networks[$i]->{'id'};
			
			$networks = array();
			$networks[] = $network_id;

			$boolNetworkExist = doesNetworkExist($network_id);

			if ($boolNetworkExist === true) {
				// dont insert again if already exists, and we can goto next visitor
				echo ("Meraki network ID exists: $network_id");
				echo "\n";
			} elseif ($boolNetworkExist === false) {
				// insert new visitor
				echo logEvent("New network found: $network_id");
				echo "\n";
				$network_name = $networks[$i]->{'name'};
				$network_timezone = $networks[$i]->{'timeZone'};
				$network_tags = $networks[$i]->{'tags'};
				$network_type = $networks[$i]->{'type'};

				insertNetwork($network_id, $org_id, $network_name, $network_timezone, $network_tags, $network_type);
			} else {
				echo logEvent("Error: Meraki networks check broke");
				die();
			}

		} // end foreach networks
	} // end foreach orgs
	return $networks;
} // end processMerakiNetworks

function processMerakiDevices ($networks) {
	echo "Start processMerakiDevices \n";
	$serials = array();
	foreach($networks as $i => $item) {
		echo ("\n");
		$network_id = $networks[$i];

		$devices = curlMeraki('networks/' . $network_id . '/devices/');

		foreach($devices as $i => $item) {
			$serial 	= $devices[$i]->{'serial'};
			$serials[] = $serial;
			
			$boolDeviceExist = doesDeviceExist($serial);

			if ($boolDeviceExist === true) {
				echo ("Meraki device exists: $serial");
				echo "\n";
			} elseif ($boolDeviceExist === false) {
				// insert new visitor
				echo logEvent("New Meraki device: $serial");
				echo "\n";
				$device_network_id = $devices[$i]->{'networkId'};
				$device_name = $devices[$i]->{'name'};
				$device_mac = $devices[$i]->{'mac'};
				$device_lan_ip = $devices[$i]->{'lanIp'};
				$device_lat = $devices[$i]->{'lat'};
				$device_lng = $devices[$i]->{'lng'};
				$device_model = $devices[$i]->{'model'};
				$device_address = $devices[$i]->{'address'};
				$device_notes = isset($devices[$i]->{'notes'}) ? $devices[$i]->{'notes'} : '';
				$device_tags = isset($devices[$i]->{'tags'}) ? $devices[$i]->{'tags'} : '';

				insertDevice( $serial, $device_network_id, $device_name, $device_mac, $device_lan_ip, $device_lat, $device_lng, $device_model, $device_address, $device_notes, $device_tags  );
			} else {
				echo logEvent("Error: Meraki devices by serial check broke");
				die();
			}

		} // end foreach devices
	} // end foreach networks
	return $serials;
} // end processMerakiDevices

function processMerakiClients ($devices) {
	echo "Start processMerakiClients for " . count($devices) . " devices \n";
	foreach($devices as $i => $item) {
		$timespan = secondsSinceMidnight();
		echo ("Seconds since midnight to set as timespan: $timespan \n");
		$device = $devices[$i];
		echo($device . "\n");

		$clients = curlMeraki('devices/' . $device . '/clients?timespan=' . $timespan);

		foreach($clients as $i => $item) {
			$mac 	= $clients[$i]->{'mac'};
			
			echo ("Insert $mac");
			echo "\n";
			$id = $clients[$i]->{'id'};
			$mac = $clients[$i]->{'mac'};
			$description = $clients[$i]->{'description'};
			$mdnsname = $clients[$i]->{'mdnsName'};
			$dhcphostname = $clients[$i]->{'dhcpHostname'};
			$ip = $clients[$i]->{'ip'};
			$vlan = $clients[$i]->{'vlan'};
			$switchport = $clients[$i]->{'switchport'};
			$sent = isset($clients[$i]->{'usage'}->{'sent'}) ? $clients[$i]->{'usage'}->{'sent'} : '';			
			$recv = isset($clients[$i]->{'usage'}->{'recv'}) ? $clients[$i]->{'usage'}->{'recv'} : '';

			insertClient( $id, $mac, $description, $mdnsname, $dhcphostname, $ip, $vlan, $switchport, $sent, $recv, $device, $timespan );
		} // end foreach client
	} // end foreach devices
} // end processMerakiClients

function curlMeraki ($merakiAPIpath) {
	global $merakiURL, $merakiHeaders;
	$curl = curl_init($merakiURL . $merakiAPIpath );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $merakiHeaders);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

	$curl_response = curl_exec($curl);

	if ($curl_response === false) {
		$info = curl_getinfo($curl);
		curl_close($curl);
		echo logEvent("Error Curl $merakiURL $merakiHeaders $merakiAPIpath ". var_export($info));
		die("\n Curl error: " . var_export($info));
	}

	// later we can see if 200 and log error
	// $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	// echo $httpcode;

	curl_close($curl);

	return json_decode($curl_response);
} //end curlMeraki

function doesOrgExist ($org_id) {
	global $aws_mysqli, $dbOrgTable;
	$org_id = $aws_mysqli->real_escape_string($org_id);
	$queryExist = "SELECT org_id FROM $dbOrgTable WHERE org_id = $org_id";
	$resultExist = $aws_mysqli->query($queryExist);
	if(!$resultExist) {
		echo logEvent("Error $aws_mysqli->error to check if org ID exists: $queryExist");
	} elseif($resultExist->num_rows == 0) {
		return false;
	  } else {
	  	return true;
	  }
} // end doesOrgExist

function doesNetworkExist($network_id) {
	global $aws_mysqli, $dbNetworkTable;
	$org_id = $aws_mysqli->real_escape_string($network_id);
	$queryExist = "SELECT network_id FROM $dbNetworkTable WHERE network_id = '$network_id'";
	$resultExist = $aws_mysqli->query($queryExist);
	if(!$resultExist) {
		echo logEvent("Error $aws_mysqli->error to check if network ID exists: $queryExist");
	} elseif($resultExist->num_rows == 0) {
		return false;
	  } else {
	  	return true;
	  }
} // end doesNetworkExist


function doesDeviceExist($serial) {
	global $aws_mysqli;
	$serial = $aws_mysqli->real_escape_string($serial);
	$queryExist = "SELECT device_serial FROM meraki_devices WHERE device_serial = '$serial'";
	$resultExist = $aws_mysqli->query($queryExist);
	if(!$resultExist) {
		echo logEvent("Error $aws_mysqli->error to check if serial exists: $queryExist");
	} elseif($resultExist->num_rows == 0) {
		return false;
	  } else {
	  	return true;
	  }
} // end doesDeviceExist


function insertOrg ($org_id, $org_name) {
	global $aws_mysqli, $dbOrgTable, $insertSuccessCounter, $insertErrorCounter;

	$org_id = $aws_mysqli->real_escape_string($org_id);
	$org_name = $aws_mysqli->real_escape_string($org_name);

	$insertFields = " org_id, org_name ";

	$queryInsertOrg = "INSERT INTO $dbOrgTable ($insertFields) VALUES " . 
		" ( $org_id, '$org_name' ); " ;

	echo $queryInsertOrg ;

	$resultInsert = $aws_mysqli->query($queryInsertOrg);
	if(!$resultInsert) {
		echo logEvent("Error $aws_mysqli->error to insert org ID $org_id: $queryInsertOrg");
		return false;
		$insertErrorCounter++; 
	  } else {
	  	echo("Insert success");
	  	$insertSuccessCounter++; 
	  	return true;
	  }
} // end insertOrg

function insertNetwork ($network_id, $network_org_id, $network_name, $network_timezone, $network_tags, $network_type) {
	global $aws_mysqli, $dbNetworkTable, $insertSuccessCounter, $insertErrorCounter;

	$network_id = $aws_mysqli->real_escape_string($network_id);
	$network_org_id =  $aws_mysqli->real_escape_string($network_org_id);
	$network_name = $aws_mysqli->real_escape_string($network_name);
	$network_timezone = $aws_mysqli->real_escape_string($network_timezone);
	$network_tags = $aws_mysqli->real_escape_string($network_tags);
	$network_type = $aws_mysqli->real_escape_string($network_type);

	$insertFields = " network_id, network_org_id, network_name, network_timezone, network_tags, network_type ";

	$queryInsertNetwork = "INSERT INTO $dbNetworkTable ($insertFields) VALUES " . 
		" ( '$network_id', '$network_org_id', '$network_name', '$network_timezone', '$network_tags', '$network_type' ); " ;

	echo $queryInsertNetwork ;

	$resultInsert = $aws_mysqli->query($queryInsertNetwork);
	if(!$resultInsert) {
		echo logEvent("Error $aws_mysqli->error to insert network ID $network_id: $queryInsertNetwork");
		$insertErrorCounter++; 
		return false;
	  } else {
	  	echo("Insert network success");
	  	$insertSuccessCounter++; 
	  	return true;
	  }
} // end insertNetwork

function insertDevice ( $serial, $device_network_id, $device_name, $device_mac, $device_lan_ip, $device_lat, $device_lng, $device_model, $device_address, $device_notes, $device_tags ) {
	global $aws_mysqli, $insertSuccessCounter, $insertErrorCounter;

	$device_serial = $aws_mysqli->real_escape_string($serial);
	$device_network_id = $aws_mysqli->real_escape_string($device_network_id);
	$device_name = $aws_mysqli->real_escape_string($device_name);
	$device_mac = $aws_mysqli->real_escape_string($device_mac);
	$device_lan_ip = $aws_mysqli->real_escape_string($device_lan_ip);
	$device_lat = $aws_mysqli->real_escape_string($device_lat);
	$device_lng = $aws_mysqli->real_escape_string($device_lng);
	$device_model = $aws_mysqli->real_escape_string($device_model);
	$device_address = $aws_mysqli->real_escape_string($device_address);
	$device_notes = $aws_mysqli->real_escape_string($device_notes);
	$device_tags = $aws_mysqli->real_escape_string($device_tags);
			
	$insertFields = " device_serial, device_network_id, device_name, device_mac, device_lan_ip, device_lat, device_lng, device_model, device_address, device_notes, device_tags ";

	$queryInsertDevice = "INSERT INTO meraki_devices ($insertFields) VALUES " . 
		" ( '$serial', '$device_network_id', '$device_name', '$device_mac', '$device_lan_ip', $device_lat, $device_lng, '$device_model', '$device_address', '$device_notes', '$device_tags' ); " ;

	echo $queryInsertDevice ;

	$resultInsert = $aws_mysqli->query($queryInsertDevice);
	if(!$resultInsert) {
		echo ("Error $aws_mysqli->error to insert device serial $serial: $queryInsertDevice");
		$insertErrorCounter++; 
		return false;
	  } else {
	  	echo("Insert device success");
	  	$insertSuccessCounter++; 
	  	return true;
	  }
} // end insertDevice

function insertClient ( $id, $mac, $description, $mdnsname, $dhcphostname, $ip, $vlan, $switchport, $sent, $recv, $device_serial, $timespan) {
	global $aws_mysqli, $insertSuccessCounter, $insertErrorCounter;

	$client_device_serial = $aws_mysqli->real_escape_string($device_serial);
	$client_id = $aws_mysqli->real_escape_string($id);
	$description = $aws_mysqli->real_escape_string($description);
	$client_mac = $aws_mysqli->real_escape_string($mac);
	$mdnsname = $aws_mysqli->real_escape_string($mdnsname);
	$dhcphostname = $aws_mysqli->real_escape_string($dhcphostname);
	$ip = $aws_mysqli->real_escape_string($ip);
	$vlan = $aws_mysqli->real_escape_string($vlan);
	$switchport = $aws_mysqli->real_escape_string($switchport);
	$sent = $aws_mysqli->real_escape_string($sent);
	$recv = $aws_mysqli->real_escape_string($recv);
	$timespan = $aws_mysqli->real_escape_string($timespan);

//todo add timespan in to know data xfer

	$insertFields = " client_device_serial, client_id, client_mac, client_description, client_mdnsname, client_dhcphostname, client_ip, client_vlan, client_switchport, client_sent, client_recv, timespan ";

	$queryInsertClient = "INSERT INTO meraki_clients ($insertFields) VALUES " . 
		" ( '$client_device_serial', '$client_id', '$client_mac', '$description', '$mdnsname', '$dhcphostname', '$ip', '$vlan', '$switchport', '$sent', '$recv', $timespan ); " ;

	$resultInsert = $aws_mysqli->query($queryInsertClient);
	if(!$resultInsert) {
		echo logEvent("Error $aws_mysqli->error to insert client $client_id: $queryInsertClient");
		$insertErrorCounter++; 
		return false;
	  } else {
	  	echo("Insert client success $client_id \n");
	  	$insertSuccessCounter++;
	  	return true;
	  }
} // end insertClient

echo logEvent("Insert successes: $insertSuccessCounter");
echo logEvent("Insert failures: $insertErrorCounter");
echo logEvent("Meraki mover finished")


?>
