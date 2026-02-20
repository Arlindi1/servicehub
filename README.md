# ServiceHub

A **full-stack client portal & project delivery platform** built around real SaaS workflows such as client management, project tracking, file sharing, and invoicing.

The project focuses on **clean structure**, **practical business logic**, and patterns commonly used in production web applications.

---

## Demo

> **Status:** Not publicly deployed  
> The application runs locally. Full source code is available in this repository.

---

## My Role

- Built application features using **PHP (Laravel)** and **Vue 3 (Inertia)**
- Implemented role-based access (**Owner / Staff / Client**)
- Worked with database models, relationships, and migrations
- Implemented server ↔ client data flow using REST-style endpoints
- Focused on clean code, readability, and maintainability

---

## Tech Stack

### Backend
- PHP (Laravel, MVC)
- MySQL / SQLite (local development)
- Authentication & authorization
- REST API concepts

### Frontend
- Vue 3 (Inertia.js)
- Vite
- JavaScript (ES6+)

### General
- Git
- Local development environment

---

## Key Features

- Organization-scoped data access
- Role-based permissions
- Client management
- Project management (tasks, files, discussions)
- Secure file uploads and downloads
- Invoice generation with PDF export
- Client portal with limited access
- In-app notifications
- Activity logging

---

## Running Locally

The project is fully runnable in a local development environment.

Basic steps:
```bash
composer install
npm install
php artisan migrate --seed
npm run dev
php artisan serve
