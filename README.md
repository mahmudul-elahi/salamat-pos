# Billora SaaS Invoice Management System


- [Introduction](#introduction)
- [Requirements](#requirements)
- [Local Installation](#local-installation)
- [Database Configuration](#database-configuration)
- [Running the Application](#running-the-application)

## Introduction

Welcome to the Billora SaaS Invoice Management System! This guide provides comprehensive instructions to help you set up and run the project locally.

## Requirements

Ensure your server meets the following requirements:

- **PHP**: Version 8.2
- **MySQL**: Version 5.6+
- **MariaDB**: Version 10.2+
- **Node.js**: Version 20.x
- **npm**: Version 10.5.0+
- **Composer**
- Symlink/storage permissions

## Local Installation

1. **Open the Project**:
    - Use your preferred editor (e.g., PHPStorm, VSCode).

2. **Configuration Updates**:
    - Move `vite.config.js` from the root folder to the `src` folder.
    - Copy the `assets` folder from the root directory and place it inside the `public` folder within the `src` directory.

3. **Environment Setup**:
    - Open the `.env` file located in the `src` folder and set `APP_INSTALLED=true`.

4. **Install Dependencies**:
    - Open a terminal in the `src` folder and execute the following commands:
      ```bash
      npm install
      npm run dev
      composer install
      php artisan app:symlink-storage
      ```

## Database Configuration

1. **Create a Local Database**:
    - Set up a new database on your local machine.

2. **Update Database Credentials**:
    - Modify the following lines in the `.env` file located in the `src` folder:
      ```env
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1 or localhost
      DB_PORT=3306
      DB_DATABASE=your_database_name
      DB_USERNAME=your_database_user_name
      DB_PASSWORD=your_database_password
      ```

3. **Run Migrations and Seed Database**:
    - Execute the following commands after updating the `.env` file:
      ```bash
      php artisan optimize:clear
      php artisan migrate:fresh --seed
      ```

## Running the Application

1. **Process Queued Jobs**:
    - In a new terminal, navigate to the `src` folder and run:
      ```bash
      php artisan schedule:work
      ```

2. **Start the Development Server**:
    - In the root directory of your project, run:
      ```bash
      php -S localhost:8000 -t ./
      ```

3. **Update the Application URL**:
    - Modify the `APP_URL` in the `.env` file to:
      ```env
      APP_URL=http://localhost:8000
      ```

---


Frontend (Vue components এ):


import { useTimezone } from "@/utils/useTimezone.js";

const { formatDate, formatDateTime, formatTime, userTimezone } = useTimezone();

// Template এ:
// {{ formatDate(payslip.created_at) }}          → "23 Jun 2026"
// {{ formatDateTime(attendance.in_time) }}       → "23 Jun 2026, 09:30 AM"
// {{ formatTime(attendance.out_time) }}          → "06:00 PM"

// Controller বা Resource এ:
'created_at' => to_user_timezone($record->created_at),
'date'       => to_user_timezone($record->date, 'Y-m-d'),

By following these instructions, you will be able to set up and run the application seamlessly. For any further assistance, please refer to the official Laravel documentation or reach out to our support team.

