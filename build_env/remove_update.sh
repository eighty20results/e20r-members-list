#!/usr/bin/env bash

#
# Copyright (c) 2020 - Thomas Sjolshagen at Eighty/20 Results by Wicked Strong Chicks, LLC
#

if [[ -z "${BUILD_DIR}" ]]; then
	echo "➤ No BUILD_DIR environment variable found!"
	exit 1
fi

echo "ℹ︎ BUILD_DIR is ${BUILD_DIR}"

# Remove any wordpress.org prohibited one-click update functionality as necessary
BASE_FILE="class.e20r-members-list.php"
UTILS_FILE="class-utility-loader.php"

if [[ -f "trunk/${BASE_FILE}" ]]; then

	has_update=$(grep "Utilities::configureUpdateServerV4" "trunk/${BASE_FILE}" | wc -l)
	utils_has_update=$(grep "Utilities::configureUpdateServerV4" "trunk/class/utilities/${UTILS_FILE}" | wc -l)

	if [[ "${has_update}" -gt 0 ]]; then
		echo "ℹ︎ Found unsupported external update script in plugin. Removing"
		grep -v "Utilities::configureUpdateServerV4" "trunk/${BASE_FILE}" > "trunk/${BASE_FILE}.new"
		mv "trunk/${BASE_FILE}.new" "trunk/${BASE_FILE}"
	fi

	if [[ "${utils_has_update}" -gt 0 ]]; then
		echo "ℹ︎ Found unsupported external update script in utilities module. Removing"
		grep -v "Utilities::configureUpdateServerV4" "trunk/class/utilities/${UTILS_FILE}" > "trunk/class/utilities/${UTILS_FILE}.new"
		mv "trunk/class/utilities/${UTILS_FILE}.new" "trunk/class/utilities/${UTILS_FILE}"
	fi

	if [[ -d trunk/class/utilities/inc/yahnis-elsts ]]; then
		echo "ℹ︎ Found unsupported update utility. Removing"
		rm -rf trunk/class/utilities/inc/yahnis-elsts
	fi
fi
