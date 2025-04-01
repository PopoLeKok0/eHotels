-- e-Hotels Database Indexes
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705)

-- Connect to the database
\c ehotels;

-- Index 1: Room Search Optimization
-- Justification: This index significantly improves performance for the most common operation:
-- searching for available rooms by area, capacity, price, and star rating.
-- The index covers the most frequently used WHERE conditions in room search queries.
-- Since hotel searches are very frequent and filtering by these criteria is common,
-- this index will provide a substantial performance improvement.

-- First, explain the query without the index
EXPLAIN ANALYZE
SELECT h.Hotel_Address, r.Room_Num, r.Capacity, r.Price
FROM Room r
JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
WHERE h.Area = 'Toronto'
  AND r.Capacity >= 2
  AND r.Price BETWEEN 200 AND 400
  AND h.Star_Rating >= 4
  AND r.Availability = TRUE;

-- Create a composite index on Room and Hotel for room search optimization
CREATE INDEX idx_room_search ON Room (Hotel_Address, Capacity, Price, Availability);
CREATE INDEX idx_hotel_search ON Hotel (Area, Star_Rating);

-- Rerun the query with the index and observe the performance difference
EXPLAIN ANALYZE
SELECT h.Hotel_Address, r.Room_Num, r.Capacity, r.Price
FROM Room r
JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
WHERE h.Area = 'Toronto'
  AND r.Capacity >= 2
  AND r.Price BETWEEN 200 AND 400
  AND h.Star_Rating >= 4
  AND r.Availability = TRUE;

-- Index 2: Booking Date Range Optimization
-- Justification: This index improves performance for checking room availability
-- during date range searches, which is critical for the booking system.
-- Date range queries are intensive and happen frequently.
-- This index speeds up the detection of overlapping bookings when searching
-- for available rooms for specific dates.

-- First, explain a date availability query without the index
EXPLAIN ANALYZE
SELECT b.Booking_ID, rb.Hotel_Address, rb.Room_Num
FROM Booking b
JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
WHERE b.Start_Date <= '2024-06-15' 
  AND b.End_Date >= '2024-06-10'
  AND rb.Hotel_Address = '100 Front Street West, Toronto, ON M5J 1E3';

-- Create index on booking dates
CREATE INDEX idx_booking_dates ON Booking (Start_Date, End_Date);
CREATE INDEX idx_reserved_by_location ON Reserved_By (Hotel_Address, Room_Num);

-- Rerun the query with the index
EXPLAIN ANALYZE
SELECT b.Booking_ID, rb.Hotel_Address, rb.Room_Num
FROM Booking b
JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
WHERE b.Start_Date <= '2024-06-15' 
  AND b.End_Date >= '2024-06-10'
  AND rb.Hotel_Address = '100 Front Street West, Toronto, ON M5J 1E3';

-- Index 3: Customer Lookup Optimization
-- Justification: This index improves performance for customer-related operations,
-- which are frequent in the booking and management interfaces.
-- Customer lookups by name are common when hotel staff search for a customer's bookings,
-- when customers log in, or when reports are generated about customer activity.

-- First, explain a customer lookup query without the index
EXPLAIN ANALYZE
SELECT Customer_ID, Full_Name, Address, ID_Type
FROM Customer
WHERE Full_Name LIKE 'Alice%';

-- Create index on customer name
CREATE INDEX idx_customer_name ON Customer (Full_Name);

-- Rerun the query with the index
EXPLAIN ANALYZE
SELECT Customer_ID, Full_Name, Address, ID_Type
FROM Customer
WHERE Full_Name LIKE 'Alice%';

-- Bonus Index: Employee-Hotel Relationship Optimization
-- Justification: While not one of the three required indexes, this additional index
-- improves performance for employee management functions, particularly for reports
-- on which employees work at which hotels, manager verification, etc.

CREATE INDEX idx_employee_hotel ON Works_At (Hotel_Address, SSN);

-- Additional Notes on Index Selection:
--
-- 1. We prioritized indexes on the most frequently queried tables and conditions
--    based on typical hotel booking system usage patterns.
--
-- 2. We avoided over-indexing, as indexes increase database size and slow down
--    write operations (inserts/updates/deletes).
--
-- 3. We created composite indexes where appropriate to maximize query optimization.
--
-- 4. The indexes are aligned with the most common queries in the application,
--    especially room searching, availability checking, and customer management.

-- Database Indexes for Performance Optimization

-- Index 1: Customer Email for Login
-- Justification: Speeds up user lookup by email during login, a frequent operation.
-- Note: MySQL typically creates an index for UNIQUE constraints automatically,
-- but explicitly defining it ensures it exists and clarifies intent.
CREATE INDEX idx_customer_email ON Customer(Email_Address);


-- Index 2: Customer Bookings by Date
-- Justification: Improves performance for fetching and sorting bookings 
-- for a specific customer (e.g., in 'My Bookings'), ordered by start date.
CREATE INDEX idx_booking_customer_date ON Booking(Customer_ID, Start_Date);


-- Index 3: Room Search Filters
-- Justification: Speeds up common room search queries involving filtering by 
-- hotel (address), capacity, price range, and availability.
CREATE INDEX idx_room_availability_filters ON Room(Hotel_Address, Capacity, Price, Availability);


-- Optional Index 4: Employee Email for Login (if used frequently)
-- CREATE INDEX idx_employee_email ON Employee(Email_Address);

-- Optional Index 5: Renting Lookups
-- CREATE INDEX idx_renting_customer_date ON Renting(Customer_ID, Start_Date);


SELECT 'Database indexes created.' AS Status; 