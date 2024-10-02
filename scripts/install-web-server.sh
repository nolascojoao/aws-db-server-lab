#!/bin/bash

# Update the instance packages
sudo yum update -y

# Install Apache and PHP
sudo yum install -y httpd php php-mysqlnd

# Start Apache
sudo systemctl start httpd

# Enable Apache to start on boot
sudo systemctl enable httpd

# Download index.php
wget -O /var/www/html/index.php https://raw.githubusercontent.com/nolascojoao/aws-rds-creation-lab/main/scripts/index.php

# Substitute variables in index.php
sudo sed -i "s/\$host = 'your-rds-endpoint.amazonaws.com';/\$host = 'YOUR_RDS_ENDPOINT';/" /var/www/html/index.php
sudo sed -i "s/\$username = 'your_rds_username';/\$username = 'YOUR_RDS_USERNAME';/" /var/www/html/index.php
sudo sed -i "s/\$password = 'your_rds_password';/\$password = 'YOUR_RDS_PASSWORD';/" /var/www/html/index.php
sudo sed -i "s/\$dbname = 'your_database_name';/\$dbname = 'YOUR_DATABASE_NAME';/" /var/www/html/index.php

# Restart Apache to apply changes
sudo systemctl restart httpd
