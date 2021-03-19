#!/usr/bin/env bash
WORDPRESS_DB_USER="${1}"
WORDPRESS_DB_PASSWORD="${2}"
WORDPRESS_DB_HOST="${3}"
E20R_PLUGIN_NAME="${4}"

echo "For Wordpress user: ${WORDPRESS_DB_USER}"
until docker container exec -it "mariadb-wp-${E20R_PLUGIN_NAME}" mysqladmin ping -P 3306 -p"${WORDPRESS_DB_PASSWORD}" -u"${WORDPRESS_DB_USER}" -h"${WORDPRESS_DB_HOST}" | grep "mysqld is alive" ; do
  >&2 echo "MySQL is unavailable - waiting for it... 😴"
  sleep 1
done
