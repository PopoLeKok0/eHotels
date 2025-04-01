CREATE DATABASE IF NOT EXISTS ehotels;
CREATE USER IF NOT EXISTS 'ehotels_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password123';
GRANT ALL PRIVILEGES ON ehotels.* TO 'ehotels_user'@'localhost';
FLUSH PRIVILEGES; 