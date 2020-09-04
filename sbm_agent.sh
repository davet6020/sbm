#!/bin/bash

LOG_DATE=$(date +"%Y-%m-%d %H:%M:%S")
LOGDIR="/var/log/sbm"
LOGFIL="$LOGDIR/sbm.log"

if [ ! -d "$LOGDIR" ]; then
  /usr/bin/mkdir "$LOGDIR"
fi

if [ ! -f "$LOGFIL" ]; then
  /usr/bin/touch "$LOGFIL"
fi


# This requires two arguments. ON, OFF, or STATUS.  The 2nd argument which is the IP
# is passed to the function of the name of the first argument.  eg. ON 1.1.1.1 calls on(1.1.1.1)

# Sets IP OFFLINE_SOFT in ProxySQL
function off() {
  if [ ! -z "$1" ]; then
    mysql -u admin -padmin -h 127.0.0.1 -P6032 -e"update mysql_servers set status='OFFLINE_SOFT' where hostname = '"$1"'"
    reload
    echo "$LOG_DATE - $2: $1 set to OFFLINE_SOFT" >> "$LOGFIL"
  fi
}

# Sets IP ONLINE in ProxySQL
function on() {
  if [ ! -z "$1" ]; then
    mysql -u admin -padmin -h 127.0.0.1 -P6032 -e"update mysql_servers set status='ONLINE' where hostname = '"$1"'"
    reload
    echo "$LOG_DATE - $2: $1 set to ONLINE" >> "$LOGFIL"
  fi
}

# Gets the status of a particular replica
function status() {
  if [ ! -z "$1" ]; then
    tmp=$(mysql -u admin -padmin -h 127.0.0.1 -P6032 -e"select status from mysql_servers where hostname = '"$1"'")
    # Clean off the select column name and | and ---s
    echo "$tmp" | cut -d":" -f2 | sed 's/ //g' | tail -1
  fi
}

# If this isnt called, the on() or off() change does not take affect
function reload() {
  mysql -u admin -padmin -h 127.0.0.1 -P6032 -e"LOAD MYSQL SERVERS TO RUNTIME;"
  mysql -u admin -padmin -h 127.0.0.1 -P6032 -e"SAVE MYSQL SERVERS TO DISK;"
}


# Main()
if [ $# -ge 2 ]; then

  if [[ "$1" = "ON" ]]; then
    on "$2" "$3"
  elif [[ "$1" = "OFF" ]]; then
    off "$2" "$3"
  elif [[ "$1" = "STATUS" ]]; then
    status "$2"
  fi

fi