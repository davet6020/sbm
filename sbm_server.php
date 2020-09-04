<?php

ver="0.3"

/** TODO
  Remove Password
  * Loop through all VDBs (1-4)
  Pass which VDB is being set along with the IP to the agent
  * Agent log the VDB cluster ID along with the IP
  Do one status call and load into an array
*/


  // */2 * * * * php /home/dtwiggs/sbm_server.php 2>/dev/null 1>/dev/null
  define('TH_OFFLINE', 60);  # If this many seconds behind mater, set offline
  define('TH_ONLINE', 20);   # Do not set back to online until number is this low

  $clst = array('vdb1', 'vdb2', 'vdb3', 'vdb4');
  $vdb1 = array('10.0.1.138', '10.0.2.187');
  $vdb2 = array('10.0.1.194', '10.0.2.179');
  $vdb3 = array('10.0.2.247', '10.0.2.233', '10.0.1.91', '10.0.1.171', '10.0.2.95', '10.0.2.44', '10.0.2.207', '10.0.1.24');
  $vdb4 = array('10.0.1.61', '10.0.2.253');

  foreach($clst as $cl) {

    // Looking for any that are offline.
    foreach($$cl as $v) {

    $secs = array();
    $status = $status_secs = $worst_ip = $worst_secs = $worst_status = 0;

      $status = call_agent("STATUS", $v);
      echo "$v\t$status\n";
      if($status == "OFFLINE_SOFT") {
        // Check to see if it can be turned back on.
        $status_secs = get_secs($v);
        // Put it back into rotation
        if($status_secs < TH_ONLINE) {
          $status = call_agent("ON", $v, $cl);
        } else {
          printf("$v is still $status_secs behind.  Leaving offline.\n");
          die;
        }
      }
    }

    // Looking for the one that has the most secs
    foreach($$cl as $v) {
      $secs["$v"] = get_secs($v);
    }

    // Sort descending and just grab element 0
    arsort($secs);
    foreach($secs as $key => $val) {
      $worst_ip = $key;
      $worst_secs = $val;
      break;
    }

    echo "worst: $worst_ip\t$worst_secs\n";

    // If not less than threshhold, exit.
    if($worst_secs < TH_OFFLINE) {
      printf("%s is not bad enough yet.\n", $worst_ip);
      continue;
    } else {  // Take it out of rotation
      $worst_status = call_agent("OFF", $worst_ip, $cl);
      printf("Turning OFF %s is %s\n", $worst_ip, $worst_status);
    }

  }

  function call_agent($option, $ip, $cl='') {
    $cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent $option $ip $cl\"";
    return exec($cmd);
  }

  function get_secs($ip) {
    $cmd = "mysql -uroot -p\"DelightedGoldenGoose\\$808\" -h$ip -e\"show slave status\\G\" | grep Seconds_Behind_Master | awk '{print $2}'";
    return exec($cmd);
  }

?>
