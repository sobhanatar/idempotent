FROM dockerhub.ir/mysql:8.0.27

ARG UID
ARG GID

RUN usermod -u ${UID} mysql && \
    groupmod -g ${GID} mysql
