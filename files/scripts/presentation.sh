#!/bin/bash

# Quick and dirty script to send "presentation" request to Machinon
# MGC / Logic Energy 2018-11-02

#arg_cmd=$1   # save the command (first argument)

presentation_command="0;1;3;0;19;1"

# config serial port for 115200 baud, 8N1, no handshaking, no echo (default for Machinon config serial port)
serialport=/dev/ttySC1
#serialport=/dev/serial3
stty -F $serialport -echo raw ispeed 115200 ospeed 115200 cs8 -crtscts
exec <> $serialport  # hold serial port open for duration of script
IFS=$'\n'       # make newline the only separator in files (default for MySensors messages as used on Machinon)

echo -e "" > $serialport    # send an LF to flush decoder
echo "Sending presentation request on port: '$serialport'"
echo $presentation_command > $serialport
echo -e "" > $serialport    # send an LF to terminate the command string

# end
