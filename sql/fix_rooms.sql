-- First, delete dependent booking/renting records
DELETE FROM Reserved_By;
DELETE FROM Rented_By;

-- Then, delete ALL existing rooms to ensure a clean slate
DELETE FROM Room;

-- Add rooms to all hotels with proper variations using CORRECT ENUM values
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 101, 2, 'WiFi, TV, Mini Bar', 'City', 150.00, 1, 1
FROM Hotel;

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 102, 2, 'WiFi, TV, Mini Bar', 'City', 150.00, 1, 1
FROM Hotel;

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 201, 3, 'WiFi, TV, Mini Bar, Balcony', 'City', 200.00, 1, 1
FROM Hotel;

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 202, 4, 'WiFi, TV, Mini Bar, Balcony, Kitchen', 'City', 250.00, 1, 1
FROM Hotel;

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 301, 2, 'WiFi, TV, Mini Bar', 'Sea', 300.00, 1, 1 -- Mapped 'Ocean View' to 'Sea'
FROM Hotel;

-- Add more rooms for hotels with star rating >= 4
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 302, 2, 'WiFi, TV, Mini Bar, Jacuzzi', 'Sea', 350.00, 1, 1
FROM Hotel
WHERE Star_Rating >= 4;

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 303, 2, 'WiFi, TV, Mini Bar, Jacuzzi, Kitchen', 'Sea', 400.00, 1, 1
FROM Hotel
WHERE Star_Rating >= 4;

-- Add luxury suites for 5-star hotels
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 401, 4, 'WiFi, TV, Mini Bar, Jacuzzi, Kitchen, Living Room', 'Sea', 500.00, 1, 1
FROM Hotel
WHERE Star_Rating = 5;

-- Add mountain view rooms for hotels in Vancouver
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 402, 2, 'WiFi, TV, Mini Bar', 'Mountain', 350.00, 1, 1
FROM Hotel
WHERE Area = 'Vancouver' AND Star_Rating >= 4;

-- Add garden view rooms for hotels in Toronto
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 402, 2, 'WiFi, TV, Mini Bar', 'Garden', 350.00, 1, 1
FROM Hotel
WHERE Area = 'Toronto' AND Star_Rating >= 4;

-- Add pool view rooms for hotels in Montreal
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 402, 2, 'WiFi, TV, Mini Bar', 'Pool', 350.00, 1, 1
FROM Hotel
WHERE Area = 'Montreal' AND Star_Rating >= 4; 