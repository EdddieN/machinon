# Installing Machinon (II) - Re:Machinon Access Install Guide

This guide covers the installation and setup of additional software that will allow you to access your Machinon devices from internet, through our Re:Machinon portal.

This guide covers up to the following software versions:
- machinon_client [v0.1-beta](https://github.com/EdddieN/machinon_client/releases/tag/v0.1-beta)
- agent_machinon [v0.3.1-beta](https://github.com/EdddieN/agent_machinon/releases/tag/v0.3.1-beta)

***You must complete the Machinon main setup before going further on this guide***

[Installing Machinon (I) - Main setup](machinon_install_guide.md)

#### CHANGELOG
v2.0 Major changes on Agent Machinon setup. 

- A Re:Machinon account is required **before** starting this installation.
- The Server PEM key manual installation is not required anymore.
- Neither getting the MQTT server credentials.
- The agent_machinon .env file has been reduced to two parameters, which are your Re:Machinon credentials.

v1.0 First release of the document

## Re:Machinon portal account

First you need a Re:Machinon portal account. 

***At this moment the new user registrations are closed. 
However you can contact us to apply for a beta-testing Re:Machinon account.***

Visit Re:Machinon portal and register.
http://re.machinon.com

Once you got your account credentials, go back to your Raspberry Pi and follow the following steps.

## Installing Machinon-Client on your Raspbian

### Add the Client config to the Nginx's server block
```
sudo nano /etc/nginx/sites-available/machinon.conf
```

**Append** the next server block after the previous server block configuration (do not replace the code, just add to it)

```
# Machinon Client server block
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
    location = /machinon/backupdatabase.php {
        auth_request /auth.php;
        proxy_pass http://127.0.0.1:8080/backupdatabase.php;
        proxy_set_header Host $host ;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Front-End-Https on;
        proxy_redirect off;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
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

### Download machinon_client app from GitHub

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


### Restart the Nginx Service

```
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

*This SSH connection method will be changed in short for a python scripted SSH connection method. However, in the meantime, the agent will continue using `autossh`*

### Download the current agent_machinon app from GitHub

As the repository is private, you'll be asked for your GitHub user and password, that's okay.

```
cd /opt/machinon
sudo git clone https://github.com/EdddieN/agent_machinon.git agent
sudo chown pi:pi -R agent
cd agent
```

### Preload the Re:Machinon server signature

This commands pre-install the Re:Machinon's server key signature, to avoid the ssh client asks for confirmation the first time the tunnel is opened (which would hang the agent).

If the agent app doesn't connect correctly, please try to run this commands again.

*Note: In case the first command returns a "No such file or directory error" that's okay, means the global known_host file doesn't exist yet.*

```
sudo ssh-keygen -R re.machinon.com 
ssh-keyscan re.machinon.com | sudo tee -a /etc/ssh/ssh_known_hosts
```

## Setup agent_machinon

The app provides a sample .env.example file as template. You can copy and modify the values as shown below or you can simply create a new .env file configured to use the re.machinon.com site.

Since agent_machinon v0.3.0 the .env file has been reduced to two directives, which are your re.machinon.com credentials. The MQTT and Server PEM key installation will be processed through the portal automatically.

```
cp .env.example .env
sudo nano .env
```
Fill in the two directives with your user credentials, save and exit
```
# Re:Machinon Service credentials  
REMACHINON_EMAIL=user@example.com  
REMACHINON_PASSWORD=yourpassword
```

*The old directives are still valid but they're not required anymore if you're using re.machinon.com service. 
These directives will be properly documented after we release Re:Machinon code, to let you setup the agent_machinon accordingly.*

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
python3 tunnel-agent.py -m
```
The command will return something like this
```
MUID : 1234ABCD4568
```
Copy the hexadecimal value, that's your device's MUID.


### Debugging possible errors

In case something goes wrong, we recommend to stop the service...
```
sudo service agent_machinon stop
```
...and run agent_machinon directly from command line:
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

Visit Re:Machinon portal, log in, register your device using the MUID and you're ready to go!

http://re.machinon.com
