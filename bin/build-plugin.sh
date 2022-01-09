#!/usr/bin/env bash
# Build script: Copyright 2016 - 2021 Eighty/20 Results by Wicked Strong Chicks, LLC

#
# Used by the custom plugin framework to build installable plugin archives
#
source build_config/helper_config "${@}"

src_path="$(pwd)"
plugin_path="${short_name}"
dst_path="${src_path}/build/${plugin_path}"
kit_path="${src_path}/build/kits"
kit_name="${kit_path}/${short_name}-${version}.zip"
echo "Building ${short_name} kit for version ${version}"

if [[ -f "${kit_name}" ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf "${dst_path}"
    rm -f "${kit_name}"
fi

mkdir -p "${kit_path}"
mkdir -p "${dst_path}"

for p in "${include[@]}"; do
  echo "Processing ${src_path}/${p}"
  if ls "${src_path}/${p}" > /dev/null 2>&1; then
    echo "Copying ${src_path}/${p} to ${dst_path}"
	  cp -R "${src_path}/${p}" "${dst_path}"
  fi
done

for e in "${exclude[@]}"; do
  if ls "${src_path}/${e}" 1> /dev/null 2>&1; then
  	if [[ "${e}" =~ '/' ]]; then
			e=$(awk -F/ '{ print $NF }' <<< "${e}")
		fi
  	echo "Excluding ${e} from ${dst_path}"
    find "${dst_path}" -iname "${e}" -exec rm -rf {} \;
  fi
done

for b in "${build[@]}"; do
  if ls "${src_path}/${b}" 1> /dev/null 2>&1; then
      cp -R "${src_path}/${b}" "${dst_path}"
  fi
done

cd "${dst_path}/.." || exit 1
zip -r "${kit_name}" "${plugin_path}"

