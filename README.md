# e-Hotels Application

A web-based hotel booking system based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463).

## Features

- User-friendly interface for searching and booking hotel rooms
- Multiple search filters including location, date, price, and amenities
- User account management system
- Booking management for customers
- Responsive design for mobile and desktop use

## Technologies Used

- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- Bootstrap 5.1
- PDO for database connections

## Installation

1. **Clone the repository**
   ```
   git clone https://github.com/yourusername/eHotels.git
   cd eHotels
   ```

2. **Create a MySQL database**
   ```sql
   CREATE DATABASE ehotels;
   CREATE USER 'ehotels_user'@'localhost' IDENTIFIED BY 'your_password_here';
   GRANT ALL PRIVILEGES ON ehotels.* TO 'ehotels_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Import database schema**
   ```
   mysql -u ehotels_user -p ehotels < database/schema.sql
   ```

4. **Update database configuration**
   
   Edit the `includes/db.php` file to match your database settings:
   ```php
   $host = 'localhost';
   $database = 'ehotels';
   $username = 'ehotels_user';
   $password = 'your_password_here'; // Change this to your actual password
   ```

5. **Configure your web server**
   
   Point your web server document root to the project directory or create a virtual host.

   For Apache, you might create a virtual host configuration like:
   ```apache
   <VirtualHost *:80>
       ServerName ehotels.local
       DocumentRoot /path/to/eHotels
       <Directory /path/to/eHotels>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

6. **Open in browser**
   
   Navigate to the website in your browser (e.g., http://localhost or http://ehotels.local if you set up a virtual host)

## Directory Structure

- `/css` - Stylesheet files
- `/js` - JavaScript files
- `/includes` - PHP components like header, footer, and database connection
- `/database` - Database schema and sample data
- `/images` - Image assets for the website

## Usage

1. **Home Page**: Browse featured hotels and search for rooms
2. **Search Page**: Filter and view available rooms
3. **Booking Page**: Book a selected room
4. **Account Page**: Manage profile and view booking history

## Credits

This application is based on a database design project by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463). 