#!/usr/bin/env bash
#
# Import the configuration information for this plugin
#
source build_config/helper_config "${@}"

if [[ -z "${wordpress_version}"  ]]; then
	echo "Error: Cannot find version number for WordPress. Exiting!"
	exit 1
fi
#
# Define all needed variables for the metadata.json file creation/update
#
today=$(date +%Y-%m-%d)
when=$(date +%H:%M:%S)
url_info="https://${remote_server}/protected-content/${short_name}/${short_name}"
url_with_version="${url_info}-${version}.zip"
metadata_template=$(cat <<- __EOF__
{
  "name": "${short_description}",
  "slug": "${short_name}",
  "download_url": "${url_with_version}",
  "version": "${version}",
  "tested": "${wordpress_version}",
  "requires": "5.0",
  "author": "Thomas Sjolshagen <thomas@eighty20results.com>",
  "author_homepage": "https://eighty20results.com/thomas-sjolshagen",
  "last_updated": "${today} ${when} CET",
  "homepage": "${plugin_homepage}",
  "sections": {
    "description": "${plugin_description_text}",
    "changelog": "See the linked <a href='CHANGELOG.md' target='_blank'>Change Log</a> for details",
    "faq": "<h3>I found a bug in the plugin.</h3><p>Please report your issue to us by using the <a href='${github_url}/issues' target='_blank'>Github Issues page</a>, and we'll try to respond within 1 business day.</p>"
    }
}
__EOF__
)

###########
#
# Update info in metadata.json
#
echo "Updating the metadata.json file"
echo "${metadata_template}" > ./metadata.json

# Add the file to the git repo if it doesn't already exist
if ! git ls-files --error-unmatch ./metadata.json; then
  git add metdata.json
fi

# Commit the updated file to the repository
if ! git commit -m "BUG FIX: Updated metadata.json for v${version} and WP ${wordpress_version}" metadata.json; then
  echo "No need to commit metadata.json (no changes recorded)"
  exit 0
fi
