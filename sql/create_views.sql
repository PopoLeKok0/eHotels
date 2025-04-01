-- View 1: Available Rooms Per Area
-- Shows the total number of rooms currently available in each hotel area.
CREATE OR REPLACE VIEW available_rooms_per_area AS
SELECT 
    h.Area, 
    COUNT(r.Room_Num) AS Total_Available_Rooms
FROM Hotel h
JOIN Room r ON h.Hotel_Address = r.Hotel_Address
WHERE r.Availability = 1 -- Assuming 1 means available
GROUP BY h.Area
ORDER BY h.Area;

-- View 2: Aggregated Hotel Capacity
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

-- Placeholder for the second view (we'll add it later)
-- CREATE OR REPLACE VIEW aggregated_hotel_capacity AS ... 