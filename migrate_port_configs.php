#!/usr/bin/env php
<?php

// Global Vars
$device_file = "mydevices.txt";

$obs_servername = "<observium_server>";
$obs_username = "<observium_user>";
$obs_password = "<observium_password>";
$obs_dbname = "observium";

$lib_servername = "<libre_server>";
$lib_username = "<libre_user>";
$lib_password = "libre_password";
$lib_dbname = "librenms";

//
// Begin Work
//

// Create connection
$obs_conn = new mysqli($obs_servername, $obs_username, $obs_password, $obs_dbname);
$lib_conn = new mysqli($lib_servername, $lib_username, $lib_password, $lib_dbname);

// Check connection
if ($obs_conn->connect_error) { die(" Observium Connection failed: " . $obs_conn->connect_error); }
if ($lib_conn->connect_error) { die(" LibreNMS Connection failed: " . $lib_conn->connect_error); }

// Read in devices file
$devices = file($device_file , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) or die("Unable to connect to open file $device_file \n");

// Loop through devices in device file
foreach( $devices as $device_name ) {

	// Get device_id from both environments
	$obs_sql = "SELECT devices.device_id,devices.hostname FROM devices WHERE hostname = '$device_name'";
	$obs_result = $obs_conn->query($obs_sql);
	$lib_sql = "SELECT devices.device_id,devices.hostname FROM devices WHERE hostname = '$device_name'";
	$lib_result = $lib_conn->query($lib_sql);

	while($obs_row = $obs_result->fetch_assoc()) { $obs_dev_id = $obs_row["device_id"]; }
	while($lib_row = $lib_result->fetch_assoc()) { $lib_dev_id = $lib_row["device_id"]; }

	// Get ports info for both environments
	$obs_sql = "SELECT ports.port_id,ports.ifIndex,ports.ignore,ports.disabled,ports.detailed,ports.deleted FROM ports WHERE device_id = $obs_dev_id";
	$obs_result = $obs_conn->query($obs_sql);
	$lib_sql = "SELECT ports.port_id,ports.ifIndex,ports.ignore,ports.disabled,ports.detailed,ports.deleted FROM ports WHERE device_id = $lib_dev_id";
	$lib_result = $lib_conn->query($lib_sql);

	while( ($obs_row = $obs_result->fetch_assoc() ) && ( $lib_row = $lib_result->fetch_assoc() ) ) {
			$obs_port_results[$obs_row["ifIndex"]] = array($obs_row["port_id"],$obs_row["ignore"],$obs_row["disabled"],$obs_row["detailed"],$obs_row["deleted"]);	
		$lib_port_results[$lib_row["ifIndex"]] = array($lib_row["port_id"],$lib_row["ignore"],$lib_row["disabled"],$lib_row["detailed"],$lib_row["deleted"]);
	}

	// Update ports info in LibreNMS
	$obs_keys = array_keys($obs_port_results);
	$lib_keys = array_keys($lib_port_results);
	for($i = 0; $i < count($lib_port_results); $i++) {
		
		// Ignore field
		if ( !($lib_port_results[$lib_keys[$i]][1] == $obs_port_results[$obs_keys[$i]][1]) ) {
			echo "Updating 'Ignore' to " . $obs_port_results[$obs_keys[$i]][1] . " for port-ID " . $lib_port_results[$lib_keys[$i]][0] . " for device $device_name\n";
			$lib_sql = "UPDATE ports SET ports.ignore = " . $obs_port_results[$obs_keys[$i]][1] . " WHERE ports.device_id = $lib_dev_id AND ports.port_id = " . $lib_port_results[$lib_keys[$i]][0];
			if ( !($lib_conn->query($lib_sql) === TRUE) ) { echo "Error updating ignore record: " . $lib_conn->error; }
		}

		// Disabled Field
		if ( !($lib_port_results[$lib_keys[$i]][2] == $obs_port_results[$obs_keys[$i]][2]) ) {
			echo "Updating 'Disabled' to " . $obs_port_results[$obs_keys[$i]][2] . " for port-ID " . $lib_port_results[$lib_keys[$i]][0] . " for device $device_name\n";
			$lib_sql = "UPDATE ports SET ports.disabled = " . $obs_port_results[$obs_keys[$i]][2] . " WHERE ports.device_id = $lib_dev_id AND ports.port_id = " . $lib_port_results[$lib_keys[$i]][0];
			echo $lib_sql . "\n";
			if ( !($lib_conn->query($lib_sql) === TRUE) ) { echo "Error updating disabled record: " . $lib_conn->error; }
		}
		
		// Detailed Field
		if ( !($lib_port_results[$lib_keys[$i]][3] == $obs_port_results[$obs_keys[$i]][3]) ) {
			echo "Updating 'Detailed' to " . $obs_port_results[$obs_keys[$i]][3] . " for port-ID " . $lib_port_results[$lib_keys[$i]][0] . " for device $device_name\n";
			$lib_sql = "UPDATE ports SET ports.detailed = " . $obs_port_results[$obs_keys[$i]][3] . " WHERE ports.device_id = $lib_dev_id AND ports.port_id = " . $lib_port_results[$lib_keys[$i]][0];
			if ( !($lib_conn->query($lib_sql) === TRUE) ) { echo "Error updating detailed record: " . $lib_conn->error; }
		}
		
		// Deleted Field
		if ( !($lib_port_results[$lib_keys[$i]][4] == $obs_port_results[$obs_keys[$i]][4]) ) {
			echo "Updating 'Deleted' to " . $obs_port_results[$obs_keys[$i]][4] . " for port-ID " . $lib_port_results[$lib_keys[$i]][0] . " for device $device_name\n";
			$lib_sql = "UPDATE ports SET ports.deleted = " . $obs_port_results[$obs_keys[$i]][4] . " WHERE ports.device_id = $lib_dev_id AND ports.port_id = " . $lib_port_results[$lib_keys[$i]][0];
			if ( !($lib_conn->query($lib_sql) === TRUE) ) { echo "Error updating deleted record: " . $lib_conn->error; }
			}	
	}

}

// Close Connection
$obs_conn->close();
$lib_conn->close();

?>