cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail -f docker-compose.clickhouse.yml up -d
./vendor/bin/sail artisan migrate:fresh

echo '';
echo '';
echo '>> This is example for a test running the sms send, look at it to understand sending process <<'
./vendor/bin/sail artisan test --filter=send_campaign_simple