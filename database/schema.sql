-- e-Hotels Database Schema
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)

-- Drop tables if they exist (for schema reset)
DROP TABLE IF EXISTS Booking;
DROP TABLE IF EXISTS Customer;
DROP TABLE IF EXISTS Room;
DROP TABLE IF EXISTS Employee;
DROP TABLE IF EXISTS Hotel;
DROP TABLE IF EXISTS HotelChain;

-- Create HotelChain table
CREATE TABLE HotelChain (
    chain_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    central_office_address VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone_number VARCHAR(20),
    number_of_hotels INT DEFAULT 0,
    website VARCHAR(255)
);

-- Create Hotel table
CREATE TABLE Hotel (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    chain_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    star_rating INT NOT NULL CHECK (star_rating BETWEEN 1 AND 5),
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state_province VARCHAR(100),
    country VARCHAR(100) NOT NULL,
    zip_postal_code VARCHAR(20),
    email VARCHAR(100),
    phone_number VARCHAR(20),
    manager_id INT,  -- Will be updated after Employee table is created
    number_of_rooms INT DEFAULT 0,
    FOREIGN KEY (chain_id) REFERENCES HotelChain(chain_id) ON DELETE CASCADE
);

-- Create Employee table
CREATE TABLE Employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(50) NOT NULL,
    department VARCHAR(50),
    email VARCHAR(100),
    phone_number VARCHAR(20),
    address VARCHAR(255),
    sin VARCHAR(9) NOT NULL UNIQUE,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),  -- Will store hashed passwords
    is_manager BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (hotel_id) REFERENCES Hotel(hotel_id) ON DELETE CASCADE
);

-- Update Hotel table to add manager foreign key
ALTER TABLE Hotel
ADD CONSTRAINT fk_hotel_manager
FOREIGN KEY (manager_id) REFERENCES Employee(employee_id);

-- Create Room table
CREATE TABLE Room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL,
    view_type VARCHAR(50),
    is_extendable BOOLEAN DEFAULT FALSE,
    amenities TEXT,
    damage_description TEXT,
    UNIQUE (hotel_id, room_number),
    FOREIGN KEY (hotel_id) REFERENCES Hotel(hotel_id) ON DELETE CASCADE
);

-- Create Customer table
CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    sin VARCHAR(9) NOT NULL UNIQUE,
    registered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Booking table
CREATE TABLE Booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    customer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    is_cancelled BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

-- Create some indexes for performance
CREATE INDEX idx_hotel_chain ON Hotel(chain_id);
CREATE INDEX idx_hotel_city ON Hotel(city);
CREATE INDEX idx_hotel_country ON Hotel(country);
CREATE INDEX idx_room_hotel ON Room(hotel_id);
CREATE INDEX idx_room_type ON Room(room_type);
CREATE INDEX idx_room_price ON Room(price);
CREATE INDEX idx_room_capacity ON Room(capacity);
CREATE INDEX idx_booking_dates ON Booking(start_date, end_date);
CREATE INDEX idx_booking_customer ON Booking(customer_id);
CREATE INDEX idx_booking_room ON Booking(room_id);

-- Create trigger to update hotel room count
DELIMITER //
CREATE TRIGGER after_room_insert
AFTER INSERT ON Room
FOR EACH ROW
BEGIN
    UPDATE Hotel
    SET number_of_rooms = number_of_rooms + 1
    WHERE hotel_id = NEW.hotel_id;
END//

CREATE TRIGGER after_room_delete
AFTER DELETE ON Room
FOR EACH ROW
BEGIN
    UPDATE Hotel
    SET number_of_rooms = number_of_rooms - 1
    WHERE hotel_id = OLD.hotel_id;
END//
DELIMITER ;

-- Create trigger to update hotel chain hotel count
DELIMITER //
CREATE TRIGGER after_hotel_insert
AFTER INSERT ON Hotel
FOR EACH ROW
BEGIN
    UPDATE HotelChain
    SET number_of_hotels = number_of_hotels + 1
    WHERE chain_id = NEW.chain_id;
END//

CREATE TRIGGER after_hotel_delete
AFTER DELETE ON Hotel
FOR EACH ROW
BEGIN
    UPDATE HotelChain
    SET number_of_hotels = number_of_hotels - 1
    WHERE chain_id = OLD.chain_id;
END//
DELIMITER ;

-- Sample data for testing
-- Hotel chains
INSERT INTO HotelChain (name, central_office_address, email, phone_number, website)
VALUES
('Marriott International', '10400 Fernwood Road, Bethesda, MD, USA', 'info@marriott.com', '1-301-380-3000', 'www.marriott.com'),
('Hilton Worldwide', '7930 Jones Branch Drive, McLean, VA, USA', 'info@hilton.com', '1-703-883-1000', 'www.hilton.com'),
('InterContinental Hotels Group', '3 Ravinia Drive, Atlanta, GA, USA', 'info@ihg.com', '1-770-604-2000', 'www.ihg.com'),
('AccorHotels', '82 rue Henri Farman, 92130 Issy-les-Moulineaux, France', 'info@accor.com', '33-1-45-38-86-00', 'www.accorhotels.com'),
('Wyndham Hotels & Resorts', '22 Sylvan Way, Parsippany, NJ, USA', 'info@wyndham.com', '1-973-753-6000', 'www.wyndhamhotels.com');

-- Hotels (a few per chain)
-- Marriott hotels
INSERT INTO Hotel (chain_id, name, star_rating, address, city, state_province, country, zip_postal_code, email, phone_number, number_of_rooms)
VALUES
(1, 'JW Marriott Essex House New York', 5, '160 Central Park S', 'New York', 'NY', 'USA', '10019', 'essexhouse@marriott.com', '1-212-247-0300', 0),
(1, 'Marriott Marquis Toronto', 4, '123 Queen Street', 'Toronto', 'ON', 'Canada', 'M5V 2Z3', 'torontomarquis@marriott.com', '1-416-555-1234', 0),
(1, 'Marriott Vancouver Waterfront', 4, '1128 W Hastings St', 'Vancouver', 'BC', 'Canada', 'V6E 4R5', 'vancouverwaterfront@marriott.com', '1-604-555-9876', 0);

-- Hilton hotels
INSERT INTO Hotel (chain_id, name, star_rating, address, city, state_province, country, zip_postal_code, email, phone_number, number_of_rooms)
VALUES
(2, 'Hilton New York Midtown', 4, '1335 Avenue of the Americas', 'New York', 'NY', 'USA', '10019', 'nymidtown@hilton.com', '1-212-586-7000', 0),
(2, 'Hilton Toronto', 4, '145 Richmond St W', 'Toronto', 'ON', 'Canada', 'M5H 2L2', 'toronto@hilton.com', '1-416-869-3456', 0),
(2, 'Hilton Vancouver', 5, '401 W Georgia St', 'Vancouver', 'BC', 'Canada', 'V6B 5A1', 'vancouver@hilton.com', '1-604-689-9999', 0);

-- InterContinental hotels
INSERT INTO Hotel (chain_id, name, star_rating, address, city, state_province, country, zip_postal_code, email, phone_number, number_of_rooms)
VALUES
(3, 'InterContinental New York Times Square', 5, '300 W 44th St', 'New York', 'NY', 'USA', '10036', 'nytimessquare@intercontinental.com', '1-212-803-4500', 0),
(3, 'InterContinental Toronto Centre', 4, '225 Front St W', 'Toronto', 'ON', 'Canada', 'M5V 2X3', 'torontocentre@intercontinental.com', '1-416-597-1400', 0),
(3, 'InterContinental Montreal', 5, '360 Saint-Antoine St W', 'Montreal', 'QC', 'Canada', 'H2Y 3X4', 'montreal@intercontinental.com', '1-514-987-9900', 0);

-- AccorHotels 
INSERT INTO Hotel (chain_id, name, star_rating, address, city, state_province, country, zip_postal_code, email, phone_number, number_of_rooms)
VALUES
(4, 'Sofitel New York', 5, '45 W 44th St', 'New York', 'NY', 'USA', '10036', 'newyork@sofitel.com', '1-212-354-8844', 0),
(4, 'Novotel Toronto Centre', 3, '45 The Esplanade', 'Toronto', 'ON', 'Canada', 'M5E 1W2', 'toronto@novotel.com', '1-416-367-8900', 0),
(4, 'Fairmont Royal York', 5, '100 Front St W', 'Toronto', 'ON', 'Canada', 'M5J 1E3', 'royalyork@fairmont.com', '1-416-368-2511', 0);

-- Wyndham hotels
INSERT INTO Hotel (chain_id, name, star_rating, address, city, state_province, country, zip_postal_code, email, phone_number, number_of_rooms)
VALUES
(5, 'Wyndham New Yorker', 4, '481 8th Ave', 'New York', 'NY', 'USA', '10001', 'newyorker@wyndham.com', '1-212-971-0101', 0),
(5, 'Ramada by Wyndham Toronto', 3, '300 Jarvis St', 'Toronto', 'ON', 'Canada', 'M5B 2C5', 'toronto@ramada.com', '1-416-977-6655', 0),
(5, 'Days Inn by Wyndham Vancouver', 2, '2075 Kingsway', 'Vancouver', 'BC', 'Canada', 'V5N 2T2', 'vancouver@daysinn.com', '1-604-876-5411', 0);

-- Add employees and update hotel managers
-- Sample employees for Marriott hotels
INSERT INTO Employee (hotel_id, name, position, department, email, phone_number, address, sin, username, password, is_manager)
VALUES
(1, 'John Smith', 'General Manager', 'Management', 'jsmith@marriott.com', '1-212-247-0301', '123 Park Ave, New York, NY', '123456789', 'jsmith', '$2y$10$GtU7amcJAZvLYB0kRyEaVuaQHEvbX9VJGoc4a9L0SGs0nWn61p0He', TRUE),
(1, 'Emily Johnson', 'Front Desk Manager', 'Front Office', 'ejohnson@marriott.com', '1-212-247-0302', '456 Madison Ave, New York, NY', '234567891', 'ejohnson', '$2y$10$zDzJ8LZqgJqX7eU/iX0yfubFS.ub/P1E3J5FR.9FoLMPRr3OL2BJO', FALSE),
(2, 'Michael Brown', 'General Manager', 'Management', 'mbrown@marriott.com', '1-416-555-1235', '789 King St, Toronto, ON', '345678912', 'mbrown', '$2y$10$xVWn2K7lzLZh7KENaUJreexL9cI22wNwM.zzLyfD9sNcBPDhEOyye', TRUE),
(3, 'Jennifer Lee', 'General Manager', 'Management', 'jlee@marriott.com', '1-604-555-9877', '234 Granville St, Vancouver, BC', '456789123', 'jlee', '$2y$10$B5zLb5B.VvdlHJQzLPEGJOWSzGzGS9Ru.0CK0YcQl7dEYAWLHlDhy', TRUE);

-- Update hotel managers
UPDATE Hotel SET manager_id = 1 WHERE hotel_id = 1;
UPDATE Hotel SET manager_id = 3 WHERE hotel_id = 2;
UPDATE Hotel SET manager_id = 4 WHERE hotel_id = 3;

-- Sample employees for Hilton hotels
INSERT INTO Employee (hotel_id, name, position, department, email, phone_number, address, sin, username, password, is_manager)
VALUES
(4, 'David Wilson', 'General Manager', 'Management', 'dwilson@hilton.com', '1-212-586-7001', '567 5th Ave, New York, NY', '567891234', 'dwilson', '$2y$10$EI1fDDxlSCnvk51X3Xyv/eNPqwFBZ3UQFpJ1jmrwcZ6V3lJl/sfTa', TRUE),
(5, 'Sarah Thompson', 'General Manager', 'Management', 'sthompson@hilton.com', '1-416-869-3457', '890 University Ave, Toronto, ON', '678912345', 'sthompson', '$2y$10$g35QIKFTdm.dKiDzlbIUweq.0h/IQ8oOFiXj5dhcPDKxVFsEhHiBS', TRUE),
(6, 'Robert Chen', 'General Manager', 'Management', 'rchen@hilton.com', '1-604-689-9991', '123 Robson St, Vancouver, BC', '789123456', 'rchen', '$2y$10$PZbS9ZnqDxJPxV1AfXoX9uG59l4TjFz/dVQVEH.wCG6kw5VQoYxSG', TRUE);

-- Update hotel managers
UPDATE Hotel SET manager_id = 5 WHERE hotel_id = 4;
UPDATE Hotel SET manager_id = 6 WHERE hotel_id = 5;
UPDATE Hotel SET manager_id = 7 WHERE hotel_id = 6;

-- Add sample rooms for hotels
-- Rooms for JW Marriott Essex House
INSERT INTO Room (hotel_id, room_number, room_type, price, capacity, view_type, is_extendable, amenities)
VALUES
(1, '101', 'Deluxe King', 450.00, 2, 'City View', TRUE, 'WiFi, Mini-bar, 50" Smart TV, Coffee Maker, Workspace'),
(1, '102', 'Double Queen', 500.00, 4, 'Park View', TRUE, 'WiFi, Mini-bar, 55" Smart TV, Coffee Maker, Sofa'),
(1, '201', 'Executive Suite', 750.00, 3, 'Central Park View', FALSE, 'WiFi, Mini-bar, 65" Smart TV, Kitchenette, Sofa, Workspace, Jacuzzi'),
(1, '301', 'Penthouse Suite', 1500.00, 6, 'Premium Park View', FALSE, 'WiFi, Full Bar, 75" Smart TV, Full Kitchen, Living Room, Dining Area, 2 Bathrooms');

-- Rooms for Marriott Marquis Toronto
INSERT INTO Room (hotel_id, room_number, room_type, price, capacity, view_type, is_extendable, amenities)
VALUES
(2, '101', 'Standard King', 250.00, 2, 'City View', TRUE, 'WiFi, 43" Smart TV, Coffee Maker'),
(2, '102', 'Double Queen', 300.00, 4, 'Lake View', TRUE, 'WiFi, 50" Smart TV, Coffee Maker, Mini-fridge'),
(2, '201', 'Deluxe Suite', 450.00, 3, 'Premium Lake View', FALSE, 'WiFi, 55" Smart TV, Kitchenette, Workspace, Sofa'),
(2, '301', 'Presidential Suite', 900.00, 4, 'Panoramic View', FALSE, 'WiFi, Full Kitchen, 65" Smart TV, Dining Area, Living Room, Jacuzzi');

-- Rooms for Hilton New York Midtown
INSERT INTO Room (hotel_id, room_number, room_type, price, capacity, view_type, is_extendable, amenities)
VALUES
(4, '101', 'Standard Queen', 200.00, 2, 'City View', TRUE, 'WiFi, 42" TV, Coffee Maker'),
(4, '102', 'King Room', 250.00, 2, 'Street View', TRUE, 'WiFi, 47" TV, Coffee Maker, Desk'),
(4, '201', 'Executive King', 350.00, 2, 'City View', TRUE, 'WiFi, 50" Smart TV, Mini-bar, Workspace, Executive Lounge Access'),
(4, '301', 'Corner Suite', 600.00, 4, 'Times Square View', FALSE, 'WiFi, 55" Smart TV, Living Room, Kitchenette, Workspace, Premium Amenities');

-- Add more rooms for each hotel as needed

-- Add sample customers
INSERT INTO Customer (name, email, address, sin)
VALUES
('Alice Johnson', 'alice.johnson@email.com', '123 Main St, New York, NY', '123789456'),
('Bob Williams', 'bob.williams@email.com', '456 Oak Ave, Toronto, ON', '234891567'),
('Catherine Davis', 'catherine.davis@email.com', '789 Pine Rd, Vancouver, BC', '345912678'),
('Daniel Miller', 'daniel.miller@email.com', '101 Elm St, Montreal, QC', '456123789'),
('Eva Garcia', 'eva.garcia@email.com', '202 Maple Dr, Chicago, IL', '567234891');

-- Add sample bookings
INSERT INTO Booking (room_id, customer_id, start_date, end_date, payment_method, total_price)
VALUES
(1, 1, '2023-11-10', '2023-11-15', 'credit_card', 2250.00),  -- 5 nights at $450/night
(5, 2, '2023-11-12', '2023-11-14', 'paypal', 500.00),        -- 2 nights at $250/night
(9, 3, '2023-11-15', '2023-11-20', 'credit_card', 1000.00),  -- 5 nights at $200/night
(2, 4, '2023-12-01', '2023-12-05', 'debit_card', 2000.00),   -- 4 nights at $500/night
(6, 5, '2023-12-10', '2023-12-15', 'credit_card', 1500.00);  -- 5 nights at $300/night 