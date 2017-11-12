#!/usr/bin/env bash

# ---------------------------------------
#          Virtual Machine Setup
# ---------------------------------------

# Adding multiverse sources.
cat > /etc/apt/sources.list.d/multiverse.list << EOF
deb http://archive.ubuntu.com/ubuntu xenial multiverse
deb http://archive.ubuntu.com/ubuntu xenial-updates multiverse
deb http://security.ubuntu.com/ubuntu xenial-security multiverse
EOF


# Updating packages
apt-get update

# ---------------------------------------
#          Apache Setup
# ---------------------------------------

# Installing Packages
apt-get install -y apache2
apt-get install -y libapache2-mod-fastcgi apache2-mpm-worker

# linking Vagrant directory to Apache 2.4 public directory
rm -rf /var/www
ln -fs /vagrant/src /var/www

# Add ServerName to httpd.conf
echo "ServerName localhost" > /etc/apache2/httpd.conf
# Setup hosts file
VHOST=$(cat <<EOF
<VirtualHost *:80>
  DocumentRoot "/var/www"
  ServerName localhost
  <Directory "/var/www">
    AllowOverride All
  </Directory>
</VirtualHost>
EOF
)
echo "${VHOST}" > /etc/apache2/sites-enabled/000-default.conf

# Loading needed modules to make apache work
a2enmod actions fastcgi rewrite
service apache2 reload

# ---------------------------------------
#          PHP Setup
# ---------------------------------------

# Installing packages
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install -y php

sudo apt-get install -y curl php-curl

sudo bash -c "echo post_max_size=20M >> /etc/php/7.1/apache2/php.ini"
sudo bash -c "echo upload_max_filesize=20M >> /etc/php/7.1/apache2/php.ini"


# Okay, we want xdebug, too
sudo apt-get install -y php-pear php-dev
sudo apt-get install -y gcc make autoconf libc-dev pkg-config php-pear
sudo pecl install xdebug
sudo bash -c "echo zend_extension=xdebug.so >> /etc/php/7.1/apache2/php.ini"
sudo bash -c "echo xdebug.remote_enable = on >> /etc/php/7.1/apache2/php.ini"
sudo bash -c "echo xdebug.remote_connect_back = on >> /etc/php/7.1/apache2/php.ini"
sudo bash -c "echo xdebug.idekey = \"vagrant\" >> /etc/php/7.1/apache2/php.ini"

# Generally enable PHP error reporting
sudo bash -c "echo display_errors = On >> /etc/php/7.1/apache2/php.ini"

# ---------------------------------------
#          MySQL Setup
# ---------------------------------------

# Setting MySQL root user password root/root
debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'

# Installing packages
apt-get install -y mysql-server mysql-client php-mysql php-gd


# Restarting apache to make changes
service apache2 restart


# ---------------------------------------
#       Tools Setup.
# ---------------------------------------
# These are some extra tools that you can remove if you will not be using them 
# They are just to setup some automation to your tasks.

#Some artifact of the apache setup
rm -rf /var/www/html

#Enable access to the apache log files
sudo su
chown vagrant /var/log/apache2
chown vagrant /var/log/apache2/access.log
chown vagrant /var/log/apache2/error.log


# -----------------------------------------
# MySQL Database provisioning
# -----------------------------------------

#mysql -u root -proot < /vagrant/database.sql