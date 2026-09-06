#!/bin/sh
set -eu

cd /var/www/html

# Resolve fixture by environment so swapping toy sites does not require compose edits.
FIXTURE_ROOT="/var/www/html/wp-content/plugins/ai-chat-widget/tests/fixtures"
FIXTURE_NAME="${WP_FIXTURE:-toy-5-products}"
FIXTURE_DIR="${FIXTURE_ROOT}/${FIXTURE_NAME}"

echo "[wp-init] Using fixture: ${FIXTURE_NAME}"

echo "[wp-init] Waiting for wp-config.php..."
tries=0
while [ ! -f /var/www/html/wp-config.php ]; do
  tries=$((tries + 1))
  if [ "$tries" -gt 120 ]; then
    echo "[wp-init] Timed out waiting for wp-config.php"
    exit 1
  fi
  sleep 2
done

echo "[wp-init] Waiting for database connection..."
tries=0
while ! php -r '
$host = getenv("WORDPRESS_DB_HOST") ?: "db:3306";
$user = getenv("WORDPRESS_DB_USER") ?: "wordpress";
$pass = getenv("WORDPRESS_DB_PASSWORD") ?: "wordpress";
$name = getenv("WORDPRESS_DB_NAME") ?: "wordpress";
$dbHost = $host;
$dbPort = 3306;
if (strpos($host, ":") !== false) {
    [$dbHost, $port] = explode(":", $host, 2);
    $dbPort = (int) $port;
}
$mysqli = @new mysqli($dbHost, $user, $pass, $name, $dbPort);
if ($mysqli->connect_errno) {
    exit(1);
}
$mysqli->close();
' >/dev/null 2>&1; do
  # WP-CLI DB checks can fail with client auth-plugin mismatches, so probe with mysqli instead.
  tries=$((tries + 1))
  if [ "$tries" -gt 120 ]; then
    echo "[wp-init] Timed out waiting for database"
    exit 1
  fi
  sleep 2
done

if ! wp core is-installed --allow-root >/dev/null 2>&1; then
  echo "[wp-init] Installing WordPress core..."
  wp core install \
    --url="$WP_SITE_URL" \
    --title="$WP_SITE_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email \
    --allow-root
fi

if [ "$FIXTURE_NAME" = "woo-test-site-1" ]; then
  echo "[wp-init] Ensuring WooCommerce is active before loading the AI plugin..."
  wp plugin install woocommerce --activate --allow-root
fi

echo "[wp-init] Ensuring plugin is active..."
wp plugin activate ai-chat-widget --allow-root >/dev/null 2>&1 || true

if [ ! -d "$FIXTURE_DIR" ]; then
  echo "[wp-init] Fixture directory not found: $FIXTURE_DIR"
  exit 1
fi

if [ -f "$FIXTURE_DIR/seed.sql" ]; then
  echo "[wp-init] Applying SQL seed: $FIXTURE_DIR/seed.sql"
  if ! wp db query --allow-root < "$FIXTURE_DIR/seed.sql"; then
    # SQL seed is optional in this runtime; fixture PHP seed remains the source of truth.
    echo "[wp-init] SQL seed failed in this runtime; continuing with PHP fixture seeding."
  fi
fi

if [ -f "$FIXTURE_DIR/seed.php" ]; then
  echo "[wp-init] Applying PHP seed: $FIXTURE_DIR/seed.php"
  wp eval-file "$FIXTURE_DIR/seed.php" --allow-root
fi

if [ -f "$FIXTURE_DIR/post-seed.sql" ]; then
  echo "[wp-init] Applying post-seed SQL: $FIXTURE_DIR/post-seed.sql"
  if ! wp db query --allow-root < "$FIXTURE_DIR/post-seed.sql"; then
    # Keep bootstrap resilient even when optional SQL post-processing is unavailable.
    echo "[wp-init] Post-seed SQL failed in this runtime; continuing."
  fi
fi

wp rewrite flush --hard --allow-root >/dev/null 2>&1 || true

echo "[wp-init] Fixture bootstrap complete."
