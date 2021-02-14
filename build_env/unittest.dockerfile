ARG PHPUNIT_VER=5.7.20
ARG PHP_VER=php7.1

FROM wordpress:$PHP_VER

RUN apt-get update && \
	apt-get install -y less wget mysql-client && \
	wget -o /usr/local/bin/phpunit \
	https://phar.phpunit.de/phpunit-$PHPUNIT_VER.phar && \
	chmod +x /usr/local/bin/phpunit && \
	wget -o /usr/local/bin/wp \
	https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
	chmod +x  /usr/local/bin/wp

COPY ./tests /tests
