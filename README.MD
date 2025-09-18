# Symfony + Docker Compose Development Environment

This repository is a minimal, production-like Docker Compose setup for local Symfony development, plus:

- A GitHub Actions CI workflow to run automated checks on pushes and pull requests
- A PHPUnit smoke test that ensures your routes respond as expected

It’s designed for Symfony 7 (tested with 7.0.x) and PHP 8.2.

## Features

- Dockerized stack suitable for local development
- Symfony CLI-style workflows inside containers
- Optional Xdebug support for step debugging
- PHPUnit smoke test for basic route availability
- CI pipeline: composer install + test run on every push/PR

## Stack Overview

- PHP-FPM 8.2 container (application runtime)
- Web server (e.g., Nginx) container to serve Symfony
- Database container (e.g., MySQL/MariaDB)
- Composer and PHPUnit executed inside the PHP container
- GitHub Actions workflow (.github/workflows/ci.yml)

Note: Service names and ports may differ in your docker-compose.yml. Adjust commands accordingly (e.g., replace
php/web/db with your service names).

## Prerequisites

- Docker Desktop 4.x or newer
- Docker Compose v2.x
- Git
- On Windows: WSL2 backend is recommended for best performance

## Quick Start

1) Clone the repository:

```
bash
git clone https://github.com/OWNER/REPO.git
cd REPO
```

2) Configure environment:

- Copy your Symfony environment file if needed (e.g., .env.local) and set local values (database DSN, mailer DSN, etc.).
- Review docker-compose.yml ports to avoid conflicts on your machine.

3) Build and start containers:

```
bash
docker compose up -d --build
```

4) Install PHP dependencies:

```
bash
docker compose exec php composer install
```

5) Initialize the database (if applicable):

```
bash
docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

6) Warm up cache and ensure permissions:

```
bash
docker compose exec php bin/console cache:warmup
```

7) Serve the application locally:

- Open http://localhost:8080 in your browser (or the port configured in docker-compose.yml).
- To confirm the exposed port, run:

```
bash
docker compose ps
```

Look for the web (or nginx) service and its PORTS column (e.g., 0.0.0.0:8080->80/tcp).

## Serving the App (Details)

- You do not need symfony server:serve. The web server container serves the app.
- Default URL: http://localhost:8080
- View web server logs:

```
bash
docker compose logs web -f
```

- Verify routes:

```
bash
docker compose exec php bin/console debug:router
```

## Database Configuration (.env.local)

When Symfony runs inside Docker, use the DB container’s service name as the host (not localhost). Use the container’s
internal port (3306 for MySQL).

- MySQL/MariaDB (replace placeholders with your values and service names):

```
env
DATABASE_URL="mysql://<DB_USER>:<DB_PASS>@db:3306/<DB_NAME>?serverVersion=8.0&charset=utf8mb4"
```

Notes:

- Replace db with your actual DB service name from docker-compose.yml if it differs.
- If connecting from your host tools (e.g., a GUI client) instead of from inside Docker, use localhost and the
  host-mapped port defined in docker-compose.yml.

Quick connectivity check:

```
bash
docker compose exec php bin/console doctrine:query:sql "select 1"
```

### Step-by-step example for MySQL in .env.local

1) Create or edit .env.local:

```
env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://<DB_USER>:<DB_PASS>@db:3306/<DB_NAME>?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN="null://null"
```

2) Create DB and run migrations:

```
bash
docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

## Docker Compose (MySQL database)

If your docker-compose.yml previously used a PostgreSQL service, replace that service with a MySQL service like below.
Ensure your PHP image has the pdo_mysql extension enabled.

- Example db service (replace placeholders as needed):

```
yaml
services:
db:
image: mysql:8.0
container_name: <project>-db
environment:
MYSQL_DATABASE: <DB_NAME>
MYSQL_USER: <DB_USER>
MYSQL_PASSWORD: <DB_PASS>
MYSQL_ROOT_PASSWORD: <DB_ROOT_PASS>
ports:
- "3306:3306" # change the host port if 3306 is already in use
volumes:
- db_data:/var/lib/mysql
healthcheck:
test: ["CMD-SHELL", "mysqladmin ping -h localhost -u $$MYSQL_USER --password=$$MYSQL_PASSWORD || exit 1"]
interval: 10s
timeout: 5s
retries: 10
restart: unless-stopped

volumes:
db_data:
```

- Update your .env.local DATABASE_URL to point to the db service (example with placeholders):

```
env
DATABASE_URL="mysql://<DB_USER>:<DB_PASS>@db:3306/<DB_NAME>?serverVersion=8.0&charset=utf8mb4"
```

- If you switched from Postgres to MySQL:
    - Stop and remove old containers and volumes to avoid data conflicts:
      ```bash
      docker compose down -v
      docker compose up -d --build
      ```
    - Recreate schema/migrate as needed:
      ```bash
      docker compose exec php bin/console doctrine:database:create --if-not-exists
      docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
      ```

Tip: Verify pdo_mysql is enabled inside the PHP container:

```
bash
docker compose exec php php -m | grep -i pdo_mysql
```

## Common Commands

- Open a shell in the PHP container (so you can run maker and doctrine commands):

```
bash
docker compose exec php bash
```

- Run Symfony console:

```
bash
docker compose exec php bin/console about
docker compose exec php bin/console debug:router
```

- Run PHPUnit:

```
bash
docker compose exec php ./vendor/bin/phpunit
```

- Stop and remove containers:

```
bash
docker compose down
```

## Route Availability Test (Smoke Test)

This project includes a PHPUnit test that crawls your defined routes and asserts a successful HTTP response (typically
200 OK). It’s useful as a lightweight health check for controllers after changes.

Run it with:

```
bash
docker compose exec php ./vendor/bin/phpunit --filter RoutesAvailabilityTest
```

## GitHub Actions CI

A minimal workflow file is included at .github/workflows/ci.yml. It typically:

- Checks out the code
- Sets up PHP 8.2
- Installs Composer dependencies with caching
- Executes PHPUnit tests

Add a status badge to your README (replace placeholders with your repo):

```
markdown
![CI](https://github.com/OWNER/REPO/actions/workflows/ci.yml/badge.svg?branch=main)
```

## Xdebug (optional)

- Enable Xdebug in your PHP container (example settings):

```
ini
xdebug.mode=debug,develop
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
```

- Configure your IDE to listen on port 9003 and set path mappings.

## Troubleshooting

- Port conflicts: Change mapped ports in docker-compose.yml.
- Database not ready on first boot: wait a few seconds, then run migrations again.
- Permissions/cache:

```
bash
docker compose exec php bin/console cache:clear
```

- Validate DB settings by connecting from the PHP container or using doctrine:query:sql.

## Project Structure (typical)

- docker-compose.yml
- .env, .env.local (override locally)
- src/, config/, public/, var/ (standard Symfony)
- tests/ (contains route smoke test)
- .github/workflows/ci.yml (CI pipeline)
- composer.json / composer.lock

## License

Provide your license of choice here (e.g., MIT).
