<?php
  // argv[0] is the name of the program and not used.
  // argv[1] is option on/off
  // argv[2] is IP Address of Replica
  // argv[3] is the vdb cluster nanme for the agent to log

  // If all 3 arguments not passed, we die;
  if(count($argv) < 4) {
    help();
    die;
  }

  switch(strtoupper($argv[1])) {
    case 'OFF':
      $status = call_agent('OFF', $argv[2], $argv[3]);
      echo "$argv[2] is $status\n";
      break;
    case 'ON':
      $status = call_agent('ON', $argv[2], $argv[3]);
      echo "$argv[2] is $status\n";
      break;
    default:
      // Maybe later return a status but not today.
  }


  function call_agent($option, $ip, $cl='') {
    $cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent $option $ip $cl\"";
    exec($cmd);
    $cmd = "/usr/local/sbin/ssh_menu_root production proxysqlha cmd \"/usr/local/bin/sbm_agent STATUS $ip $cl\"";
    return exec($cmd);
  }


  function help() {
    echo "psadmin requires 3 arguments\n";
    echo "psadmin OPTION IPADDRESS VDB_CLUSTER_NAME\n";
    echo "eg. psadmin on 10.0.0.0 vdb3\n\n";
  }

?>
