# Set base image
FROM mysql:5

# Install required package(s)
RUN apt-get update && apt-get install -y procps && apt-get dist-upgrade -y && rm -rf /var/lib/apt/lists/*

RUN ls -al /etc/mysql/conf.d
RUN rm -rf /etc/mysql/conf.d
COPY ./conf.d /etc/mysql/conf.d
