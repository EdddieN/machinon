# Raspberry Pi Software Setup for machinon

The host Raspberry Pi requires some software setup to be able to access all features of the Machinon board, such as the SPI-UART serial ports and the RTC, and operate as a headless embedded system.

The steps below are based on starting with a clean install of the official Raspian Stretch Lite 2018-04-18 SD card image.

## Raspberry Pi System Installation/Configuration

1. Write standard Raspian Lite image to SD card.
2. With SD card still in PC card reader, create new empty file `ssh` in root directory (/boot partition) to enable SSH login.
3. Edit `/boot/config.txt` and add the following lines:  
   ```
   dtoverlay=sc16is752-spi1,24
   dtoverlay=i2c-rtc,mcp7941x
   dtoverlay=pi3-act-led, gpio=26
   dtoverlay=pi3-miniuart-bt  #for Pi3 only
   dtoverlay=pi3-disable-wifi  #optional to disable wifi
   dtoverlay=pi3-disable-bt  #to disable bluetooth
   enable_uart=1`  #for Pi3 and Jessie or later
   ```
4. Edit `/boot/cmdline.txt` and remove the text `console=serial0,115200` to allow applications to use serial port. See https://www.raspberrypi.org/documentation/configuration/uart.md for more info.
5. Connect the Pi to the Machinon board with the GPIO ribbon cable and power up. Wait for the Pi to boot, then find its IP and log in via SSH (use PuTTY or similar SSH client)
6. Run raspi-config and:  
   1. Enable SPI
   1. Enable I2C
   1. Enable Serial (but disable login shell over serial) (should already be done by the previous edits to config.txt and cmdline.txt)
   1. Expand filesystem to fill card
   1. Change GPU memory to 16 MB (not essential)
7. Edit `/etc/modules` and add a new line with `rtc-mcp7941x`
8. Edit `/lib/udev/hwclock-set` and comment out (add # to start of lines) the lines:  
   ```
   if [ -e /run/systemd/system ] ; then
       exit 0
   fi
   ```
9. Reboot and check that the Pi has correct time from network. Then optionally manually set HW clock with `sudo hwclock -w` to write system time to HW clock. The Pi will automatically load the time/date from the HW clock at boot. This can can be forced manually with `sudo hwclock -r` to set the system clock from the HW clock. The Pi does an NTP update of system clock at boot, and then every 1024 secs (17 mins) thereafter, and sets the RTC from this.
10. Optionally set static IP:  
    1. Edit /etc/dhcpcd.conf and uncomment or add these lines (change to suit):  
       ```
       interface eth0
       static ip_address=192.168.1.15/24
       static routers=192.168.1.1
       static domain_name_servers=192.168.1.1
       ```
    2. Alternatively, edit the "fall back to static IP" section to make the Pi fall back to that static IP if DHCP fails.
    3. Reboot for network changes to take effect
11. Add permanent aliases for the SPI UARTs (Domoticz does not show port names like "ttySC1"):
    1. create a new udev rules file /etc/udev/rules.d/98-minibms.rules with:  
       ```
       KERNEL=="ttySC0" SYMLINK="serial2"
       KERNEL=="ttySC1" SYMLINK="serial3"
       ```
    2. Save file and reboot
    3. Check for the aliases serial2 and serial3:  
    `ls -l /dev`  
    (serial0 and serial1 are the Pi internal ports)
12. Change the default password, or optionally add a new user to run everything as instead of "pi". See https://mattwilcox.net/web-development/setting-up-a-secure-home-web-server-with-raspberry-pi for ideas.
13. Install any other desired updates, packages, etc. For Domoticz suggestions see https://www.domoticz.com/wiki/Raspberry_Pi#Raspberry_Pi_additional_software_installations

## Install NGINX with PHP
The Machinon software support includes a set of PHP web forms to make the I/O configuration simple. This requires a web server that supports PHP, such as NGINX.

See https://www.raspberrypi.org/documentation/remote-access/web-server/nginx.md and https://howtoraspberrypi.com/install-nginx-raspbian-and-accelerate-your-raspberry-web-server/ for NGINX install details.

1. Install NGINX (and php-fpm if required. On Raspian Stretch this will install PHP7.0)  
`sudo apt install nginx php-fpm`
2. Edit PHP section of NGINX conf file as below to handle php:  
   ```
   # pass PHP scripts to FastCGI server
   #
   location ~ \.php$ {
       include snippets/fastcgi-php.conf;
       #
       # With php-fpm (or other unix sockets):
       fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
       # With php-cgi (or other tcp sockets):`  
       #fastcgi_pass 127.0.0.1:9000;
   }
   ```
3. Reload config (or start server):  
`sudo service nginx restart`  
or  
`sudo nginx -s reload`
4. Copy the config forms PHP files and resources to the NGINX web pages directory, under a "config" subdirectory, and set permissions to allow NGINX to access the files and execute the .sh scripts.

## Status LEDs Control
There are two front-panel status LEDs directly connected to the 40-way GPIO header on the Machinon board. These can be controlled by Raspberry pi GPIO pins.

To indicate Raspberry Pi SD card access on the Machinon front panel red "ACT/disk" LED D4, the green "ACT" LED on the Pi3 can be redirected to the GPIO26 pin using the `pi3-act-led` overlay. See the system installation section above for details.

The "ACT" LED can also be controlled directly (NB this disables the normal "SD activity" function!):  
ON: `echo 1 | sudo tee /sys/class/leds/led0/brightness`  
OFF: `echo 0 | sudo tee /sys/class/leds/led0/brightness`

To restore the SD ACT function for LED0:  
`echo mmc0 | sudo tee /sys/class/leds/led0/trigger`

The Machinon front panel "user" green LED D5 is connected to Pi GPIO13 and can be controlled manually or from a user script:
```
# Enable GPIO 13 and set to output  
echo "13" > /sys/class/gpio/export
echo "out" > /sys/class/gpio/gpio13/direction
# Write a 1/high to GPIO13 to turn LED ON
echo "1" > /sys/class/gpio/gpio13/value
# Write a 0/low to GPIO13 to turn LED OFF
echo "0" > /sys/class/gpio/gpio13/value
# Clean up (disables GPIO pin)
echo "13" > /sys/class/gpio/unexport
```
