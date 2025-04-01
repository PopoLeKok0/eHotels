-- First, clear any existing problematic data
DELETE FROM Reserved_By;
DELETE FROM Rented_By;

-- Insert corrected booking references
INSERT INTO Reserved_By (Booking_ID, Hotel_Address, Room_Num) VALUES
('book-001', '100 Front Street West, Toronto, ON M5J 1E3', 101),
('book-002', '225 Front Street West, Toronto, ON M5V 2X3', 201),
('book-003', '475 Howe Street, Vancouver, BC V6C 2B3', 301);

-- Insert corrected renting references
INSERT INTO Rented_By (Renting_ID, Hotel_Address, Room_Num) VALUES
('rent-001', '100 Front Street West, Toronto, ON M5J 1E3', 102),
('rent-002', '225 Front Street West, Toronto, ON M5V 2X3', 202),
('rent-003', '475 Howe Street, Vancouver, BC V6C 2B3', 302); 