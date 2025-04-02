-- e-Hotels Database Schema
-- Based on Deliverable 1 by Mouad Ben lahbib (300259705)

-- Create the database
CREATE DATABASE ehotels;

-- Connect to the database
\c ehotels;

-- Enable UUID extension (for generating unique IDs)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create ENUM types for better data integrity
CREATE TYPE room_view_type AS ENUM ('Mountain', 'Sea', 'City', 'Garden', 'Pool', 'None');
CREATE TYPE id_type AS ENUM ('Passport', 'Driver License', 'National ID');
CREATE TYPE employee_position AS ENUM ('Manager', 'Receptionist', 'Cleaner', 'Security', 'Maintenance');

-- Hotel Chain
CREATE TABLE Hotel_Chain (
    Chain_Name VARCHAR(50) PRIMARY KEY,
    Central_Office_Address VARCHAR(100) NOT NULL,
    Num_Hotels INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT check_num_hotels CHECK (Num_Hotels >= 0)
);

-- Chain Phone (multivalued attribute)
CREATE TABLE Chain_Phone (
    Chain_Name VARCHAR(50) NOT NULL,
    Phone_Num VARCHAR(20) NOT NULL,
    PRIMARY KEY (Chain_Name, Phone_Num),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE CASCADE
);

-- Chain Email (multivalued attribute)
CREATE TABLE Chain_Email (
    Chain_Name VARCHAR(50) NOT NULL,
    Contact_Email VARCHAR(100) NOT NULL,
    PRIMARY KEY (Chain_Name, Contact_Email),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE CASCADE
);

-- Employee
CREATE TABLE Employee (
    SSN CHAR(9) PRIMARY KEY,
    Full_Name VARCHAR(100) NOT NULL,
    Address VARCHAR(150) NOT NULL,
    Position employee_position NOT NULL,
    CONSTRAINT check_ssn CHECK (SSN ~ '^[0-9]{9}$')
);

-- Hotel
CREATE TABLE Hotel (
    Hotel_Address VARCHAR(100) PRIMARY KEY,
    Chain_Name VARCHAR(50) NOT NULL,
    Star_Rating INTEGER NOT NULL,
    Manager_SSN CHAR(9) NOT NULL,
    Num_Rooms INTEGER NOT NULL DEFAULT 0,
    Area VARCHAR(50) NOT NULL, -- Added for area-based searches
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE RESTRICT,
    FOREIGN KEY (Manager_SSN) REFERENCES Employee(SSN) ON DELETE RESTRICT,
    CONSTRAINT check_star_rating CHECK (Star_Rating BETWEEN 1 AND 5),
    CONSTRAINT check_num_rooms CHECK (Num_Rooms >= 0)
);

-- Hotel Phone (multivalued attribute)
CREATE TABLE Hotel_Phone (
    Hotel_Address VARCHAR(100) NOT NULL,
    Phone_Num VARCHAR(20) NOT NULL,
    PRIMARY KEY (Hotel_Address, Phone_Num),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Hotel Email (multivalued attribute)
CREATE TABLE Hotel_Email (
    Hotel_Address VARCHAR(100) NOT NULL,
    Contact_Email VARCHAR(100) NOT NULL,
    PRIMARY KEY (Hotel_Address, Contact_Email),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Employee Works At Hotel relationship
CREATE TABLE Works_At (
    SSN CHAR(9) NOT NULL,
    Hotel_Address VARCHAR(100) NOT NULL,
    PRIMARY KEY (SSN, Hotel_Address),
    FOREIGN KEY (SSN) REFERENCES Employee(SSN) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Manager Manages Hotel relationship (with total participation of Hotel)
CREATE TABLE Manages (
    SSN CHAR(9) NOT NULL,
    Hotel_Address VARCHAR(100) NOT NULL,
    PRIMARY KEY (SSN, Hotel_Address),
    FOREIGN KEY (SSN) REFERENCES Employee(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Room
CREATE TABLE Room (
    Hotel_Address VARCHAR(100) NOT NULL,
    Room_Num INTEGER NOT NULL,
    Capacity INTEGER NOT NULL,
    Amenities TEXT,
    View_Type room_view_type NOT NULL DEFAULT 'None',
    Price DECIMAL(10, 2) NOT NULL,
    Extendable BOOLEAN NOT NULL DEFAULT FALSE,
    Availability BOOLEAN NOT NULL DEFAULT TRUE,
    Damages TEXT,
    PRIMARY KEY (Hotel_Address, Room_Num),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE RESTRICT,
    CONSTRAINT check_room_num CHECK (Room_Num > 0),
    CONSTRAINT check_capacity CHECK (Capacity BETWEEN 1 AND 10),
    CONSTRAINT check_price CHECK (Price > 0)
);

-- Customer
CREATE TABLE Customer (
    Customer_ID VARCHAR(36) PRIMARY KEY DEFAULT uuid_generate_v4(),
    Full_Name VARCHAR(100) NOT NULL,
    Address VARCHAR(150) NOT NULL,
    ID_Type id_type NOT NULL,
    Date_of_Registration DATE NOT NULL DEFAULT CURRENT_DATE
);

-- Customer Email (additional multivalued attribute)
CREATE TABLE Customer_Email (
    Customer_ID VARCHAR(36) NOT NULL,
    Contact_Email VARCHAR(100) NOT NULL,
    PRIMARY KEY (Customer_ID, Contact_Email),
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID) ON DELETE CASCADE
);

-- Booking
CREATE TABLE Booking (
    Booking_ID VARCHAR(36) PRIMARY KEY DEFAULT uuid_generate_v4(),
    Creation_Date DATE NOT NULL DEFAULT CURRENT_DATE,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Customer_ID VARCHAR(36) NOT NULL,
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID) ON DELETE CASCADE,
    CONSTRAINT check_booking_dates CHECK (
        End_Date > Start_Date AND 
        Start_Date >= Creation_Date
    )
);

-- Reserved By relationship (Room is reserved by Booking)
CREATE TABLE Reserved_By (
    Booking_ID VARCHAR(36) NOT NULL,
    Hotel_Address VARCHAR(100) NOT NULL,
    Room_Num INTEGER NOT NULL,
    PRIMARY KEY (Booking_ID, Hotel_Address, Room_Num),
    FOREIGN KEY (Booking_ID) REFERENCES Booking(Booking_ID) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address, Room_Num) REFERENCES Room(Hotel_Address, Room_Num) ON DELETE RESTRICT
);

-- Renting
CREATE TABLE Renting (
    Renting_ID VARCHAR(255) PRIMARY KEY,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Check_in_Date DATE NOT NULL,
    Direct_Renting BOOLEAN NOT NULL,
    Customer_ID VARCHAR(255) NOT NULL,
    Payment_Amount DECIMAL(10, 2) NULL, -- Added payment amount, nullable for now
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID)
);

-- Rented By relationship (Room is rented by Renting)
CREATE TABLE Rented_By (
    Renting_ID VARCHAR(36) NOT NULL,
    Hotel_Address VARCHAR(100) NOT NULL,
    Room_Num INTEGER NOT NULL,
    PRIMARY KEY (Renting_ID, Hotel_Address, Room_Num),
    FOREIGN KEY (Renting_ID) REFERENCES Renting(Renting_ID) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address, Room_Num) REFERENCES Room(Hotel_Address, Room_Num) ON DELETE RESTRICT
);

-- Processes relationship (Employee processes Renting)
CREATE TABLE Processes (
    SSN CHAR(9) NOT NULL,
    Renting_ID VARCHAR(36) NOT NULL,
    PRIMARY KEY (SSN, Renting_ID),
    FOREIGN KEY (SSN) REFERENCES Employee(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (Renting_ID) REFERENCES Renting(Renting_ID) ON DELETE CASCADE
);

-- Archived tables for historical data retention

-- Archived Booking
CREATE TABLE Archived_Booking (
    Booking_ID VARCHAR(36) PRIMARY KEY,
    Creation_Date DATE NOT NULL,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Customer_ID VARCHAR(36)
    -- Note: Customer_ID can be NULL if the customer is deleted
);

-- Archived Renting
CREATE TABLE Archived_Renting (
    Renting_ID VARCHAR(36) PRIMARY KEY,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Check_in_Date DATE NOT NULL,
    Customer_ID VARCHAR(36)
    -- Note: Customer_ID can be NULL if the customer is deleted
); 