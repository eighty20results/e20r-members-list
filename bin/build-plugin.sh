#!/usr/bin/env bash
# Build script: Copyright 2016 - 2021 Eighty/20 Results by Wicked Strong Chicks, LLC
#
# Used by the custom plugin framework to build installable plugin archives
#
short_name="e20r-members-list"
remote_server="eighty20results.com"
declare -a include=( \
	"src" \
	"languages" \
	"class-${short_name}.php" \
	"README.txt" \
	"CHANGELOG.md"
	)
declare -a exclude=( \
	"*.yml" \
	"*.phar" \
	"composer.*" \
	"vendor" \
	"inc" \
	"tests" \
	)
declare -a build=( \
	"plugin-updates/vendor/*.php" \
)
plugin_path="${short_name}"
version=$(egrep "^Version:" "../class-${short_name}.php" | \
	sed 's/[[:alpha:]|(|[:space:]|\:]//g' | \
	awk -F- '{printf "%s", $1}')
metadata="../metadata.json"
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"
remote_path="./www/eighty20results.com/public_html/protected-content/"
echo "Building ${short_name} kit for version ${version}"

mkdir -p "${kit_path}"
mkdir -p "${dst_path}"

if [[ -f "${dst_path}/composer.json" ]]; then
	echo "Loading all composer packages"
	composer --no-dev install
fi

if [[ -f "${kit_name}" ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf "${dst_path}"
    rm -f "${kit_name}"
fi

# Add all files for the .zip archive
for p in "${include[@]}"; do
  echo "Processing ${src_path}${p}"
  if ls "${src_path}${p}" > /dev/null 2>&1; then
    echo "Copying ${src_path}${p} to ${dst_path}"
	  cp -R "${src_path}${p}" "${dst_path}"
  fi
done

# Remove files we do not want in the .zip archive
for e in "${exclude[@]}"; do
  if ls "${src_path}${e}" 1> /dev/null 2>&1; then
  	if [[ "${e}" =~ '/' ]]; then
			e=$(awk -F/ '{ print $NF }' <<< "${e}")
		fi
  	echo "Excluding ${e} from ${dst_path}"
    find "${dst_path}" -iname "${e}" -exec rm -rf {} \;
  fi
done

# Remove any file(s) that are part of the custom plugin update (non .org)
for b in "${build[@]}"; do
  if ls "${src_path}${b}" 1> /dev/null 2>&1; then
      cp -R "${src_path}${b}" "${dst_path}"
  fi
done

cd "${dst_path}/.." || exit 1
zip -r "${kit_name}.zip" "${plugin_path}"
# We _want_ to expand the variables on the client side
# shellcheck disable=SC2029
ssh "${remote_server}" "cd ${remote_path}; mkdir -p \"${short_name}\""

echo "Copying ${kit_name}.zip to ${remote_server}:${remote_path}/${short_name}/"
scp "${kit_name}.zip" "${remote_server}:${remote_path}/${short_name}/"

echo "Copying ${metadata} to ${remote_server}:${remote_path}/${short_name}/"
scp "${metadata}" "${remote_server}:${remote_path}/${short_name}/"

echo "Linking ${short_name}/${short_name}-${version}.zip to ${short_name}.zip on remote server"
# We _want_ to expand the variables on the client side
# shellcheck disable=SC2029
ssh "${remote_server}" \
	"cd ${remote_path}/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"

# Return to the root directory
cd "${src_path}" || die 1

# And clean up
rm -rf "${dst_path}"
