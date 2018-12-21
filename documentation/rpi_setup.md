# Machinon setup on Raspberry Pi

The host Raspberry Pi requires some software setup to be able to access all features of the Machinon board, such as the SPI-UART serial ports and the RTC, and operate as a headless embedded system.

The steps below are based on starting with a clean install of the official Raspian Stretch Lite 2018-04-18 SD card image.

## Raspberry Pi System Installation/Configuration

1. Write standard Raspian Lite image to SD card.

2. With SD card still in PC card reader, create new empty file ssh in root directory (/boot partition) to enable SSH login.

3. Edit `/boot/config.txt` and add the following lines:

4. ```
   dtoverlay=sc16is752-spi1,24
   dtoverlay=i2c-rtc,mcp7941x
   dtoverlay=pi3-act-led, gpio=26
   dtoverlay=pi3-miniuart-bt  #for Pi3 only
   dtoverlay=pi3-disable-wifi #optional to disable wifi
   dtoverlay=pi3-disable-bt #to disable bluetooth
   enable_uart=1  #for Pi3 and Jessie or later
   ```

5. 
DUPLICATED ENTRY

6. Disable bluetooth service from starting (prevents service startup errors):

   ```
   sudo systemctl disable hciuart
   ```

7. Edit `/boot/cmdline.txt` and remove the text `console=serial0,115200` to allow applications to use serial port. See <https://www.raspberrypi.org/documentation/configuration/uart.md> for more info.

8. Connect the Pi to the Machinon board with the GPIO ribbon cable and power up. Wait for the Pi to boot, then find its IP and log in via SSH (use PuTTY or similar SSH client)

9. Run raspi-config and:

10. 1. Enable SPI
    2. Enable I2C
    3. Enable Serial (but disable login shell over serial) (should already be done by the previous edits to config.txt and cmdline.txt)
    4. Expand filesystem to fill card
    5. Change GPU memory to 16 MB (not essential)

11. Edit `/etc/modules` and add a new line with `rtc-mcp7941x`

12. Edit `/lib/udev/hwclock-set` and comment out (add # to start of lines) the lines:

    ```
    if [ -e /run/systemd/system ] ; then
        exit 0
    fi 
    ```

    Reboot and check that the Pi has correct time from network. Then optionally manually set HW clock with `sudo hwclock -w` to write system time to HW clock. The Pi will automatically load the time/date from the HW clock at boot. This can can be forced manually with `sudo hwclock -r` to set the system clock from the HW clock. The Pi does an NTP update of system clock at boot, and then every 1024 secs (17 mins) thereafter, and sets the RTC from this.
    ADD HOW TO CHECK TIME VIA CLI

13. Optionally set static IP:

14. 1. Edit `/etc/dhcpcd.conf` and uncomment or add these lines (change to suit):

       ```
       interface eth0
       static ip_address=192.168.1.15/24
       static routers=192.168.1.1
       static domain_name_servers=192.168.1.1
       ```

       Alternatively, edit the "fall back to static IP" section to make the Pi fall back to that static IP if DHCP fails.

    2. Reboot for network changes to take effect

15. Add permanent aliases for the SPI UARTs (Domoticz does not show port names like "ttySC1", so here we create aliases to "serial2" for RS485 and "serial3" for machinon config):

16. 1. 1. create a new udev rules file ```/etc/udev/rules.d/98-minibms.rules``` with:  
          ```KERNEL=="ttySC0" SYMLINK="serial485"```
       2. Save file and reboot
       3. Check for the aliases serial2 and serial3:
          ```ls -l /dev```
          (serial0 and serial1 are the Pi internal ports)

17. Change the default password, or optionally add a new user to run everything as instead of "pi". See <https://mattwilcox.net/web-development/setting-up-a-secure-home-web-server-with-raspberry-pi> for ideas.

18. Install any other desired updates, packages, etc. For Domoticz suggestions see <https://www.domoticz.com/wiki/Raspberry_Pi#Raspberry_Pi_additional_software_installations>

## Domoticz Setup

The steps below apply to Domoticz automation software (version 4.9700 or later) on Raspberry Pi (Debian Stretch Lite 2018-06-27 or later), but the same principles can be applied to other automation software. refer to the documentation for your preferred software for configuration details.

1. Install latest Domoticz release using the command below. See official install guide at [https://www.domoticz.com/wiki/Raspberry_Pi
   ](https://www.domoticz.com/wiki/Raspberry_Pi)  
   ```curl -L install.domoticz.com | sudo bash```  
   (choose HTTP port=8080 and HTTPS port=4443 when prompted)

2. Optional: Change the service startup mode to use systemd (on Raspian Stretch) and change user that Domoticz runs as. See <https://www.domoticz.com/wiki/Linux> for info.  

3. ```sudo nano /etc/systemd/system/domoticz.service```  
    If the file is empty, add:  

```
[Unit]

Description=domoticz_service
[Service]

User=pi

Group=users

    ExecStart=/home/pi/domoticz/domoticz -www 8080 -sslwww 4443

WorkingDirectory=/home/pi/domoticz

ExecStartPre=setcap 'cap_net_bind_service=+ep'
/home/pi/domoticz/domoticz

Restart=on-failure

RestartSec=1m

\#StandardOutput=null

[Install]

WantedBy=multi-user.target
```  

1. 1. Uncomment (remove # from) lines:  
      ```
      #User=pi
      #Group=users
      ```  
   2. Change ```-www``` and ```-sslwww``` ports if required (0 to disable)
   3. Save the file (Ctrl-X in nano)

2. Enable the service:  
   ```
   sudo systemctl daemon-reload
   sudo systemctl enable domoticz.service
   ```

3. Start the service:
   ```
   sudo systemctl start domoticz.service
   ```
   or
   ```
   sudo systemctl restart domoticz.service
   ```

4. Can also run from console for testing and interactive output:
   ```
   cd /path/to/domoticz
   ./domoticz
   ```

5. Add Hardware on Domoticz

6. 1. Add MySensors USB Gateway new hardware under ‘Hardware’ menu
   2. Call is machinon_io
   3. Select Serial port: serial0

## Install NGINX with PHP

The Machinon software support includes a set of PHP web forms to make the I/O configuration simple. This requires a web server that supports PHP, such as NGINX.

See <https://www.raspberrypi.org/documentation/remote-access/web-server/nginx.md> and <https://howtoraspberrypi.com/install-nginx-raspbian-and-accelerate-your-raspberry-web-server/> for NGINX install details.

1. Install NGINX (and php-fpm if required. On Raspian Stretch this will install PHP7.0)
   ```
   sudo apt install nginx php-fpm
   ```
2. For HTTPS access on NGINX, either install your own certificate and specify this in the config file below, OR install the ‘snakeoil’ non-production certificate:
   ```
   apt-get install ssl-cert
   ```
3. Create a new empty NGINX config file:
   ```
   cd /etc/nginx/sites-available
   sudo nano nginx-machinon.conf
   ```
4. Paste in the following content, then save/exit (Ctrl+X):
```
\# Machinon Web Config Interface and Proxy Server Configuration

\# Optionally Redirect all HTTP requests to HTTPS (will not work for tunneled requests with different URL path)

\#server {

\#    listen 80;

\#    #listen [::]:80;

\#    server_name localhost;

\#    return 302 https://$host$request_uri;

\#}

\# Default server configuration

server {

    \# Optionally listen on HTTP port 80 (comment out the 80 -> 443 redirect above)

    listen 80 default_server;

    

    \# SSL configuration

    listen 443 ssl default_server;

    \#listen [::]:443 ssl default_server;

    \#

    \# Note: You should disable gzip for SSL traffic.

    \# See: https://bugs.debian.org/773332

    \#

    \# Read up on ssl_ciphers to ensure a secure configuration.

    \# See: https://bugs.debian.org/765782

    \#

    \# Self signed certs generated by the ssl-cert package

    \# Don't use them in a production server!

    \# Replace with your own certs if you have them.

    include snippets/snakeoil.conf;

    root /var/www/html;

    index index.html index.htm index.php;

    \# Custom error 404 page

    error_page 404 /error404.html;

    server_name _;

    server_name_in_redirect off;

    \#absolute_redirect off;    # only in v1.11.8 or later

    \#rewrite_log on;

    location = / {

        \# Redirect requests for "/" to the landing page or Domoticz directory

        try_files $uri /index.html @domo;

    }

    location = /config {

        \# Redirect "config" to the "config/" directory

        rewrite ^ config/ redirect;

    }

    

    location = /machinon {

        \# Redirect "machinon" to the directory

        rewrite ^ machinon/ redirect;

    }

    location / {

        \# Redirect requests for files in "/" to the Domoticz directory

        try_files $uri =404;   # try serving file, and fall back to domoticz redirect

    }

    location @domo {

        \# redirect to machinon/ directory

        rewrite ^ machinon/ redirect;

    }

    \# pass PHP scripts to FastCGI server

    location ~ \.php$ {

        include snippets/fastcgi-php.conf;

        \# With php7 fpm (or other unix sockets):

        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;

        \# With php5 fpm:

        \#fastcgi_pass unix:/var/run/php5-fpm.sock;

        \# With php-cgi (or other tcp sockets):

        \#fastcgi_pass 127.0.0.1:9000;

    }

    location = /config/ {

        \# manually redirect to the index page (directory index does not work over tunnel)

        rewrite ^ index.php redirect;

        try_files $uri $uri/ =404;

    }

    location /machinon/{

        \# Optionally add authentication, unless the host software has login authentication

        \# NB: set Domoticz to use port 4443 for SSL (or port 0 to disable SSL), so that the default 443 can be used by NGINX

        \# Pass requests to Domoticz HTTPS port

        \#proxy_pass https://localhost:4443/;

        \# OR Pass requests to Domoticz HTTP port

        proxy_pass http://localhost:8080/;

        \#proxy_pass http://127.0.0.1:8080/;

    }

    \# deny access to .htaccess files, if Apache's document root concurs with nginx's one

    location ~ /\.ht {

        deny all;

    }

    \# deny access to .sh files

    location ~\.sh$ {

        deny all;

        \# fake a "not found" response

        return 404;

    }

}
```

1. Disable the default config by deleting the "sites-enabled" symbolic link:
   ```
   sudo rm /etc/nginx/sites-enabled/default
   ```
2. Create symbolic link to the config file so that NGINX uses it:
   ```
   cd /etc/nginx/sites-enabled
   sudo ln -s ../sites-available/nginx-machinon.conf nginx-machinon.conf
   ```
3. Reload config (or start server):
   ```
   sudo service nginx restart
   ```
   or
   ```
   sudo nginx -s reload
   ```
4. Set user/group permissions to allow NGINX group/user www-data to access the serial port:
   ```
   sudo usermod -a -G dialout www-data
   sudo usermod -a -G www-data pi
   ```
   (need to reboot for group changes to take effect)
5. Clone the config forms PHP files and resources to the NGINX web pages directory:
   ```
   cd /var/www/html
   git clone https://github.com/EdddieN/machinon_config .
   ```
