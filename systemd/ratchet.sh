#!/bin/bash
nohup /usr/bin/php /usr/share/ratchet-sync/cli/server.php /usr/share/ratchet-sync/configuration/websocket.json & >> /var/log/ratchet.log 2>&1