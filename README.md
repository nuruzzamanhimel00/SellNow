# SellNow – Assessment Project

A **simplified, intentionally imperfect** platform for selling digital products, built for **candidate assessment**.
This project contains **deliberate flaws, bad practices, and security holes** for learning and evaluation.

---

## 📌 Project Overview

SellNow is a small marketplace where:

1. Users can register and get a public profile at `/username`
2. Users can upload digital products (image + file)
3. Buyers can browse products CRUD ,  Update profile information

---

## ⚙️ Setup Instructions

### 1. Install Dependencies

```bash
composer install
```

### 2. Database Setup (SQLite by Default)

```bash
sqlite3 database/database.sqlite < database/schema.sql
```

> If you want MySQL, update: `src/Config/Database.php`

### 3. Environment File

```bash
cp .env.example .env
```

### 4. Run Development Server

```bash
php -S localhost:8001 -t public
```

### 5. Open in Browser

```
http://localhost:8001
```

### 6. Test Login

* Email: `hello@app.com`
* Password: `12345678`

---

## 📁 Directory Structure

```
public/      → Web root, uploads  
src/         → Controllers, Services, Security, Middleware  
templates/   → Twig views  
database/    → SQLite file and schema  
config/      → Routes and services  
bootstrap.php→ App bootstrap
```

---

## 🔄 Recent Fixes & Enhancements

### 1. Authentication & Security Fixes

**Problems Fixed**

* Login failed after registration
* Missing `.env`
* No CSRF protection
* Plain-text passwords
* Error messages not showing

**Solutions**

* Added `.env` from example
* Added CSRF tokens to forms
* Switched to `password_hash()` and `password_verify()`
* Refactored controllers with Request/Response
* Fixed error and success message display

**Files Updated**

* `AuthController.php`
* `login.html.twig`
* `register.html.twig`
* `.env`

---

### 2. .gitignore Added

Prevents committing:

* vendor/
* .env
* uploads/
* logs/
* IDE files

---

### 3. CSRF Fix on Product Add Form

**Problem**: POST `/products/add` returned 403

**Cause**: Missing CSRF token in form

**Fix**:

```twig
<form ...>
  {{ csrf_field()|raw }}
  ...
</form>
```

**Flow**

1. Token generated and stored in session
2. Token added to form
3. Middleware validates token
4. Request allowed

---

### 4. Product Management (CRUD)

#### Features

* Product list page
* Edit product page
* Update product
* Delete product

#### Security

* Ownership check
* CSRF protection
* Auth middleware
* Prepared statements
* File cleanup

#### Files

Created:

* `products/index.html.twig`
* `products/edit.html.twig`

Modified:

* `ProductController.php`
* `routes.php`
* `dashboard.html.twig`

---
### 5. User Profile Update

**Features**:

* Edit profile at `/profile/edit`
* Update: email, username, full name
* Change password (optional)
* Link to view public profile

**Security**:

* Current password required for password change
* Email/username uniqueness check
* CSRF protection
* Password confirmation validation
* Bcrypt hashing
* Min 6 chars for password

**Validation**:

* Email: Required, valid format
* Username: 3-50 chars, alphanumeric
* Full Name: 2-100 chars
* Passwords must match

**Files Created**:

* `auth/profile-edit.html.twig`

**Files Modified**:

* `AuthController.php` - editProfile(), updateProfile()
* `AuthService.php` - getUserById(), updateProfile()
* `routes.php` - /profile/edit, /profile/update
* `dashboard.html.twig` - Edit Profile button
* `base.html.twig` - My Profile nav link

---
## 🏗 Architecture

```
config/     → routes, services  
src/Core/   → Router, Request, Response, Container  
src/Database→ Connection, Repository  
src/Services→ Auth, Product, Cart, Payment  
src/Controllers
src/Middleware
src/Security
src/Validation
src/Payment
```

---

## 🧩 Design Patterns

* Dependency Injection
* Repository Pattern
* Strategy Pattern (Payment)
* Middleware Pattern
* Service Layer

---

## 🔒 Security Enhancements

| Area        | Before        | After                  |
| ----------- | ------------- | ---------------------- |
| Password    | Plain text    | Bcrypt                 |
| SQL         | Raw queries   | Prepared statements    |
| CSRF        | None          | Token-based            |
| File Upload | No validation | MIME + extension check |
| XSS         | Raw output    | Escaped output         |

---

## ⚖️ Trade-offs

| Choice        | Cost           | Benefit            |
| ------------- | -------------- | ------------------ |
| Custom Router | Fewer features | Full control       |
| Simple DI     | Manual wiring  | Easy to understand |
| Repository    | More code      | Testable           |
| Session Cart  | Lost on expire | Simple             |
| Mock Payment  | Not real       | Shows architecture |
| No ORM        | More SQL       | No magic           |

---

## 🧪 Testing Checklist

### Authentication

* Register user
* Password is hashed
* Login works

### Security

* SQL Injection blocked
* XSS escaped
* CSRF blocked
* PHP file upload blocked

### Product

* Add product
* Edit product
* Delete product

### Profile

* Update profile information
* Change password
* View public profile

---

* Framework-like system without framework
* Security-first mindset
* Clean layered architecture
* Easily extendable
* Developer friendly

---

## 📐 Code Principles

* SOLID
* DRY
* Separation of Concerns

---

## 🎓 What I Learned

* Frameworks are just organized code
* Security must be intentional
* Architecture improves speed
* Trade-offs are everywhere
* Clean code needs discipline

---

## 🔮 Future Improvements

* Unit tests
* Email system
* Admin panel
* REST API
* Redis cache
* Queue system
* Real payment
* Migrations

---

## 🛠 Technologies

* PHP 8.x
* Composer
* Twig
* SQLite / MySQL
* PDO
* Dotenv

---

## 🏁 Conclusion

SellNow shows that **clean architecture and security are possible without frameworks**.
Every decision was intentional and educational.

---

## 🔧 SQLite Enable (Windows Example)

1. Check ini:

```bash
php --ini
```

2. Check module:

```bash
php -m | find "sqlite"
```

3. Enable in php.ini:

```ini
extension=pdo_sqlite
extension=sqlite3
```

4. Restart server

---

## 👨‍💻 Author

Refactored with ❤️ Md Nuruzzaman
LinkedIn: [https://www.linkedin.com/in/md-nuruzzaman-bb2027169/](https://www.linkedin.com/in/md-nuruzzaman-bb2027169/)

**Philosophy**: Code is read more than it's written. Make it count.
