docker-up:
	docker-compose up -d --force-recreate --build

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-restart:
	make docker-down docker-up

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

docker-init:
	make docker-down-clear docker-pull docker-build docker-up

docker-login:
	docker-compose exec ${name} sh

backend-php-cli:
	docker-compose run backend-php-cli sh
