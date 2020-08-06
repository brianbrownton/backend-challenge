# EverlyWell Backend Challenge

### Requirements
- a recent version of docker
- docker-compose (supporting at least api version 3)

### Init
- clone repo

#### install packages/dependencies
- `$ cd backend-challenge/src`
- 
    ```
    $ docker run --rm \
       -v $PWD:/app \
       -v ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
       -u "$(id -u):$(id -g)" \
       composer install
    ```
#### bring up the environment
- `$ cd backend-challenge/docker`
- `$ docker-compose up`
- verify app is running at http://localhost

### Usage

### Tests
We can use the existing php docker container to run the tests by using the following genericized command:
```
$docker exec -it <container_id_or_name> /var/www/api/vendor/bin/phpunit -c /var/www/api/phpunit.xml
```
Find the docker container ID for php:
```
$ docker ps
CONTAINER ID        IMAGE                    COMMAND                  CREATED             STATUS              PORTS                               NAMES
6d25436a0d22        nginx:mainline-alpine    "nginx -g 'daemon of…"   5 minutes ago       Up 5 minutes        0.0.0.0:80->80/tcp                  docker_everlywell_nginx_1
1c3b9ff7f8d6        docker_everlywell_php7   "docker-php-entrypoi…"   5 minutes ago       Up 5 minutes        0.0.0.0:9000->9000/tcp              docker_everlywell_php7_1
4086b6e01f8e        docker_everlywell_db     "docker-entrypoint.s…"   5 minutes ago       Up 5 minutes        0.0.0.0:3306->3306/tcp, 33060/tcp   docker_everlywell_db_1
```

In the above sample output it is `1c3b9ff7f8d6`.

Thus, the command to run the actual tests is
```
docker exec -it 1c3b9ff7f8d6 /var/www/api/vendor/bin/phpunit -c /var/www/api/phpunit.xml
```

### Todo
- write tests