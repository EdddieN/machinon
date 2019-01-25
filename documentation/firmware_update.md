# Machinon Firmware Update

The Machinon I/O board uses two microcontrollers (main and slave) to handle its I/O and internal functions. Each microcontroller has a bootloader that allows firmware updates from the host Raspberry Pi.

The helper script `fwupdate.sh` in this repository performs the update process.

## Update Procedure

### Install AVRDUDE

Install AVRDude if you don't already have at least version 6.3:

```bash
sudo apt-get install avrdude
```

and check that it works (should see AVRDude version info, with version 6.3 or later):

```bash
avrdude -v
```

### Update Main Microcontroller Firmware

1. Copy or clone the main firmware file to your Pi, into your home directory or the same directory as the `fwupdate` script. The firmware file is normally named `machinon_main.hex` or similar.

2. Update the "main" firmware (substitute the actual path and filename of the main update file):

   ```bash
   ./fwupdate.sh -m machinon_main.hex
   ```

   You should see the AVRDude progress on the terminal, and a few seconds after it finishes, the Machinon will restart and the LCD will display version and serial info.

### Update Slave Microcontroller Firmware

1. Copy or clone the slave firmware file to your Pi, into your home directory or the same directory as the `fwupdate` script. The firmware file is normally named `machinon_slave.hex` or similar.

2. Update the "slave" firmware (substitute the actual filename of the slave update file):

     ```bash
     ./fwupdate.sh -s machinon_slave.hex
     ```

     You should see the AVRDude progress on the terminal, and a few seconds after it finishes, the Machinon should restart and display version and serial info on its LCD.