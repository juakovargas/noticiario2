# Noticiario - Base Administration System

Laravel 12 monolithic application with Inertia.js + React + TypeScript frontend.

## Stack

- Laravel 12
- Inertia.js + React + TypeScript
- Tailwind CSS
- shadcn/ui-style component foundation
- Spatie Laravel Permission
- MySQL

## Included in this first phase

- Authentication (register, login, logout, password reset routes from starter kit)
- Protected admin area under /admin
- Admin dashboard
- User management
  - List, create, show, edit, delete
  - Active/inactive toggle with is_active field
  - Optional password update on edit
  - Role and direct permission assignment
- Role management
  - List, create, edit, delete
  - Assign permissions to roles
- Permission management
  - List, create, edit, delete
- Authorization
  - Admin routes protected by auth + permission middleware
  - admin.access permission required
  - super-admin has full access via Gate::before

## Initial seeded data

Roles:

- super-admin
- admin
- editor
- viewer

Permissions:

- users.view
- users.create
- users.update
- users.delete
- roles.view
- roles.create
- roles.update
- roles.delete
- permissions.view
- permissions.create
- permissions.update
- permissions.delete
- dashboard.view
- admin.access

Initial user:

- Name: Super Admin
- Email: admin@example.com
- Password: password
- Role: super-admin

## Installation

1. Install dependencies:

	composer install
	npm install

2. Configure environment:

	- Copy .env.example to .env if needed
	- Set DB credentials for MySQL
	- Ensure DB_DATABASE exists (default: noticiario)

3. Generate app key (if needed):

	php artisan key:generate

4. Run migrations and seeders:

	php artisan migrate --seed

5. Start development:

	npm run dev
	php artisan serve

## Admin route names

- admin.dashboard
- admin.users.index
- admin.users.create
- admin.users.store
- admin.users.show
- admin.users.edit
- admin.users.update
- admin.users.destroy
- admin.roles.index
- admin.roles.create
- admin.roles.store
- admin.roles.edit
- admin.roles.update
- admin.roles.destroy
- admin.permissions.index
- admin.permissions.create
- admin.permissions.store
- admin.permissions.edit
- admin.permissions.update
- admin.permissions.destroy
