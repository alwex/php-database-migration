#!/bin/bash
docker-compose up -d && docker-compose exec php bin/phpunit && docker-compose kill