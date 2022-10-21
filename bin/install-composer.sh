#!/bin/bash
BASE_PATH="${1}/";
echo "Install the PHP Composer component to ${BASE_PATH}"
$(which php) -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
$(which php) -r "copy('https://composer.github.io/installer.sig', '.composer.sig');"
EXPECTED_CHKSUM=$(cat .composer.sig)
ACTUAL_CHKSUM=$(php -r "echo hash_file('sha384', 'composer-setup.php');")
if [[ "${EXPECTED_CHKSUM}" != "${ACTUAL_CHKSUM}" ]]; then
	echo 'Installer corrupt, exiting!';
	rm -f composer-setup.php .composer.sig
	exit 1
fi
$(which php) composer-setup.php --install-dir="${BASE_PATH}" --quiet
$(which php) -r "unlink('composer-setup.php'); unlink('.composer.sig');"
