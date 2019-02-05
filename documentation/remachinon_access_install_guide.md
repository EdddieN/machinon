# Installing Machinon (II) - Re:Machinon Access Install Guide

- This guide covers the installation and setup of additional software that will allow you to access your Machinon devices from internet, through our Re:Machinon portal.

***You must complete the Machinon main setup before going further on this guide***

[Installing Machinon (I) - Main setup](machinon_install_guide.md)

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
