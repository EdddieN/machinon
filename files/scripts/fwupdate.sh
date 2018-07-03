#!/bin/bash

# Support script to update Machinon main or slave microcontroller firmware using AVRDUDE
# www.machinon.com
#
# Usage: fwupdate.sh {-m|-s} [hexfile]
#
# -m specifies to program Main AVR firmware
# -s specifies to program Slave AVR firmware
# <hexfile> parameter is optional and specifies the HEX file to use, eg "fwupdate.sh -m myfile.hex"
#

# TODO automatically find the script/files
reset_script="reset-machinon.sh"
fw_slave="machinon_slave.hex"
fw_main="machinon_main.hex"

if [[ "$1" = "-m" ]] ; then
    # Main AVR bootload
    # if hexfile argument is blank, then use default
    if [[ "$2" = "" ]] ; then
        hexfile=$fw_main
        echo "Using default HEX file '${hexfile}'"
    else
        hexfile=$2   # save the command (2nd argument)
    fi

    if [[ -f "${reset_script}" ]] ; then
        bash ${reset_script}
        sleep 0.5  # allow AVR bootloader to start
    else
        echo "'${reset_script}' not found! Manual AVR reset required."
    fi

    # program using AVRDUDE and default settings for RPi and Machinon
    avrdude -v -p atxmega256a3u -c avr109 -P /dev/ttySC1 -b 115200 -U flash:w:$hexfile:i -e
elif [[ "$1" = "-s" ]] ; then
    # Slave AVR bootload
    # if hexfile argument is blank, then use default
    if [[ "$2" = "" ]] ; then
        hexfile=$fw_slave
        echo "Using default HEX file '${hexfile}'"
    else
        hexfile=$2   # save the command (2nd argument)
    fi

    if [[ -f "${reset_script}" ]] ; then
        bash ${reset_script} -s
        if [[ $? -ne 0 ]] ; then
            # slave reset failed
            echo "Slave bootload failed!"
            exit 1
        fi
        sleep 0.5  # allow AVR bootloader to start
    else
        echo "'${reset_script}' not found! Manual AVR reset required."
    fi

    # program using AVRDUDE and default settings for RPi and Machinon
    avrdude -v -p atxmega64a3u -c avr109 -P /dev/ttySC1 -b 115200 -U flash:w:$hexfile:i -e
    if [[ $? -ne 0 ]] ; then
        echo "Slave bootload failed! Rebooting Main AVR..."
        bash ${reset_script}
    fi
    # TODO check if main AVR restarted, and force reboot if required?
else
    echo "Usage: fwupdate.sh {-m|-s} [hexfile]"
    echo "-m specifies to program Main AVR firmware"
    echo "-s specifies to program Slave AVR firmware"
    echo "<hexfile> parameter is optional and specifies the HEX file to use, eg 'fwupdate.sh -m myfile.hex'"
fi
# end
