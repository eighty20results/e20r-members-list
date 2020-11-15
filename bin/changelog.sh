#!/usr/bin/env bash
# short_name="00-e20r-utilties"
# remote_server="eighty20results.com"
sed=/usr/bin/sed
readme_path="../build_readmes/"
changelog_source="${readme_path}current.txt"
incomplete_out=tmp.txt
json_out=json_changelog.txt
readme_out=readme_changelog.txt
version=$(egrep "^Version:" ../class-utilitiy-loader.php | "${sed}" 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
json_header="<h3>v${version}</h3><ol>"
json_footer="</ol>"
readme_header="== v${version} =="
###########
#
# Create a metadata.json friendly changelog entry for the current ${version}
#
${sed} -e"s/\"/\'/g" -e's/.*/\<li\>&\<\/li\>/' -e's/\\/\\\\\\\\/g' "${changelog_source}" > "${readme_path}${incomplete_out}"
echo -n "${json_header}" > "${readme_path}${json_out}"
tr -d '\n' >> "${readme_path}${json_out}" < "${readme_path}${incomplete_out}"
echo -n "${json_footer}" >> "${readme_path}${json_out}"
rm "${readme_path}${incomplete_out}"
###########
#
# Create a README.txt friendly changelog entry for the current ${version}
#
echo "${readme_header}" > "${readme_path}${readme_out}"
echo '' >> "${readme_path}${readme_out}"
${sed} -e"s/\"/\'/g" -e"s/.*/\*\ &/" "${changelog_source}" >> "${readme_path}${readme_out}"
echo '' >> "${readme_path}${readme_out}"
