# SellNow (Assessment Project)

This is a **simplified, imperfect** platform for selling digital products, built for **candidate assessment functionality**.
It contains **intentional flaws, bad practices, and security holes**.

## Project Overview

A platform where:
1. Users register and get a public profile (`/username`).
2. Users can upload products (images + digital files).
3. Buyers can browse, add to cart, and "checkout".

## Setup Instructions

1. **Install Dependencies**:
   ```bash
   composer install
   ```

2. **Database**:
   The project is configured to use SQLite by default.
   Initialize the database:
   ```bash
   sqlite3 database/database.sqlite < database/schema.sql
   ```
   *Note: If you switch to MySQL, update `src/Config/Database.php`.*

3. **Run Server**:
   Use PHP built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```

4. **Access**:
   http://localhost:8000


## Directory Structure

- `public/`: Web root (index.php, uploads).
- `src/`: Application classes (Controllers, Models, Config).
- `templates/`: Twig views.
- `database/`: Schema and SQLite file.

Good luck!

Audit: 
1. Setting Up Project Dependencies
Installing Composer dependencies and setting up the development environment for the SellNow project.

2. Add .gitignore in this project

3. Need to add success.html.twig for after completing success.html.twig

4. schema.sql file's schema updated 