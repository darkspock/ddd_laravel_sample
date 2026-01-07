.PHONY: start stop up down shell artisan migrate test phpstan

# Start Sail containers (detached)
start:
	./vendor/bin/sail up -d

# Stop Sail containers
stop:
	./vendor/bin/sail down

# Aliases for docker-compose conventions
up: start
down: stop

# Open a shell in the app container
shell:
	./vendor/bin/sail shell

# Run artisan commands (usage: make artisan cmd="migrate")
artisan:
	./vendor/bin/sail artisan $(cmd)

# Run database migrations
migrate:
	./vendor/bin/sail artisan migrate

# Run tests
test:
	./vendor/bin/sail artisan test

# Run PHPStan
phpstan:
	./vendor/bin/sail exec laravel.test ./vendor/bin/phpstan analyse
