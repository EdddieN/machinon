#!/bin/bash

# Quick and dirty Machinon command sender
# MGC / Logic Energy 2018-07-11

arg_cmd=$1   # save the command (first argument)

# config serial port for 115200 baud, 8N1, no handshaking, no echo (default for Machinon config serial port)
serialport=/dev/ttySC1
#serialport=/dev/serial3
stty -F $serialport -echo raw ispeed 115200 ospeed 115200 cs8 -crtscts
exec <> $serialport  # hold serial port open for duration of script
IFS=$'\n'       # make newline the only separator in files (default for MySensors messages as used on Machinon)

# check the cmd argument for a MySensors format (single digit 0-6 then ";xx;x;x;xx;x..." )
if [[ $arg_cmd =~ ^([0-6]{1};[0-9]{1,2};[0-9]{1};[0-9]{1};[0-9]{1,2};.+)$ ]] ; then
	echo -e "" > $serialport    # send an LF to flush decoder
	echo "Sending: '$arg_cmd'"
	echo $arg_cmd > $serialport
	echo -e "" > $serialport    # send an LF to terminate the command string

	read -t 0.5 -s response < $serialport || { echo "No response"; exit 1; }
	echo "Got response: '${response}'"
	#exit 0
else
	echo "No command or invalid command: '$arg_cmd'"
	echo
	echo "Usage: $0 '1;2;3;4;5;6'"
	echo "       where '1;2;3;4;5;6' is any valid MySensors command enclosed in quotes."
	exit 1
fi

# end
