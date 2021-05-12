DOCKER_USER ?= eighty20results
E20R_PLUGIN_NAME ?= e20r-members-list
WP_IMAGE_VERSION ?= 1.0
COMPOSER_VERSION ?= 1.29.2

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
APACHE_RUN_USER ?= $(shell id -u)
# APACHE_RUN_GROUP ?= $(shell id -g)
APACHE_RUN_GROUP ?= $(shell id -u)
SQL_BACKUP_FILE ?= $(PWD)/docker/test/db_backup
MYSQL_DATABASE ?= wordpress
MYSQL_USER ?= wordpress
MYSQL_PASSWORD ?= wordpress
DB_IMAGE ?= mariadb
DB_VERSION ?= latest
WORDPRESS_DB_HOST ?= localhost
WP_VERSION ?= latest
WP_DEPENDENCIES ?= paid-memberships-pro
WP_PLUGIN_URL ?= "https://downloads.wordpress.org/plugin/"
WP_CONTAINER_NAME ?= codecep-wp-$(E20R_PLUGIN_NAME)
DB_CONTAINER_NAME ?= $(DB_IMAGE)-wp-$(E20R_PLUGIN_NAME)
CONTAINER_ACCESS_TOKEN ?= $(shell [[ -f ./docker.hub.key ]] && cat ./docker.hub.key)
CONTAINER_REPO ?= 'docker.io/$(DOCKER_USER)'

# PROJECT := $(shell basename ${PWD}) # This is the default as long as the plugin name matches
PROJECT := $(E20R_PLUGIN_NAME)
VOLUME_CONTAINER ?= $(PROJECT)_volume

# Settings for docker-compose
DC_CONFIG_FILE ?= $(PWD)/docker-compose.yml
DC_ENV_FILE ?= ./tests/_envs/.env.testing

STACK_RUNNING := $(shell APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
    		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) ps -q | wc -l)

.PHONY: \
	clean \
	real-clean \
	deps \
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
	gitlog \
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
	repo-login

clean:
	@if [[ -n "$(STACK_RUNNING)" ]]; then \
		if [[ -f inc/bin/codecept ]]; then \
			inc/bin/codecept clean ; \
		fi ; \
		rm -rf inc/wp_plugins ; \
	fi
	@rm -rf _actions/
	@rm -rf workflow

repo-login:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker login --username $(DOCKER_USER) --password-stdin <<< $(CONTAINER_ACCESS_TOKEN)

image-build: deps
	@echo "Building the docker container stack for $(PROJECT)"
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) build --pull --progress tty

image-scan: repo-login
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker scan --accept-license $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

image-push: repo-login # image-scan - TODO: Enable image-scan if we can get the issues fixed
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker tag $(PROJECT)_wordpress $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker push $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

image-pull: repo-login
	@echo "Pulling image from Docker repo"
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
      		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
        	docker pull $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

real-clean: stop-stack clean
	@echo "Make sure docker-compose stack for $(PROJECT) isn't running"
	@echo "Stack is running: $(STACK_RUNNING)"
	@if [[ 2 -ne "$(STACK_RUNNING)" ]]; then \
		echo "Stopping docker-compose stack" ; \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) rm --stop --force -v ; \
	fi ; \
	echo "Removing docker images" ; \
	docker image remove $(PROJECT)_wordpress --force && \
	docker image remove $(DB_IMAGE) --force && \
	echo "Removing the composer dependencies" && \
	rm -rf inc/*

php-composer:
	@if [[ -z "$(PHP_BIN)" ]]; then \
		echo "Install the PHP Composer component" && \
		$(which php) -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
		$(which php) -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
        $(which php) composer-setup.php --install-dir=/usr/local/bin && \
        $(which php) -r "unlink('composer-setup.php');" ; \
    fi

composer-prod: real-clean php-composer
	@echo "Install/Update the Production composer dependencies"
	@rm -rf inc/*
	@php composer update --ansi --prefer-stable --no-dev

composer-dev: php-composer
	@echo "Use composer to install/update the PHP test dependencies"
	@$(PHP_BIN) composer update --ansi --prefer-stable

docker-compose:
	@if [[ -z "$(DC_BIN)" && ! -f /usr/local/bin/docker-compose ]]; then \
		echo "Installing docker-compose" && \
		sudo curl -L https://github.com/docker/compose/releases/download/$(COMPOSER_VERSION)/docker-compose-`uname -s`-`uname -m` \
			-o /usr/local/bin/docker-compose && \
		sudo chmod +x /usr/local/bin/docker-compose ; \
	fi

deps: clean docker-compose composer-dev
	@echo "Loading WordPress plugin dependencies"
	@for dep_plugin in $(WP_DEPENDENCIES) ; do \
  		if [[ ! -d "inc/wp_plugins/$${dep_plugin}" ]]; then \
  		  echo "Download and install $${dep_plugin} to inc/wp_plugins/$${dep_plugin}" && \
  		  mkdir -p "inc/wp_plugins/$${dep_plugin}" && \
  		  $(CURL) -L "$(WP_PLUGIN_URL)/$${dep_plugin}.zip" -o i"nc/wp_plugins/$${dep_plugin}.zip" -s && \
  		  $(UNZIP) -o "inc/wp_plugins/$${dep_plugin}.zip" -d inc/wp_plugins/ 2>&1 > /dev/null && \
  		  rm -f "inc/wp_plugins/$${dep_plugin}.zip" ; \
  		fi ; \
  	done

start-stack: image-pull
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@echo "Current directory: $(shell pwd)"
	@if [[ 2 -ne "$(STACK_RUNNING)" ]]; then \
  		echo "Building and starting the WordPress stack for testing purposes" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
			docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --build --detach ; \
	fi

ci-start-stack: image-pull
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@echo "Current directory: $(shell pwd)"
	@if [[ 2 -ne "$(STACK_RUNNING)" ]]; then \
  		echo "Building and starting the WordPress stack for testing purposes (CircleCI)" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
			docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --build --detach ; \
	fi

db-import: start-stack
	@echo "Maybe load WordPress data...?"
	@bin/wait-for-db.sh '$(MYSQL_USER)' '$(MYSQL_PASSWORD)' '$(WORDPRESS_DB_HOST)' '$(E20R_PLUGIN_NAME)'
	@if [[ -f "$(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql" ]]; then \
  		echo "Loading WordPress data to use for testing $(E20R_PLUGIN_NAME)"; \
	  	docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
        	exec -T database \
        	/usr/bin/mysql -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) < $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql; \
  	fi

stop-stack:
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@if [[ 0 -lt "$(STACK_RUNNING)" ]]; then \
  		echo "Stopping the $(PROJECT) WordPress stack" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
        		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
        		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) down 2>/dev/null ; \
	fi


restart: stop-stack start-stack db-import

wp-shell:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec wordpress /bin/bash

wp-log:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) logs -f wordpress

db-shell:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database /bin/bash

db-backup:
	docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database \
 		/usr/bin/mysqldump -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) > $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql

phpstan-test: start-stack db-import
	@echo "Loading the WordPress test stack"
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
		exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
		wordpress php -d display_errors=on inc/bin/phpstan.phar analyse -c ./phpstan.dist.neon --memory-limit 128M

code-standard-test:
	@echo "Running WP Code Standards testing"
	@inc/bin/phpcs \
		--runtime-set ignore_warnings_on_exit true \
		--report=full \
		--colors \
		-p \
		--standard=WordPress-Extra \
		--ignore='inc/*,node_modules/*,src/utilities/*' \
		--extensions=php \
		*.php src/members-list/admin/*/*.php

wp-unit-test: start-stack db-import
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
		exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
		wordpress inc/bin/codecept run -v wpunit --coverage --coverage-html

acceptance-test: start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress inc/bin/codecept run -v acceptance

build-test: start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress $(PWD)/inc/bin/codecept build -v

test: clean deps code-standard-test start-stack db-import wp-unit-test # TODO: phpstan-test between phpcs & unit tests

changelog: build_readmes/current.txt
	@./bin/changelog.sh

gitlog:
	@./bin/create_log.sh

new-release: test composer-prod
	@./bin/get_version.sh && \
		git tag $${VERSION} && \
		./build_env/create_release.sh
