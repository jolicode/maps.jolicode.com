# hadolint global ignore=DL3008

FROM debian:bookworm-20240904-slim AS map

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
        git \
        gnupg \
        wget \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# pmtiles
RUN cd /tmp \
    && wget -O go-pmtiles.tar.gz https://github.com/protomaps/go-pmtiles/releases/download/v1.23.1/go-pmtiles_1.23.1_Linux_x86_64.tar.gz \
    && tar xzvf go-pmtiles.tar.gz \
    && cp pmtiles /usr/local/bin/pmtiles

# tilemaker
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        libboost-dev \
        libboost-filesystem-dev \
        libboost-iostreams-dev \
        libboost-program-options-dev \
        libboost-system-dev \
        lua5.1 \
        liblua5.1-0-dev \
        libshp-dev \
        libsqlite3-dev \
        libz-dev \
        rapidjson-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN cd /tmp \
    && wget -O tilemaker.tar.gz https://github.com/systemed/tilemaker/archive/refs/tags/v3.0.0.tar.gz \
    && tar xzvf tilemaker.tar.gz \
    && cd /tmp/tilemaker-3.0.0 \
    && make \
    && make install

# ogr2ogr
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gdal-bin \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# node
ARG NODEJS_VERSION=22.x
RUN curl -s https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /usr/share/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODEJS_VERSION} nodistro main" > /etc/apt/sources.list.d/nodesource.list

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nodejs \
    && apt-get clean \
    && npm install -g yarn@1.22 \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

WORKDIR /home/app/maps

SHELL ["/bin/bash", "-o", "pipefail", "-c"]
