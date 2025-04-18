-- Update passwords for existing sample users.
-- IMPORTANT: Replace the placeholder hash below with the actual hash 
-- generated by running: php -r "echo password_hash('password123', PASSWORD_DEFAULT);"

-- Using a valid BCrypt hash for password123
SET @password_hash = '$2y$10$ZC8gy6w6t03NWP9MsEOIxuG1CBsL5mT0lUgiJJTQp2aYpZATP0l/a'; 

-- Update some sample Customers (assuming emails like customer1@example.com etc. exist)
-- If these emails don't exist, these UPDATE statements will do nothing.
UPDATE Customer 
SET Password_Hash = @password_hash 
WHERE Email_Address = 'customer1@example.com'; 

UPDATE Customer 
SET Password_Hash = @password_hash 
WHERE Email_Address = 'customer2@example.com'; 

-- Update some sample Employees (assuming emails like employee1@example.com etc. exist)
UPDATE Employee 
SET Password_Hash = @password_hash 
WHERE Email_Address = 'employee1@example.com';

UPDATE Employee 
SET Password_Hash = @password_hash 
WHERE Email_Address = 'employee2@example.com';

-- Add/Update default test users
INSERT INTO Customer (Customer_ID, Full_Name, Address, Email_Address, Password_Hash, ID_Type, Date_of_Registration) 
VALUES (UUID(), 'Test Customer', '123 Test St', 'test@example.com', @password_hash, 'Passport', CURDATE())
ON DUPLICATE KEY UPDATE Password_Hash = @password_hash; -- Update password if customer exists

INSERT INTO Employee (SSN, Full_Name, Address, Email_Address, Password_Hash, Position) 
VALUES ('999888777', 'Test Employee', '456 Staff Rd', 'employee@example.com', @password_hash, 'Receptionist')
ON DUPLICATE KEY UPDATE Password_Hash = @password_hash, Email_Address = 'employee@example.com'; -- Update password/email if employee exists

SELECT 'Password update script executed.' AS Status; 