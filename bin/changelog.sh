#!/usr/bin/env bash
short_name="e20r-members-list"
server="eighty20results.com"
sed="$(which sed)"
readme_path="build_readmes/"
changelog_source=${readme_path}current.txt
changelog_out="CHANGELOG.md"
wordpress_version=$(wget -q -O - http://api.wordpress.org/core/stable-check/1.0/  | grep latest | awk '{ print $1 }' | sed -e 's/"//g')
tmp_changelog=$(mktemp /tmp/chlog-XXXXXX)
version=$(egrep "^Version:" class-${short_name}.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
today=$(date "+%Y-%m-%d")
time=$(date "+%h:%m:00")
changelog_new_version="## [${version}] - ${today}"
changelog_header=$(cat <<- __EOF__
# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


__EOF__
)

if [[ ! -f metadata.json ]]; then
	cp "${readme_path}/metadata.json" metadata.json
fi

###########
#
# Update plugin and wordpress version info in metadata.json
#
if [[ -f metadata.json ]]; then
	echo "Updating the metadata.json file"
	"${sed}" -r -e "s/\"version\": \"([0-9]+\.[0-9].*)\"\,/\"version\": \"${version}\"\,/" \
					 -e "s/\"tested\"\:\ \"([0-9]+\.[0-9].*)\"\,/\"tested\"\:\ \"${wordpress_version}\"\,/" \
					 -e "s/\"last_updated\": \"(.*)\",/\"last_updated\": \"${today} ${time} CET\",/g" \
					 -e "s/\"download_url\": \"https:\/\/${server}\/protected-content\/${short_name}\/${short_name}-([0-9]+\.[0-9].*)\.zip\",/\"download_url\": \"https:\/\/${server}\/protected-content\/${short_name}\/${short_name}-${version}\.zip\",/g" \
					 metadata.json > new_metadata.json
		mv new_metadata.json metadata.json
fi

###########
#
# Update plugin and wordpress version info in README.txt
#
if [[ -f README.txt ]]; then
	echo "Updating the README.txt file"
	"${sed}" -r -e "s/Stable tag: ([0-9]+\.[0-9].*)/Stable\ tag:\ ${version}/g" \
	 				 -e "s/^Tested up to: ([0-9]+\.[0-9].*)/Tested up to: ${wordpress_version}/g"\
	 				 README.txt > NEW_README.txt
	mv NEW_README.txt README.txt
fi

if [[ ! -f "${changelog_out}" ]]; then
	cp "${readme_path}/${changelog_out}" "${changelog_out}"
fi

###########
#
# Create the CHANGELOG.md for the current ${version}
#
# Extract the old changelog entries iof they don't already exist in the log
if ! grep "${changelog_new_version}" "${changelog_out}"; then
	echo "Updating the CHANGELOG.md file"
	# shellcheck disable=SC2016
	sed -e '1,/##\ \[Unreleased\]/d' "${changelog_out}" > "${tmp_changelog}"
	# Create the new CHANGELOG.md file
	{
		echo "${changelog_header}" ;
		echo "" ;
		echo "${changelog_new_version}" ;
	} > "${changelog_out}"
	# Add dash (-) to all entries in the changelog source for the new CHANGELOG.md file
	"${sed}" -e"s/\"/\'/g" -e"s/.*/-\ &/" "${changelog_source}" >> "${changelog_out}"
	# Append the old change log to the new file
	cat "${tmp_changelog}" >> "${changelog_out}"
	# Clean up temp file(s)
	rm -f "${tmp_changelog}"
fi

git commit \
	-m "Updated version info during build (v${version} for WP ${wordpress_version})" \
	CHANGELOG.md \
	README.txt \
	metadata.json \
	"${readme_path}/current.txt"
