-- Index 1: On Room(Hotel_Address, Availability, Capacity, Price)
-- Justification: This index is designed to optimize the main room search functionality.
-- It covers filtering by Hotel (via Hotel_Address), basic availability,
-- capacity requirements, and price range, which are common search criteria.
DROP INDEX IF EXISTS idx_room_search ON Room;
CREATE INDEX idx_room_search ON Room (Hotel_Address, Availability, Capacity, Price);


-- Index 2: On Booking(Customer_ID, End_Date)
-- Justification: This index supports the "My Bookings" page for customers.
-- It allows quick lookup of a specific customer's bookings (using Customer_ID).
-- Including End_Date allows filtering or sorting by date efficiently, which is common for viewing current/upcoming/past bookings.
DROP INDEX IF EXISTS idx_booking_customer_date ON Booking;
CREATE INDEX idx_booking_customer_date ON Booking (Customer_ID, End_Date);


-- Index 3: On Processes(SSN)
-- Justification: Speeds up lookups related to employees processing bookings/rentings,
-- potentially useful for audit trails or employee-specific views.
DROP INDEX IF EXISTS idx_processes_ssn ON Processes;
CREATE INDEX idx_processes_ssn ON Processes (SSN);


-- Index 4: On Hotel(Area, Chain_Name, Star_Rating)
-- Justification: Supports filtering hotels based on common criteria in search forms.
-- Allows efficient lookup when users filter by area, chain, or star rating.
DROP INDEX IF EXISTS idx_hotel_filters ON Hotel;
CREATE INDEX idx_hotel_filters ON Hotel (Area, Chain_Name, Star_Rating); 