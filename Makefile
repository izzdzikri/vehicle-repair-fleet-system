.PHONY: help status serve dev fresh reseed test cache clear db-start

help:
	@echo "Teraju Fleet Management System - Developer Commands"
	@echo "-----------------------------------------------------"
	@echo "  make status    : Check database, environment & backend health"
	@echo "  make serve     : Start Laravel built-in development server"
	@echo "  make dev       : Start server, queue, logs, and frontend concurrently"
	@echo "  make fresh     : Run fresh migrations and seeders"
	@echo "  make reseed    : Truncate and reseed all demo data"
	@echo "  make test      : Run PHPUnit test suite"
	@echo "  make cache     : Cache config, routes, and views for production"
	@echo "  make clear     : Clear all application and configuration caches"
	@echo "  make db-start  : Start local MariaDB container via Podman"

status:
	php artisan app:status

serve:
	php artisan serve

dev:
	composer run dev

fresh:
	php artisan migrate:fresh --seed --force

reseed:
	php artisan app:reseed --force

test:
	./vendor/bin/phpunit

cache:
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache

clear:
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan cache:clear

db-start:
	podman start teraju-db 2>/dev/null || true
