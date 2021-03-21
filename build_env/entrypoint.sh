#!/bin/bash

# Note that this does not use pipefail
# because if the grep later doesn't match any deleted files,
# which is likely the majority case,
# it does not exit with a 0, and I only care about the final exit.
set -eo

# Ensure SVN username and password are set
# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!
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
	VERSION=$(echo "${VERSION}" | sed -e "s/^v//")
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

# Should be excluded from the Wordpress.org repo
read -r -a RM_LIST <<< "trunk/.git \
	trunk/.circleci \
	trunk/.github \
	trunk/bin \
	trunk/build_env \
	trunk/docker \
	trunk/inc \
	trunk/src/utilities/.git \
	trunk/src/utilities/bin \
	trunk/src/utilities/.gitignore \
	trunk/src/utilities/README.txt \
	trunk/src/utilities/.editorconfig \
	trunk/src/utilities/metadata.json \
	trunk/src/utilities/composer.json \
	trunk/test \
	trunk/tests \
	trunk/.distignore \
	trunk/.dockerignore \
	trunk/.editorconfig \
	trunk/.env.testing \
	trunk/.gitattributes \
	trunk/.gitignore \
	trunk/.phpcs.xml.dist \
	trunk/action.yml \
	trunk/codeception.dist.yml \
	trunk/composer.json \
	trunk/composer.phar \
	trunk/docker-compose.yml \
	trunk/Dockerfile \
	trunk/Makefile \
	trunk/metadata.json \
	trunk/package.json \
	trunk/patchwork.json \
	trunk/phpstan.dist.neon \
	trunk/phpunit.xml \
	trunk/remove_update.sh \
	tags/${VERSION} \
	"

# Checkout just trunk and assets for efficiency
# Tagging will be handled on the SVN level
echo "➤ Checking out .org repository with SVN..."
svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
cd "${SVN_DIR}"
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

	cd "${GITHUB_WORKSPACE}"

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

	cd "${SVN_DIR}"

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

for remove_file in "${RM_LIST[@]}"; do
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

if [[ -n "${BRANCH}" && "${BRANCH}" =~ ^v[0-9]+\..*[0-9]$ ]]; then
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
