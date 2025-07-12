cd /home/forge/selamail.com
git pull origin $FORGE_SITE_BRANCH

git fetch

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan cache:clear

$FORGE_PHP artisan config:clear
$FORGE_PHP artisan config:cache

$FORGE_PHP artisan view:clear
$FORGE_PHP artisan view:cache

$FORGE_PHP artisan event:clear
$FORGE_PHP artisan event:cache

$FORGE_PHP artisan route:clear
$FORGE_PHP artisan route:cache

$FORGE_PHP artisan queue:restart

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

npm install
npm run build

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
fi

$FORGE_PHP artisan queue:restart
