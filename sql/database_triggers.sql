-- e-Hotels Database Triggers
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)

-- Connect to the database
\c ehotels;

-- 1. Manager employment constraint trigger
-- Ensures a manager works at the hotel they manage
CREATE OR REPLACE FUNCTION ensure_manager_works_at_hotel()
RETURNS TRIGGER AS $$
BEGIN
    -- Check if there is a corresponding entry in Works_At
    IF NOT EXISTS (
        SELECT 1 FROM Works_At 
        WHERE SSN = NEW.SSN AND Hotel_Address = NEW.Hotel_Address
    ) THEN
        -- Insert into Works_At automatically
        INSERT INTO Works_At (SSN, Hotel_Address) VALUES (NEW.SSN, NEW.Hotel_Address);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER manager_works_at_hotel_trigger
BEFORE INSERT OR UPDATE ON Manages
FOR EACH ROW EXECUTE FUNCTION ensure_manager_works_at_hotel();

-- Same constraint check when updating hotel manager
CREATE OR REPLACE FUNCTION ensure_hotel_manager_works_at_hotel()
RETURNS TRIGGER AS $$
BEGIN
    -- Ensure the manager works at the hotel
    IF NOT EXISTS (
        SELECT 1 FROM Works_At 
        WHERE SSN = NEW.Manager_SSN AND Hotel_Address = NEW.Hotel_Address
    ) THEN
        -- Insert into Works_At automatically
        INSERT INTO Works_At (SSN, Hotel_Address) VALUES (NEW.Manager_SSN, NEW.Hotel_Address);
    END IF;
    
    -- Update the Manages table to reflect the new manager
    IF EXISTS (
        SELECT 1 FROM Manages
        WHERE Hotel_Address = NEW.Hotel_Address
    ) THEN
        UPDATE Manages 
        SET SSN = NEW.Manager_SSN 
        WHERE Hotel_Address = NEW.Hotel_Address;
    ELSE
        INSERT INTO Manages (SSN, Hotel_Address)
        VALUES (NEW.Manager_SSN, NEW.Hotel_Address);
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER hotel_manager_works_at_hotel_trigger
AFTER INSERT OR UPDATE OF Manager_SSN ON Hotel
FOR EACH ROW EXECUTE FUNCTION ensure_hotel_manager_works_at_hotel();

-- 2. Room booking/renting conflict prevention trigger
-- Prevents overlapping bookings and rentings for a room
CREATE OR REPLACE FUNCTION check_room_availability()
RETURNS TRIGGER AS $$
DECLARE
    booking_conflict INTEGER;
    renting_conflict INTEGER;
BEGIN
    -- Check for booking conflicts
    SELECT COUNT(*) INTO booking_conflict
    FROM Booking b
    JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
    WHERE rb.Hotel_Address = NEW.Hotel_Address 
    AND rb.Room_Num = NEW.Room_Num
    AND b.Booking_ID != NEW.Booking_ID
    AND (
        (NEW.Start_Date BETWEEN b.Start_Date AND b.End_Date)
        OR (NEW.End_Date BETWEEN b.Start_Date AND b.End_Date)
        OR (b.Start_Date BETWEEN NEW.Start_Date AND NEW.End_Date)
    );
    
    -- Check for renting conflicts
    SELECT COUNT(*) INTO renting_conflict
    FROM Renting r
    JOIN Rented_By rb ON r.Renting_ID = rb.Renting_ID
    WHERE rb.Hotel_Address = NEW.Hotel_Address 
    AND rb.Room_Num = NEW.Room_Num
    AND (
        (NEW.Start_Date BETWEEN r.Start_Date AND r.End_Date)
        OR (NEW.End_Date BETWEEN r.Start_Date AND r.End_Date)
        OR (r.Start_Date BETWEEN NEW.Start_Date AND NEW.End_Date)
    );
    
    IF booking_conflict > 0 OR renting_conflict > 0 THEN
        RAISE EXCEPTION 'Room already booked or rented for the specified dates';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger to Reserved_By table to check availability before booking
CREATE TRIGGER check_booking_availability
BEFORE INSERT ON Reserved_By
FOR EACH ROW
EXECUTE FUNCTION check_room_availability();

-- Similar trigger for rentings
CREATE OR REPLACE FUNCTION check_renting_availability()
RETURNS TRIGGER AS $$
DECLARE
    booking_conflict INTEGER;
    renting_conflict INTEGER;
BEGIN
    -- Check for booking conflicts (except if it's the same booking being converted to renting)
    SELECT COUNT(*) INTO booking_conflict
    FROM Booking b
    JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
    WHERE rb.Hotel_Address = NEW.Hotel_Address 
    AND rb.Room_Num = NEW.Room_Num
    -- Allow conflicts with current booking being converted to rental
    AND NOT EXISTS (
        SELECT 1 FROM Renting r
        WHERE r.Renting_ID = NEW.Renting_ID
        AND r.Direct_Renting = FALSE
        AND r.Start_Date = b.Start_Date
        AND r.End_Date = b.End_Date
    )
    AND (
        (NEW.Start_Date BETWEEN b.Start_Date AND b.End_Date)
        OR (NEW.End_Date BETWEEN b.Start_Date AND b.End_Date)
        OR (b.Start_Date BETWEEN NEW.Start_Date AND NEW.End_Date)
    );
    
    -- Check for renting conflicts
    SELECT COUNT(*) INTO renting_conflict
    FROM Renting r
    JOIN Rented_By rb ON r.Renting_ID = rb.Renting_ID
    WHERE rb.Hotel_Address = NEW.Hotel_Address 
    AND rb.Room_Num = NEW.Room_Num
    AND rb.Renting_ID != NEW.Renting_ID
    AND (
        (NEW.Start_Date BETWEEN r.Start_Date AND r.End_Date)
        OR (NEW.End_Date BETWEEN r.Start_Date AND r.End_Date)
        OR (r.Start_Date BETWEEN NEW.Start_Date AND NEW.End_Date)
    );
    
    IF booking_conflict > 0 OR renting_conflict > 0 THEN
        RAISE EXCEPTION 'Room already booked or rented for the specified dates';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger to Rented_By table to check availability before renting
CREATE TRIGGER check_renting_availability
BEFORE INSERT ON Rented_By
FOR EACH ROW
EXECUTE FUNCTION check_renting_availability();

-- 3. Update hotel counts trigger 
-- Automatically updates Num_Hotels in Hotel_Chain
CREATE OR REPLACE FUNCTION update_hotel_chain_count()
RETURNS TRIGGER AS $$
BEGIN
    -- If a hotel is inserted
    IF TG_OP = 'INSERT' THEN
        UPDATE Hotel_Chain 
        SET Num_Hotels = Num_Hotels + 1 
        WHERE Chain_Name = NEW.Chain_Name;
    -- If a hotel is deleted
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE Hotel_Chain 
        SET Num_Hotels = Num_Hotels - 1 
        WHERE Chain_Name = OLD.Chain_Name;
    -- If a hotel chain is updated
    ELSIF TG_OP = 'UPDATE' AND OLD.Chain_Name != NEW.Chain_Name THEN
        UPDATE Hotel_Chain 
        SET Num_Hotels = Num_Hotels - 1 
        WHERE Chain_Name = OLD.Chain_Name;
        
        UPDATE Hotel_Chain 
        SET Num_Hotels = Num_Hotels + 1 
        WHERE Chain_Name = NEW.Chain_Name;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_hotel_count
AFTER INSERT OR DELETE OR UPDATE OF Chain_Name ON Hotel
FOR EACH ROW EXECUTE FUNCTION update_hotel_chain_count();

-- 4. Update room counts trigger
-- Automatically updates Num_Rooms in Hotel
CREATE OR REPLACE FUNCTION update_hotel_room_count()
RETURNS TRIGGER AS $$
BEGIN
    -- If a room is inserted
    IF TG_OP = 'INSERT' THEN
        UPDATE Hotel 
        SET Num_Rooms = Num_Rooms + 1 
        WHERE Hotel_Address = NEW.Hotel_Address;
    -- If a room is deleted
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE Hotel 
        SET Num_Rooms = Num_Rooms - 1 
        WHERE Hotel_Address = OLD.Hotel_Address;
    -- If a room's hotel changes
    ELSIF TG_OP = 'UPDATE' AND OLD.Hotel_Address != NEW.Hotel_Address THEN
        UPDATE Hotel 
        SET Num_Rooms = Num_Rooms - 1 
        WHERE Hotel_Address = OLD.Hotel_Address;
        
        UPDATE Hotel 
        SET Num_Rooms = Num_Rooms + 1 
        WHERE Hotel_Address = NEW.Hotel_Address;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_room_count
AFTER INSERT OR DELETE OR UPDATE OF Hotel_Address ON Room
FOR EACH ROW EXECUTE FUNCTION update_hotel_room_count();

-- 5. Archive booking trigger
-- Moves deleted bookings to the archive table
CREATE OR REPLACE FUNCTION archive_booking()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO Archived_Booking (
        Booking_ID,
        Creation_Date,
        Start_Date,
        End_Date,
        Customer_ID
    ) VALUES (
        OLD.Booking_ID,
        OLD.Creation_Date,
        OLD.Start_Date,
        OLD.End_Date,
        OLD.Customer_ID
    );
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER booking_archive_trigger
BEFORE DELETE ON Booking
FOR EACH ROW
EXECUTE FUNCTION archive_booking();

-- 6. Archive renting trigger
-- Moves deleted rentings to the archive table
CREATE OR REPLACE FUNCTION archive_renting()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO Archived_Renting (
        Renting_ID,
        Start_Date,
        End_Date,
        Check_in_Date,
        Customer_ID
    ) VALUES (
        OLD.Renting_ID,
        OLD.Start_Date,
        OLD.End_Date,
        OLD.Check_in_Date,
        OLD.Customer_ID
    );
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER renting_archive_trigger
BEFORE DELETE ON Renting
FOR EACH ROW
EXECUTE FUNCTION archive_renting();

-- Triggers for Archiving Data

-- Trigger 1: Archive Booking before deletion
-- Copies the Booking row to Archived_Booking before it's deleted.
DROP TRIGGER IF EXISTS before_booking_delete;
DELIMITER //
CREATE TRIGGER before_booking_delete
BEFORE DELETE ON Booking
FOR EACH ROW
BEGIN
    INSERT INTO Archived_Booking (Booking_ID, Creation_Date, Start_Date, End_Date, Customer_ID)
    VALUES (OLD.Booking_ID, OLD.Creation_Date, OLD.Start_Date, OLD.End_Date, OLD.Customer_ID);
END //
DELIMITER ;

-- Trigger 2: Archive Renting before deletion
-- Copies the Renting row to Archived_Renting before it's deleted.
DROP TRIGGER IF EXISTS before_renting_delete;
DELIMITER //
CREATE TRIGGER before_renting_delete
BEFORE DELETE ON Renting
FOR EACH ROW
BEGIN
    INSERT INTO Archived_Renting (Renting_ID, Start_Date, End_Date, Check_in_Date, Direct_Renting, Customer_ID)
    VALUES (OLD.Renting_ID, OLD.Start_Date, OLD.End_Date, OLD.Check_in_Date, OLD.Direct_Renting, OLD.Customer_ID);
END //
DELIMITER ;

SELECT 'Archiving triggers created.' AS Status; 