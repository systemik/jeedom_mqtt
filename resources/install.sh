#! /bin/bash


source /etc/lsb-release
if (DISTRIB_ID == "Debian") {
  wget http://repo.mosquitto.org/debian/mosquitto-repo.gpg.key
  apt-key add mosquitto-repo.gpg.key
  cd /etc/apt/sources.list.d/
  if (DISTRIB_CODENAME == "wheezy") {
    wget http://repo.mosquitto.org/debian/mosquitto-wheezy.list
  elif (DISTRIB_CODENAME == "jessie") {
    wget http://repo.mosquitto.org/debian/mosquitto-jessie.list
  }
} elif (DISTRIB_ID == "Ubuntu") {
  apt-add-repository ppa:mosquitto-dev/mosquitto-ppa
}

apt-get update
apt-get -y install mosquitto mosquitto-clients libmosquitto-dev
echo "" | pecl install Mosquitto-alpha
echo "extension=mosquitto.so" | tee -a /etc/php5/fpm/php.ini
service php5-fpm restart
