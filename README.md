# EverlyWell Backend Challenge

### Requirements
- a recent version of docker
- docker-compose (supporting at least api version 3)
- something to test API endpoints with like [Postman](https://www.postman.com/)

#

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

#

### Usage
The api endpoints are accessed by either GET or POST requests. See below for endpoints and parameters.

#### GET /test
No parameters, should return json hello world.

#### GET /listMembers
No parameters, returns a list of all members.

#### GET /viewMember/{memberId}
`memberId` should be an `int` discovered from the `/listMembers` endpoint.
Returns information on the member.

#### POST /addMember
Adds a member.
Body needs to include `name` and `websiteUrl`.
Success will return the memberId.
Failsure will return a message on why it failed.

#### POST /createFriendship/{mIdOne}/{mIdTwo}
Create a friendship between `mIdOne` and `mIdTwo` (bidirectional). No body required even though this is a POST.
Returns a success message when successful, or error message if fails.

#### POST /search
Searches (based on a root user) for friends-of-friends with headlines including the search term.
Body needs to include `term` and `memberReference`
Returns found headlines (with path to member) on success, error message on failure.

#

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

#

### Todo
- write more tests