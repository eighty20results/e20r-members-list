#!/usr/bin/env bash

# Remove any wordpress.org prohibited one-click update functionality when necessary
BASE_FILE="class.e20r-members-list.php"

if [[ -f trunk/${BASE_FILE} ]]; then
	has_update=$(grep "Utilities::configureUpdateServerV4" trunk/${BASE_FILE} | wc -l)

	if [[ ${has_update} -gt 0 ]]; then
		echo "ℹ︎ Found unsupported external update script. Removing"
		grep -v "Utilities::configureUpdateServerV4" trunk/${BASE_FILE} > trunk/${BASE_FILE}
	fi
fi
