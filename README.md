# AiiMS Autoresponder Bot

## Installation
### Install dependencies
```
composer install          # Installs dependencies in composer.json
```

### Setup environment variables
```
cp .env.example .env      # Makes .env file based on .env.example
php artisan key:generate  # Generates application key
```
Populate .env file with environment variables (e.g. Database Cridentials)

### Migrate database structure
```
php artisan migrate
```
