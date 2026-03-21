
Main Channel Brewing Website – Deployment & Infrastructure Documentation

OVERVIEW
This document provides step-by-step instructions to fully recreate the Main Channel Brewing website environment on a new VPS. It is designed for non-expert users and walks through every layer of setup including server provisioning, web stack installation, database configuration, application deployment, and security.

==================================================
SECTION 1 – VPS SETUP
==================================================

1. Purchase a VPS (Hostinger recommended)
2. Select Ubuntu OS
3. Obtain server IP address
4. Connect via SSH:

ssh root@YOUR_SERVER_IP

5. Update system:

sudo apt update
sudo apt upgrade -y

==================================================
SECTION 2 – INSTALL WEB STACK (LNMP)
==================================================

Install Nginx:

sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx

Install PHP:

sudo apt install php-fpm php-cli php-mysql php-curl php-xml php-mbstring php-zip -y

Verify:

php -v
sudo systemctl status php8.3-fpm

==================================================
SECTION 3 – INSTALL DATABASE
==================================================

sudo apt install mariadb-server -y
sudo systemctl enable mariadb
sudo systemctl start mariadb

Secure:

sudo mysql_secure_installation

==================================================
SECTION 4 – CREATE DATABASE
==================================================

sudo mysql -u root -p

CREATE DATABASE mainchannel_db;

CREATE USER 'mainchannel_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON mainchannel_db.* TO 'mainchannel_user'@'localhost';
FLUSH PRIVILEGES;

==================================================
SECTION 5 – DEPLOY APPLICATION
==================================================

cd /var/www
git clone https://github.com/Mark-Langston/capstone-brewery-site.git brewery-site

sudo chown -R www-data:www-data /var/www/brewery-site
sudo chmod -R 755 /var/www/brewery-site

==================================================
SECTION 6 – CREATE DB CONNECTION FILE
==================================================

Create file: /var/www/brewery-site/db.php

Add:

<?php
$host = 'localhost';
$db   = 'mainchannel_db';
$user = 'mainchannel_user';
$pass = 'YOUR_PASSWORD';
?>

NOTE: This file must NOT be committed to GitHub.

==================================================
SECTION 7 – IMPORT DATABASE TABLES
==================================================

mysql -u mainchannel_user -p mainchannel_db < schema.sql

(or run individual .sql files)

==================================================
SECTION 8 – FILE UPLOAD PERMISSIONS
==================================================

mkdir -p /var/www/brewery-site/assets/images/inventory
mkdir -p /var/www/brewery-site/assets/images/seasonal
mkdir -p /var/www/brewery-site/assets/images/merch
mkdir -p /var/www/brewery-site/assets/images/map

sudo chown -R www-data:www-data /var/www/brewery-site/assets
sudo chmod -R 755 /var/www/brewery-site/assets

==================================================
SECTION 9 – SSL SETUP
==================================================

sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx

==================================================
SECTION 10 – APPLICATION ARCHITECTURE
==================================================

Layers:

Physical: VPS hardware
OS: Ubuntu Linux
Network: HTTP/HTTPS/SSH
Web Server: Nginx
App Layer: PHP
Data Layer: MariaDB
Security Layer: Sessions, hashing, RBAC
Frontend: HTML/CSS/JS + Leaflet

==================================================
SECTION 11 – AUTHENTICATION
==================================================

- Passwords stored using password_hash()
- Verified using password_verify()
- Sessions used for login persistence
- Role-based access enforced
- CSRF tokens protect forms

==================================================
SECTION 12 – AUDIT LOGGING
==================================================

Tracks:
- CREATE
- UPDATE
- DELETE

Entities:
- users
- inventory
- merch
- seasonal
- map

==================================================
SECTION 13 – DEPLOYMENT FLOW
==================================================

GitHub push → GitHub Actions → VPS → git pull → live site

==================================================
SECTION 14 – BACKUP
==================================================

mysqldump -u mainchannel_user -p mainchannel_db > backup.sql

Restore:

mysql -u mainchannel_user -p mainchannel_db < backup.sql

==================================================
FINAL RESULT
==================================================

Fully functional secure web application with:
- Admin dashboard
- CRUD operations
- File uploads
- Map integration
- Audit logging
- Role-based access control

==================================================
FIN~
