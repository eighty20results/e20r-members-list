#!/usr/bin/env bash

#
# Copyright (c) 2020 - Thomas Sjolshagen at Eighty/20 Results by Wicked Strong Chicks, LLC
#

# Remove any wordpress.org prohibited one-click update functionality as necessary
read -r -a FILE_LIST <<< "class.e20r-members-list.php class-utility-loader.php"
read -r -a UPDATE_LIST <<< "yahnis-elsts"
srch_string="Utilities::configureUpdateServer"

if [[ -z "${BUILD_DIR}" ]]; then
	echo "➤ No BUILD_DIR environment variable found!"
	exit 1
fi

echo "ℹ︎ BUILD_DIR is ${BUILD_DIR}"

echo "Working in: $(pwd)"
find ./ -print

for file_name in "${FILE_LIST[@]}"; do

	# Look for the file we're processing in the build directory
	found_file=$(find "${BUILD_DIR}" -name "${file_name}" -print)

	echo "ℹ︎ Found File path: '${found_file}'"

	if [[ -z "${found_file}" ]]; then
		echo "ℹ︎ ${file_name} not found... Skipping!"
		continue
	fi

	# See if it contains the stuff we want to remove
	has_update=$(grep -c "${srch_string}" "${found_file}")

	echo "ℹ︎ Contains a call-out to the 3rd party update logic: '${has_update}'"

	if [[ "${has_update}" -eq 0 ]]; then
		echo "ℹ︎ ${found_file} does not contain ${srch_string}. Skipping!"
		continue
	fi

	# Remove the actual line +1 line in front of, and after, the target line.
	echo "ℹ︎ Found ${srch_string} in ${found_file}. Removing..."
	sed -i "/\n/!N;/\n.*\n/!N;/\n.*\n.*${srch_string}/{\$d;N;N;d};P;D" "${found_file}"

done

for dir_name in "${UPDATE_LIST[@]}"; do
	# Remove all instances of the update module
	if ! find ./ -name "${dir_name}" -print -delete; then
		echo "ℹ︎ Update utility not found..."
	fi
done
