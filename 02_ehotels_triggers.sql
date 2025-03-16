-- Trigger to ensure a manager works at the hotel they manage
CREATE OR REPLACE FUNCTION check_manager_works_at_hotel()
RETURNS TRIGGER AS $$
BEGIN
    -- Check if the manager works at the hotel they're supposed to manage
    IF NOT EXISTS (
        SELECT 1 FROM Works_At 
        WHERE SSN = NEW.Manager_SSN AND Hotel_Address = NEW.Hotel_Address
    ) THEN
        -- If not, automatically add them to Works_At
        INSERT INTO Works_At (SSN, Hotel_Address) 
        VALUES (NEW.Manager_SSN, NEW.Hotel_Address);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER ensure_manager_works_at_hotel
BEFORE INSERT OR UPDATE ON Hotel
FOR EACH ROW
EXECUTE FUNCTION check_manager_works_at_hotel();

-- Trigger to prevent booking a room that's already booked/rented for overlapping dates
CREATE OR REPLACE FUNCTION check_room_booking_availability()
RETURNS TRIGGER AS $$
DECLARE
    booking_start_date DATE;
    booking_end_date DATE;
    check_room_hotel_address VARCHAR(100);
    check_room_num INT;
BEGIN
    -- Get the booking dates
    SELECT b.Start_Date, b.End_Date INTO booking_start_date, booking_end_date
    FROM Booking b
    WHERE b.Booking_ID = NEW.Booking_ID;
    
    check_room_hotel_address := NEW.Hotel_Address;
    check_room_num := NEW.Room_Num;
    
    -- Check if room is already booked for overlapping dates
    IF EXISTS (
        SELECT 1 
        FROM Reserved_By rb
        JOIN Booking b ON rb.Booking_ID = b.Booking_ID
        WHERE rb.Hotel_Address = check_room_hotel_address
        AND rb.Room_Num = check_room_num
        AND b.End_Date > booking_start_date
        AND b.Start_Date < booking_end_date
        AND b.Booking_ID != NEW.Booking_ID
    ) THEN
        RAISE EXCEPTION 'Room is already booked for the requested dates';
    END IF;
    
    -- Check if room is already rented for overlapping dates
    IF EXISTS (
        SELECT 1 
        FROM Rented_By rb
        JOIN Renting r ON rb.Renting_ID = r.Renting_ID
        WHERE rb.Hotel_Address = check_room_hotel_address
        AND rb.Room_Num = check_room_num
        AND r.End_Date > booking_start_date
        AND r.Start_Date < booking_end_date
    ) THEN
        RAISE EXCEPTION 'Room is already rented for the requested dates';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_room_booking_availability_trigger
BEFORE INSERT ON Reserved_By
FOR EACH ROW
EXECUTE FUNCTION check_room_booking_availability();

-- Trigger to prevent renting a room that's already booked/rented for overlapping dates
CREATE OR REPLACE FUNCTION check_room_renting_availability()
RETURNS TRIGGER AS $$
DECLARE
    renting_start_date DATE;
    renting_end_date DATE;
    check_room_hotel_address VARCHAR(100);
    check_room_num INT;
BEGIN
    -- Get the renting dates
    SELECT r.Start_Date, r.End_Date INTO renting_start_date, renting_end_date
    FROM Renting r
    WHERE r.Renting_ID = NEW.Renting_ID;
    
    check_room_hotel_address := NEW.Hotel_Address;
    check_room_num := NEW.Room_Num;
    
    -- Check if room is already booked for overlapping dates
    IF EXISTS (
        SELECT 1 
        FROM Reserved_By rb
        JOIN Booking b ON rb.Booking_ID = b.Booking_ID
        WHERE rb.Hotel_Address = check_room_hotel_address
        AND rb.Room_Num = check_room_num
        AND b.End_Date > renting_start_date
        AND b.Start_Date < renting_end_date
    ) THEN
        RAISE EXCEPTION 'Room is already booked for the requested dates';
    END IF;
    
    -- Check if room is already rented for overlapping dates
    IF EXISTS (
        SELECT 1 
        FROM Rented_By rb
        JOIN Renting r ON rb.Renting_ID = r.Renting_ID
        WHERE rb.Hotel_Address = check_room_hotel_address
        AND rb.Room_Num = check_room_num
        AND r.End_Date > renting_start_date
        AND r.Start_Date < renting_end_date
        AND r.Renting_ID != NEW.Renting_ID
    ) THEN
        RAISE EXCEPTION 'Room is already rented for the requested dates';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_room_renting_availability_trigger
BEFORE INSERT ON Rented_By
FOR EACH ROW
EXECUTE FUNCTION check_room_renting_availability();

-- Trigger to update Num_Rooms in Hotel table when rooms are added/deleted
CREATE OR REPLACE FUNCTION update_hotel_room_count()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE Hotel
        SET Num_Rooms = Num_Rooms + 1
        WHERE Hotel_Address = NEW.Hotel_Address;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE Hotel
        SET Num_Rooms = Num_Rooms - 1
        WHERE Hotel_Address = OLD.Hotel_Address;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER maintain_hotel_room_count
AFTER INSERT OR DELETE ON Room
FOR EACH ROW
EXECUTE FUNCTION update_hotel_room_count();

-- Trigger to update Num_Hotels in Hotel_Chain when hotels are added/deleted
CREATE OR REPLACE FUNCTION update_chain_hotel_count()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE Hotel_Chain
        SET Num_Hotels = Num_Hotels + 1
        WHERE Chain_Name = NEW.Chain_Name;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE Hotel_Chain
        SET Num_Hotels = Num_Hotels - 1
        WHERE Chain_Name = OLD.Chain_Name;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER maintain_chain_hotel_count
AFTER INSERT OR DELETE ON Hotel
FOR EACH ROW
EXECUTE FUNCTION update_chain_hotel_count();

-- Trigger to archive bookings when they are converted to rentings
CREATE OR REPLACE FUNCTION archive_booking()
RETURNS TRIGGER AS $$
BEGIN
    -- Only execute for non-direct rentings (converted from bookings)
    IF NEW.Direct_Renting = FALSE THEN
        -- Find the booking that corresponds to this renting and archive it
        WITH booking_details AS (
            SELECT b.Booking_ID, b.Creation_Date, b.Start_Date, b.End_Date, 
                   b.Customer_ID, rb.Hotel_Address, rb.Room_Num
            FROM Booking b
            JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            WHERE b.Customer_ID = NEW.Customer_ID
            AND b.Start_Date = NEW.Start_Date
            AND b.End_Date = NEW.End_Date
            LIMIT 1
        )
        INSERT INTO Archived_Booking
        SELECT * FROM booking_details;
        
        -- Delete the original booking from the booking tables
        DELETE FROM Booking 
        WHERE Booking_ID IN (
            SELECT b.Booking_ID
            FROM Booking b
            JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            WHERE b.Customer_ID = NEW.Customer_ID
            AND b.Start