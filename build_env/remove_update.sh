#!/usr/bin/env bash

#
# Copyright (c) 2020 - Thomas Sjolshagen at Eighty/20 Results by Wicked Strong Chicks, LLC
#

# Remove any wordpress.org prohibited one-click update functionality as necessary
read -r -a FILE_LIST <<< "class-e20r-members-list.php class-utility-loader.php class-loader.php"
read -r -a UPDATE_LIST <<< "yahnis-elsts"
srch_string="Utilities::configureUpdateServer"

if [[ -z "${BUILD_DIR}" ]]; then
	echo "➤ No BUILD_DIR environment variable found!"
	exit 1
fi

echo "ℹ︎ BUILD_DIR is ${BUILD_DIR}"

for file_name in "${FILE_LIST[@]}"; do

	# Look for the file we're processing in the build directory
	found_file=$(find ./ -name "${file_name}" -print)

	if [[ -z "${found_file}" ]]; then
		echo "ℹ︎ ${file_name} not found... Skipping!"
		continue
	fi

	echo "ℹ︎ Found '${found_file}'"

	# See if it contains the stuff we want to remove
	has_update=$(grep -c "${srch_string}" "${found_file}")

	if [[ "${has_update}" -eq 0 ]]; then
		echo "ℹ︎ ${found_file} does not contain ${srch_string}. Skipping!"
		continue
	fi

	# Remove the actual line +1 line in front of, and after, the target line.
	echo "ℹ︎ Found ${srch_string} in ${found_file}. Removing..."
	sed -i -r "/\n/!N;/\n.*\n/!N;/\n.*\n.*${srch_string}/{\$d;N;N;d};P;D" "${found_file}"

done

for dir_name in "${UPDATE_LIST[@]}"; do
	echo "ℹ︎ Looking for the ${dir_name} module..."
	# Remove all instances of the update module
	if ! find ./ -name "${dir_name}" -print -delete; then
		echo "ℹ︎ Update utility not found..."
	fi
done
