#!/bin/bash
tag_info="$(git describe --tags --abbrev=0)"
git log --pretty=format:"%s (%an)" $tag_info..HEAD > build_readmes/current.txt
