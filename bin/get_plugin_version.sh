#!/bin/bash
plugin_file="${1}"
grep -E "^Version:" "${plugin_file}" | \
	sed 's/[[:alpha:]|(|[:space:]|\:]//g' | \
	awk -F- '{printf "%s", $1}'
