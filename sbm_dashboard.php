<?php

// while [ true ]; do clear; php sbm_dashboard.php ; sleep 60; echo; php sbm_dashboard.php; sleep 60; done

// $clst = array('vdb1', 'vdb2', 'vdb3', 'vdb4');
$clst = array('vdb3');
$vdb1 = array('10.0.1.138', '10.0.2.187');
$vdb2 = array('10.0.1.194', '10.0.2.179');
$vdb3 = array('10.0.2.247', '10.0.2.233', '10.0.1.91', '10.0.1.171', '10.0.2.95', '10.0.2.44', '10.0.2.207', '10.0.1.24');
$vdb4 = array('10.0.1.61', '10.0.2.253');

foreach($clst as $cl) {
	head($cl);

	foreach($$cl as $v) {
		$status = call_agent("STATUS", $v);
		$secs = get_secs($v);
		echo "$v\t$status\t$secs\n";
	}
	echo "\n";
}

function head($cl) {
	$dte = date("Y-m-d h:i:sa");
	echo "$cl - $dte\n";
	echo "----------------------------\n";
}

function call_agent($option, $ip) {
	$cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent $option $ip\"";
	return exec($cmd);
}

function get_secs($ip) {
	$cmd = "mysql -uroot -p\"DelightedGoldenGoose\\$808\" -h$ip -e\"show slave status\\G\" | grep Seconds_Behind_Master | awk '{print $2}'";
	return exec($cmd);
}

?>
