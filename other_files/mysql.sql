   CREATE DATABASE ehotels;
   CREATE USER 'ehotels_user'@'localhost' IDENTIFIED BY 'password123';
   GRANT ALL PRIVILEGES ON ehotels.* TO 'ehotels_user'@'localhost';
   FLUSH PRIVILEGES;