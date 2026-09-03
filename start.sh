#!/bin/bash

# Initialize DB structure first
php -r "require 'database.php';"

# Run master bot in background
php main_bot.php &

# Restore and run all previously saved user bots
php -r "
require 'database.php';
\$bots = get_all_bots();
foreach (\$bots as \$token) {
    exec('php user_bot_runner.php ' . escapeshellarg(\$token) . ' > /dev/null 2>&1 &');
}
"

# Keep container alive
tail -f /dev/null
