#!/usr/bin/env bash
WORDPRESS_DB_USER="${1}"
WORDPRESS_DB_PASSWORD="${2}"
WORDPRESS_DB_HOST="${3}"
E20R_PLUGIN_NAME="${4}"

until docker container exec \
	"mariadb-wp-${E20R_PLUGIN_NAME}" \
	mysqladmin ping -P 3306 -p"${WORDPRESS_DB_PASSWORD}" -u"${WORDPRESS_DB_USER}" -h"${WORDPRESS_DB_HOST}" | \
	grep "mysqld is alive" ; do
  >&2 echo "MySQL is unavailable - waiting for it... 😴"
  sleep 2
done
echo "Standby to let Docker stack for ${E20R_PLUGIN_NAME} testing settle a bit"
sleep 5
