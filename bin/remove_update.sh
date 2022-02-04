#!/usr/bin/env bash

#
# Copyright (c) 2020 - 2022 - Thomas Sjolshagen at Eighty/20 Results by Wicked Strong Chicks, LLC
#

source build_config/helper_config "${@}"

for file_name in "${update_to_check[@]}"; do

	# Look for the file we're processing in the build directory
	found_file=$(find ./ -name "${file_name}" -print)

	if [[ -z "${found_file}" ]]; then
		echo "ℹ︎ ${file_name} not found... Skipping!"
		continue
	fi

	echo "ℹ︎ Found '${found_file}'"

	# See if it contains the stuff we want to remove
	has_update=$(grep -c "${search_string}" "${found_file}")

	if [[ "${has_update}" -eq 0 ]]; then
		echo "ℹ︎ ${found_file} does not contain ${search_string}. Skipping!"
		continue
	fi

	# Remove the actual line +1 line in front of, and after, the target line.
	echo "ℹ︎ Found ${search_string} in ${found_file}. Removing..."
	sed -i -r "/\n/!N;/\n.*\n/!N;/\n.*\n.*${search_string}/{\$d;N;N;d};P;D" "${found_file}"

done

for dir_name in "${codeception_update_list[@]}"; do
	echo "ℹ︎ Looking for the ${dir_name} module..."
	# Remove all instances of the update module
	if ! find ./ -name "${dir_name}" -print -delete; then
		echo "ℹ︎ Codeception one-click update utility not found..."
	fi
done
