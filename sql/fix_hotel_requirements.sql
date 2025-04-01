-- First, let's add more hotels to Wyndham to ensure 3 star categories
INSERT INTO Hotel (Hotel_Address, Chain_Name, Star_Rating, Manager_SSN, Area) VALUES
('2000 Peel Street, Montreal, QC H3A 2W5', 'Wyndham Hotels & Resorts', 5, '123456789', 'Montreal'),
('1500 Stanley Street, Montreal, QC H3A 1P8', 'Wyndham Hotels & Resorts', 5, '123456789', 'Montreal');

-- Add rooms for the new Wyndham hotels
INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability) VALUES
('2000 Peel Street, Montreal, QC H3A 2W5', 101, 2, 'TV,AC,Fridge', 'City', 299.99, 1, 1),
('2000 Peel Street, Montreal, QC H3A 2W5', 102, 4, 'TV,AC,Fridge,Kitchen', 'City', 399.99, 1, 1),
('2000 Peel Street, Montreal, QC H3A 2W5', 103, 2, 'TV,AC,Fridge', 'City', 199.99, 0, 1),
('2000 Peel Street, Montreal, QC H3A 2W5', 104, 6, 'TV,AC,Fridge,Kitchen,Jacuzzi', 'City', 599.99, 1, 1),
('2000 Peel Street, Montreal, QC H3A 2W5', 105, 2, 'TV,AC,Fridge', 'City', 249.99, 1, 1),

('1500 Stanley Street, Montreal, QC H3A 1P8', 101, 2, 'TV,AC,Fridge', 'City', 299.99, 1, 1),
('1500 Stanley Street, Montreal, QC H3A 1P8', 102, 4, 'TV,AC,Fridge,Kitchen', 'City', 399.99, 1, 1),
('1500 Stanley Street, Montreal, QC H3A 1P8', 103, 2, 'TV,AC,Fridge', 'City', 199.99, 0, 1),
('1500 Stanley Street, Montreal, QC H3A 1P8', 104, 6, 'TV,AC,Fridge,Kitchen,Jacuzzi', 'City', 599.99, 1, 1),
('1500 Stanley Street, Montreal, QC H3A 1P8', 105, 2, 'TV,AC,Fridge', 'City', 249.99, 1, 1);

-- Add contact information for new hotels
INSERT INTO Hotel_Phone (Hotel_Address, Phone_Num) VALUES
('2000 Peel Street, Montreal, QC H3A 2W5', '+1-514-555-0131'),
('1500 Stanley Street, Montreal, QC H3A 1P8', '+1-514-555-0132');

INSERT INTO Hotel_Email (Hotel_Address, Contact_Email) VALUES
('2000 Peel Street, Montreal, QC H3A 2W5', 'montreal.peel@wyndham.com'),
('1500 Stanley Street, Montreal, QC H3A 1P8', 'montreal.stanley@wyndham.com');

-- Update Number_of_Hotels for Wyndham
UPDATE Hotel_Chain 
SET Number_of_Hotels = (
    SELECT COUNT(*) 
    FROM Hotel 
    WHERE Chain_Name = 'Wyndham Hotels & Resorts'
)
WHERE Chain_Name = 'Wyndham Hotels & Resorts'; 