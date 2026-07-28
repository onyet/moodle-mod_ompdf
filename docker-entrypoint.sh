#!/bin/bash
set -e

# Generate config.php if not present
if [ ! -f /var/www/html/config.php ]; then
    cat << 'EOF' > /var/www/html/config.php
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'mariadb';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodleuser';
$CFG->dbpass    = 'moodlepass';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport' => 3306,
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://localhost:8080';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;

require_once(__DIR__ . '/lib/setup.php');
EOF
    chown www-data:www-data /var/www/html/config.php
fi

# Wait for MariaDB to be ready
echo "Waiting for MariaDB connection..."
until php -r "
\$mysqli = @new mysqli('mariadb', 'moodleuser', 'moodlepass', 'moodle');
if (\$mysqli->connect_error) { exit(1); }
"; do
    sleep 2
done
echo "MariaDB is connected and ready!"

# Install Moodle database if not installed
if ! php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
global \$DB;
if (isset(\$DB) && \$DB->get_tables()) { exit(0); }
exit(1);
" 2>/dev/null; then
    echo "Initializing Moodle database schema..."
    php /var/www/html/admin/cli/install_database.php \
        --lang=en \
        --adminuser=admin \
        --adminpass="Admin12345!" \
        --adminemail="admin@example.com" \
        --agree-license \
        --fullname="OMPDF Moodle Test Suite" \
        --shortname="OMPDF"
fi

exec apache2-foreground
