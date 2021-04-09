SHELL := /bin/bash
BASE_PATH := $(PWD)
FIND := $(shell which find)
CURL := $(shell which curl)
UNZIP := $(shell which unzip)
PHP_BIN := $(shell which php)
APACHE_RUN_USER ?= $(shell id -u)
APACHE_RUN_GROUP ?= $(shell id -g)
SQL_BACKUP_FILE ?= $(PWD)/docker/test/db_backup
E20R_PLUGIN_NAME ?= e20r-members-list
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

# PROJECT := $(shell basename ${PWD}) # This is the default as long as the plugin name matches
PROJECT := $(E20R_PLUGIN_NAME)

# Settings for docker-compose
DC_CONFIG_FILE ?= $(PWD)/docker-compose.yml
DC_ENV_FILE ?= $(PWD)/.env.testing

STACK_RUNNING := $(shell APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
    		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) \
    		docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) ps -q)

.PHONY: \
	clean \
	real-clean \
	deps \
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
	test

clean:
	@if [[ -n "$(STACK_RUNNING)" ]]; then \
		if [[ -f inc/bin/codecept ]]; then \
			inc/bin/codecept clean ; \
		fi ; \
		rm -rf inc/wp_plugins ; \
	fi

real-clean: stop-stack clean
	@echo "Make sure docker-compose stack for $(PROJECT) isn't running"
	@if [[ -z "$(STACK_RUNNING)" ]]; then \
		echo "Removing docker-compose stack" ; \
		docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) rm --stop --force -v ; \
	fi ; \
	echo "Removing docker images" ; \
	docker image remove $(PROJECT)_wordpress --force && \
	docker image remove $(DB_IMAGE) --force && \
	echo "Removing the composer dependencies" && \
	rm -rf inc/*

composer-prod: real-clean
	@echo "Install/Update the Production composer dependencies"
	@rm -rf inc/*
	@$(PHP_BIN) composer update --prefer-stable --no-dev

deps: clean
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

start-stack: clean deps
	@if [[ -z "$(STACK_RUNNING)" ]]; then \
  		echo "Building and starting the WordPress stack for testing purposes" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) \
			docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --build --detach ; \
	fi

db-import: clean deps start-stack
	@bin/wait-for-db.sh '$(MYSQL_USER)' '$(MYSQL_PASSWORD)' '$(WORDPRESS_DB_HOST)' '$(E20R_PLUGIN_NAME)'
	@if [[ -f "$(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql" ]]; then \
  		echo "Loading the $(E20R_PLUGIN_NAME).sql data"; \
	  	docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
        	exec -T database \
        	/usr/bin/mysql -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) < $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql; \
  	fi

stop-stack:
	@echo "Shutting down the WordPress test stack"
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) \
		docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) down 2>/dev/null

restart: stop-stack start-stack db-import

wp-shell:
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec wordpress /bin/bash

wp-log:
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) logs -f wordpress

db-shell:
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database /bin/bash

db-backup:
	docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database \
 		/usr/bin/mysqldump -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) > $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql

phpstan-test: start-stack db-import
	@echo "Loading the WordPress test stack"
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
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
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
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
