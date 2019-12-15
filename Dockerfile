FROM debian:stable-slim

RUN apt-get update \
	&& apt-get install -y subversion rsync git \
	&& apt-get clean -y \
	&& rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /entrypoint.sh
COPY bin/remove_update.sh /remove_update.sh
ENTRYPOINT ["/entrypoint.sh"]
