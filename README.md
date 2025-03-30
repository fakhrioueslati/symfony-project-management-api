# Symfony Project & Task Management API
This backend API, built with Symfony, is designed for managing projects, tasks, and comments. It allows users to register, log in, create projects, add members to projects, and assign tasks to users within the projects. The API provides a secure and scalable solution with JWT authentication, role-based access control, and Doctrine ORM for database management.

# Key Features
- User Authentication & Authorization (JWT-based security) 
- Project Management (Create, update, assign, and delete projects)- Task Management (Tasks within projects, assignments, and tracking)
- Comment System (Users can add comments to tasks) 
- Role-Based Access Control (Project owners, managers, and assigned members)
- Database Management with Doctrine ORM (Efficient data handling)
- Data Validation (Ensuring data integrity and validation before saving to the database)


## Requirements
- PHP >= 8.2
- Symfony 7.2
- Composer
- MySQL or any other database supported by Doctrine ORM

## Installation

1. **Clone the repository:**
    ```bash
    git clone <repository-url>
    ```
    Replace `<repository-url>` with the URL of the repository you want to clone.

2. **Install dependencies:**
    ```bash
    composer install
    ```

3. **Create the database:**
    ```bash
    php bin/console doctrine:database:create
    ```

4. **Generate migrations:**
    ```bash
    php bin/console make:migration
    ```

5. **Run migrations:**
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

6. **Load Fixtures:**
    ```bash
    php bin/console doctrine:fixtures:load
    ```

7. **Start the Symfony server (or use a PHP built-in server):**
    ```bash
    symfony server:start
    ```
    Or with PHP's built-in server:
    ```bash
    php -S 127.0.0.1:8000 -t public
    ```
