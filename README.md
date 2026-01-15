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
   php -S localhost:8001 -t public
   ```

4. **Environment Setup**:
   Copy `.env.example` to `.env` (already done):
   ```bash
   cp .env.example .env
   ```

5. **Access**:
   http://localhost:8001

6. **Test Login**:
   A test user has been created:
   - Email: `hello@app.com`
   - Password: `12345678`


## Directory Structure

- `public/`: Web root (index.php, uploads).
- `src/`: Application classes (Controllers, Models, Config).
- `templates/`: Twig views.
- `database/`: Schema and SQLite file.

Good luck!

## Recent Fixes & Enhancements

### Authentication & Security Fixes (Latest)

#### 1. **Fixed Login/Registration Issue**
**Problem**: Users could register but login failed immediately after registration.

**Root Causes Identified**:
- Missing `.env` file (application expected environment configuration)
- CSRF tokens not present in login/register forms
- AuthController was using old plain-text password comparison instead of bcrypt
- Form error messages not displaying properly

**Solutions Implemented**:
- ✅ Created `.env` file from `.env.example` with proper configuration
- ✅ Added CSRF token fields to both login and register forms (`{{ csrf_field()|raw }}`)
- ✅ Updated AuthController to use modern Request/Response classes
- ✅ Integrated AuthService with bcrypt password hashing (password_hash/password_verify)
- ✅ Fixed error message display in templates (passing `error` and `msg` parameters)
- ✅ Cleared existing users with plain-text passwords from database
- ✅ Added success message display on login page after registration

**Technical Changes**:
```php
// Before (Insecure)
if ($user && $password == $user['password']) { ... }

// After (Secure)
$result = $this->authService->login($email, $password);
// Uses password_verify() internally with bcrypt
```

**Files Modified**:
- `src/Controllers/AuthController.php` - Refactored to use DI, Request/Response, AuthService
- `templates/auth/login.html.twig` - Added CSRF token and error/success message display
- `templates/auth/register.html.twig` - Added CSRF token and error message display
- `.env` - Created from example with SQLite configuration

#### 2. **Created .gitignore**
**Purpose**: Prevent committing vendor directory and sensitive files to Git repository.

**Contents**:
- `/vendor/` - Composer dependencies (can be rebuilt with `composer install`)
- `.env` files - Environment configuration with sensitive data
- `/storage/logs/*.log` - Application logs
- `/public/uploads/*` - User-uploaded files
- IDE files (.vscode, .idea)
- Cache and temporary files

#### 3. **Fixed CSRF Token Validation Error on Product Add Form**
**Issue Title**: CSRF Token Validation Failed on POST /products/add

**Problem**: 
When submitting the "Add Product" form at `http://localhost:8001/products/add`, the application returned a 403 error with the message "CSRF token validation failed".

**Why This Issue Occurred**:
The issue occurred due to a mismatch between the application's security requirements and the form implementation:

1. **Security Middleware Active**: The route `POST /products/add` was protected with `CsrfMiddleware` (defined in `config/routes.php`)
2. **Missing CSRF Token in Form**: The form template (`templates/products/add.html.twig`) did not include the CSRF token field
3. **Middleware Validation Failure**: When the form was submitted, `CsrfMiddleware` checked for `_csrf_token` in POST data, found none, and rejected the request
4. **CSRF Function Already Available**: The Twig environment had `csrf_field()` function registered (in `config/services.php`), but it wasn't being used in the template

**Root Cause Analysis**:
```
Flow of the problem:
1. User clicks "Add Product" → GET /products/add (shows form)
2. Form rendered WITHOUT csrf token field
3. User fills form and submits → POST /products/add
4. Request passes through middleware stack
5. CsrfMiddleware::handle() executes
6. Looks for $_POST['_csrf_token'] → NOT FOUND
7. Returns Response::make('CSRF token validation failed', 403)
8. Request never reaches ProductController::store()
```

**Fix Implementation Steps**:

**Step 1**: Identified the missing CSRF token in the form
- Opened `templates/products/add.html.twig`
- Verified that form had no `<input type="hidden" name="_csrf_token">` field

**Step 2**: Checked Twig functions availability
- Reviewed `config/services.php` to confirm `csrf_field()` function was already registered
- Confirmed `CsrfToken` class was properly injected into Twig environment

**Step 3**: Added CSRF token field to the form
- Updated `templates/products/add.html.twig`
- Added `{{ csrf_field()|raw }}` immediately after the `<form>` opening tag
- The `|raw` filter is necessary because `csrf_field()` returns HTML markup that should not be escaped

**Step 4**: Verified the fix
- The form now includes: `<input type="hidden" name="_csrf_token" value="[64-character token]">`
- When submitted, `CsrfMiddleware` can validate the token
- Request successfully reaches `ProductController::store()`

**Code Changes**:
```twig
<!-- Before (templates/products/add.html.twig) -->
<form action="/products/add" method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Title</label>
        ...
    </div>
</form>

<!-- After (templates/products/add.html.twig) -->
<form action="/products/add" method="POST" enctype="multipart/form-data">
    {{ csrf_field()|raw }}
    <div class="mb-3">
        <label>Title</label>
        ...
    </div>
</form>
```

**How CSRF Protection Works in This Application**:

1. **Token Generation**: 
   - `CsrfToken::generate()` creates a 64-character random token using `random_bytes(32)` and `bin2hex()`
   - Token is stored in session: `$_SESSION['_csrf_token']`

2. **Token Inclusion**: 
   - `csrf_field()` Twig function generates hidden input field with current token
   - Token is embedded in forms that perform state-changing operations

3. **Token Validation**: 
   - `CsrfMiddleware` intercepts POST/PUT/DELETE requests
   - Extracts token from `$_POST['_csrf_token']`
   - Compares with session token using `hash_equals()` (prevents timing attacks)
   - Returns 403 if validation fails, otherwise allows request to continue

4. **Security Benefit**: 
   - Prevents attackers from forging requests on behalf of authenticated users
   - Each user session has unique token
   - Tokens are unpredictable and validated server-side

**Files Modified**:
- `templates/products/add.html.twig` - Added CSRF token field to product creation form

**Related Files** (for reference, not modified):
- `src/Middleware/CsrfMiddleware.php` - CSRF validation logic
- `src/Security/CsrfToken.php` - Token generation and validation
- `config/routes.php` - Route definition with CsrfMiddleware
- `config/services.php` - Twig csrf_field() function registration

**Testing**:
After the fix, the product creation flow works as follows:
1. User navigates to `/products/add` (GET request, no CSRF check)
2. Form is rendered with CSRF token hidden field
3. User fills form and submits
4. POST request includes `_csrf_token` parameter
5. `CsrfMiddleware` validates token successfully
6. Request reaches `ProductController::store()`
7. Product is created and user is redirected to dashboard

---

### Previous Audit Log

1. Setting Up Project Dependencies
Installing Composer dependencies and setting up the development environment for the SellNow project.

2. Add .gitignore in this project

3. Need to add success.html.twig for after completing success.html.twig

4. schema.sql file's schema updated 

5.
   - Creating environment configuration and bootstrap files
   - Creating Router, Request, and Response classes
   - Creating security components (Password Hasher, CSRF Token, File Upload Validator)
   - Creating database layer with Connection, QueryBuilder, and Repository pattern
   - Creating Validation layer and Service layer (AuthService, ProductService)
   - Creating Payment Gateway abstraction and refactoring controllers
   - Creating service configuration, routes, and refactoring controllers
   - Updating front controller and normalizing database schema


## 🏗️ The Architecture: Clean & Scalable

### Directory Structure
```
SellNow/
├── config/
│   ├── services.php          # DI container configuration
│   └── routes.php             # Centralized route definitions
├── database/
│   ├── schema.sql             # Normalized database schema
│   └── database.sqlite        # SQLite database
├── public/
│   ├── index.php              # Front controller
│   └── uploads/               # User-uploaded files
├── src/
│   ├── Core/                  # Framework-like components
│   │   ├── Container.php      # Dependency injection
│   │   ├── Router.php         # HTTP routing
│   │   ├── Request.php        # Request abstraction
│   │   └── Response.php       # Response abstraction
│   ├── Database/              # Data access layer
│   │   ├── Connection.php     # Database connection
│   │   └── Repository.php     # Base repository
│   ├── Repositories/          # Specific repositories
│   │   ├── UserRepository.php
│   │   ├── ProductRepository.php
│   │   └── OrderRepository.php
│   ├── Services/              # Business logic
│   │   ├── AuthService.php
│   │   ├── ProductService.php
│   │   ├── CartService.php
│   │   └── PaymentService.php
│   ├── Controllers/           # HTTP controllers
│   ├── Middleware/            # Request middleware
│   ├── Security/              # Security utilities
│   ├── Validation/            # Input validation
│   └── Payment/               # Payment gateway abstraction
├── templates/                 # Twig templates
├── bootstrap.php              # Application bootstrap
├── .env                       # Environment configuration
└── composer.json              # Dependencies
```

### Design Patterns Used

#### 1. **Dependency Injection**
```php
// Services are resolved automatically from container
$container->singleton(AuthService::class, function ($container) {
    return new AuthService(
        $container->make(UserRepository::class),
        $container->make(PasswordHasher::class)
    );
});
```

#### 2. **Repository Pattern**
```php
// Data access abstraction - easy to swap SQLite for MySQL
$user = $userRepository->findByEmail($email);
$products = $productRepository->findByUserId($userId);
```

#### 3. **Strategy Pattern** (Payment Gateways)
```php
// Swappable payment providers
$paymentService->registerGateway('stripe', new StripeGateway());
$paymentService->registerGateway('paypal', new PayPalGateway());
```

#### 4. **Middleware Pattern**
```php
// Composable request processing
$router->get('/dashboard', [AuthController::class, 'dashboard'], [
    AuthMiddleware::class,  // Ensure authenticated
    CsrfMiddleware::class   // Validate CSRF token
]);
```

#### 5. **Service Layer**
```php
// Business logic separated from controllers
$result = $authService->register($data);
$result = $productService->createProduct($data, $files, $userId);
```

---

## 🔒 Security Enhancements

### 1. Password Security
- **Before**: Plain-text passwords (`password == $user['password']`)
- **After**: Bcrypt hashing with `password_hash()` and `password_verify()`

### 2. SQL Injection Prevention
- **Before**: `$db->query("SELECT * FROM products WHERE user_id = $user->id")`
- **After**: Prepared statements in repositories: `$stmt->execute([$userId])`

### 3. CSRF Protection
- **Before**: No protection
- **After**: Token-based validation on all POST/PUT/DELETE requests

### 4. File Upload Security
- **Before**: No validation, any file accepted
- **After**: 
  - MIME type validation
  - Extension whitelist
  - Path traversal prevention
  - Executable file blocking
  - Safe filename generation

### 5. XSS Prevention
- **Before**: Raw output of user input
- **After**: `InputSanitizer` with `htmlspecialchars()` and Twig auto-escaping

---

## 📊 The Trade-offs: What I Sacrificed

### 1. **Custom Router vs. Third-Party**
- **Chosen**: Custom lightweight router
- **Cost**: Limited features (no regex patterns, basic middleware)
- **Benefit**: Full control, no external dependencies, educational value
- **Rationale**: For this scale, a simple router is sufficient. Adding a library like FastRoute would be trivial if needed.

### 2. **Simple DI Container vs. Full-Featured**
- **Chosen**: Simple custom container
- **Cost**: No auto-wiring for complex dependencies, manual service registration
- **Benefit**: Transparent, easy to understand, no magic
- **Rationale**: The container handles 90% of use cases. For complex scenarios, explicit factory functions work fine.

### 3. **Repository Pattern vs. Active Record**
- **Chosen**: Repository Pattern
- **Cost**: More boilerplate code, additional abstraction layer
- **Benefit**: Better testability, clear separation of concerns, easier to swap data sources
- **Rationale**: Testability and flexibility outweigh the extra code. Active Record couples models to database.

### 4. **Session-Based Cart vs. Database**
- **Chosen**: Session-based (kept from original)
- **Cost**: Cart lost on session expiry, can't track abandoned carts
- **Benefit**: Simpler, faster, no database overhead for anonymous users
- **Rationale**: For a marketplace, session-based is acceptable. Database carts can be added later for logged-in users.

### 5. **Mock Payment Gateways vs. Real Integration**
- **Chosen**: Mock implementations
- **Cost**: Not production-ready
- **Benefit**: Demonstrates architecture without requiring API keys
- **Rationale**: The goal is to show the **interface design** (strategy pattern). Real integrations are trivial to add.

### 6. **No ORM**
- **Chosen**: Raw PDO with Repository pattern
- **Cost**: More SQL writing, no automatic migrations
- **Benefit**: Full control, no ORM overhead, explicit queries
- **Rationale**: ORMs add complexity and magic. For this scale, repositories + PDO are cleaner.

---

## 🧪 Verification & Testing

### Manual Testing Checklist

#### 1. **Authentication Flow**
```bash
# Start server
php -S localhost:8001 -t public

# Test registration
1. Navigate to http://localhost:8001/register
2. Register with: email, username, full_name, password (min 6 chars)
3. Verify password is hashed in database (not plain text)
4. Login with credentials at http://localhost:8001/login
5. Verify session is created and redirected to dashboard

# Test with existing user
Email: hello@app.com
Password: 12345678
```

#### 2. **Security Testing**
```bash
# SQL Injection Test
- Try injecting SQL in username: `admin' OR '1'='1`
- Should fail (prepared statements prevent it)

# XSS Test
- Try product title: `<script>alert('XSS')</script>`
- Should be escaped in output

# CSRF Test
- Try submitting form without CSRF token
- Should fail with 403

# File Upload Test
- Try uploading .php file as product
- Should be rejected
```

#### 3. **Product Creation**
```bash
1. Login as user
2. Navigate to /products/add
3. Upload product with image and file
4. Verify files are validated
5. Verify product appears in dashboard
```

#### 4. **Cart & Checkout**
```bash
1. Browse to user profile (/username)
2. Add product to cart
3. View cart at /cart
4. Proceed to checkout
5. Select payment provider
6. Verify order is created
7. Verify cart is cleared
```

### Database Verification
```bash
# Check password hashing
sqlite3 database/database.sqlite "SELECT email, password FROM users LIMIT 1;"
# Password should start with $2y$ (bcrypt)

# Check foreign keys
sqlite3 database/database.sqlite "PRAGMA foreign_key_list(products);"
# Should show user_id references users(id)

# Check indexes
sqlite3 database/database.sqlite "PRAGMA index_list(products);"
# Should show indexes on user_id, slug, is_active
```

---

## 🚀 What Makes This Special

### 1. **No Framework, But Framework-Like**
I built my own:
- Dependency Injection Container
- Router with middleware support
- Request/Response abstractions
- Repository pattern
- Service layer

This demonstrates **deep understanding** of what frameworks do under the hood.

### 2. **Security-First Mindset**
Every feature considers security:
- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- CSRF protection (token validation)
- XSS prevention (input sanitization)
- File upload security (MIME validation)

### 3. **Clean Architecture**
- **Controllers** are thin (just HTTP handling)
- **Services** contain business logic
- **Repositories** handle data access
- **Entities** represent domain models
- **Middleware** handles cross-cutting concerns

### 4. **Extensibility**
- Want to add a new payment gateway? Implement `PaymentGatewayInterface`
- Want to switch from SQLite to MySQL? Change `.env` file
- Want to add caching? Add a middleware
- Want to add logging? Inject a logger service

### 5. **Developer-Friendly**
- **Comprehensive comments** on every class and method
- **Type hints** everywhere (PHP 8.x features)
- **Clear naming** (no abbreviations, descriptive names)
- **Consistent structure** (easy to navigate)

---

## 📝 Code Quality Principles

### 1. **SOLID Principles**
- **S**ingle Responsibility: Each class has one job
- **O**pen/Closed: Open for extension (payment gateways), closed for modification
- **L**iskov Substitution: All payment gateways are interchangeable
- **I**nterface Segregation: Small, focused interfaces
- **D**ependency Inversion: Depend on abstractions (interfaces), not concretions

### 2. **DRY (Don't Repeat Yourself)**
- Base `Repository` class for common CRUD operations
- Middleware for reusable request processing
- Service layer to avoid duplicating business logic

### 3. **Separation of Concerns**
- Controllers: HTTP handling
- Services: Business logic
- Repositories: Data access
- Middleware: Cross-cutting concerns
- Validation: Input validation

---

## 🎓 What I Learned

This refactoring taught me:
1. **Frameworks are just organized code** - I can build the same patterns myself
2. **Security must be intentional** - It doesn't happen by accident
3. **Architecture enables velocity** - Good structure makes adding features easy
4. **Trade-offs are inevitable** - Every decision has costs and benefits
5. **Clean code is a discipline** - It requires constant attention

---

## 🔮 Future Enhancements

If I had more time, I would add:
1. **Unit Tests** (PHPUnit for services and repositories)
2. **Email Notifications** (order confirmations, password resets)
3. **Admin Panel** (manage users, products, orders)
4. **API Endpoints** (RESTful API for mobile apps)
5. **Caching Layer** (Redis for session storage, query caching)
6. **Queue System** (background jobs for email, file processing)
7. **Real Payment Integration** (actual Stripe/PayPal API calls)
8. **Database Migrations** (version-controlled schema changes)

---

## 📚 Technologies Used

- **PHP 8.x** (type hints, constructor property promotion)
- **Composer** (dependency management, PSR-4 autoloading)
- **Twig** (template engine)
- **SQLite** (database, easily swappable for MySQL)
- **PDO** (database abstraction)
- **Dotenv** (environment configuration)

---

## 🏁 Conclusion

This project demonstrates that **clean architecture, security, and maintainability are possible without frameworks**. Every line of code was written with intention, every pattern chosen for a reason, and every trade-off carefully considered.

The result is a codebase that is:
- ✅ **Secure** (bcrypt, prepared statements, CSRF, input validation)
- ✅ **Maintainable** (clean architecture, separation of concerns)
- ✅ **Testable** (dependency injection, repository pattern)
- ✅ **Extensible** (strategy pattern, middleware, DI container)
- ✅ **Documented** (comprehensive comments, clear naming)

**This is what "Clean Code" means to me.**

---

🔧 SQLite Driver Enable
Step 1: PHP.ini তে SQLite Extension Enable
   - ✅  run: php --ini

Step 2: SQLite extension enable
   - ✅ php -m | Select-String -Pattern "sqlite"
Step 3: SQLite extension not loaded. We have to  php.ini file edit for enable 
   - ✅  Get-Content "D:\laragon\bin\php\php-8.3.19-Win32-vs16-x64\php.ini" | Select-String -Pattern "extension=pdo_sqlite"
   or we have to go through php.ini file and remove (;) before extension=pdo_sqlite
Step 4: we have to enable sqlite3 into the php.ini file
   - ✅ (Get-Content "D:\laragon\bin\php\php-8.3.19-Win32-vs16-x64\php.ini") -replace ';extension=sqlite3', 'extension=sqlite3' | Set-Content "D:\laragon\bin\php\php-8.3.19-Win32-vs16-x64\php.ini"
Step 5: we need to restart server
   - ✅  taskkill /F /FI "WINDOWTITLE eq php*"
   - ✅ php -m | Select-String -Pattern "pdo_sqlite"
   - ✅ php -S localhost:8000 -t public
## 👨‍💻 Author

Refactored with ❤️ Md Nuruzzaman
linkedin url: https://www.linkedin.com/in/md-nuruzzaman-bb2027169/ 

**Philosophy**: Code is read more than it's written. Make it count.
