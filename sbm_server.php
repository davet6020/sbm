<?php


  $debug_mode = TRUE;

  // Get DB login information
  $login = get_password();

  define('DEBUG_MODE', FALSE); // If true outputs to the console, otherwise does not.
  define('TH_OFFLINE', 300);  // If this many seconds behind master, set offline
  define('TH_ONLINE', 30);    // Do not set back to online until number is this low

  // The groups in which to loop through and process one at a time
  $groups = array('vdb3');

  // These IPs are what is in each $groups
  $vdb3 = array('10.0.1.125', '10.0.1.151', '10.0.2.86', '10.0.2.139', '10.0.2.148');
  $vdb3_grp2 = array('10.0.1.62', '10.0.1.77', '10.0.1.125', '10.0.1.141', '10.0.1.151');
  $vdb3_grp3 = array('10.0.2.86', '10.0.2.120', '10.0.2.139', '10.0.2.148', '10.0.2.225');

  // Each vdb_grp must have its own configs
  $vdb3_config['max_offline'] = 1;
  $vdb3_grp2_config['max_offline'] = 1;
  $vdb3_grp3_config['max_offline'] = 2;

  // Initialize some arrays
  $stats = array();   // Create array [ip, status, sbm]


  foreach($groups as $vdbs) {
    // Build an array of stats for each group to work with
    $stats = get_stats($vdbs, $$vdbs, $login);

    if(DEBUG_MODE) {
      for($i=0; $i<count($stats); $i++) {
        echo $stats[$i]['vdb'] . "\t" . $stats[$i]['secs_behind'] . "\t" . $stats[$i]['ps_status'] . "\n";
      }
    }

    // Looking for any that are offline and turns them online if they are ready.
    $num_off = follow_up($$vdbs, $stats, $login);

    // eg for vdb3_grp2, config = vdb3_grp2_config. So, ${$config}['max_offline'] is an integer
    $config = $vdbs . '_config';  // name of the config array for this vdbs group

    // If this vdb group already has the max number of offline replicas, exit
    if($num_off >= ${$config}['max_offline']) {
      return;
    }

    /*
      set_offline looks at all of the hosts in $stats for ones that
        are online to see if they should be offline
     */
    set_offline($$config, $num_off, $stats, $login);

  }


  /*
    Check to see if an ONLINE replica should be turned off
   */
  function set_offline($config, $num_off, $stats, $login) {

    foreach($stats as $stat) {
      if(strstr($stat['ps_status'], 'ONLINE')) {

        // ONLINE but should be taken out of rotation
        if($stat['secs_behind'] > TH_OFFLINE) {

          // Cant take any more out of rotation this cycle.
          if($num_off >= $config['max_offline']) {
            return;
          }

          $status = call_agent("OFF", $stat['vdb']);
          $num_off++;
        }
      }
    }

  }


  /*
    Sends calls to the proxysql agent to get replica stats, or turn it on or off
   */
  function call_agent($option, $ip) {
    $cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent $option $ip\"";
    return exec($cmd);
  }


  /*
    Check each vdb in this group to see if it is offline
    If it is offline, check to see if it should STILL be offline
    If it should be online, set it back online.
   */
  function follow_up($group, $stats, $login) {
    $num_off = 0;

    foreach($stats as $stat) {
      if(strstr($stat['ps_status'], 'OFFLINE')) {
        $num_off++;

        // OFFLINE but should be put back in rotation
        if($stat['secs_behind'] < TH_ONLINE) {
          $status = call_agent("ON", $stat['vdb']);
          $num_off--;
        }
      }
    }

    return $num_off;
  }


  /*
    Gets the number of seconds behind the master
   */
  function get_secs($ip, $login) {
    $user = trim(quotemeta($login[0]));
    $pass = trim(quotemeta($login[1]));
    $cmd = "mysql -u$user -p$pass -h$ip -e\"show slave status\\G\" | grep Seconds_Behind_Master | awk '{print $2}'";
    return exec($cmd);
  }


  /*
    Pulls the username and password from an external file so its not in this program
   */
  function get_password() {
    $arr = file('/usr/local/bin/mysql.ini');
    return $arr;
  }


  /*
    Gets the stats for all servers in a group so we minimize
    the number of external calls.
   */
  function get_stats($group, $vdbs, $login) {
    $i=0;
    foreach($vdbs as $vdb) {
      $ps_status = call_agent("STATUS", $vdb);
      $status_secs = get_secs($vdb, $login);

      $stats[$i]['vdb'] = $vdb;
      $stats[$i]['ps_status'] = $ps_status;
      $stats[$i]['secs_behind'] = $status_secs;
      $i++;
    }

    return $stats;
  }

?>
