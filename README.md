
Main Channel Brewing Website – Deployment & Infrastructure Documentation

OVERVIEW
This document provides step-by-step instructions to fully recreate the Main Channel Brewing website environment on a new VPS. It is designed for non-expert users and walks through every layer of setup including server provisioning, web stack installation, database configuration, application deployment, and security.
<p algin="center">
###==================================================<br>
###SECTION 1 – VPS SETUP<br>
###==================================================<br>
</p>
1. Purchase a VPS (Hostinger recommended)
2. Select Ubuntu OS
3. Obtain server IP address
4. Connect via SSH:

ssh root@YOUR_SERVER_IP

5. Update system:

sudo apt update
sudo apt upgrade -y
<p algin="center">
==================================================<br>
SECTION 2 – INSTALL WEB STACK (LNMP)<br>
==================================================<br>
</p>
Install Nginx:

sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx

Install PHP:

sudo apt install php-fpm php-cli php-mysql php-curl php-xml php-mbstring php-zip -y

Verify:

php -v
sudo systemctl status php8.3-fpm
<p algin="center">
==================================================<br>
SECTION 3 – INSTALL DATABASE<br>
==================================================<br>
</p>
sudo apt install mariadb-server -y
sudo systemctl enable mariadb
sudo systemctl start mariadb

Secure:

sudo mysql_secure_installation
<p algin="center">
==================================================<br>
SECTION 4 – CREATE DATABASE<br>
==================================================<br>
</p>
sudo mysql -u root -p

CREATE DATABASE mainchannel_db;

CREATE USER 'mainchannel_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON mainchannel_db.* TO 'mainchannel_user'@'localhost';
FLUSH PRIVILEGES;
<p algin="center">
==================================================<br>
SECTION 5 – DEPLOY APPLICATION<br>
==================================================<br>
</p>
cd /var/www
git clone https://github.com/Mark-Langston/capstone-brewery-site.git brewery-site

sudo chown -R www-data:www-data /var/www/brewery-site
sudo chmod -R 755 /var/www/brewery-site
<p algin="center">
==================================================<br>
SECTION 6 – CREATE DB CONNECTION FILE<br>
==================================================<br>
</p>
Create file: /var/www/brewery-site/db.php

Add:

<?php
$host = 'localhost';
$db   = 'mainchannel_db';
$user = 'mainchannel_user';
$pass = 'YOUR_PASSWORD';
?>

NOTE: This file must NOT be committed to GitHub.
<p algin="center">
==================================================<br>
SECTION 7 – IMPORT DATABASE TABLES<br>
==================================================<br>
</p>
mysql -u mainchannel_user -p mainchannel_db < schema.sql

(or run individual .sql files)
<p algin="center">
==================================================<br>
SECTION 8 – FILE UPLOAD PERMISSIONS<br>
==================================================<br>
</p>
mkdir -p /var/www/brewery-site/assets/images/inventory
mkdir -p /var/www/brewery-site/assets/images/seasonal
mkdir -p /var/www/brewery-site/assets/images/merch
mkdir -p /var/www/brewery-site/assets/images/map

sudo chown -R www-data:www-data /var/www/brewery-site/assets
sudo chmod -R 755 /var/www/brewery-site/assets
<p algin="center">
==================================================<br>
SECTION 9 – SSL SETUP<br>
==================================================<br>
</p>
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx
<p algin="center">
==================================================<br>
SECTION 10 – APPLICATION ARCHITECTURE<br>
==================================================<br>
</p>
Layers:

Physical: VPS hardware
OS: Ubuntu Linux
Network: HTTP/HTTPS/SSH
Web Server: Nginx
App Layer: PHP
Data Layer: MariaDB
Security Layer: Sessions, hashing, RBAC
Frontend: HTML/CSS/JS + Leaflet
<p algin="center">
==================================================<br>
SECTION 11 – AUTHENTICATION<br>
==================================================<br>
</p>
- Passwords stored using password_hash()
- Verified using password_verify()
- Sessions used for login persistence
- Role-based access enforced
- CSRF tokens protect forms
<p algin="center">
==================================================<br>
SECTION 12 – AUDIT LOGGING<br>
==================================================<br>
</p>
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
<p algin="center">
==================================================<br>
SECTION 13 – DEPLOYMENT FLOW<br>
==================================================<br>
</p>
GitHub push → GitHub Actions → VPS → git pull → live site
<p algin="center">
==================================================<br>
SECTION 14 – BACKUP<br>
==================================================<br>
</p>
mysqldump -u mainchannel_user -p mainchannel_db > backup.sql

Restore:

mysql -u mainchannel_user -p mainchannel_db < backup.sql
<p algin="center">
==================================================<br>
FINAL RESULT<br>
==================================================<br>
</p>
Fully functional secure web application with:
- Admin dashboard
- CRUD operations
- File uploads
- Map integration
- Audit logging
- Role-based access control
<p algin="center">
==================================================<br>
</p>
FIN~
