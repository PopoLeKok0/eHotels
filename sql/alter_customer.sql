-- Add missing ID_Value column required by description
ALTER TABLE Customer 
ADD COLUMN ID_Value VARCHAR(50) NOT NULL COMMENT 'Value corresponding to ID_Type (e.g., SIN, License #)' AFTER ID_Type;

-- Add columns needed for standard login functionality (implied by requirement 2g)
ALTER TABLE Customer 
ADD COLUMN Email_Address VARCHAR(255) NULL UNIQUE COMMENT 'Email for login and contact' AFTER Address;

ALTER TABLE Customer 
ADD COLUMN Password_Hash VARCHAR(255) NULL COMMENT 'Hashed password for login' AFTER Email_Address;

-- Note: Existing customers will have NULL for Email/Password initially.
-- Consider adding constraints like NOT NULL after populating/implementing registration.
-- We might need to populate Email_Address for existing customers based on Full_Name temporarily if needed. 