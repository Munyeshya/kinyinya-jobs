-- Kinyinya Jobs — dedicated application database user.
-- Run this once as root, AFTER schema.sql:
--   mysql -u root -p < database/create_user.sql
--
-- Most MySQL/MariaDB installs restrict the root account to local
-- "unix_socket" auth and won't accept it over TCP with a password —
-- which is what PHP's PDO driver uses. Creating a dedicated app user
-- avoids that, and is better practice than pointing the app at root
-- anyway. Change the password below (and includes/config.php to match)
-- before deploying anywhere other than your own machine.

CREATE USER IF NOT EXISTS 'kinyinya_app'@'%' IDENTIFIED BY 'kinyinya_dev_password';
GRANT ALL PRIVILEGES ON kinyinya_jobs.* TO 'kinyinya_app'@'%';
FLUSH PRIVILEGES;
