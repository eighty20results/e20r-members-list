#!/bin/zsh
#
# Build script for Eighty/20 Results WordPress plugins
#
# Copyright 2014 - 2020 (c) Eighty / 20 Results by Wicked Strong Chicks, LLC
#
short_name="00-e20r-utilities"
remote_server="eighty20results.com"
declare -a include=("inc" "licensing" "utilities" "class-utility-loader.php" "README.txt")
declare -a exclude=("*.yml" "*.phar" "composer.*" "vendor")
declare -a build=()
plugin_path="${short_name}"
version=$(egrep "^Version:" ../class-utility-loader.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
metadata="../metadata.json"
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building ${short_name} kit for version ${version}"

mkdir -p "${kit_path}"
mkdir -p "${dst_path}"

if [[ -f "${kit_name}" ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf "${dst_path}"
    rm -f "${kit_name}"
fi

for p in "${include[@]}"; do
  echo "Processing ${src_path}${p}"
  if ls "${src_path}${p}" > /dev/null 2>&1; then
    echo "Copying ${src_path}${p} to ${dst_path}"
	  cp -R "${src_path}${p}" "${dst_path}"
  fi
done

for e in "${exclude[@]}"; do
  if ls "${src_path}${e}" 1> /dev/null 2>&1; then
    find "${dst_path}" -type d -iname "${e}" -exec rm -rf {} \;
  fi
done

# mkdir -p "${dst_path}/

for b in "${build[@]}"; do
  if ls "${src_path}${b}" 1> /dev/null 2>&1; then
      cp -R "${src_path}${b}" "${dst_path}"
  fi
done

cd "${dst_path}/.." || exit 1
zip -r "${kit_name}.zip" "${plugin_path}"
ssh "${remote_server}" "cd ./${remote_server}/protected-content/ ; mkdir -p \"${short_name}\""

echo "Copying ${kit_name}.zip to ${remote_server}:./${remote_server}/protected-content/${short_name}/"
scp "${kit_name}.zip" "${remote_server}:./${remote_server}/protected-content/${short_name}/"

echo "Copying ${metadata} to ${remote_server}:./${remote_server}/protected-content/${short_name}/"
scp "${metadata}" "${remote_server}:./${remote_server}/protected-content/${short_name}/"

echo "Linking ${short_name}/${short_name}-${version}.zip to ${short_name}.zip on remote server"
ssh "${remote_server}" "cd ./${remote_server}/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf "${dst_path}"


