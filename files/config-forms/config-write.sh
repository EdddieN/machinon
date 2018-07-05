#!/bin/bash

# Machinon support script to write lines from config file to Machinon config serial port, OR read existing config from Machinon and save in file
# www.machinon.com
#
# Usage: config-write.sh <command> <NodeID>
#  <command> is:  -r     read existing config for specified node from Machinon and save to file "node<NodeID>_read.conf"
#                 -w     write new config to Machinon for specified node from file "node<NodeID>.conf"
#  <NodeID> is:   0...6  to specify corresponding node (I/O bank) on Machinon
#
#
# If used from PHP/webserver, the webserver group needs to be a member of dialout:
#     sudo usermod -a -G dialout www-data
# (or have access to tty ports as a group member)

# Write each line in a conf file to the serial port
do_write()
{
    config_file="node${arg_node}.conf"
    if [[ -e $config_file ]] ; then
        # send an LF to flush/clear the Machinon parser
        echo -e "" > $serialport
        # read each line from config file and send to serial port
        while IFS= read -r -s line; do
            echo "sending: $line"
            echo $line > $serialport
            sleep 0.05    # allow 50 msec between commands. Should really use MySensors ACK=1 to verify that command was received?
        done < $config_file
        # send an extra LF to flush last line in case file does not have trailing LF
        echo -e "" > $serialport
    else
        echo "File '${config_file}' not found!"
        exit 1
    fi
}


# Request each parameter for specified node, and save responses in a file
do_read()
{
    config_file="node${1}.read"
    if [[ -e $config_file ]] ; then
        # delete any existing file
        rm $config_file
        echo "Deleted existing file '${config_file}'"
    fi
    # send an LF to flush/clear the Machinon parser
    echo -e "" > $serialport
    # TODO also flush serial receive? Read anything already available on the port?
    if [[ $arg_node = '0' ]] ; then
        # node 0 = master/internal settings
        echo "Reading from node 0"
        echo "Child: 1"
        # node=0/child=1/type=V_VAR1 is reporting interval
        echo "${arg_node};1;2;0;24;0" > $serialport
        read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
        echo "Got: '${response}'"
        echo $response >> $config_file  # append to the file
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '1' ]] ; then
        # node 1 = DIN status inputs 1-16
        echo "Reading from node 1"
        for child_id in {1..16} ; do
            # send a request for each parameter and append responses to file
            # message format: <node_id>;<child_id>;2;0;24;0
            echo "Child: $child_id"
            echo "${arg_node};${child_id};2;0;24;0" > $serialport
            read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
            echo "Got: '${response}'"
            echo $response >> $config_file  # append to the file
        done
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '2' ]] ; then
        # node 2 = DIN counter inputs 1-16
        echo "Reading from node 2"
        for child_id in {1..16} ; do
            # send a request for each parameter and append responses to file
            # message format: <node_id>;<child_id>;2;0;<24|25|26>;0
            echo "Child: $child_id"
            for val_type in {24..26} ; do
                echo "${arg_node};${child_id};2;0;${val_type};0" > $serialport
                read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
                echo "Got: '${response}'"
                echo $response >> $config_file  # append to the file
            done
        done
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '3' ]] ; then
        # node 3 = CT inputs 1-6
        echo "Reading from node 3"
        for child_id in {1..6} ; do
            # send a request for each parameter and append responses to file
            # message format: <node_id>;<child_id>;2;0;<25|26>;0
            echo "Child: $child_id"
            for val_type in 25 26 ; do

                #echo "Sending: ${arg_node};${child_id};2;0;${val_type};0"
                echo "${arg_node};${child_id};2;0;${val_type};0" > $serialport
                read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
                echo "Got: '${response}'"
                echo $response >> $config_file  # append to the file
            done
        done
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '4' ]] ; then
        # node 4 = AIN analogue inputs 1-8
        echo "Reading from node 4"
        for child_id in {1..8} ; do
            # send a request for each parameter and append responses to file
            # message format: <node_id>;<child_id>;2;0;<24|25|26>;0
            echo "Child: $child_id"
            for val_type in {24..26} ; do

                #echo "Sending: ${arg_node};${child_id};2;0;${val_type};0"
                echo "${arg_node};${child_id};2;0;${val_type};0" > $serialport
                read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
                echo "Got: '${response}'"
                echo $response >> $config_file  # append to the file
                #sleep 0.025    # allow 25 msec between commands.
            done
        done
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '5' ]] ; then
        # node 5 = DOUT 1-16
        echo "Reading from node 5"
        for child_id in {1..16} ; do
            # send a request for each parameter and append responses to file
            # message format: <node_id>;<child_id>;2;0;24;0
            echo "Child: $child_id"
            echo "${arg_node};${child_id};2;0;24;0" > $serialport
            read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
            echo "Got: '${response}'"
            echo $response >> $config_file  # append to the file
        done
        echo "Wrote to file '${config_file}'"

    elif [[ $arg_node = '6' ]] ; then
        # node 6 = front panel
        echo "Reading from node 6"
        # send a request for each parameter and append responses to file
        # message format: <node_id>;<child_id>;2;0;<25|26>;0
        echo "Child: 11"
        echo "${arg_node};11;2;0;24;0" > $serialport
        read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
        echo "Got: '${response}'"
        echo $response >> $config_file  # append to the file

        echo "Wrote to file '${config_file}'"
    else
        echo "Bad NodeID: $arg_node"
    fi
}


arg_cmd=$1   # save the command (first argument)
arg_node=$2  # save the node number

# config serial port for 115200 baud, 8N1, no handshaking, no echo (default for Machinon config serial port)
serialport=/dev/ttySC1
#serialport=/dev/serial3
stty -F $serialport -echo raw ispeed 115200 ospeed 115200 cs8 -crtscts
exec <> $serialport  # hold serial port open for duration of script
IFS=$'\n'       # make newline the only separator in files (default for MySensors messages as used on Machinon)

# check the node ID argument for valid range (single digit 0-6)
if [[ $arg_node =~ ^([0-6]{1})$ ]] ; then
    echo "NodeID: $arg_node"
    if [[ $arg_cmd = "-w" ]] ; then
        do_write $arg_node
    elif [[ $arg_cmd = "-r" ]] ; then
        do_read $arg_node
    else
        echo "Bad command: '${arg_cmd}'"
        exit 1
    fi
else
    echo "Bad NodeID: $arg_node"
    exit 1
fi

# end
