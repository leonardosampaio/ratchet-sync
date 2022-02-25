# Ratchet API Sync

Ratchet websocket server to synchronize API push events to a MySQL database

# Iptables

Open inbound connections to the socket ports with

    sudo yum install iptables-services
    sudo systemctl start iptables
    sudo systemctl enable iptables
    sudo iptables -A INPUT -p tcp -m tcp --dport PORT_NUMBER -j ACCEPT
    sudo service iptables save

ufw: 
    sudo ufw allow 5732
# MySQL

Create database(s), tables and user(s) with the DDL template in `sql/create.sql`

    mysql -uroot -p < sql/create.sql

## Configuration

1. Copy configuration/configuration.json.dist to configuration/configuration.json
2. Edit the variables accordingly
3. Run server with

    php cli/server.php path_to/configuration_file.json

# To run as service

1. ln -s /usr/share/ratchet-sync/systemd/ratchet.service /etc/systemd/system
2. systemctl daemon-reload
3. systemctl enable ratchet
4. systemctl start ratchet