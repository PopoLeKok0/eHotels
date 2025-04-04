# e-Hotels Application

A web-based hotel booking system based on Deliverable 1 by Mouad Ben lahbib (300259705).

## Features

- User-friendly interface for searching and booking hotel rooms
- Multi-criteria search filters (Dates, Capacity, Area, Chain, Rating, Price, etc.)
- Customer registration and login
- Customer booking management (view bookings)
- Employee portal with separate login
- Employee functionalities:
    - Check-in guests (convert booking to renting)
    - Direct room rental without prior booking
    - CRUD operations for Hotel Chains, Hotels, Rooms, Employees, Customers (limited), Contacts
    - Dashboard displaying key information and database views
- Responsive design

## Technologies Used

- PHP (Tested on 8.x)
- MySQL (Tested on 5.7+ / MariaDB 10.x+)
- Apache (or compatible web server like Nginx)
- HTML5, CSS3, JavaScript (ES6)
- Bootstrap 5.3
- Font Awesome 6
- PDO for database connections

## Installation & Setup

**Tested Environment:**
- Windows OS
- XAMPP (with Apache, MariaDB/MySQL, PHP 8.x)

**Prerequisites:**
- XAMPP installed and running (Apache & MySQL services started).
- A web browser.

**Steps:**

1.  **Place Project Files:**
    *   Copy the entire `eHotels` project folder (excluding the `.git` and `non_submission` directories if present) into your XAMPP's `htdocs` directory (typically `C:\xampp\htdocs\`).

2.  **Configure Database Connection:**
    *   Open the file `config/database.php` using a text editor.
    *   Ensure the variables match the default XAMPP MySQL settings:
        ```php
        private $host = '127.0.0.1'; // Or 'localhost'
        private $db_name = 'ehotels'; // You will create this database name
        private $username = 'root'; // Default XAMPP username
        private $password = ''; // Default XAMPP password is empty
        ```
    *   *(If your XAMPP MySQL uses a different username or password, update accordingly.)*
    *   Save the `config/database.php` file.

3.  **Create Database via phpMyAdmin:**
    *   Open your web browser and go to `http://localhost/phpmyadmin`.
    *   Click on the "Databases" tab.
    *   In the "Create database" field, enter the database name specified in `config/database.php` (e.g., `ehotels`).
    *   Select `utf8mb4_unicode_ci` as the collation.
    *   Click "Create".

4.  **Import Schema & Data via phpMyAdmin:**
    *   In phpMyAdmin, click on the database name you just created (e.g., `ehotels`) in the left-hand sidebar.
    *   Click on the "Import" tab near the top.
    *   Under "File to import", click "Browse..." or "Choose File".
    *   Navigate to the `eHotels/sql/` directory inside your `htdocs` folder.
    *   Import the following files **one by one, in this exact order**:
        1.  Select `database_schema.sql` and click "Go" (or "Import"). Wait for success.
        2.  Click "Import" again, select `database_population.sql`, and click "Go". Wait for success.
        3.  Click "Import" again, select `create_views.sql`, and click "Go". Wait for success.
        4.  Click "Import" again, select `create_indexes.sql`, and click "Go". Wait for success.
        5.  Click "Import" again, select `triggers.sql`, and click "Go". Wait for success.
    *   *Note:* Ensure each import completes successfully before proceeding to the next.

5.  **Access the Application:**
    *   Open your web browser and navigate to the project directory URL.
    *   Example: If you placed the folder as `eHotels` in `htdocs`, the URL will likely be `http://localhost/eHotels/` or `http://127.0.0.1/eHotels/`.

6.  **Login Credentials (from population script):**
    *   **Customer:** Please use the registration page to create a customer account. 
    *   **Employee:** Please use the registration page to create an employee account. 

## Directory Structure (Essential for Submission)

- `/config` - Database configuration
- `/css` - Stylesheet files
- `/employee` - PHP files for employee-specific functionality
- `/hotel` - PHP files for hotel management
- `/hotel_chain` - PHP files for hotel chain management
- `/images` - Image assets
- `/includes` - Common PHP components (header, footer)
- `/js` - JavaScript files
- `/sql` - Essential SQL setup files (`database_schema.sql`, `database_population.sql`, `create_views.sql`, `create_indexes.sql`, `triggers.sql`)
- `index.php` - Main entry point / homepage
- `login.php` - User login page
- `logout.php` - User logout script
- `my_bookings.php` - Customer booking view page
- `process_booking.php` - Handles the booking submission
- `README.md` - This file
- `register.php` - Customer registration page
- `search.php` - Room search page

## Usage Notes

- The application uses sessions to manage user login state.
- Error messages should appear at the top of the page if issues occur.
- Database errors are logged to the PHP error log.

## Credits

This application is based on a database design project by Mouad Ben lahbib (300259705).

## Contributors
- Mouad Ben lahbib (300259705)

## Disclaimer
This application is based on a database design project by Mouad Ben lahbib (300259705).

## License 
All Rights Reserved.
