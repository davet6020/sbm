<?php

// while [ true ]; do clear; php sbm_dashboard.php ; sleep 60; echo; php sbm_dashboard.php; sleep 60; done

	// Get DB login information
	$login = get_password();
	$user = trim(quotemeta($login[0]));
	$pass = trim(quotemeta($login[1]));

	$clst = array('vdb1', 'vdb2', 'vdb3_grp0', 'vdb3_grp1', 'vdb3_grp2', 'vdb4');
	$vdb1 = array('10.0.1.138', '10.0.2.187');
	$vdb2 = array('10.0.1.194', '10.0.2.179');
	$vdb3_grp0 = array('10.0.1.171', '10.0.2.95', '10.0.2.233', '10.0.2.247');
	$vdb3_grp1 = array('10.0.1.24', '10.0.1.91', '10.0.2.44', '10.0.2.207');
	$vdb3_grp2 = array('10.0.1.62', '10.0.1.77', '10.0.1.125', '10.0.1.141', '10.0.1.151');

	$vdb4 = array('10.0.1.61', '10.0.2.253');


	foreach($clst as $cl) {
		head($cl);

		foreach($$cl as $v) {
			$status = call_agent("STATUS", $v);
			$secs = get_secs($v, $user, $pass);
			echo "$v\t$status\t$secs\n";
		}
		echo "\n";
	}

	function head($cl) {
		$dte = date("Y-m-d h:i:sa");
		echo "$cl - $dte\n";
		echo "---------------------------------\n";
	}

	function call_agent($option, $ip) {
		$cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent $option $ip\"";
		return exec($cmd);
	}

	function get_secs($ip, $user, $pass) {
		$cmd = "mysql -u$user -p$pass -h$ip -e\"show slave status\\G\" | grep Seconds_Behind_Master | awk '{print $2}'";
		return exec($cmd);
	}

	function get_password() {
		$arr = file('/usr/local/bin/mysql.ini');
		return $arr;
	}

?>
