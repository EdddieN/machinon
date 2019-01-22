
# Installing Machinon (I) - Main setup

- This guide covers the main Machinon setup, which is mandatory and provides all the functionality to use your Machinon in a local network environment.

## Install Raspbian

NOOBS is a very simple way to create a bootable Raspbian linux SD card from any OS (windows, osx, etc...) as it only implies downloading a zip file and unzipping it into a fresh formatted SD card. 
However, if you plan to clone your SD card after you finish this installation (using `dd` or any other SD cloning software), to use it into multiple Raspberries, creating a traditional Raspbian image SD card is recommended.

#### Using Noobs

1. Download NOOBS installer from 
https://www.raspberrypi.org/downloads/noobs/
2. Formatting SD Card in fat32 with SDFormatter (Mac) or Windows similar tool.
3. Unzip NOOBS zip file into SD card. Ensure the SD card's root folder shows a bunch of files. If the root folder contains only a NOOBS folder, you've done wrong. Check the INSTRUCTIONS-README.txt file inside the NOOBS folder for detailed explanation.
4. Eject SD card safely, put it into Raspberry, attach keyboard, network cable, monitor, etc... and boot.
5. A very easy installation wizard will appear, choose to install Raspbian Lite and follow instructions. You may need to configure your WiFi if not using cabled networking with DHCP. 
6. The installation takes some minutes depending of your network connection.

#### Using Raspbian image

1. Download the Raspbian Stretch Lite OS .zip image from the Raspbian site.
2. "Burn" the image into SD card using one of the various tools available on internet. For OSX I used `balenaEtcher`, which can load  .iso, .img and even .zip images into cards.
3. Eject SD card safely, put it into Raspberry, attach keyboard, network cable, monitor, etc... and power it on.


### Post installation setup:

Once rebooted and the screen shows the classic linux login.
Use username `pi` with password `raspberry` to login.

Run the Raspbian configuration tool

```
sudo raspi-config
```

0. Update raspi-config tool (8th option)
1. Set new password for user 'pi', chose anything you want.
2. Network options :
* N1 - Set hostname to something like `machinonNN` where NN is a number.
* N3 - Do not enable predictable network interface names.
* N2 - You can enable WiFi and connect to internet through it**

** agent-machinon uses the eth0 interface MAC Address as Re:Machinon's *MUID* (Machinon Unit ID). So, even if you are using WiFI to access internet with the Raspberry, don't disable eth0 interface.

3. Boot options : 
* Boot on console (WITHOUT autologin)
4. Localization options :
* Timezone
	* Choose "None of the above", then  UTC
* Locales
	* Usually choosing EN_GB@UTC-8 is fine. Raspbian detects your location so, if you use another locale, install it to avoid Raspbian dropping Locale error messages. 
* WiFi Country
	* Set your country here in case you'll use WiFi. 
5. Interfacing options (based on Matthew's instructions)
* Enable SPI
*  Enable I2C
*  Enable Serial 
	* Do NOT enable login shell over serial.
* Enable remote command line through SSH. 
	* Not required but it allows to do the rest of the setup through SSH, so you can detach the Raspberry from monitor/keyboard/etc...
6. Advanced options :
*  Expand filesystem to fill card. 
	* If you installed Raspbian using NOOBS this step is not required.
	* Latest Raspbian image also performs this procedure during the first boot.
*  Change GPU memory to 16 MB

The program will ask you to reboot, select "Yes"

### Updating the SO

Once you login again in the Pi, update the operative system. Answer yes (Y) if the commands ask for confirmation.

```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get dist-upgrade
sudo apt-get clean
```

### Network address setup

At this step, your Pi probably already has an dynamic IP assigned by DHCP.
To know this IP run: 

```ip route get 1 | awk '{print $NF;exit}'```

And note down the IP returned, you'll need it to open the web server pages later. 

*Through this document we will use 192.168.1.15 as a sample IP address, this address may be different on your device, though.*

### == OPTIONAL == Setting an static IP address 

Optionally you can set an static IP address.
Doing this step implies you know your network settings, setting a wrong IP address could leave your Raspberry unaccesable.

```
sudo nano /etc/dhcpcd.conf
```

Uncomment or add the following lines, using a proper IP settings.
As an example we are using a 192.168.1.x network range, but you must choose an IP that suits your network settings:

```
interface eth0
static ip_address=192.168.1.15/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1
```

Reboot to apply the network changes
```
sudo reboot
```

#### Accessing your Raspberry through SSH

You can access your Raspberry through SSH instead of having it attached to a monitor, keyboard, etc...

### Adding system overlays to boot config

```
sudo nano /boot/config.txt
```

Add the following lines. 
**enable_uart** is probably already enabled in the file due to the raspi-config setup

```
# Enable UART Pi3 and Jessie or later
enable_uart=1
dtoverlay=sc16is752-spi1,24
dtoverlay=i2c-rtc,mcp7941x
dtoverlay=pi3-act-led, gpio=26
# Change BT to mini-uart (Pi3 only)
dtoverlay=pi3-miniuart-bt
# Optionally disable wifi
dtoverlay=pi3-disable-wifi
# Optionally disable bluetooth
dtoverlay=pi3-disable-bt
```
  
### Disable bluetooth service from starting 

This prevents service startup errors:

```
sudo systemctl disable hciuart
```

### Allow applications to use serial port

```
sudo nano /boot/cmdline.txt
``` 
If appears, remove the following text `console=serial0,115200` 
See [https://www.raspberrypi.org/documentation/configuration/uart.md](https://www.raspberrypi.org/documentation/configuration/uart.md) for more info.

### Adding modules

```
sudo nano /etc/modules
``` 
 
 Add a new line with  `rtc-mcp7941x`

### Setting hardware clock

```
sudo nano /lib/udev/hwclock-set
```

Comment out (add # to start of lines) the following lines:

```
if [ -e /run/systemd/system ] ; then
    exit 0
fi 
```

Reboot to apply all previous changes
```
sudo reboot
```

Check that the Pi has correct time from network with `date` (keep in mind we are using UTC time zone). 

Optionally manually set HW clock with  `sudo hwclock -w`  to write system time to HW clock. The Pi will automatically load the time/date from the HW clock at boot. This can can be forced manually with  `sudo hwclock -r`  to set the system clock from the HW clock. 

The Pi does an NTP update of system clock at boot, and then every 1024 secs (17 mins) thereafter, and sets the RTC from this.

### Aliasing serial ports nodes

Add permanent aliases for the SPI UARTs (Domoticz does not show port names like "ttySC1", so here we create aliases to "serial2" for RS485 and "serial3" for machinon config).
```
sudo nano /etc/udev/rules.d/98-minibms.rules
```
Put the following content on the file
```
KERNEL=="ttySC0" SYMLINK="serial485"
```

Reboot and after that check  `ls -l /dev`  command to ensure serial0 and serial1 appear in the results as aliases for the Pi internal ports.

## Install Domoticz

`curl -L install.domoticz.com | sudo bash`

An install wizard will run after a few minutes:
* Enable both `HTTP` and `HTTPS` access 
* Set HTTP port  `8080 `
* Set HTTPS port `4443`
* Set installation folder into `/opt/domoticz`

Domoticz is installed as a service, to control it you can run the usual systemctl commands.
```
sudo service domoticz.sh start|stop|restart|status|etc...
```

In case of errors you can run Domoticz from command line for testing and interactive output:
```
cd /opt/domoticz
./domoticz
```

### ==OPTIONAL== : Showing your IP on the Machinon LCD screen

If you are using DHCP IP assignment, this will help you quickly know the current IP of your Machinon.

```sudo nano /opt/domoticz/scripts/get-ip.sh```

Put the following code on the file, save and exit

```
#!/bin/bash
 
# Print the local IP address associated with the default route (i.e. the main network interface IP)
# See https://stackoverflow.com/questions/13322485/how-to-get-the-primary-ip-address-of-the-local-machine-on-linux-and-os-x
#     https://stackoverflow.com/a/25851186

# Usage: get-ip.sh

# get local IP address of default route interface
# the awk command splits off and prints the contents of the last field in the "ip" command output, i.e. the IP address
my_ip=$(ip route get 1 | awk '{print $NF;exit}')
ip_type=" D"

# if that didn't work, get the current IP of the eth0 interface
if [ -z $my_ip ] ; then
    # got empty result from last test
    #echo "No IP for default route"
    my_ip=$(hostname -I)
    # another option from https://unix.stackexchange.com/questions/8518/how-to-get-my-own-ip-address-and-save-it-to-a-variable-in-a-shell-script
    #my_ip=$(ip -o -4 addr list eth0 | awk '{print $4}' | cut -d/ -f1)
    # or another from https://unix.stackexchange.com/questions/8518/how-to-get-my-own-ip-address-and-save-it-to-a-variable-in-a-shell-script
    #my_ip=$(ip -o addr show up primary scope global | while read -r num dev fam addr rest; do echo ${addr%/*}; done)

    ip_type=" S"
    if [ -z $my_ip ] ; then
        # got empty result from last test
        #echo "No IP for default adapter"
        my_ip="unknown"
        ip_type=""
    fi
fi

echo ${my_ip}

#end
```

Then run 

```
sudo chown pi:pi /opt/domoticz/script/get-ip.sh
sudo chmod 755 /opt/domoticz/script/get-ip.sh
```

After that you'll have to add a dzVents script on Domoticz.

### Add Hardware on Domoticz (optional)

Open a browser and access http://192.168.1.15:8080/
***Change the IP in these URLS to match your network configuration.***

The Domoticz screen should appear.

1.  Add a new "MySensors USB Gateway" hardware under "Hardware" menu
    1.  Name the hardware "Machinon_IO" or similar
    2.  Select Serial port  `serial0`  and baut rate  `115200`
2.  Copy/clone the  `presentation.sh`  script from Machinon Github to your Pi home directory and run it to present the Machinon I/O devices to Domoticz. This tells Domoticz the I/O channel names, firmware versions etc. Refresh the Domoticz hardware list and click "Setup" on the Machinon_IO hardware entry to see the names, or view the Devices list.

## Install Nginx + PHP

This covers all requirements for machinon_config and machinon_client packages.

```
sudo apt-get -y install nginx php-cli php-common php-fpm php-cgi php-pear php-mcrypt php-curl memcached ssl-cert
```

## Install machinon_config


### Create nginx server block (a.k.a. virtual host) 

```
cd /etc/nginx/sites-available
sudo nano machinon.conf
```

Add the following config in that file:

```
# Machinon Config server block
server {
    listen 80 default_server;
    root /opt/machinon/config/public;
    index index.html index.htm index.php;
    server_name _;
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
    }
    location = /machinon {
        rewrite ^ machinon/ redirect;
    }
    location /machinon/{
        proxy_pass http://localhost:8080/;
    }
    location ~ /\.ht {
        deny all;
    }
    location ~\.sh$ {
        deny all;
    }
}
```

Enable the server block and reload config (or start server):
```
cd /etc/nginx/sites-enabled
sudo rm -f default
sudo ln -s ../sites-available/machinon.conf machinon.conf
```

### Setting serial port permissions for nginx

Set user/group permissions to allow NGINX group/user www-data to access the serial port:

```
sudo usermod -a -G dialout www-data
sudo usermod -a -G www-data pi
```

### Download  machinon_config

```
sudo mkdir -p /opt/machinon
cd /opt/machinon
sudo git clone https://github.com/EdddieN/machinon_config config
sudo chown pi:pi -R /opt/machinon
```

### Register the Nginx service and restart it.

```
sudo systemctl enable nginx
sudo service nginx restart
```
You can access the Machinon's hardware config app going to 
http://192.168.1.15/

To open the Domoticz app go to
http://192.168.1.15/machinon/

***Change the IP in these URLS to match your network configuration.***

At this point, your Machinon setup is completed for a local network environment.

## Accesing your Machinon device from outside of your network

If you want to access remotely your Machinon devices through our Re:Machinon portal read this guide.
* [Re:Machinon Access Install Guide](remachinon_access_install_guide.md)
