#!/usr/bin/env bash
VERSION=$(cat ../TAG)
if [[ "${VERSION}" =~ ^v.*$ ]]; then
	ghr -t "${GITHUB_OAUTH_TOKEN}" \
  -u "${CIRCLE_PROJECT_USERNAME}" \
	-r "${CIRCLE_PROJECT_REPONAME}" \
	-c "${CIRCLE_SHA}" \
	-b "See [CHANGELOG](https://github.com/${CIRCLE_PROJECT_USERNAME}/${CIRLCE_PROJECT_REPONAME}/blob/${VERSION}/CHANGELOG.md)" \
	-delete "${VERSION}" ../docs
else
	echo "No version found - no release created";
fi
