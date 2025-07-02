set-testing-env:
	cp .env.testing .env
	php artisan config:clear

restore-env:
	cp .env.backup .env
	php artisan config:clear
