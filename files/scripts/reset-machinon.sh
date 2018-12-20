#!/bin/bash

# Machinon support script for MCU software reset via serial command or hardware reset via Raspberry Pi GPIO pulse
# www.machinon.com
#
# Usage:
#   reset-machinon.sh -m      Reset the Machinon main microcontroller and trigger its bootloader
#   reset-machinon.sh -s      Reset the Machinon slave microcontroller and trigger its bootloader
#

# config serial port for 115200 baud, 8N1, no handshaking (default for Machinon config serial port)
serialport=/dev/ttySC1
stty -F $serialport raw ispeed 115200 ospeed 115200 cs8 -crtscts
exec <> $serialport  # hold serial port open for duration of script
IFS=$'\n'       # make newlines the only separator in files (default for MySensors messages as used on MiniBMS/Machinon2)
# send a couple of LF to flush/clear the Machinon parser
echo -e "\n" > $serialport

if [[ "$1" = "-s" ]] ; then
    # send command to reset slave
    echo "Resetting Machinon Slave AVR..."

    # send MySensors request to Main AVR for it to reset Slave AVR
    echo "0;1;1;1;25;2" > $serialport
    # expect an ACK reply with same message content
    read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; exit 1; }
    if [[ "$response" = "0;1;1;1;25;2" ]] ; then
        echo "Slave reset OK"
        exit 0
    else
        echo "Slave reset failed!"
        exit 1
    fi
elif [[ "$1" = "-m" ]] ; then
    echo "Resetting Machinon Main AVR..."

    # send MySensors request to Main AVR for it to reset itself
    echo "0;1;1;1;25;1" > $serialport
    # expect an ACK reply with same message content
    read -t 0.5 -s response < $serialport || { echo "Timeout getting response"; }
    if [[ "$response" = "0;1;1;1;25;1" ]] ; then
        echo "Main software reset OK"
        exit 0
    else
        # fall back to direct hardware reset of Main AVR
        echo "Main software reset failed! Performing hardware reset."

        # Enable GPIO 23 and set to output
        echo "23" > /sys/class/gpio/export
        sleep 0.5  # allow time for filesystem changes
        echo "out" > /sys/class/gpio/gpio23/direction
         
        # Write a high pulse to GPIO23
        echo "1" > /sys/class/gpio/gpio23/value
        sleep 0.05
        echo "0" > /sys/class/gpio/gpio23/value

        # Clean up
        echo "23" > /sys/class/gpio/unexport
    fi
else
    echo "  Usage:"
    echo "    reset-machinon.sh -m      Reset the Machinon main microcontroller and trigger its bootloader"
    echo "    reset-machinon.sh -s      Reset the Machinon slave microcontroller and trigger its bootloader"
fi
#end
