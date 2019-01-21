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
