# LibraryAPI

> Headless REST API for library management — Laravel 13 · PHP 8.4 · JSON:API

[![Tests](https://github.com/orebarranco/library-api/actions/workflows/ci.yml/badge.svg)](https://github.com/orebarranco/library-api/actions)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/orebarranco/library-api)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen)](https://phpstan.org)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

---

**LibraryAPI** handles the complete lifecycle of books, physical copies, reservations, loans, fines, notifications and reporting — with role-based access control and automated background jobs.

Built on top of [`orebarranco/laravel-api-starter-kit`](https://github.com/orebarranco/laravel-api-starter-kit).

---

## Requirements

- PHP 8.4+
- Composer 2.x
- MySQL 8.0+ or PostgreSQL 14+
- Redis (recommended for queues and cache)

---

## Installation

```bash
git clone https://github.com/orebarranco/library-api.git
cd library-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed  # optional: seeds one user per role + sample catalog
php artisan serve
```

---

## Testing

```bash
composer test       # lint + static analysis + coverage
composer test:unit  # unit tests only
composer lint       # Rector + Pint
```

---

## License

MIT — see [LICENSE](LICENSE) for details.
