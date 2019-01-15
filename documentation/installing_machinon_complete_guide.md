# Installing Machinon - The complete guide

The document is divided in two parts. 

- Part I covers the main Machinon setup, which is mandatory and provides all the functionality to use your Machinon in a local network environment.

- Part II covers the installation and setup of additional software that will allow you to access your Machinon devices from internet, through our Re:Machinon portal.

# Part I : Machinon main setup

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

### Setting IP address

At this moment your Machinon should have a DHCP assigned IP address. If you want to continue with dynamic IP assignation, you can skip this step.

However, we recommend setting a fixed IP address in your Machinon, here are the steps to configure it:

```
sudo nano /etc/dhcpcd.conf
```
Uncomment or add these lines, with the proper IP settings.
As an example we are using a 192.168.1.x network range, you must choose your IP to suit your network settings):

```
interface eth0
static ip_address=192.168.1.15/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1
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

### Add Hardware on Domoticz (optional)

Open a browser and access http://192.168.1.15:8080/
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
sudo nano machinon_config.conf
```

Put the following config in that file:

```
# Default server configuration
server {
    listen 80 default_server;
    root /opt/machinon/config;
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
sudo ln -s ../sites-available/machinon_config.conf machinon_config.conf
sudo service nginx restart
```
You can try in your browser http://192.168.1.15
It should take you directly to the Domoticz screen.

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

You can access the Machinon's config app going to 

http://192.168.1.15/config/

At this point, your Machinon setup is completed for a local network environment.

If you want to access remotely your Machinon devices through our Re:Machinon portal, proceed to Part II.


# Part II : Re:Machinon remote access setup


## Installing Machinon-Client on your Raspbian


### Create server block file
```
sudo nano /etc/nginx/sites-available/machinon_client.conf
```

Put the next contents on it

```
server {
    listen 81 default_server;
    root /opt/machinon/client/public;
    index index.php;
    server_name _;
    location /machinon/ {
        auth_request /auth.php;
        proxy_pass http://127.0.0.1:8080/;
        proxy_set_header Host $host ;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Front-End-Https on;
        proxy_redirect off;
    }
    location / {
        auth_request /auth.php;
        proxy_pass http://127.0.0.1/;
        proxy_set_header Host $host ;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Front-End-Https on;
        proxy_redirect off;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        fastcgi_read_timeout 600;
    }
    location ~ /\.ht {
        deny all;
    }
    location ~\.sh$ {
        deny all;
    }
    error_page 401 = @error401;
    location @error401 {
        return 302 /index.php;
    }
}
```

Now enable the Server Block

```
cd /etc/nginx/sites-enabled
sudo ln -s ../sites-available/machinon_client.conf machinon_client.conf
```

### Download the current machinon_client app from GitHub

As the repository is private, you'll be asked for your GitHub user and password, that's okay.

```
cd /opt/machinon
sudo git clone https://github.com/EdddieN/machinon_client.git client
sudo chown pi:pi -R client
```

### Setup machinon_client

The app comes with a default configuration file, you just have to rename it.

```
cd /opt/machinon/client/config
mv config.example.php config.php
```


### Start the Nginx Service

Register the Nginx service to run on boot and restart it

```
sudo systemctl enable nginx
sudo service nginx restart
```
 
## Install Agent-Machinon 

### Install Python 3.5+ and required libraries

Change the package versions accordingly to your Raspbian repository available version.

```
sudo apt-get -y install python3 python3-pip
sudo -H pip3 install paho-mqtt python-dotenv
```

### Install autossh

This app is required to open the SSH tunnels and keep them opened without timeouts.

```
sudo apt-get -y install autossh
```

### Download the current agent_machinon app from GitHub

As the repository is private, you'll be asked for your GitHub user and password, that's okay.

```
cd /opt/machinon
sudo git clone https://github.com/EdddieN/agent_machinon.git agent
sudo chown pi:pi -R agent
cd agent
```

### Installing SSH Re:Machinon server  key and signature

The Re:Machinon's server PEM key is needed to let the app open the link with it.

> Jose :  This PEM key will be downloaded / installed internally or generated in the Pi and installed on the server somehow when automating the installation.
> At the moment I'll send the key to you by email. 

### Copy the contents of the key I've sent you in this file and set the  right permissions

> Jose : Use the PEM file I sent by email.

```
sudo nano /etc/ssh/remachinon_rsa_key.pem 
sudo chmod 400 /etc/ssh/remachinon_rsa_key.pem
```

### Preload the Re:Machinon server signature

This commands pre-install the Re:Machinon's server key signature, to avoid the ssh client asks for confirmation the first time the tunnel is opened (which would hang the agent).

If the agent app doesn't connect correctly, please try to run this commands again.

```
sudo ssh-keygen -R re.machinon.com 
ssh-keyscan re.machinon.com | sudo tee -a /etc/ssh/ssh_known_hosts
```

*Note: In case the first command returns a "No such file or directory error" that's okay, means the global known_host file doesn't exist yet.*

## Setup agent_machinon

The app provides a sample .env.example file as template. You can copy and modify the values as shown below or you can simply create a new .env file configured to use the re.machinon.com site.

```
sudo nano .env
```
Put on it the following contents, save and exit:

> Jose : In the MQTT_SERVER_PASSWORD line you must write the password I sent by email

```
# MQTT Broker definitions  
MQTT_SERVER_HOST=re.machinon.com  
MQTT_SERVER_PORT=1883  
MQTT_SERVER_PORT_SSL=8883  
MQTT_SERVER_USE_SSL=True  
MQTT_SERVER_USERNAME=remachinon  
MQTT_SERVER_PASSWORD=password  
MQTT_CERTS_PATH=/etc/ssl/certs  
  
# MQTT client and topic definitions  
MQTT_CLIENT_ID_PREFIX=agent_machinon:  
MQTT_TOPIC_PREFIX_REMOTECONNECT=remote  
  
# SSH Tunnel details  
SSH_HOSTNAME=re.machinon.com  
SSH_USERNAME=remachinon
SSH_KEY_FILE=/etc/ssh/remachinon_rsa_key.pem  
  
# Remachinon API base URL  
REMACHINON_API_URL=https://${SSH_HOSTNAME}/api/v1  

# Nginx port listening machinon_client web app (default 81)  
MACHINON_CLIENT_PORT=81

# script user must have write access to this file or folder  
LOG_FILE=tunnel-agent.log
```

### Installing agent_machinon as service

You have to create a new service and put some code on it
```
sudo nano /etc/systemd/system/agent_machinon.service
```
Write in the service file que following code, save and exit

```
# Service for Logic Energy Re:Machinon Tunnel Agent  
[Unit]  
       Description=agent_machinon_service  
[Service]  
       User=pi  
       Group=users  
       ExecStart=/usr/bin/python3 /opt/machinon/agent/tunnel-agent.py
       WorkingDirectory=/opt/machinon/agent/
       Restart=always  
       RestartSec=20  
       #StandardOutput=null  
[Install]  
       WantedBy=multi-user.target
```

Register and start the new service

```
sudo systemctl daemon-reload  
sudo systemctl enable agent_machinon.service  
sudo systemctl start agent_machinon.service
```

### Getting your device's MUID

Let's identify your Raspberry MUID (the ethernet MAC address in use), which you'll need to register the device in Re:Machinon. 

```
cd /opt/machinon/agent
cat tunnel-agent.log | grep "remote"
```
If the agent is running correctly, you'll get a message like this
```
MQTT Subscribed to 'remote/B827EB8B4A89' QOS=(0,)
```
Copy the hexadecimal value **after** `remote/` , that's the device's MUID!

If the tunnel-agent.log does not exist please re-check all the previous steps, as something's not working.

### Debugging possible errors

In case something goes wrong, you can always run agent_machinon directly from command line:

```
cd /opt/machinon/agent
env python3 tunnel-agent.py
```

If the app is running properly you'll see the app connects to MQTT server and waits for incoming commands. Otherwise it will drop some Python errors.

### Monitoring Agent-Machinon

You can also check Agent Machinon while the service is running by watching the log file. 
This command will continuously show the log contents until Ctrl+C is pressed:

```
cd /opt/machinon/agent
tail -f tunnel-agent.log
```

## Now what?

Visit Re:Machinon portal, join up, register your device using the MUID and you're ready to go!

http://re.machinon.com


> Written with [StackEdit](https://stackedit.io/).
