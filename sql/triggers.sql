-- sql/triggers.sql (MySQL/MariaDB Syntax)
-- Triggers to fulfill Requirement 2d

-- Make sure to connect to the 'ehotels' database before running this.
-- \c ehotels

-- Trigger 1: Update Hotel_Chain.Num_Hotels on Hotel INSERT

DELIMITER //
CREATE TRIGGER hotel_insert_trigger
AFTER INSERT ON Hotel
FOR EACH ROW
BEGIN
    UPDATE Hotel_Chain
    SET Num_Hotels = Num_Hotels + 1
    WHERE Chain_Name = NEW.Chain_Name;
END; //
DELIMITER ;

-- Trigger 2: Update Hotel_Chain.Num_Hotels on Hotel DELETE

DELIMITER //
CREATE TRIGGER hotel_delete_trigger
AFTER DELETE ON Hotel
FOR EACH ROW
BEGIN
    UPDATE Hotel_Chain
    SET Num_Hotels = GREATEST(0, Num_Hotels - 1) -- Ensure count doesn't go below 0
    WHERE Chain_Name = OLD.Chain_Name;
END; //
DELIMITER ;

-- Trigger 3: Prevent deletion of an Employee who is currently a Hotel Manager

DELIMITER //
CREATE TRIGGER employee_delete_manager_check_trigger
BEFORE DELETE ON Employee
FOR EACH ROW
BEGIN
    DECLARE is_manager INT DEFAULT 0;
    -- Check if the employee being deleted is a manager in the Hotel table
    -- Need to reference the correct table and column names based on your FINAL schema
    -- Assuming Hotel table has Manager_SSN referencing Employee(SSN)
    SELECT COUNT(*) INTO is_manager FROM Hotel WHERE Manager_SSN = OLD.SSN;
    
    IF is_manager > 0 THEN
        -- Raise a generic user-defined error using SIGNAL
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = CONCAT('Cannot delete employee (SSN: ', OLD.SSN, '): They are currently managing one or more hotels. Reassign managers first.');
    END IF;
END; //
DELIMITER ;