FROM debian:stable-slim

ENV BUILD_DIR /build_env
ENV BASE_DIR /

RUN apt-get update \
	&& apt-get install -y subversion rsync git \
	&& apt-get clean -y \
	&& rm -rf /var/lib/apt/lists/*

RUN mkdir -p $BUILD_DIR

COPY .${BUILD_DIR}/entrypoint.sh ${BUILD_DIR}/entrypoint.sh
COPY .${BUILD_DIR}/remove_update.sh ${BUILD_DIR}/remove_update.sh

ENTRYPOINT ["${BUILD_DIR}/entrypoint.sh"]
