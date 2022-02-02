#!/usr/bin/env bash
#
# Import the configuration information for this plugin
#
source build_config/helper_config "${@}"

declare sed
sed="$(which sed)"

if [[ -z "${sed}" ]]; then
    echo "Error: The sed utility is not installed. Exiting!"
    exit 1;
fi

readme_path="./build_readmes/"
changelog_source=${readme_path}current.txt
changelog_out_new="CHANGELOG.new.md"
changelog_out="CHANGELOG.md"
tmp_changelog=$(mktemp /tmp/chlog-XXXXXX)
today=$(date +%Y-%m-%d)
changelog_new_version="## v${version} - ${today}"
changelog_header=$(cat <<- __EOF__
# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


__EOF__
)

if [[ ! -f "${changelog_out}" ]]; then
	cp "${readme_path}/${changelog_out}" "./${changelog_out}"
fi

###########
#
# Create the CHANGELOG.md for the current ${version}
#
# Extract the old changelog entries if they don't already exist in the log
if ! grep "${changelog_new_version}" "${changelog_out}"; then
	echo "Updating the CHANGELOG.md file"
	cp "${changelog_out}" "${changelog_out_new}"
	# shellcheck disable=SC2016
	sed -e '1,/##\ \[Unreleased\]/d' "${changelog_out_new}" > "${tmp_changelog}"
	# Create the new CHANGELOG.md file
	{
		echo "${changelog_header}" ;
		echo "" ;
		echo "${changelog_new_version}" ;
	} > "./${changelog_out_new}"
	# Add dash (-) to all entries in the changelog source for the new CHANGELOG.md file
	"${sed}" -r -e "s/^Merge branch(.*)$//g" \
					 -e "s/^Updated (.*)$//g" \
					 -e '/^[[:space:]]*$/d' \
					 -e "s/\"/\'/g" \
					 -e "s/.*/-\ &/" \
					 "${changelog_source}" >> "./${changelog_out_new}"
	# Append the old change log to the new file
	cat "${tmp_changelog}" >> "${changelog_out_new}"
	uniq "${changelog_out_new}" "${changelog_out}"
	# Clean up temp file(s)
	rm -f "${tmp_changelog}" "${changelog_out_new}"
fi

# Add the file to the git repo if it doesn't already exist
if ! git ls-files --error-unmatch ./CHANGELOG.md; then
  git add CHANGELOG.md
fi

if ! git commit -m "BUG FIX: Updated CHANGELOG (v${version} for WP ${wordpress_version})" CHANGELOG.md; then
  echo "No need to commit CHANGELOG.md (no changes recorded)"
  exit 0
fi
