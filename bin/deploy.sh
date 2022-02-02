#!/usr/bin/env bash
#
# Send plugin kit to the specified E20R WooCommerce server (assumes appropriate secrets for GitHub Action)
# OR (TODO: to the WordPress.org plugin repository (SVN based))
#
# Copyright 2021 - 2022(c) Eighty/20 Results by Wicked Strong Chicks, LLC
#

function to_woocommerce_store() {
	declare metadata
	declare remote_path
	declare src_path
	declare dst_path
	declare plugin_path
	declare kit_path
	declare kit_name
	declare target_server
	declare ssh_port
	declare ssh_user
	declare ssh_host

	# Should only be used when running as a GitHub action for a non-main branch
	if [[ ! "${BRANCH_NAME}" =~ (release-([vV])?[0-9]+\.[0-9]+(\.[0-9]+)?|([vV])?[0-9]+\.[0-9]+(\.[0-9]+)?) ]]; then
		echo "Creating mocked ssh and scp command, then we won't actually deploy anything from ${BRANCH_NAME}"

		function ssh() {
			echo ssh "$@"
		}

		function scp() {
			echo scp "$@"
		}
	else
		echo "Not sure what the BRANCH_NAME environment variable is..? '${BRANCH_NAME}'"
	fi

	src_path="$(pwd)"
	plugin_path="${short_name}"
	dst_path="${src_path}/build/${plugin_path}"
	ssh_port="22"
	ssh_host="${remote_server}"
	ssh_user="$(id -un)"

	kit_path="${src_path}/build/kits"
	kit_name="${kit_path}/${short_name}-${version}.zip"

	if [ -n "${E20R_SSH_USER}" ]; then
		echo "Using environment variable to set SSH target server user"
		ssh_user="${E20R_SSH_USER}"
	fi

	if [ -n "${E20R_SSH_SERVER}" ]; then
		echo "Using environment variable to set SSH target server"
		ssh_host="${E20R_SSH_SERVER}"
	fi

	if [ -n "${E20R_SSH_PORT}" ]; then
		echo "Using environment variable to set SSH target server port"
		ssh_port="${E20R_SSH_PORT}"
	fi

	target_server="${ssh_user}@${ssh_host}"
	remote_path="./www/eighty20results.com/public_html/protected-content"
	metadata="${src_path}/metadata.json"

	# We _want_ to expand the variables on the client side
	# shellcheck disable=SC2029
	if ! ssh -o StrictHostKeyChecking=no -p "${ssh_port}" "${target_server}" "cd ${remote_path}; mkdir -p \"${short_name}\""; then
		echo "Error: Cannot create ${short_name} directory in ${remote_path}"
		exit 1
	fi

	echo "Copying ${kit_name} to ${remote_server}:${remote_path}/${short_name}/"
	if ! scp -r -o StrictHostKeyChecking=no -P "${ssh_port}" "${kit_name}" "${target_server}:${remote_path}/${short_name}/"; then
		echo "Error: Cannot copy ${kit_name} to ${remote_server}:${remote_path}/${short_name}/!"
		exit 1
	fi

	echo "Copying ${metadata} to ${remote_server}:${remote_path}/${short_name}/"
	if ! scp -r -o StrictHostKeyChecking=no -P "${ssh_port}" "${metadata}" "${target_server}:${remote_path}/${short_name}/"; then
		echo "Error: Unable to copy ${metadata} to ${remote_server}:${remote_path}/${short_name}/"
		exit 1
	fi

	echo "Linking ${short_name}/${short_name}-${version}.zip to ${short_name}.zip on remote server"

	# We _want_ to expand the variables on the client side
	# shellcheck disable=SC2029
	if ! ssh -o StrictHostKeyChecking=no -p "${ssh_port}" "${target_server}" \
		"cd ${remote_path}/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip" ; then
		echo "Error: Unable to link ${short_name}/${short_name}-${version}.zip to ${short_name}.zip"
		exit 1
	fi

	# Return to the root directory
	cd "${src_path}" || exit 1

	# And clean up
	rm -rf "${dst_path}" || exit 1
}

# Ensure SVN username and password are set
# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!
function to_wordpress_org() {

	if [[ -z "${SVN_USERNAME}" ]]; then
		echo "Set the SVN_USERNAME secret"
		exit 1
	fi

	if [[ -z "${SVN_PASSWORD}" ]]; then
		echo "Set the SVN_PASSWORD secret"
		exit 1
	fi

	if [[ -z "${BUILD_DIR}" ]]; then
		echo "Set BUILD_DIR environment variable!"
		exit 1
	fi

	# Allow some ENV variables to be customized
	if [[ -z "${SLUG}" ]]; then
		SLUG="${GITHUB_REPOSITORY#*/}"
	fi
	echo "ℹ︎ SLUG is ${SLUG}"

	if [[ -z "${BRANCH}" ]]; then
		BRANCH=$( awk -F/ '{ print $NF }' <<< "${GITHUB_REF}" )
	fi
	echo "ℹ︎ BRANCH is ${BRANCH}"

	# Does it even make sense for VERSION to be editable in a workflow definition?
	if [[ -z "${VERSION}" ]]; then
		VERSION="${GITHUB_REF#refs/tags/}"
		VERSION=$("${VERSION}" | sed -e "s/^v//")
	fi
	echo "ℹ︎ VERSION is ${VERSION}"

	if [[ -z "${ASSETS_DIR}" ]]; then
		ASSETS_DIR=".wordpress-org"
	fi
	echo "ℹ︎ ASSETS_DIR is ${ASSETS_DIR}"

	if [[ -f "${GITHUB_WORKSPACE}/.gitmodules" ]]; then
		git config --global user.email "thomas@eighty20results.com"
		git config --global user.name "Eighty/20Results Bot on Github"

		echo "➤ Refresh all submodule(s) for the project"
		git submodule update --remote
	fi

	SVN_URL="http://plugins.svn.wordpress.org/${SLUG}/"
	SVN_DIR="/github/svn-${SLUG}"

	# Checkout just trunk and assets for efficiency
	# Tagging will be handled on the SVN level
	echo "➤ Checking out .org repository with SVN..."
	svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
	cd "${SVN_DIR}" || (echo "ℹ︎ Unable to change directory to ${SVN_DIR}" ; exit 1)
	svn update --set-depth infinity assets
	svn update --set-depth infinity trunk

	if [[ -d "${SVN_DIR}/tags/${VERSION}" ]]; then
		echo "ℹ︎ Removing pre-existing release from /tags/ directory"
		rm -rf "${SVN_DIR}/tags/${VERSION}"
		# TODO(?): Remove commit that contains the update(d) version from SVN?
	fi

	echo "➤ Copying files..."
	if [[ -e "${GITHUB_WORKSPACE}/.distignore" ]]; then
		echo "ℹ︎ Using .distignore in ${GITHUB_WORKSPACE}"
		# Copy from current branch to /trunk, excluding dotorg assets
		# The --delete flag will delete anything in destination that no longer exists in source
		rsync --recursive --checksum --verbose --exclude-from="${GITHUB_WORKSPACE}/.distignore" "${GITHUB_WORKSPACE}/" trunk/ --delete-during
		echo "ℹ︎ Copied data to ${GITHUB_WORKSPACE}/"
	else
		echo "ℹ︎ Using .gitattributes in ${GITHUB_WORKSPACE}"

		cd "${GITHUB_WORKSPACE}" || (echo "ℹ︎ Cannot change directory to ${GITHUB_WORKSPACE}!" ; exit 1)

		# "Export" a cleaned copy to a temp directory
		TMP_DIR="/github/archivetmp"
		mkdir "${TMP_DIR}"

		git config --global user.email "thomas@eighty20results.com"
		git config --global user.name "Eighty/20Results Bot on Github"

		# If there's no .gitattributes file, write a default one into place
		if [[ ! -e "${GITHUB_WORKSPACE}/.gitattributes" ]]; then
			cat > "${GITHUB_WORKSPACE}/.gitattributes" <<-EOL
			/${ASSETS_DIR} export-ignore
			/.gitattributes export-ignore
			/.gitignore export-ignore
			/.github export-ignore
			EOL

			# Ensure we are in the $GITHUB_WORKSPACE directory, just in case
			# The .gitattributes file has to be committed to be used
			# Just don't push it to the origin repo :)
			git add .gitattributes && git commit -m "Add .gitattributes file"
		fi

		# This will exclude everything in the .gitattributes file with the export-ignore flag
		git archive HEAD | tar x --directory="${TMP_DIR}"

		cd "${SVN_DIR}" || (echo "ℹ︎ SVN_DIR environment variable is not set. Exiting!" ; exit 1)

		# Copy from clean copy to /trunk, excluding dotorg assets
		# The --delete flag will delete anything in destination that no longer exists in source
		rsync --recursive --checksum --verbose "$TMP_DIR/" trunk/ --delete
	fi

	# Removal of unsupported/disallowed one-click update functionality
	if [[ -f "${BUILD_DIR}/remove_update.sh" ]]; then
		echo "➤ Trigger removal of custom one-click update functionality. In ${PWD}"
		"${BUILD_DIR}/remove_update.sh"
	fi

	# Copy dotorg assets to /assets
	if [[ -d "${GITHUB_WORKSPACE}/$ASSETS_DIR/" ]]; then
		rsync -rc "${GITHUB_WORKSPACE}/$ASSETS_DIR/" assets/ --delete
	else
		echo "ℹ︎ No assets directory found; skipping asset copy"
	fi

	if [[ -f "${SVN_DIR}/src/utilities/class.utilities.php" ]]; then
		echo "ℹ︎ Refreshing the Utilities module from ${SVN_DIR}/class/utilities:"
		cp -R "${SVN_DIR}/src/utilities/*" "trunk/src/utilities/"
	fi

	# Should be excluded from the Wordpress.org repo
	if [ -z "${excluded_for_svn}" ]; then
		echo "ℹ︎ Don't believe there's nothing Git related that should be excluded from the SVN repository!"
		exit 1
	fi

	for remove_file in "${excluded_for_svn[@]}"; do
		# Only need to remove the file if it exists
		if [[ -f "${remove_file}" || -d $"${remove_file}" ]]; then
			echo "ℹ︎ Removing ${remove_file}. Not to be included in the SVN repo"
			rm -rf "${remove_file}"
		fi
	done

	# Add everything and commit to SVN
	# The force flag ensures we recurse into subdirectories even if they are already added
	# Suppress stdout in favor of svn status later for readability
	echo "➤ Preparing files..."
	svn add . --force > /dev/null

	# SVN delete all deleted files
	# Also suppress stdout here
	svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null

	echo "➤ Copying tag..."
	svn cp "trunk" "tags/${VERSION}"

	svn status

	echo "➤ Testing that we need to push to Wordpress.org"

	if [[ -n "${BRANCH_NAME}" && "${BRANCH_NAME}" =~ ^v[0-9]+\..*[0-9]$ ]]; then
		echo "➤ In main branch so committing files to Wordpress.org SVN repository..."
		svn commit -m "Update to version ${VERSION} from GitHub" \
		--no-auth-cache \
		--non-interactive  \
		--username "${SVN_USERNAME}" \
		--password "${SVN_PASSWORD}"
		echo "✓ Plugin deployed!"
	else
		echo "✓ Not in main branch. Nothing to do"
	fi
}

source build_config/helper_config "${@}"

if [ -z "${SVN_USERNAME}"  ] && [ -n "${E20R_SSH_USER}" ]; then
	echo "ℹ︎ Will attempt to deploy ${E20R_PLUGIN_NAME} to the WooCommerce Store"
	to_woocommerce_store "$@"
fi

if [ -z "${E20R_SSH_USER}" ] && [ -n "${SVN_USERNAME}" ]; then
	echo "ℹ︎ Will attempt to deploy ${E20R_PLUGIN_NAME} to the Wordpress.org Repository"
	to_wordpress_org "$@"
fi
