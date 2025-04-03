-- e-Hotels Database Queries
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705)

-- Connect to the database
\c ehotels;

-- Query 1: Room availability search with multiple criteria (location, dates, capacity, price range)
-- This is the core query for the booking system - find available rooms based on criteria
SELECT 
    h.Hotel_Address,
    h.Chain_Name,
    h.Star_Rating,
    h.Area,
    r.Room_Num,
    r.Capacity,
    r.View_Type,
    r.Price,
    r.Amenities,
    r.Extendable
FROM 
    Room r
JOIN 
    Hotel h ON r.Hotel_Address = h.Hotel_Address
WHERE 
    h.Area = 'Toronto' -- Area parameter
    AND r.Capacity >= 2 -- Capacity parameter
    AND r.Price BETWEEN 200 AND 400 -- Price range parameter
    AND h.Star_Rating >= 4 -- Star rating parameter
    AND r.Availability = TRUE
    -- Check that the room is not booked for the date range
    AND NOT EXISTS (
        SELECT 1
        FROM Booking b
        JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
        WHERE rb.Hotel_Address = r.Hotel_Address
            AND rb.Room_Num = r.Room_Num
            AND (
                -- Check for overlapping date ranges
                (b.Start_Date <= '2024-06-15' AND b.End_Date >= '2024-06-10')
            )
    )
    -- Check that the room is not rented for the date range
    AND NOT EXISTS (
        SELECT 1
        FROM Renting rnt
        JOIN Rented_By rb ON rnt.Renting_ID = rb.Renting_ID
        WHERE rb.Hotel_Address = r.Hotel_Address
            AND rb.Room_Num = r.Room_Num
            AND (
                -- Check for overlapping date ranges
                (rnt.Start_Date <= '2024-06-15' AND rnt.End_Date >= '2024-06-10')
            )
    )
ORDER BY 
    h.Star_Rating DESC, r.Price ASC;

-- Query 2: Aggregation query - Average price and count of rooms per hotel chain and star rating
-- This helps understand pricing across different chains and categories
SELECT 
    hc.Chain_Name,
    h.Star_Rating,
    COUNT(r.Room_Num) AS Total_Rooms,
    ROUND(AVG(r.Price), 2) AS Average_Price,
    MIN(r.Price) AS Min_Price,
    MAX(r.Price) AS Max_Price
FROM 
    Hotel_Chain hc
JOIN 
    Hotel h ON hc.Chain_Name = h.Chain_Name
JOIN 
    Room r ON h.Hotel_Address = r.Hotel_Address
GROUP BY 
    hc.Chain_Name, h.Star_Rating
ORDER BY 
    hc.Chain_Name, h.Star_Rating DESC;

-- Query 3: Nested query - Find customers who have bookings in multiple hotel chains
-- This helps identify high-value customers who use multiple chains
SELECT 
    c.Customer_ID,
    c.Full_Name,
    COUNT(DISTINCT h.Chain_Name) AS Number_Of_Chains_Booked
FROM 
    Customer c
JOIN 
    Booking b ON c.Customer_ID = b.Customer_ID
JOIN 
    Reserved_By rb ON b.Booking_ID = rb.Booking_ID
JOIN 
    Hotel h ON rb.Hotel_Address = h.Hotel_Address
GROUP BY 
    c.Customer_ID, c.Full_Name
HAVING 
    COUNT(DISTINCT h.Chain_Name) > 1
ORDER BY 
    Number_Of_Chains_Booked DESC, c.Full_Name;

-- Query 4: Hotel chain performance analysis - Bookings and rentings per chain
-- This helps analyze which chains are performing best
SELECT 
    hc.Chain_Name,
    COUNT(DISTINCT h.Hotel_Address) AS Number_Of_Hotels,
    SUM(h.Num_Rooms) AS Total_Rooms,
    COUNT(DISTINCT b.Booking_ID) AS Total_Bookings,
    COUNT(DISTINCT r.Renting_ID) AS Total_Rentings,
    ROUND(
        COUNT(DISTINCT b.Booking_ID)::NUMERIC / NULLIF(SUM(h.Num_Rooms), 0), 
        2
    ) AS Bookings_Per_Room,
    ROUND(
        COUNT(DISTINCT r.Renting_ID)::NUMERIC / NULLIF(SUM(h.Num_Rooms), 0), 
        2
    ) AS Rentings_Per_Room
FROM 
    Hotel_Chain hc
LEFT JOIN 
    Hotel h ON hc.Chain_Name = h.Chain_Name
LEFT JOIN 
    Reserved_By rb ON h.Hotel_Address = rb.Hotel_Address
LEFT JOIN 
    Booking b ON rb.Booking_ID = b.Booking_ID
LEFT JOIN 
    Rented_By rntb ON h.Hotel_Address = rntb.Hotel_Address
LEFT JOIN 
    Renting r ON rntb.Renting_ID = r.Renting_ID
GROUP BY 
    hc.Chain_Name
ORDER BY 
    Total_Bookings DESC, Total_Rentings DESC; 