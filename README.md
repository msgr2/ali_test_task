# MSGR 2.0 SMS management platform.

This repo is a clone of a point in time of the original repo. It is used for the purpose of a test like the one you're
doing now.

API docs are automatically generated at

- http://localhost/docs/api#/ (using [scramble package](https://scramble.dedoc.co/installation))
- generate api's into typescript (runs from frontend) with `openapi-typescript` (further reading at nuxt/readme.md)

# Starting project

Create a .env file from .env.example

# Github

- we use (https://nvie.com/posts/a-successful-git-branching-model/) branching model
-
    - working on feature/<feature> name
-
    - create a PR to merge to development branch when done
-
    - we deploy to production from master branch

```bash
cp .env.example .env
```

Check if correct binary for your architecture is specified in /docker/clickhouse/Dockerfile
Look for the following two lines and comment/uncomment the correct one

```bash
#ARG single_binary_location_url="https://builds.clickhouse.com/master/amd64/clickhouse"

# Apple chip solution - unomment the line above and use this instead.
ARG single_binary_location_url="https://builds.clickhouse.com/master/aarch64/clickhouse"
````

Install dependencies, start the project and run tests

```bash
composer install
sail up -d
sail -f docker-compose.clickhouse.yml up -d
sail artisan migrate:fresh --seed
sail artisan jwt:secret
sail test
```

- if composer is not installed run `docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php82-composer:latest \
  composer install --ignore-platform-reqs`

If `clickhouse` container doesn't exist, run this command:

```bash
sail -f docker-compose.clickhouse.yml up -d
```

# queues

`sail php artisan horizon` to start
`http://v2.local/horizon/dashboard` to view

make sure queues are not retrying automatically

# logs

I use https://lnav.org/ for it
run `lnav storage/logs/laravel.log`

# supporting services

- frontend: https://v2-frontend.vercel.app/ (https://github.com/msgr2/v2_frontend)
- url shortener: https://github.com/msgr2/v2_shortener

### Running User Campaign Queues

To start the queue workers for user campaigns, follow these steps:

1. Make sure your Docker environment is up and running.
2. Open a terminal window.
3. Navigate to your Laravel project directory.
4. Run the following command to start the queue worker for user campaigns:

```bash
php artisan queue:work --queue=user_campaigns