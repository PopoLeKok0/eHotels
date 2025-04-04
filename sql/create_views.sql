-- View 1: Number of available rooms per area
-- A room is considered available if it's not currently booked or rented.
DROP VIEW IF EXISTS AvailableRoomsPerArea;
CREATE VIEW AvailableRoomsPerArea AS
SELECT
    h.Area,
    COUNT(r.Room_Num) AS NumberOfAvailableRooms -- Assuming Room PK is Room_Num based on other queries
FROM
    Hotel h
JOIN
    Room r ON h.Hotel_Address = r.Hotel_Address
LEFT JOIN (
    -- Subquery to find rooms currently booked
    SELECT DISTINCT rb.Hotel_Address, rb.Room_Num
    FROM Booking b
    JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
    WHERE CURDATE() < b.End_Date AND CURDATE() >= b.Start_Date -- Check for overlap with today
) AS booked_rooms ON r.Hotel_Address = booked_rooms.Hotel_Address AND r.Room_Num = booked_rooms.Room_Num
LEFT JOIN (
    -- Subquery to find rooms currently rented
    SELECT DISTINCT rent_by.Hotel_Address, rent_by.Room_Num
    FROM Renting rent
    JOIN Rented_By rent_by ON rent.Renting_ID = rent_by.Renting_ID
    WHERE CURDATE() < rent.End_Date AND CURDATE() >= rent.Start_Date -- Corrected column names
) AS rented_rooms ON r.Hotel_Address = rented_rooms.Hotel_Address AND r.Room_Num = rented_rooms.Room_Num
WHERE
    booked_rooms.Room_Num IS NULL AND rented_rooms.Room_Num IS NULL
GROUP BY
    h.Area;

-- View 2: Aggregated capacity of all the rooms of a specific hotel
DROP VIEW IF EXISTS HotelRoomCapacity;
CREATE VIEW HotelRoomCapacity AS
SELECT
    Hotel_Address,
    SUM(Capacity) AS TotalCapacity
FROM
    Room
GROUP BY
    Hotel_Address;

-- View 3: Aggregated Hotel Capacity
-- Shows the total capacity of all rooms combined for each hotel.
CREATE OR REPLACE VIEW aggregated_hotel_capacity AS
SELECT
    h.Hotel_Address,
    h.Chain_Name,
    h.Area,
    SUM(r.Capacity) AS Total_Capacity
FROM Hotel h
JOIN Room r ON h.Hotel_Address = r.Hotel_Address
GROUP BY h.Hotel_Address, h.Chain_Name, h.Area
ORDER BY h.Chain_Name, h.Area, h.Hotel_Address;
