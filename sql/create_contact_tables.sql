-- Table for storing multiple email addresses for a Hotel Chain
DROP TABLE IF EXISTS Hotel_Chain_Emails;
CREATE TABLE Hotel_Chain_Emails (
    Chain_Name VARCHAR(255) NOT NULL,
    Email_Address VARCHAR(255) NOT NULL,
    PRIMARY KEY (Chain_Name, Email_Address),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Table for storing multiple phone numbers for a Hotel Chain
DROP TABLE IF EXISTS Hotel_Chain_Phones;
CREATE TABLE Hotel_Chain_Phones (
    Chain_Name VARCHAR(255) NOT NULL,
    Phone_Number VARCHAR(20) NOT NULL, -- Adjusted size based on previous usage
    PRIMARY KEY (Chain_Name, Phone_Number),
    FOREIGN KEY (Chain_Name) REFERENCES Hotel_Chain(Chain_Name)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Table for storing multiple email addresses for a specific Hotel
DROP TABLE IF EXISTS Hotel_Emails;
CREATE TABLE Hotel_Emails (
    Hotel_Address VARCHAR(255) NOT NULL,
    Email_Address VARCHAR(255) NOT NULL,
    PRIMARY KEY (Hotel_Address, Email_Address),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Table for storing multiple phone numbers for a specific Hotel
-- Note: Hotel table already has one Phone_Number. This allows for *additional* numbers.
DROP TABLE IF EXISTS Hotel_Phones;
CREATE TABLE Hotel_Phones (
    Hotel_Address VARCHAR(255) NOT NULL,
    Phone_Number VARCHAR(20) NOT NULL,
    PRIMARY KEY (Hotel_Address, Phone_Number),
    FOREIGN KEY (Hotel_Address) REFERENCES Hotel(Hotel_Address)
        ON DELETE CASCADE
        ON UPDATE CASCADE
); 