-- Drop tables if they exist (in reverse dependency order)
DROP TABLE IF EXISTS Payment CASCADE;
DROP TABLE IF EXISTS Archived_Renting CASCADE;
DROP TABLE IF EXISTS Archived_Booking CASCADE;
DROP TABLE IF EXISTS Rented_By CASCADE;
DROP TABLE IF EXISTS Renting CASCADE;
DROP TABLE IF EXISTS Reserved_By CASCADE;
DROP TABLE IF EXISTS Booking CASCADE;
DROP TABLE IF EXISTS Room CASCADE;
DROP TABLE IF EXISTS Hotel_Email CASCADE;
DROP TABLE IF EXISTS Hotel_Phone CASCADE;
DROP TABLE IF EXISTS Works_At CASCADE;
DROP TABLE IF EXISTS Hotel CASCADE;
DROP TABLE IF EXISTS Employee CASCADE;
DROP TABLE IF EXISTS Chain_Email CASCADE;
DROP TABLE IF EXISTS Chain_Phone CASCADE;
DROP TABLE IF EXISTS Hotel_Chain CASCADE;
DROP TABLE IF EXISTS Customer CASCADE;

-- Hotel Chain and related tables
CREATE TABLE Hotel_Chain (
    Chain_Name VARCHAR(50) PRIMARY KEY,
    Central_Office_Address VARCHAR(100) NOT NULL,
    Num_Hotels INT CHECK (Num_Hotels > 0)
);

CREATE TABLE Chain_Phone (
    Chain_Name VARCHAR(50),
    Phone_Num VARCHAR(20),
    PRIMARY KEY (Chain_Name, Phone_Num),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE CASCADE
);

CREATE TABLE Chain_Email (
    Chain_Name VARCHAR(50),
    Contact_Email VARCHAR(100),
    PRIMARY KEY (Chain_Name, Contact_Email),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE CASCADE
);

-- Employee table (needed before Hotel due to Manager_SSN FK)
CREATE TABLE Employee (
    SSN CHAR(9) PRIMARY KEY,
    Full_Name VARCHAR(100) NOT NULL,
    Address VARCHAR(100) NOT NULL,
    Position VARCHAR(50) CHECK (Position IN ('Manager', 'Receptionist', 'Cleaner', 'Security', 'Maintenance'))
);

-- Customer table
CREATE TABLE Customer (
    Customer_ID VARCHAR(20) PRIMARY KEY,
    Full_Name VARCHAR(100) NOT NULL,
    Address VARCHAR(100) NOT NULL,
    ID_Type VARCHAR(20) CHECK (ID_Type IN ('Passport', 'Driver License', 'National ID', 'SSN', 'SIN')),
    Date_of_Registration DATE NOT NULL
);

-- Hotel and related tables
CREATE TABLE Hotel (
    Hotel_Address VARCHAR(100) PRIMARY KEY,
    Chain_Name VARCHAR(50) NOT NULL,
    Star_Rating INT CHECK (Star_Rating BETWEEN 1 AND 5),
    Manager_SSN CHAR(9) NOT NULL,
    Num_Rooms INT CHECK (Num_Rooms > 0),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name) ON DELETE CASCADE,
    FOREIGN KEY (Manager_SSN) REFERENCES Employee(SSN)
);

CREATE TABLE Hotel_Phone (
    Hotel_Address VARCHAR(100),
    Phone_Num VARCHAR(20),
    PRIMARY KEY (Hotel_Address, Phone_Num),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

CREATE TABLE Hotel_Email (
    Hotel_Address VARCHAR(100),
    Contact_Email VARCHAR(100),
    PRIMARY KEY (Hotel_Address, Contact_Email),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

CREATE TABLE Works_At (
    SSN CHAR(9),
    Hotel_Address VARCHAR(100),
    PRIMARY KEY (SSN, Hotel_Address),
    FOREIGN KEY (SSN) REFERENCES Employee(SSN) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Room table
CREATE TABLE Room (
    Hotel_Address VARCHAR(100),
    Room_Num INT,
    Capacity INT CHECK (Capacity > 0 AND Capacity <= 10),
    Amenities TEXT,
    View_Type VARCHAR(20) CHECK (View_Type IN ('Mountain', 'Sea', 'City', 'Garden', 'Pool')),
    Price DECIMAL(10, 2) CHECK (Price > 0),
    Extendable BOOLEAN DEFAULT FALSE,
    Availability BOOLEAN DEFAULT TRUE,
    Damages TEXT,
    PRIMARY KEY (Hotel_Address, Room_Num),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address) ON DELETE CASCADE
);

-- Booking tables
CREATE TABLE Booking (
    Booking_ID VARCHAR(20) PRIMARY KEY,
    Creation_Date DATE NOT NULL,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Customer_ID VARCHAR(20) NOT NULL,
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
    CHECK (End_Date > Start_Date),
    CHECK (Start_Date >= Creation_Date)
);

CREATE TABLE Reserved_By (
    Booking_ID VARCHAR(20),
    Hotel_Address VARCHAR(100),
    Room_Num INT,
    PRIMARY KEY (Booking_ID, Hotel_Address, Room_Num),
    FOREIGN KEY (Booking_ID) REFERENCES Booking(Booking_ID) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address, Room_Num) REFERENCES Room(Hotel_Address, Room_Num)
);

-- Renting tables
CREATE TABLE Renting (
    Renting_ID VARCHAR(20) PRIMARY KEY,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Check_in_Date DATE NOT NULL,
    Direct_Renting BOOLEAN NOT NULL,
    Customer_ID VARCHAR(20) NOT NULL,
    Employee_SSN CHAR(9) NOT NULL,
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
    FOREIGN KEY (Employee_SSN) REFERENCES Employee(SSN),
    CHECK (End_Date > Start_Date),
    CHECK (Check_in_Date <= Start_Date)
);

CREATE TABLE Rented_By (
    Renting_ID VARCHAR(20),
    Hotel_Address VARCHAR(100),
    Room_Num INT,
    PRIMARY KEY (Renting_ID, Hotel_Address, Room_Num),
    FOREIGN KEY (Renting_ID) REFERENCES Renting(Renting_ID) ON DELETE CASCADE,
    FOREIGN KEY (Hotel_Address, Room_Num) REFERENCES Room(Hotel_Address, Room_Num)
);

-- Archive tables
CREATE TABLE Archived_Booking (
    Booking_ID VARCHAR(20) PRIMARY KEY,
    Creation_Date DATE NOT NULL,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Customer_ID VARCHAR(20),
    Hotel_Address VARCHAR(100),
    Room_Num INT,
    CHECK (End_Date > Start_Date),
    CHECK (Start_Date >= Creation_Date)
);

CREATE TABLE Archived_Renting (
    Renting_ID VARCHAR(20) PRIMARY KEY,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Check_in_Date DATE NOT NULL,
    Customer_ID VARCHAR(20),
    Hotel_Address VARCHAR(100),
    Room_Num INT,
    Direct_Renting BOOLEAN NOT NULL,
    Employee_SSN CHAR(9),
    CHECK (End_Date > Start_Date),
    CHECK (Check_in_Date <= Start_Date)
);

-- Payment table (not storing history as per requirements)
CREATE TABLE Payment (
    Payment_ID VARCHAR(20) PRIMARY KEY,
    Renting_ID VARCHAR(20) NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL CHECK (Amount > 0),
    Payment_Date DATE NOT NULL,
    Payment_Method VARCHAR(20) CHECK (Payment_Method IN ('Credit Card', 'Debit Card', 'Cash')),
    FOREIGN KEY (Renting_ID) REFERENCES Renting(Renting_ID) ON DELETE CASCADE
);