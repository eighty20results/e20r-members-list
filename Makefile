SHELL := /bin/bash
BASE_PATH := $(PWD)
FIND := $(shell which find)
APACHE_RUN_USER ?= $(shell id -u)
APACHE_RUN_GROUP ?= $(shell id -g)
SQL_BACKUP_FILE ?= $(PWD)/docker/test/db_backup
E20R_PLUGIN_NAME ?= e20r-members-list
MYSQL_DATABASE ?= wordpress
MYSQL_USER ?= wordpress
MYSQL_PASSWORD ?= wordpress
WORDPRESS_DB_HOST ?= localhost
WP_CONTAINER_NAME ?= codecep-wp-$(E20R_PLUGIN_NAME)
DB_CONTAINER_NAME ?= mariadb-wp-$(E20R_PLUGIN_NAME)

# PROJECT := $(shell basename ${PWD}) # This is the default as long as the plugin name matches
PROJECT := $(E20R_PLUGIN_NAME)

# Settings for docker-compose
DC_CONFIG_FILE ?= $(PWD)/docker-compose.yml
DC_ENV_FILE ?= $(PWD)/.env.testing


.PHONY: \
	clean \
	start \
	start-stack \
	stop \
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
	wp-shell \
	wp-log \
	db-shell \
	db-backup \
	db-import \
	tests

clean:
	@inc/bin/codecept clean
#	$(FIND) $(BASE_PATH)/inc -path composer -prune \
#		-path yahnis-elsts -prune \
#		-path 10quality -prune \
#		-type d -print
#		-exec rm -rf {} \;

start:
	echo "TODO: Add a standard start"

start-stack: clean
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --build --detach

db-import:
	@bin/wait-for-db.sh '$(MYSQL_USER)' '$(MYSQL_PASSWORD)' '$(WORDPRESS_DB_HOST)' '$(E20R_PLUGIN_NAME)'
	@if [[ -f "$(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql" ]]; then \
  		echo "Loading the $(E20R_PLUGIN_NAME).sql data"; \
	  	docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
        	exec -T database \
        	/usr/bin/mysql -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) < $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql; \
  	fi

stop-stack:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) down

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

phpstan-test: start-stack
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
        	exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
        	wordpress php -d display_errors=on inc/bin/phpstan.phar analyse -c ./phpstan.dist.neon --memory-limit 128M

code-standard-test: start-stack
	@docker-compose -p ${PROJECT} --env-file ${DC_ENV_FILE} --file ${DC_CONFIG_FILE} exec \
    		-T -w /var/www/html/wp-content/plugins/$(PROJECT)/ wordpress \
    		inc/bin/phpcs \
    		--runtime-set ignore_warnings_on_exit true \
    		--report=full \
    		--colors \
    		-p \
    		--standard=WordPress-Extra \
    		--ignore=*/inc/*,*/node_modules/*,src/utilities/* \
    		--extensions=php \
    		*.php src/*/*.php

wp-unit-test: start-stack code-standard-test
#	php composer.phar run unit-test
	@docker-compose -p $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
		exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
		wordpress inc/bin/codecept run -v wpunit --coverage

acceptance-test: start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress inc/bin/codecept run -v acceptance

build-test: start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress $(PWD)/inc/bin/codecept build -v

tests: start-stack db-import code-standard-test wp-unit-test stop-stack # TODO: phpstan-test between phpcs & unit tests

changelog: build_readmes/current.txt
	@./bin/changelog.sh

gitlog:
	@./bin/create_log.sh
