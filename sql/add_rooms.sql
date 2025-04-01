-- Add rooms to all hotels that currently have none
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 101, 2, 'WiFi, TV, Mini Bar', 'City View', 150.00, 1, 1
FROM Hotel
WHERE Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 102, 2, 'WiFi, TV, Mini Bar', 'City View', 150.00, 1, 1
FROM Hotel
WHERE Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 201, 3, 'WiFi, TV, Mini Bar, Balcony', 'City View', 200.00, 1, 1
FROM Hotel
WHERE Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 202, 4, 'WiFi, TV, Mini Bar, Balcony, Kitchen', 'City View', 250.00, 1, 1
FROM Hotel
WHERE Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 301, 2, 'WiFi, TV, Mini Bar, Ocean View', 'Ocean View', 300.00, 1, 1
FROM Hotel
WHERE Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

-- Add more rooms for hotels with star rating >= 4
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 302, 2, 'WiFi, TV, Mini Bar, Ocean View, Jacuzzi', 'Ocean View', 350.00, 1, 1
FROM Hotel
WHERE Star_Rating >= 4
AND Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 303, 2, 'WiFi, TV, Mini Bar, Ocean View, Jacuzzi, Kitchen', 'Ocean View', 400.00, 1, 1
FROM Hotel
WHERE Star_Rating >= 4
AND Hotel_Address NOT IN (SELECT Hotel_Address FROM Room);

-- Add luxury suites for 5-star hotels
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability)
SELECT Hotel_Address, 401, 4, 'WiFi, TV, Mini Bar, Ocean View, Jacuzzi, Kitchen, Living Room', 'Ocean View', 500.00, 1, 1
FROM Hotel
WHERE Star_Rating = 5
AND Hotel_Address NOT IN (SELECT Hotel_Address FROM Room); 