-- sql/fix_foreign_keys.sql
-- Fix Customer_ID type in Renting, apply ON DELETE SET NULL, add Payment_Date, remove archives.

-- Make sure to connect to the 'ehotels' database before running this.
-- \c ehotels

-- --- Fix Renting Table Data Type FIRST --- 
-- Ensure Renting.Customer_ID matches Customer.Customer_ID type (VARCHAR(36))
-- Allow NULL because ON DELETE SET NULL requires it.
ALTER TABLE Renting MODIFY COLUMN Customer_ID VARCHAR(36) NULL;

-- --- Booking Table Foreign Key --- 

-- Find the exact constraint name if different from 'booking_customer_id_fkey'
-- Example: SELECT constraint_name FROM information_schema.key_column_usage 
-- WHERE table_name = 'booking' AND column_name = 'customer_id';
ALTER TABLE Booking DROP CONSTRAINT IF EXISTS booking_customer_id_fkey; 

-- Add the foreign key constraint with ON DELETE SET NULL
-- Ensure Booking.Customer_ID is also nullable or modify it first.
ALTER TABLE Booking MODIFY COLUMN Customer_ID VARCHAR(36) NULL;
ALTER TABLE Booking ADD CONSTRAINT booking_customer_id_fkey
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID) ON DELETE SET NULL;


-- --- Renting Table Foreign Key --- 

-- Find the exact constraint name if different from 'renting_customer_id_fkey'
-- Example: SELECT constraint_name FROM information_schema.key_column_usage 
-- WHERE table_name = 'renting' AND column_name = 'customer_id';
ALTER TABLE Renting DROP CONSTRAINT IF EXISTS renting_customer_id_fkey;

-- Now add the constraint, referencing the correctly typed Customer_ID
ALTER TABLE Renting ADD CONSTRAINT renting_customer_id_fkey
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID) ON DELETE SET NULL;

-- --- Room Deletion Handling --- 
-- The requirement states history should persist even if the room is deleted.
-- The current schema uses ON DELETE RESTRICT for Room FKs in Reserved_By and Rented_By.
-- Changing this to SET NULL requires the FK columns in Reserved_By/Rented_By to be nullable.
-- Given the composite key structure (Hotel_Address, Room_Num) in the provided schema for Room and its references,
-- implementing ON DELETE SET NULL cleanly is complex without schema redesign (e.g., using a single Room_ID primary key).
-- We will leave the Room foreign keys as ON DELETE RESTRICT for now.
-- This means rooms with active/past bookings/rentings cannot be deleted, which partially contradicts the requirement 
-- but avoids complex schema changes at this stage. The history *related to the room* persists because the room cannot be deleted.


-- --- Add Payment Date Column --- 
-- Make sure this runs *after* potential modifications to Renting table structure
ALTER TABLE Renting ADD COLUMN Payment_Date DATE NULL;

-- --- Remove Redundant Archive Tables --- 

DROP TABLE IF EXISTS Archived_Booking;
DROP TABLE IF EXISTS Archived_Renting;

-- --- Final Check --- 
-- Verify the changes by inspecting the table constraints, e.g., using \d Booking in psql or information_schema queries. 