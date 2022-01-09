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

if [[ -z "${wordpress_version}"  ]]; then
	echo "Error: Cannot find version number for WordPress. Exiting!"
	exit 1
fi

###########
#
# Update plugin and wordpress version info in README.txt
#
if [[ -f ./README.txt ]]; then
	echo "Updating the README.txt file"
	"${sed}" -r -e "s/Stable tag: ([0-9]+\.[0-9]+)|Stable tag: ([0-9]+\.[0-9]+\.[0-9]+)/Stable tag: ${version}/g" \
		-e "s/^Tested up to: ([0-9]+\.[0-9]+)|Tested up to: ([0-9]+\.[0-9]+\.[0-9]+)/Tested up to: ${wordpress_version}/g" \
	 	 ./README.txt > ./NEW_README.txt
	mv ./NEW_README.txt ./README.txt
	cp ./README.txt ./README.md
	echo "Generating the README.md file"
	"${sed}" -r -e "s/^\= (.*) \=/## \1/g" \
		 -e "s/^\=\= (.*) \=\=/### \1/g" \
		 -e "s/^\=\=\= (.*) \=\=\=/### \1/g" \
		 -e "s/^\* (.*)$/- \1/g" \
		 -e "s/^([a-zA-Z ]*): ([A-zA-Z0-9\.\,\\\/: -]*)/\`\1\: \2\` <br \/>/g" \
		 ./README.md > NEW_README.md
	mv ./NEW_README.md ./README.md
fi

# Add the file to the git repo if it doesn't already exist
if ! git ls-files --error-unmatch README.txt; then
  git add README.txt
fi

if ! git ls-files --error-unmatch README.md; then
  git add README.md
fi

if ! git commit -m "BUG FIX: Updated README info (v${version} for WP ${wordpress_version})" ./README.{txt,md}; then
  echo "No need to commit README.md/README.txt (no changes recorded)"
  exit 0
fi
