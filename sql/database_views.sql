-- e-Hotels Database Views
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705)

-- Connect to the database
\c ehotels;

-- View 1: Number of available rooms per area
-- This view counts rooms marked as available and not currently booked/rented for today.
-- NOTE: This doesn't check future availability, just current status.
CREATE OR REPLACE VIEW available_rooms_per_area AS
SELECT 
    h.Area,
    COUNT(r.Room_Num) AS Total_Available_Rooms
FROM Room r
JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
WHERE r.Availability = TRUE
  AND NOT EXISTS (
      -- Check active bookings today
      SELECT 1 FROM Booking b JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
      WHERE rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
        AND b.Start_Date <= CURDATE() AND b.End_Date > CURDATE()
  )
  AND NOT EXISTS (
      -- Check active rentings today
      SELECT 1 FROM Renting rnt JOIN Rented_By rntb ON rnt.Renting_ID = rntb.Renting_ID
      WHERE rntb.Hotel_Address = r.Hotel_Address AND rntb.Room_Num = r.Room_Num
        AND rnt.Start_Date <= CURDATE() AND rnt.End_Date > CURDATE()
  )
GROUP BY h.Area;

-- Test View 1
SELECT * FROM available_rooms_per_area;

-- View 2: Aggregated capacity of all rooms of a specific hotel
CREATE OR REPLACE VIEW aggregated_hotel_capacity AS
SELECT 
    h.Hotel_Address,
    h.Chain_Name,
    SUM(r.Capacity) AS Total_Capacity
FROM Room r
JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
GROUP BY h.Hotel_Address, h.Chain_Name;

-- Test View 2
SELECT * FROM aggregated_hotel_capacity;

-- Additional View 3: Hotel booking statistics (OPTIONAL)
-- This view provides booking and occupancy statistics for each hotel,
-- which is useful for management reports and performance analysis.
CREATE OR REPLACE VIEW hotel_booking_statistics AS
SELECT 
    h.Hotel_Address,
    h.Chain_Name,
    h.Star_Rating,
    h.Area,
    COUNT(DISTINCT b.Booking_ID) AS Total_Bookings,
    COUNT(DISTINCT r.Renting_ID) AS Total_Rentings,
    COUNT(DISTINCT CASE WHEN r.Direct_Renting = TRUE THEN r.Renting_ID ELSE NULL END) AS Direct_Rentings,
    COUNT(DISTINCT CASE WHEN r.Direct_Renting = FALSE THEN r.Renting_ID ELSE NULL END) AS Bookings_To_Rentings,
    ROUND(
        COUNT(DISTINCT r.Renting_ID)::NUMERIC / NULLIF(COUNT(DISTINCT b.Booking_ID), 0) * 100, 
        2
    ) AS Booking_Conversion_Rate
FROM 
    Hotel h
LEFT JOIN 
    Reserved_By rb ON h.Hotel_Address = rb.Hotel_Address
LEFT JOIN 
    Booking b ON rb.Booking_ID = b.Booking_ID
LEFT JOIN 
    Rented_By rntb ON h.Hotel_Address = rntb.Hotel_Address
LEFT JOIN 
    Renting r ON rntb.Renting_ID = r.Renting_ID
GROUP BY 
    h.Hotel_Address, h.Chain_Name, h.Star_Rating, h.Area
ORDER BY 
    Total_Bookings DESC;

-- Test View 3
SELECT * FROM hotel_booking_statistics;

-- Additional View 4: Customer booking history (OPTIONAL)
-- This view shows the booking history for customers, useful for the customer profile section
-- and for generating loyalty rewards or targeted promotions.
CREATE OR REPLACE VIEW customer_booking_history AS
SELECT 
    c.Customer_ID,
    c.Full_Name,
    COUNT(DISTINCT b.Booking_ID) AS Total_Bookings,
    COUNT(DISTINCT r.Renting_ID) AS Total_Rentings,
    COUNT(DISTINCT h.Hotel_Address) AS Different_Hotels_Visited,
    COUNT(DISTINCT h.Chain_Name) AS Different_Chains_Used,
    MAX(b.End_Date) AS Last_Booking_End_Date,
    SUM(
        CASE 
            WHEN r.Renting_ID IS NOT NULL 
            THEN (r.End_Date - r.Start_Date) 
            ELSE 0 
        END
    ) AS Total_Nights_Stayed,
    ROUND(
        AVG(
            CASE 
                WHEN r.Renting_ID IS NOT NULL 
                THEN rm.Price * (r.End_Date - r.Start_Date) 
                ELSE NULL 
            END
        ), 
        2
    ) AS Average_Stay_Value
FROM 
    Customer c
LEFT JOIN 
    Booking b ON c.Customer_ID = b.Customer_ID
LEFT JOIN 
    Reserved_By rb ON b.Booking_ID = rb.Booking_ID
LEFT JOIN 
    Hotel h ON rb.Hotel_Address = h.Hotel_Address
LEFT JOIN 
    Renting r ON (c.Customer_ID = r.Customer_ID)
LEFT JOIN 
    Rented_By rntb ON (r.Renting_ID = rntb.Renting_ID)
LEFT JOIN 
    Room rm ON (rntb.Hotel_Address = rm.Hotel_Address AND rntb.Room_Num = rm.Room_Num)
GROUP BY 
    c.Customer_ID, c.Full_Name
ORDER BY 
    Total_Bookings DESC, Total_Rentings DESC;

-- Test View 4
SELECT * FROM customer_booking_history;

SELECT 'Database views created.' AS Status; 