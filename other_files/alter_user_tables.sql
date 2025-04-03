-- Add Email and Password Hash columns to Customer table
ALTER TABLE Customer
ADD COLUMN Email_Address VARCHAR(100) UNIQUE NOT NULL AFTER Address,
ADD COLUMN Password_Hash VARCHAR(255) NOT NULL AFTER Email_Address;

-- Add Email and Password Hash columns to Employee table
ALTER TABLE Employee
ADD COLUMN Email_Address VARCHAR(100) UNIQUE AFTER Address, -- Email is optional for employees initially
ADD COLUMN Password_Hash VARCHAR(255) NOT NULL AFTER Email_Address;

-- Drop the now redundant Customer_Email table
DROP TABLE IF EXISTS Customer_Email;

-- Note: Consider adding an index to Email_Address columns for faster lookups
-- ALTER TABLE Customer ADD INDEX idx_customer_email (Email_Address);
-- ALTER TABLE Employee ADD INDEX idx_employee_email (Email_Address); 