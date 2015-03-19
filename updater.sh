#!/bin/bash

HOST=host.here.com
REMOTEHOST='http://localhost:9191'
CUR_IP=$(wget -qO - http://icanhazip.com)
DNS_IP=$(dig +short $HOST.)
FOLDER='/location/of/script/folder'
USER=youruser
PASS=yourtoken

# Change folder
cd $FOLDER
# Start PHP Server
php -S localhost:9191 &
sleep 2
PID=$!
echo "current IP: $CUR_IP"
echo "DNS IP: $DNS_IP"
if [ "$CUR_IP" != "$DNS_IP" ]; then
    echo 'updating..'
    wget --http-user=$USER --http-password=$PASS -qO - "$REMOTEHOST"/DNSUpdater.php
fi

# Kill PHP server
kill $PID
