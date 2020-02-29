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

# Checkout just trunk and assets for efficiency
# Tagging will be handled on the SVN level
echo "➤ Checking out .org repository with SVN..."
svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
cd "{$SVN_DIR}"
svn update --set-depth infinity assets
svn update --set-depth infinity trunk

echo "➤ Copying files..."
if [[ -e "${GITHUB_WORKSPACE}/.distignore" ]]; then
	echo "ℹ︎ Using .distignore in ${GITHUB_WORKSPACE}"
	# Copy from current branch to /trunk, excluding dotorg assets
	# The --delete flag will delete anything in destination that no longer exists in source
	rsync --recursive --checksum --verbose --exclude-from="${GITHUB_WORKSPACE}/.distignore" "${GITHUB_WORKSPACE}/" trunk/ --delete-during
	echo "ℹ︎ Copied data to ${GITHUB_WORKSPACE}/"
	ls -l ${GITHUB_WORKSPACE}/class/
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
	${BUILD_DIR}/remove_update.sh
fi

# Copy dotorg assets to /assets
if [[ -d "${GITHUB_WORKSPACE}/$ASSETS_DIR/" ]]; then
	rsync -rc "${GITHUB_WORKSPACE}/$ASSETS_DIR/" assets/ --delete
else
	echo "ℹ︎ No assets directory found; skipping asset copy"
fi

if [[ -f "${SVN_DIR}/class/utilities/class.utilities.php" ]]; then
	echo "ℹ︎ Refreshing the Utilities module from ${SVN_DIR}/class/utilities:"
	cp -R "${SVN_DIR}/class/utilities/*" "trunk/class/utilities/"
	rm -rf "trunk/class/utilities/.git"
	rm -rf "trunk/class/utilities/.gitignore"
	rm -rf "trunk/class/utilities/.editorconfig"
	rm -rf "trunk/class/utilities/composer.json"
fi

if [[ -d "trunk/.git" ]]; then
	echo "ℹ︎ Removing .git directory - not to be included in SVN"
	rm -rf "trunk/.git"
fi

if [[ -f "trunk/Dockerfile" ]]; then
	echo "ℹ︎ Removing Dockerfile - not to be included in SVN"
	rm -rf "trunk/Dockerfile"
fi

if [[ -f "trunk/remove_update.sh" ]]; then
	echo "ℹ︎ Removing remove_update.sh - not to be included in SVN"
	rm -rf "trunk/remove_update.sh"
fi

if [[ -f "trunk/metadata.json" ]]; then
	echo "ℹ︎ Removing metadata.json - not to be included in SVN"
	rm -rf "trunk/metadata.json"
fi

if [[ -f "trunk/package.json" ]]; then
	echo "ℹ︎ Removing package.json - not to be included in SVN"
	rm -rf "trunk/package.json"
fi


# Copy tag locally to make this a single commit (if the tag doesn't exist already
if [[ -d "tags/${VERSION}" ]]; then
	echo "➤ Refresh ${VERSION} tag..."
	rm -rf "tags/${VERSION}"
fi

if [[ -d "tags/${VERSION}/.git" ]]; then
	echo "➤ Refresh ${VERSION} tag..."
	rm -rf "tags/${VERSION}/.git"
fi

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

echo "➤ Committing files..."
svn commit -m "Update to version $VERSION from GitHub" --no-auth-cache --non-interactive  --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}"

echo "✓ Plugin deployed!"
