-- First, update the Number_of_Hotels for each chain
UPDATE Hotel_Chain SET Number_of_Hotels = 8 WHERE Chain_Name = 'AccorHotels';

-- Add more hotels for AccorHotels to meet requirements
INSERT INTO Hotel (Hotel_Address, Chain_Name, Star_Rating, Manager_SSN, Area) VALUES
('1234 Robson Street, Vancouver, BC V6E 1C2', 'AccorHotels', 5, '123456789', 'Vancouver'),
('5678 Granville Street, Vancouver, BC V6C 1V2', 'AccorHotels', 4, '123456789', 'Vancouver'),
('9012 Alberni Street, Vancouver, BC V6E 4A2', 'AccorHotels', 3, '123456789', 'Vancouver'),
('3456 West Georgia Street, Vancouver, BC V6B 2P9', 'AccorHotels', 5, '123456789', 'Vancouver'),
('7890 Burrard Street, Vancouver, BC V6Z 2H2', 'AccorHotels', 4, '123456789', 'Vancouver'),
('2345 Howe Street, Vancouver, BC V6Z 2L5', 'AccorHotels', 3, '123456789', 'Vancouver'),
('6789 Thurlow Street, Vancouver, BC V6E 3L9', 'AccorHotels', 5, '123456789', 'Vancouver'),
('0123 Hornby Street, Vancouver, BC V6Z 1W2', 'AccorHotels', 4, '123456789', 'Vancouver');

-- Add rooms for the new hotels
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability) VALUES
-- AccorHotels Vancouver hotels
('1234 Robson Street, Vancouver, BC V6E 1C2', 101, 2, 'TV,AC,Fridge', 'City', 299.99, 1, 1),
('1234 Robson Street, Vancouver, BC V6E 1C2', 102, 4, 'TV,AC,Fridge,Kitchen', 'Ocean', 399.99, 1, 1),
('1234 Robson Street, Vancouver, BC V6E 1C2', 103, 2, 'TV,AC,Fridge', 'City', 199.99, 0, 1),
('1234 Robson Street, Vancouver, BC V6E 1C2', 104, 6, 'TV,AC,Fridge,Kitchen,Jacuzzi', 'Ocean', 599.99, 1, 1),
('1234 Robson Street, Vancouver, BC V6E 1C2', 105, 2, 'TV,AC,Fridge', 'City', 249.99, 1, 1),

('5678 Granville Street, Vancouver, BC V6C 1V2', 101, 2, 'TV,AC,Fridge', 'City', 199.99, 1, 1),
('5678 Granville Street, Vancouver, BC V6C 1V2', 102, 4, 'TV,AC,Fridge,Kitchen', 'City', 299.99, 1, 1),
('5678 Granville Street, Vancouver, BC V6C 1V2', 103, 2, 'TV,AC,Fridge', 'City', 179.99, 0, 1),
('5678 Granville Street, Vancouver, BC V6C 1V2', 104, 4, 'TV,AC,Fridge,Kitchen', 'City', 279.99, 1, 1),
('5678 Granville Street, Vancouver, BC V6C 1V2', 105, 2, 'TV,AC,Fridge', 'City', 199.99, 1, 1);

-- Add hotel contact information
INSERT INTO Hotel_Phone (Hotel_Address, Phone_Num) VALUES
('1234 Robson Street, Vancouver, BC V6E 1C2', '+1-604-555-0123'),
('5678 Granville Street, Vancouver, BC V6C 1V2', '+1-604-555-0124'),
('9012 Alberni Street, Vancouver, BC V6E 4A2', '+1-604-555-0125'),
('3456 West Georgia Street, Vancouver, BC V6B 2P9', '+1-604-555-0126'),
('7890 Burrard Street, Vancouver, BC V6Z 2H2', '+1-604-555-0127'),
('2345 Howe Street, Vancouver, BC V6Z 2L5', '+1-604-555-0128'),
('6789 Thurlow Street, Vancouver, BC V6E 3L9', '+1-604-555-0129'),
('0123 Hornby Street, Vancouver, BC V6Z 1W2', '+1-604-555-0130');

INSERT INTO Hotel_Email (Hotel_Address, Contact_Email) VALUES
('1234 Robson Street, Vancouver, BC V6E 1C2', 'vancouver.robson@accor.com'),
('5678 Granville Street, Vancouver, BC V6C 1V2', 'vancouver.granville@accor.com'),
('9012 Alberni Street, Vancouver, BC V6E 4A2', 'vancouver.alberni@accor.com'),
('3456 West Georgia Street, Vancouver, BC V6B 2P9', 'vancouver.georgia@accor.com'),
('7890 Burrard Street, Vancouver, BC V6Z 2H2', 'vancouver.burrard@accor.com'),
('2345 Howe Street, Vancouver, BC V6Z 2L5', 'vancouver.howe@accor.com'),
('6789 Thurlow Street, Vancouver, BC V6E 3L9', 'vancouver.thurlow@accor.com'),
('0123 Hornby Street, Vancouver, BC V6Z 1W2', 'vancouver.hornby@accor.com'); 