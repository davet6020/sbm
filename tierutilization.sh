#!/bin/bash

USER=`sed -n "1p" /etc/mysql/mysql.ini`
PASS=`sed -n "2p" /etc/mysql/mysql.ini`

# Update this whenever you make a change.
VERSION=0.1.3

# Date info was logged
LOG_DATE=$(date --date="0 days ago" +"%Y-%m-%d_%H:%M:%S")

environment=('us' 'eu' 'ca')
tiersus=('app' 'bizops' 'cassandra' 'community' 'customworker' 'elk' 'glusterfs' 'haproxy' 'ids' 'indexingcluster1' 'indexingcluster2' 'indexingcluster3' 'indexingcluster4' 'internal' 'it' 'kubenodes' 'logging' 'loggingv2' 'mysql' 'pmta' 'proxysqlha' 'puppet' 'responseprocessor' 'webroids' 'worker')
tierseu=('app' 'cassandra' 'haproxy' 'ids' 'indexingcluster1' 'internal' 'it' 'loggingv2' 'mysql' 'nat' 'proxysqlha' 'web' 'worker')
stats=('cpu' 'df' 'io' 'load' 'mem')

if [ "$1" = "-v" ]; then
   echo version $VERSION
   exit
fi

# If they specify 'all' accept it and force $2 to also = all
if [ "$1" = "all" ]; then
   runenv="Production"
   tiersus+=('all')
   stats+=('all')
   # The next command keeps argument $1 as is but forces $2 = "all"
   # set -- "${@:1}" "all"
fi

if [ "$1" = "-h" ]; then
   echo
   echo "Tier Utilization"
   echo "Requires two arguments, tier and stat.  eg tu worker cpu"
   echo "Possible Arguments"
   echo "TiersUS: ${tiersus[*]}"
   echo "TiersEU: ${tierseu[*]}"
   echo "Stats: ${stats[*]}"
   echo
   exit
fi

if [ -z "$1" ]; then
      echo "For what environment?  ${environment[*]}"
      exit
else
      # A environment (us, eu) was specified, but for what environments tier?
      if [ "$1" = "us" ]; then
         workingtier=${tiersus[*]}
      else
         workingtier=${tierseu[*]}
      fi
fi

if [ -z "$2" ]; then
   echo "For what tier?  ${workingtier[*]}"
   exit
fi

if [ -z "$3" ]; then
      echo "What stat? ${stats[*]}"
      exit
fi


# Make sure the tier specified is real
if [[ ! " ${workingtier[@]} " =~ " ${2} " ]]; then
    echo "For what tier? ${workingtier[*]}"
    exit
fi

# Make sure the stat specified is real
if [[ ! " ${stats[@]} " =~ " ${3} " ]]; then
    echo "What stat? ${stats[*]}"
    exit
fi

# The file to write to.  Make it the vdb cluster name eg vdbc1_vdb1
OUTPUTDIR="/opt/tier_utilisation/"
OUTPUTFILE=$OUTPUTDIR$1_$LOG_DATE.out

# Convert short name of environment to what it is on the ssh_menu list
case "$1" in
   us) runenv="Production"
         ;;
   eu) runenv="ProductionEU"
         ;;
   ca) runenv="ProductionCA"
       echo "Sorry, you cannot run Canada from this puppet server."
         exit
         ;;
   *)  exit
         ;;
esac

# Process the command for output
case "$3" in
   cpu) sudo ssh_menu_root $runenv $2 cmd "echo; hostname -i; iostat -c | grep -vE 'Linux|^$'"
         exit
         ;;
   df) sudo ssh_menu_root $runenv $2 cmd "echo; hostname -i; df -h | grep -vE 'tmpfs'"
         exit
         ;;
   io) sudo ssh_menu_root $runenv $2 cmd "echo; hostname -i; iostat -dm | grep -vE 'Linux|^$'"
         exit
         ;;
   load) sudo ssh_menu_root $runenv $2 cmd "echo; hostname -i; uptime"
         exit
         ;;
   mem) sudo ssh_menu_root $runenv $2 cmd "echo; hostname -i; free | head -2"
         exit
         ;;
   all) echo "you are doing it all"
         # Remove "all" from the array as it is not a real tier.
         tiersus=('app' 'bizops' 'cassandra' 'community' 'customworker' 'elk' 'glusterfs' 'haproxy' 'ids' 'indexingcluster1' 'indexingcluster2' 'indexingcluster3' 'indexingcluster4' 'internal' 'it' 'kubenodes' 'logging' 'loggingv2' 'mysql' 'pmta' 'proxysqlha' 'puppet' 'responseprocessor' 'webroids' 'worker')
         for tier in "${workingtier[@]}"; do
            echo "Tier: $tier"
            sudo ssh_menu_root $runenv $tier cmd "echo; hostname -i; iostat -c | grep -vE 'Linux|^$'"
            sudo ssh_menu_root $runenv $tier cmd "echo; hostname -i; df -h | grep -vE 'tmpfs'"
            sudo ssh_menu_root $runenv $tier cmd "echo; hostname -i; iostat -dm | grep -vE 'Linux|^$'"
            sudo ssh_menu_root $runenv $tier cmd "echo; hostname -i; uptime"
         done
         exit
         ;;
   *) exit
         ;;
esac


