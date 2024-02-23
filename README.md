# Employee Management Software

This is a Laravel-based employee management software designed as a technical assignment to showcase various skills including PHP 8.0 syntax, proper code structure, design patterns, and comprehensive test coverage.

## Description

The project is an API for managing employees with advanced features implemented to demonstrate proficiency in the Laravel framework.

## Stack/Tools

-   Laravel 10
-   PHPUnitfor tests
-   Laravel Sail
-   Laravel Sanctum for authentication
-   laravel-snappy (for PDF reports)
-   Laravel Excel (for Excel reports)
-   Mailpit
-   OpenAPI Specification (v3) for documenting endpoints

## Features

-   Full authentication system using Sanctum (Register, Login, Logout, Forgot password, Password reset)
-   Employee CRUD operations
-   Attendance management to record employee arrivals and departures
-   Automated email notifications to employees upon attendance record creation
-   Attendance report generation in PDF and Excel formats with daily attendance data

## Requirements

-   PHP 8.1
-   laravel-snappy 1.0
-   guzzlehttp/guzzle 7.2
-   laravel/framework 10.10
-   laravel/sanctum 3.3
-   laravel/tinker 2.8
-   maatwebsite/excel 3.1

### Development Requirements

-   fakerphp/faker 1.9.1
-   laravel/pint 1.0
-   laravel/sail 1.18
-   mockery/mockery 1.4.4
-   nunomaduro/collision 7.0
-   phpunit/phpunit 10.1
-   spatie/laravel-ignition 2.0

## Installation

1. Clone this repository to your local machine.
2. Install PHP dependencies using Composer:

```bash
composer install
```

3. Copy the .env.example file to .env and configure your environment variables, including database connection settings.
4. Generate a new application key:

```bash
php artisan key:generate
```

5. Migrate the database and seeds

```bash
php artisan migrate --seed
```

6. Serve the application using Laravel Sail:

```bash
./vendor/bin/sail up
```

## Bonus Points

1. GitHub Actions for running tests on PR events.
