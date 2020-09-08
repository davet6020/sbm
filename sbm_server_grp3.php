<?php

  define('TH_OFFLINE', 300);  # If this many seconds behind master, set offline
  define('TH_ONLINE', 30);   # Do not set back to online until number is this low

  // Get DB login information
  $login = get_password();
  $user = trim(quotemeta($login[0]));
  $pass = trim(quotemeta($login[1]));

  $groups = array('vdb3_grp1', 'vdb3_grp2');
  $vdb3_grp1 = array('10.0.1.24', '10.0.1.91', '10.0.2.44', '10.0.2.207');
  $vdb3_grp2 = array('10.0.1.62', '10.0.1.77', '10.0.1.125', '10.0.1.141', '10.0.1.151');
  $max_offline = 2;
  $num_off = 0;
  $secs = array();


  // Looking for any that are offline and turns them online if they are ready.
  foreach($groups as $vdbs) {
    foreach($$vdbs as $vdb) {

      // TODO: Only call agent for status one time and get all results
      // put them in array and parse that.
      $status = call_agent("STATUS", $vdb);

      // Check status of offline replica
      if($status == "OFFLINE_SOFT") {
        $num_off++;

        $status_secs = get_secs($vdb, $user, $pass);

        // Safe to put it back into rotation
        if($status_secs < TH_ONLINE) {
          $status = call_agent("ON", $vdb);
          $num_off--;
        }

        // This means we have reached $max_offline so quit.
        if($num_off == $max_offline) {
          die;
        }

      }
    }
  }


  // Sets replicas offline if needed.
  foreach($groups as $vdbs) {
    foreach($$vdbs as $vdb) {
      $status_secs = get_secs($vdb, $user, $pass);

      if($status_secs > TH_OFFLINE) {
        if((int)$num_off <= (int)$max_offline) {
          $status = call_agent("OFF", $vdb);
          $num_off++;
        }
      }

    }
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
