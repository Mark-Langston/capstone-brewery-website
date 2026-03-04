README.md Content for Main Channel Brewing Deployment

# Main Channel Brewing Website – Deployment & Infrastructure Documentation

## Overview

This document provides technical instructions for replicating the hosting environment used for the **Main Channel Brewing Website Modernization Project**. Designed and developed for National University 2026 February-April CS480 Computer Science Capstone project, team Ironclad Solutions. The goal is to allow the customer/stakeholder or another development team to reproduce the exact deployment environment used in this project.

The website is hosted on a **Hostinger VPS using the lowest-tier plan**, running a **Linux + Nginx + MariaDB + PHP (LNMP) stack** with automated deployment using **GitHub Actions**.

This documentation includes:

- VPS provisioning
- Server configuration
- Web server setup
- PHP installation
- MariaDB installation
- GitHub CI/CD deployment configuration
- Basic project directory structure

---

# Hosting Platform

## Primary Hosting Environment

The project is hosted using:

**Provider:** Hostinger  
**Plan:** Lowest-tier VPS plan  
**Estimated Specs:**

- 1 vCPU
- 4 GB RAM
- ~50 GB SSD storage
- Ubuntu Linux
- Public IP address

This configuration provides sufficient performance for a small business website while remaining cost effective.

### Why Hostinger VPS?

Hostinger was selected for:

- Low cost
- Simple VPS provisioning
- Reliable uptime
- SSH access for server management
- Easy DNS configuration
- Compatibility with standard Linux web stacks

---

# Alternative Hosting Options

Although Hostinger was used for this project, the system architecture is portable and can easily run on other cloud providers.

Examples include:

## Amazon Web Services (AWS)

Equivalent configuration could use:

- **EC2 instance**
- Ubuntu Server
- Elastic IP
- Security Groups for ports 80/443/22

Advantages:

- Highly scalable
- Enterprise grade infrastructure
- Integration with other AWS services

Potential trade offs:

- Higher cost
- More complex configuration

---

## Other Compatible Providers

This architecture would also run on:

- DigitalOcean Droplets
- Linode
- Google Cloud Compute Engine
- Microsoft Azure Virtual Machines

Because the stack is standard **Linux + Nginx + MariaDB + PHP**, it remains portable across nearly all VPS providers.

---

# System Architecture

The deployed stack uses a traditional **LNMP architecture**.

Browser
↓
Nginx Web Server
↓
PHP-FPM
↓
MariaDB Database

Components:

| Component | Purpose |
|--------|--------|
| Linux | Operating system |
| Nginx | Web server |
| PHP-FPM | Executes PHP scripts |
| MariaDB | Database |
| GitHub Actions | Continuous deployment |
| Let's Encrypt | SSL certificates |

---

# Initial Server Setup

After acquiring the VPS and receiving the IP address, connect using SSH.

Example:

ssh root@YOUR_SERVER_IP

Update the server packages:

sudo apt update
sudo apt upgrade -y

---

# Install Nginx Web Server

Install Nginx:

sudo apt install nginx -y

Verify installation:

sudo systemctl status nginx

Enable Nginx at boot:

sudo systemctl enable nginx

Open required firewall ports:

sudo ufw allow 'Nginx Full'

---

# Install PHP and PHP-FPM

Install PHP and required modules:

sudo apt install php-fpm php-cli php-mysql php-curl php-xml php-mbstring php-zip -y

Verify PHP installation:

php -v

Confirm PHP-FPM service:

sudo systemctl status php8.3-fpm

---

# Website Directory

The site files are stored in:

/var/www/brewery-site

Example structure:

/var/www/brewery-site
│
├── index.php
├── about.php
├── beermenu.php
├── merch.php
├── contact.php
├── header.php
├── footer.php
├── style.css
│
├── assets/
│   └── images/
│       └── logos/
│
└── admin/

---

# GitHub Deployment Integration

The server connects to GitHub using **SSH keys**.

Deployment workflow:

Developer pushes code to GitHub
↓
GitHub Actions workflow triggers
↓
SSH connection to VPS
↓
Server executes:

cd /var/www/brewery-site
git fetch origin
git reset --hard origin/main

This ensures the VPS always mirrors the latest version of the repository.

---

# Install MariaDB Database

Install MariaDB:

sudo apt install mariadb-server -y

Start the service:

sudo systemctl start mariadb

Enable at boot:

sudo systemctl enable mariadb

Secure the installation:

sudo mysql_secure_installation

Recommended options:

- Remove anonymous users
- Disallow remote root login
- Remove test database
- Reload privilege tables

---

# MariaDB Installation Walkthrough Video

The MariaDB installation process is demonstrated in the following video:

https://www.youtube.com/watch?v=J0a256ZdZko

For GitHub README embedding, the video can be displayed using:

[![MariaDB Installation Walkthrough](https://img.youtube.com/vi/J0a256ZdZko/0.jpg)](https://www.youtube.com/watch?v=J0a256ZdZko)

---

# SSL Configuration

SSL certificates are installed using Let's Encrypt.

Install Certbot:

sudo apt install certbot python3-certbot-nginx -y

Generate certificate:

sudo certbot --nginx

Certificates automatically renew using a scheduled system task.

---

# Final Result

After completing these steps the system provides:

- Secure HTTPS website
- PHP application support
- MariaDB database
- Automated deployment pipeline
- Modular PHP site structure

The result is a lightweight, cost effective, and maintainable hosting environment suitable for a small business website.

---

# Future Expansion

The infrastructure allows for future upgrades such as:

- Inventory database tables
- Administrative dashboard
- Authentication system
- API integrations
- Horizontal scaling via load balancers

Because the architecture follows standard web development practices, the system remains portable and extensible.

---
Demo site temporarily available at https://mainchannelbrewing.dev
End of Document


