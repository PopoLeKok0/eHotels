-- Add Payment_Amount column to Renting table
ALTER TABLE Renting
ADD COLUMN Payment_Amount DECIMAL(10, 2) NULL;

SELECT 'Payment_Amount column added to Renting table.' AS Status; 