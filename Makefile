###
# Plugin specific settings for Makefile - You may need to change information
# in the included file!
###
include build_config/plugin_config.mk

###
# Standard settings for Makefile - Probably won't need to change anything here
###
SHELL := /bin/bash
BASE_PATH := $(PWD)
FIND := $(shell which find)
CURL := $(shell which curl)
UNZIP := $(shell which unzip)
PHP_BIN := $(shell which php)
DC_BIN := $(shell which docker-compose)
SQL_BACKUP_FILE ?= tests/_data
MYSQL_DATABASE ?= wordpress
MYSQL_USER ?= wordpress
MYSQL_PASSWORD ?= wordpress
DB_IMAGE ?= mariadb
WORDPRESS_DB_HOST ?= localhost
WP_PLUGIN_URL ?= "https://downloads.wordpress.org/plugin/"
E20R_PLUGIN_URL ?= "https://eighty20results.com/protected-content"
WP_CONTAINER_NAME ?= codecep-wp-$(E20R_PLUGIN_NAME)
DB_CONTAINER_NAME ?= $(DB_IMAGE)-wp-$(E20R_PLUGIN_NAME)

FOUND_WP_UNIT_TESTS ?= $(wildcard tests/wpunit/testcases/*.php)
FOUND_UNIT_TESTS ?= $(wildcard tests/unit/testcases/*.php)
FOUND_WP_ACCEPTANCE_TESTS ?= $(wildcard /tests/acceptance/testcases/*.php)
FOUND_FUNCTIONAL_TESTS ?= $(wildcard tests/functional/testcases/*.php)

UNIT_TEST_CASE_PATH := tests/unit/testcases/
WPUNIT_TEST_CASE_PATH := tests/wpunit/testcases/
FUNCTIONAL_TEST_CASE_PATH := tests/functional/testcases/
ACCEPTANCE_TEST_CASE_PATH := tests/acceptance/testcases/

ifneq ($(wildcard ./tests/docker/docker.hub.key),)
$(info Path to key for docker hub exists)
CONTAINER_ACCESS_TOKEN := $(shell cat ./tests/docker/docker.hub.key)
endif

ifneq ($(wildcard ./docker.hub.key),)
$(info Path to key for docker hub exists)
CONTAINER_ACCESS_TOKEN := $(shell cat ./docker.hub.key)
endif

ifeq ($(DOCKER_USER),)
$(info Using Makefile variable to set the docker hub username)
DOCKER_USER ?= $(DOCKER_HUB_USER)
endif

CONTAINER_REPO ?= 'docker.io/$(DOCKER_USER)'
DOCKER_IS_RUNNING := $(shell ps -ef | grep Docker.app | wc -l | xargs)

ifeq ($(CONTAINER_ACCESS_TOKEN),)
$(info Setting CONTAINER_ACCESS_TOKEN from environment variable)
CONTAINER_ACCESS_TOKEN := $(shell echo "$${CONTAINER_ACCESS_TOKEN}" )
endif

DOWNLOAD_MODULE := 1

$(info Wildcard result: $(wildcard $(E20R_UTILITIES_PATH)/src/licensing/class-licensing.php))

# Determine if there is a local (to this system) instance of the E20R Utilities module repository
ifneq ($(wildcard $(E20R_UTILITIES_PATH)/src/licensing/class-licensing.php),)
DOWNLOAD_MODULE := $(shell grep -q 'public function __construct' $(E20R_UTILITIES_PATH)/src/licensing/class-licensing.php 2>/dev/null && echo "0")
endif

$(info Download the E20R Utilities module: $(DOWNLOAD_MODULE))

#ifeq ($(CONTAINER_ACCESS_TOKEN),)
#	echo "Error: Docker login token is not defined!"
#	exit 1
#endif

# PROJECT := $(shell basename ${PWD}) # This is the default as long as the plugin name matches
PROJECT := $(E20R_PLUGIN_NAME)
VOLUME_CONTAINER ?= $(PROJECT)_volume

# Settings for docker-compose
DC_CONFIG_FILE ?= $(PWD)/docker-compose.yml
DC_ENV_FILE ?= $(PWD)/tests/_envs/.env.testing

STACK_RUNNING := $(shell APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
    		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) ps -q 2> /dev/null | wc -l | xargs)

$(info Number of running docker images:$(STACK_RUNNING))

.PHONY: \
	docs \
	readme \
	changelog \
	metadata \
	git-log \
	clean \
	clean-inc \
	clean-wp-deps \
	real-clean \
	wp-deps \
	e20r-deps \
	php-composer \
	is-docker-running \
	docker-deps \
	docker-compose-deps \
	start-stack \
	stop-stack \
	restart \
	shell \
	lint-test \
	code-standard-test \
	phpstan-test \
	wp-unit-test \
	acceptance-test \
	build-test \
	new-release \
	wp-shell \
	wp-log \
	db-shell \
	db-backup \
	db-import \
	test \
	image-build \
	image-pull \
	image-push \
	image-scan \
	docker-hub-login

#
# Clean up Codeception and GitHub action artifacts
#
clean:
	@if [[ -n "$(STACK_RUNNING)" ]]; then \
		if [[ -f $(COMPOSER_DIR)/bin/codecept ]]; then \
			$(COMPOSER_DIR)/bin/codecept clean ; \
		fi ; \
	fi
	@rm -rf _actions/
	@rm -rf workflow

#
# Remove all installed composer, WordPress and E20R plugins/components from the $(COMPOSER_DIR) - /inc - directory
#
clean-inc:
	@find $(COMPOSER_DIR)/* -type d -maxdepth 0 -exec rm -rf {} \; && rm $(COMPOSER_DIR)/*.php

#
# Log in to your Docker HUB account before performing pull/push operations
#
docker-hub-login:
	@if [[ "inactive" != "$(LOCAL_NETWORK_STATUS)" ]]; then \
		echo "Login for Docker Hub" ; \
		docker login --username ${DOCKER_USER} --password ${CONTAINER_ACCESS_TOKEN} ; \
	fi


#
# (re)Build the Docker images for this development/test environment
#
image-build: docker-deps
	@if [[ "$(LOCAL_NETWORK_STATUS)" != "inactive" ]]; then \
		echo "Building the docker container stack for $(PROJECT)" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
  		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) build --pull --progress tty ; \
	fi

#
# Trigger the security scan of the docker image(s)
#
image-scan: docker-hub-login
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker scan --accept-license $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

#
# Push the custom Docker images for this plugin to the Docker HUB (security scan if possible)
# FIXME: Enable the image-scan target (if we can get the issues fixed)
#
image-push: docker-hub-login # image-scan
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker tag $(PROJECT)_wordpress $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker push $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

#
# Attempt to pull (download) the plugin specific Docker images for the test/development environment
#
image-pull: docker-hub-login
	@if docker manifest inspect $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION) > /dev/null && "$(LOCAL_NETWORK_STATUS)" != "inactive"; then \
		echo "Pulling image from Docker repo" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
      		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
        	docker pull $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION); \
     fi

#
# Clean up Composer and WP/E20R dependencies as well as the Docker container(s) used for testing/development
#
real-clean: stop-stack clean clean-inc clean-wp-deps
	echo "Removing docker images" && \
	docker image remove $(PROJECT)_wordpress --force

#
# Install the composer.phar file to the local directory
#
php-composer:
	@if [[ ! -z "$(PHP_BIN)" && "Xinactive" != "X$(LOCAL_NETWORK_STATUS)" ]]; then \
	    echo "Install the PHP Composer component" && \
	    $(PHP_BIN) -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
	    $(PHP_BIN) -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
            $(PHP_BIN) composer-setup.php --install-dir=$(BASE_PATH)/ && \
            $(PHP_BIN) -r "unlink('composer-setup.php');" ; \
    fi

#
# Install the Composer packages required by this plugin when it is released
#
composer-prod: composer.json real-clean clean-inc php-composer
	@echo "Install/Update the Production composer dependencies"
	@$(PHP_BIN) $(COMPOSER_BIN) update --ansi --prefer-stable --no-dev

#
# Install the required Composer packages to develop/test this plugin
#
composer-dev: composer.json php-composer
	@echo "Use composer to install/update the PHP test dependencies"
	@$(PHP_BIN) $(COMPOSER_BIN) update --ansi --prefer-stable

#
# Install docker-compose for use
# FIXME: At some point soon, we should be able to use `docker compose ...` rather than `docker-compose ...`
#
docker-compose:
	@if [[ -z "$(DC_BIN)" && ! -f /usr/local/bin/docker-compose ]]; then \
		echo "Installing docker-compose" && \
		sudo curl --silent -L https://github.com/docker/compose/releases/download/$(COMPOSER_VERSION)/docker-compose-`uname -s`-`uname -m` \
			-o /usr/local/bin/docker-compose && \
		sudo chmod +x /usr/local/bin/docker-compose ; \
	fi

#
# Remove any WP or E20R custom plugins from inc/wp_plugins/*
#
clean-wp-deps:
	@rm -rf $(COMPOSER_DIR)/wp_plugins/*

# git archive --prefix="$${e20r_plugin}/" --format=zip --output="$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" --worktree-attributes main &&

#
# Install custom plugins from Eighty/20 Results (hosted on eighty20results.com) or in the local filesystem
# Test whether there is a local presence for the specified custom plugin and build it if present.
# If not present on the local filesystem, then we'll download it and install it to inc/wp_plugins
#
e20r-deps:
	@echo "Loading defined E20R custom plugin dependencies"
	@mkdir -p $(COMPOSER_DIR)/wp_plugins
	@DOWNLOAD_MODULE=${DOWNLOAD_MODULE} ; \
	for e20r_plugin in $(E20R_DEPENDENCIES) ; do \
		echo "Checking for presence of $(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}..." ; \
		if [[ ! -d "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" ]]; then \
			echo "Download or build $${e20r_plugin}.zip dependency?" && \
			if [[ "0" -eq "$${DOWNLOAD_MODULE}" && "00-e20r-utilities" -eq "$${e20r_plugin}" ]]; then \
				echo "Build $${e20r_plugin} archive and save to $(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
				cd ${E20R_UTILITIES_PATH} && \
				make build && \
				new_kit="$$(ls -art build/kits/$${e20r_plugin}* | tail -1)" && \
				echo "Copy $${new_kit} to $(BASE_PATH)/$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" && \
				cp "$${new_kit}" "$(BASE_PATH)/$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" && \
				cd $(BASE_PATH) ; \
			else \
				echo "Download $${e20r_plugin} to $(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
				$(CURL) --silent -L "$(E20R_PLUGIN_URL)/$${e20r_plugin}.zip" -o "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" ; \
			fi ; \
			mkdir -p "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
			echo "Installing the $${e20r_plugin}.zip plugin" && \
			$(UNZIP) -o "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" -d $(COMPOSER_DIR)/wp_plugins/ 2>&1 > /dev/null && \
			rm -f "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" ; \
		fi ; \
	done

#
# Check if Docker is running. If not, exit the make operation
#
is-docker-running:
	@if [[ "0" -eq $(DOCKER_IS_RUNNING) ]]; then \
		echo "Error: Docker is not running on this system!" && \
		exit 1; \
	fi

#
# Install docker-compose and the WordPress plugins or E20R plugin dependencies listed
#
docker-deps: is-docker-running docker-compose wp-deps

#
# Install any composer dependencies (test & development) plus
# WordPress plugins (using the WP.org plugin slug) that are listed as dependencies in the WP_DEPENDENCIES, and
# triggers the e20r-deps target, which installs any Eighty20Results.com custom plugins we need (normally the
# E20R Utilities module).
#
wp-deps: clean composer-dev e20r-deps
	@echo "Loading WordPress plugin dependencies"
	@for dep_plugin in ${WP_DEPENDENCIES} ; do \
  		if [[ ! -d "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" ]]; then \
  		  echo "Download and install $${dep_plugin} to $(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" && \
  		  mkdir -p "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" && \
  		  $(CURL) --silent -L "$(WP_PLUGIN_URL)/$${dep_plugin}.zip" -o "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" && \
  		  $(UNZIP) -o "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" -d $(COMPOSER_DIR)/wp_plugins/ 2>&1 > /dev/null && \
  		  rm -f "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" ; \
  		fi ; \
  	done

#
# Install all required docker dependencies (docker-compose)
# NOTE: This target assumes the main docker binaries are installed on the system where this Makefile runs!)
#
start-stack: docker-deps image-pull image-build
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@if [[ "2" -ne $(STACK_RUNNING) ]]; then \
  		echo "Building and starting the WordPress stack for testing purposes" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --detach --remove-orphans ; \
	fi
	@echo "Started the $(PROJECT) docker environment..."

#
# Start the docker-compose stack for this plugin _and_ import the .sql file (data) stored as
# ./tests/_data/{PLUGIN_NAME}.sql
#
db-import: start-stack
	@echo "Maybe load WordPress data...?"
	@bin/wait-for-db.sh "$(MYSQL_USER)" "$(MYSQL_PASSWORD)" "$(WORDPRESS_DB_HOST)" "$(E20R_PLUGIN_NAME)"
	@if [[ -f $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql ]]; then \
  		echo "Loading WordPress data to use for testing $(E20R_PLUGIN_NAME)" && \
  		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec -T database \
        	mysql \
        		--user=$(MYSQL_USER) \
        		--password="$(MYSQL_PASSWORD)" \
        		--host=$(WORDPRESS_DB_HOST) \
        		$(MYSQL_DATABASE) < $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql ; \
  	fi

#
# Stop the docker-compose stack for this plugin
#
stop-stack:
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@if [[ 0 -lt "$(STACK_RUNNING)" ]]; then \
  		echo "Stopping the $(PROJECT) WordPress stack" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
			docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) down 2>/dev/null ; \
	fi


#
# Restart the docker-compose stack and re-import the database
#
restart: stop-stack db-import

#
# Open a shell against the Docker container for the WordPress instance
#
wp-shell:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
    		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec wordpress /bin/bash

#
# Show the Docker logs for the WordPress instance
# (likely containing debug logging from the plugin - if it uses the error_log() function)
#
wp-log:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) logs -f wordpress

#
# Open a shell against the Docker container for the MariaDB instance
#
db-shell: start-stack
	@echo "Launching the docker shell for the user"
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database /bin/bash

#
# Saves the MySQL Database content to the designated (local) path.
# NOTE: You will need to commit/update the .sql file
#
db-backup:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database \
 			/usr/bin/mysqldump -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) > $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql

#
# Using the local environment to execute the PHPStan tests (code analysis)
#
phpstan-test: composer-dev wp-deps
	@echo "Loading the PHP-Stan tests for $(PROJECT)"
	@inc/bin/phpstan analyze \
		--ansi \
		--debug \
		-v \
		--configuration=./phpstan.dist.neon \
		--no-interaction \
		--memory-limit=-1

#
# Using the local environment to execute the PHP Code Standards tests (using WPCS-Extra ruleset)
#
code-standard-test: wp-deps
	@echo "Running WP Code Standards testing for $(PROJECT)"
	@$(COMPOSER_DIR)/bin/phpcs \
		--runtime-set ignore_warnings_on_exit true \
		--report=full \
		--colors \
		-p \
		-s \
		--standard=phpcs.xml \
		--ignore='$(PHP_IGNORE_PATHS)' \
		--extensions=php \
		$(PHP_CODE_PATHS)

#
# Using codeception to execute standard Unit Tests for this plugin
#
unit-test: wp-deps
	@if [[ -n "$(FOUND_UNIT_TESTS)" ]]; then \
		echo "Running Unit tests for $(PROJECT)"; \
		$(COMPOSER_DIR)/bin/codecept run unit --verbose --debug --coverage-html ./coverage/unit $(UNIT_TEST_CASE_PATH); \
	fi
# TODO: Add coverage support to the unit-test target

coverage: wp-deps
	@if [[ -n "$(FOUND_UNIT_TESTS)" ]]; then \
		echo "Running Unit tests for $(PROJECT)"; \
		$(COMPOSER_DIR)/bin/codecept run unit --coverage --verbose --debug $(UNIT_TEST_CASE_PATH); \
	fi
#
# Using codeception to execute the WP Unit Tests (aka WP integration tests) for this plugin
#
wp-unit-test: docker-deps start-stack db-import
	@if [[ -n "$(FOUND_WP_UNIT_TESTS)" ]]; then \
  		echo "Running WP Unit/Functional tests for $(PROJECT)"; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
  		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
  			exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
  			wordpress $(COMPOSER_DIR)/bin/codecept run wpunit --coverage-html ./coverage/wp-unit --verbose --debug $(WPUNIT_TEST_CASE_PATH); \
	fi
# TODO: Add coverage support to the wp-unit-test target
wp-unit-start: docker-deps start-stack db-import

wp-unit:
	@if [[ -n "$(FOUND_WP_UNIT_TESTS)" ]]; then \
  		echo "Running WP Unit/Functional tests for $(PROJECT)/$(TEST_TO_RUN)"; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
  		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
  			exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
  			wordpress $(COMPOSER_DIR)/bin/codecept run wpunit --coverage-html ./coverage/wp-unit --verbose --debug $(TEST_TO_RUN); \
	fi

#
# Using codeception to execute the WP Unit Tests (aka WP integration tests) for this plugin
#
functional-test: docker-deps start-stack db-import
	@if [[ -n "$(FOUND_FUNCTIONAL_TESTS)" ]]; then \
  		echo "Running WP Unit tests for $(PROJECT)"; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
			docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
				exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
				wordpress $(COMPOSER_DIR)/bin/codecept run --coverage-html ./coverage/functional --verbose --debug functional $(FUNCTIONAL_TEST_CASE_PATH); \
	fi
# TODO: Add coverage support to the functional-test target

#
# Using codeception to execute the Plugin Acceptance tests
#
acceptance-test: docker-deps start-stack db-import
	@if [[ -n "$(FOUND_WP_ACCEPTANCE_TESTS)" ]]; then \
  		echo "Running WP Acceptance tests for $(PROJECT)"; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 		exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ wordpress \
	 		$(COMPOSER_DIR)/bin/codecept run acceptance --debug --verbose --coverage-html ./coverage/acceptance $(ACCEPTANCE_TEST_CASE_PATH); \
	fi
#
# Using codeception to build the plugin
#
build-test: docker-deps start-stack db-import
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) COMPOSE_INTERACTIVE_NO_CLI=1 \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 		exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 		wordpress $(PWD)/$(COMPOSER_DIR)/bin/codecept build -v

#
# Using codeception to execute all defined tests for the plugin
#
test: clean wp-deps code-standard-test phpstan-test unit-test db-import wp-unit-test acceptance-test stop-stack

#
# Generate a GIT commit log in build_readmes/current.txt
#
git-log:
	@./bin/create_log.sh

#
# Generate (and update) the custom WP Plugin Updater metadata.json file
#
metadata:
	@./bin/metadata.sh "$(E20R_PLUGIN_BASE_FILE)" "$(E20R_DEPLOYMENT_SERVER)"

#
# Generate the CHANGELOG.md file for the plugin based on the git commit log
#
changelog: build_readmes/current.txt
	@./bin/changelog.sh "$(E20R_PLUGIN_BASE_FILE)" "$(E20R_DEPLOYMENT_SERVER)"

#
# Generate and update the README.txt plus README.md files for the plugin
#
readme:
	@./bin/readme.sh "$(E20R_PLUGIN_BASE_FILE)" "$(E20R_DEPLOYMENT_SERVER)"

#
# Build the plugin .zip archive (and upload to the eighty20results.com server if applicable
# Saves the built plugin .zip archive to build/kits
#
$(E20R_PLUGIN_BASE_FILE): stop-stack clean-inc composer-prod
	@if [[ -z "$${USE_LOCAL_BUILD}" ]]; then \
  		echo "Deploying kit to $(E20R_DEPLOYMENT_SERVER)" && \
  		E20R_PLUGIN_NAME=$(E20R_PLUGIN_NAME) ./bin/build-plugin.sh "$(E20R_PLUGIN_BASE_FILE)" "$(E20R_DEPLOYMENT_SERVER)"; \
	else \
		rm -rf $(COMPOSER_DIR)/wp_plugins && \
		mkdir -p build/kits/ && \
		E20R_PLUGIN_VERSION=$$(./bin/get_plugin_version.sh "$(E20R_PLUGIN_BASE_FILE)") \
		git archive --prefix=$(E20R_PLUGIN_NAME)/ --format=zip --output=build/kits/$(E20R_PLUGIN_NAME)-$${E20R_PLUGIN_VERSION}.zip --worktree-attributes main ; \
	fi

#
# Build the plugin .zip archive (and upload to the eighty20results.com server if applicable
# Saves the built plugin .zip archive to build/kits
#
build: $(E20R_PLUGIN_BASE_FILE)
	@echo "Built kit for $(E20R_PLUGIN_NAME)"

deploy:
	@echo "Deploy ${E20R_PLUGIN_NAME}.zip to ${E20R_DEPLOYMENT_SERVER}"
	@./bin/deploy.sh "${E20R_PLUGIN_BASE_FILE}" "${E20R_DEPLOYMENT_SERVER}"

#new-release: test composer-prod
#	@./build_env/get_version.sh && \
#		git tag $${VERSION} && \
#		./build_env/create_release.sh

#
# Generate the README.*, CHANGELOG.md and metadata.json files for the plugin (all current docs)
#
docs: changelog readme metadata
	@if ! git commit -m "Updated the changelog source file" build_readmes/current.txt; then \
    	echo "No need to commit build_readmes/current.txt (no changes recorded)" ; \
    	exit 0 ; \
  	fi
