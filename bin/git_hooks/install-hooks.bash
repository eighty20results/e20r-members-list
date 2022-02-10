#!/usr/bin/env bash
#
# Installs all configured git hooks (using ZSH)
# Copyright 2022(c) Thomas Sjolshagen, Eighty/20 Results by Wicked Strong Chicks, LLC

function main() {
	declare git_dir
	declare source_dir
	declare -A hook_list

	hook_list=( ["pre_commit"]=pre_commit_handler )
	git_dir=$(git rev-parse --git-dir)
	source_dir="git_hooks"

	echo "Installing Git hooks from ${source_dir} to ${git_dir}/hooks"
	for key in "${!hook_list[@]}"; do
		declare hook_file="${key//_/\-}"
		echo "${source_dir}/${hook_list[$key]} -> ${git_dir}/hooks/${hook_file}"
		ln -sf "${source_dir}/${hook_list[$key]}" "${git_dir}/hooks/${key}" || die "Error: Unable to link ${source_dir}/${hook_list[$key]} to ${git_dir}/hooks/${hook_file}"
	done
}
}

main "$@"
