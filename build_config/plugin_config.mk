E20R_PLUGIN_NAME ?= default-plugin-name
E20R_PLUGIN_BASE_FILE ?= ./class-e20r-members-list.php
E20R_DEPLOYMENT_SERVER ?= wordpress.org
LOCAL_NETWORK_IF ?= en0

ifneq ($(LOCAL_NETWORK_IF), "")
LOCAL_NETWORK_STATUS ?= $(shell ifconfig $(LOCAL_NETWORK_IF) | awk '/status:/ { print $$2 }')
$(info Setting local network interface status for $(LOCAL_NETWORK_IF): $(LOCAL_NETWORK_STATUS))
endif

ifeq ($(LOCAL_NETWORK_IF), "")
LOCAL_NETWORK_STATUS ?= ""
endif

WP_DEPENDENCIES ?= paid-memberships-pro woocommerce
E20R_DEPENDENCIES ?= 00-e20r-utilities

DOCKER_HUB_USER ?= eighty20results
DOCKER_ENV ?= Docker.app
DOCKER_IS_RUNNING := $(shell ps -ef | grep $(DOCKER_ENV) | wc -l | xargs)

COMPOSER_VERSION ?= 1.29.2
# COMPOSER_BIN := $(shell which composer)
COMPOSER_BIN := composer.phar
COMPOSER_DIR := inc

APACHE_RUN_USER ?= $(shell id -u)
# APACHE_RUN_GROUP ?= $(shell id -g)
APACHE_RUN_GROUP ?= $(shell id -u)

WP_VERSION ?= latest
DB_VERSION ?= latest
WP_IMAGE_VERSION ?= 1.0

PHP_CODE_PATHS := *.php src/*/*/*/*.php src/*/*/*/*/*.php
PHP_IGNORE_PATHS := $(COMPOSER_DIR)/*,node_modules/*,src/utilities/*
E20R_MAIN_BRANCH_NAME := main
