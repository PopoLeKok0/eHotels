-- Sample data for Hotel_Chain
INSERT INTO Hotel_Chain (Chain_Name, Central_Office_Address, Num_Hotels) VALUES
('Stellar Hotels', '123 Corporate Drive, New York, NY 10001', 8),
('Azure Resorts', '456 Business Blvd, Los Angeles, CA 90001', 8),
('Emerald Inns', '789 Company Street, Chicago, IL 60007', 8),
('Ruby Suites', '321 Executive Ave, Miami, FL 33101', 8),
('Sapphire Lodging', '654 Corporate Lane, Seattle, WA 98101', 8);

-- Contact information for Hotel_Chain
INSERT INTO Chain_Phone (Chain_Name, Phone_Num) VALUES
('Stellar Hotels', '212-555-1000'),
('Stellar Hotels', '212-555-1001'),
('Azure Resorts', '310-555-2000'),
('Azure Resorts', '310-555-2001'),
('Emerald Inns', '312-555-3000'),
('Emerald Inns', '312-555-3001'),
('Ruby Suites', '305-555-4000'),
('Ruby Suites', '305-555-4001'),
('Sapphire Lodging', '206-555-5000'),
('Sapphire Lodging', '206-555-5001');

INSERT INTO Chain_Email (Chain_Name, Contact_Email) VALUES
('Stellar Hotels', 'info@stellarhotels.com'),
('Stellar Hotels', 'reservations@stellarhotels.com'),
('Azure Resorts', 'info@azureresorts.com'),
('Azure Resorts', 'reservations@azureresorts.com'),
('Emerald Inns', 'info@emeraldinns.com'),
('Emerald Inns', 'reservations@emeraldinns.com'),
('Ruby Suites', 'info@rubysuites.com'),
('Ruby Suites', 'reservations@rubysuites.com'),
('Sapphire Lodging', 'info@sapphirelodging.com'),
('Sapphire Lodging', 'reservations@sapphirelodging.com');

-- Sample data for Employee (including managers)
INSERT INTO Employee (SSN, Full_Name, Address, Position) VALUES
-- Stellar Hotels managers and employees
('123456789', 'John Smith', '123 Manager St, New York, NY 10001', 'Manager'),
('123456790', 'Emily Johnson', '124 Employee Ave, New York, NY 10001', 'Receptionist'),
('123456791', 'Michael Brown', '125 Staff Blvd, New York, NY 10001', 'Maintenance'),
('123456792', 'Sarah Davis', '126 Worker Lane, Boston, MA 02108', 'Manager'),
('123456793', 'Robert Wilson', '127 Staff St, Boston, MA 02108', 'Cleaner'),
('123456794', 'Jennifer Taylor', '128 Employee Rd, San Francisco, CA 94102', 'Manager'),
('123456795', 'David Martinez', '129 Staff Ave, San Francisco, CA 94102', 'Security'),
('123456796', 'Lisa Anderson', '130 Worker Blvd, Denver, CO 80202', 'Manager'),
('123456797', 'James Thomas', '131 Employee St, Denver, CO 80202', 'Receptionist'),
('123456798', 'Daniel Jackson', '132 Staff Lane, Toronto, ON M5H 2N2', 'Manager'),
('123456799', 'Michelle White', '133 Worker Rd, Toronto, ON M5H 2N2', 'Cleaner'),
('123456800', 'Christopher Lee', '134 Employee Ave, Montreal, QC H3B 2Y5', 'Manager'),
('123456801', 'Amanda King', '135 Staff Blvd, Montreal, QC H3B 2Y5', 'Maintenance'),
('123456802', 'Matthew Wright', '136 Worker St, Vancouver, BC V6C 3E1', 'Manager'),
('123456803', 'Jessica Scott', '137 Employee Lane, Vancouver, BC V6C 3E1', 'Security'),
('123456804', 'Andrew Green', '138 Staff Rd, Calgary, AB T2P 2M5', 'Manager'),
('123456805', 'Nicole Baker', '139 Worker Ave, Calgary, AB T2P 2M5', 'Receptionist'),

-- Azure Resorts managers and employees
('234567890', 'Richard Harris', '223 Manager St, Los Angeles, CA 90001', 'Manager'),
('234567891', 'Elizabeth Clark', '224 Employee Ave, Los Angeles, CA 90001', 'Receptionist'),
('234567892', 'William Lewis', '225 Staff Blvd, Los Angeles, CA 90001', 'Maintenance'),
('234567893', 'Patricia Robinson', '226 Worker Lane, San Diego, CA 92101', 'Manager'),
('234567894', 'Joseph Walker', '227 Staff St, San Diego, CA 92101', 'Cleaner'),
('234567895', 'Karen Young', '228 Employee Rd, Las Vegas, NV 89101', 'Manager'),
('234567896', 'Edward Allen', '229 Staff Ave, Las Vegas, NV 89101', 'Security'),
('234567897', 'Laura King', '230 Worker Blvd, Phoenix, AZ 85001', 'Manager'),
('234567898', 'Steven Wright', '231 Employee St, Phoenix, AZ 85001', 'Receptionist'),
('234567899', 'Margaret Hill', '232 Staff Lane, Austin, TX 78701', 'Manager'),
('234567900', 'Thomas Scott', '233 Worker Rd, Austin, TX 78701', 'Cleaner'),
('234567901', 'Dorothy Green', '234 Employee Ave, Dallas, TX 75201', 'Manager'),
('234567902', 'Charles Adams', '235 Staff Blvd, Dallas, TX 75201', 'Maintenance'),
('234567903', 'Betty Nelson', '236 Worker St, Houston, TX 77002', 'Manager'),
('234567904', 'Ronald Baker', '237 Employee Lane, Houston, TX 77002', 'Security'),
('234567905', 'Helen Carter', '238 Staff Rd, New Orleans, LA 70112', 'Manager'),
('234567906', 'Gary Mitchell', '239 Worker Ave, New Orleans, LA 70112', 'Receptionist'),

-- Emerald Inns managers and employees
('345678901', 'Kevin Turner', '323 Manager St, Chicago, IL 60007', 'Manager'),
('345678902', 'Carol Phillips', '324 Employee Ave, Chicago, IL 60007', 'Receptionist'),
('345678903', 'Mark Campbell', '325 Staff Blvd, Chicago, IL 60007', 'Maintenance'),
('345678904', 'Ruth Parker', '326 Worker Lane, Detroit, MI 48201', 'Manager'),
('345678905', 'Steven Evans', '327 Staff St, Detroit, MI 48201', 'Cleaner'),
('345678906', 'Donna Edwards', '328 Employee Rd, Cleveland, OH 44113', 'Manager'),
('345678907', 'Brandon Collins', '329 Staff Ave, Cleveland, OH 44113', 'Security'),
('345678908', 'Julia Stewart', '330 Worker Blvd, Philadelphia, PA 19102', 'Manager'),
('345678909', 'Gregory Sanchez', '331 Employee St, Philadelphia, PA 19102', 'Receptionist'),
('345678910', 'Deborah Morris', '332 Staff Lane, Pittsburgh, PA 15222', 'Manager'),
('345678911', 'Joshua Rogers', '333 Worker Rd, Pittsburgh, PA 15222', 'Cleaner'),
('345678912', 'Judith Reed', '334 Employee Ave, Washington, DC 20001', 'Manager'),
('345678913', 'Brian Cook', '335 Staff Blvd, Washington, DC 20001', 'Maintenance'),
('345678914', 'Rebecca Morgan', '336 Worker St, Atlanta, GA 30303', 'Manager'),
('345678915', 'Timothy Bell', '337 Employee Lane, Atlanta, GA 30303', 'Security'),
('345678916', 'Kimberly Murphy', '338 Staff Rd, Orlando, FL 32801', 'Manager'),
('345678917', 'Jeffrey Bailey', '339 Worker Ave, Orlando, FL 32801', 'Receptionist'),

-- Ruby Suites managers and employees
('456789012', 'Angela Rivera', '423 Manager St, Miami, FL 33101', 'Manager'),
('456789013', 'Jack Cooper', '424 Employee Ave, Miami, FL 33101', 'Receptionist'),
('456789014', 'Melissa Richardson', '425 Staff Blvd, Miami, FL 33101', 'Maintenance'),
('456789015', 'Dennis Cox', '426 Worker Lane, Nashville, TN 37201', 'Manager'),
('456789016', 'Carolyn Howard', '427 Staff St, Nashville, TN 37201', 'Cleaner'),
('456789017', 'Jerry Ward', '428 Employee Rd, Memphis, TN 38103', 'Manager'),
('456789018', 'Christine Torres', '429 Staff Ave, Memphis, TN 38103', 'Security'),
('456789019', 'Peter Peterson', '430 Worker Blvd, Charleston, SC 29401', 'Manager'),
('456789020', 'Shirley Gray', '431 Employee St, Charleston, SC 29401', 'Receptionist'),
('456789021', 'Frank Ramirez', '432 Staff Lane, New Orleans, LA 70112', 'Manager'),
('456789022', 'Virginia James', '433 Worker Rd, New Orleans, LA 70112', 'Cleaner'),
('456789023', 'Tyler Watson', '434 Employee Ave, Birmingham, AL 35203', 'Manager'),
('456789024', 'Brenda Brooks', '435 Staff Blvd, Birmingham, AL 35203', 'Maintenance'),
('456789025', 'Wayne Kelly', '436 Worker St, Jacksonville, FL 32202', 'Manager'),
('456789026', 'Anna Sanders', '437 Employee Lane, Jacksonville, FL 32202', 'Security'),
('456789027', 'Roy Price', '438 Staff Rd, Tampa, FL 33602', 'Manager'),
('456789028', 'Lois Bennett', '439 Worker Ave, Tampa, FL 33602', 'Receptionist'),

-- Sapphire Lodging managers and employees
('567890123', 'Ralph Wood', '523 Manager St, Seattle, WA 98101', 'Manager'),
('567890124', 'Tina Barnes', '524 Employee Ave, Seattle, WA 98101', 'Receptionist'),
('567890125', 'Carlos Ross', '525 Staff Blvd, Seattle, WA 98101', 'Maintenance'),
('567890126', 'Jean Henderson', '526 Worker Lane, Portland, OR 97201', 'Manager'),
('567890127', 'Randy Coleman', '527 Staff St, Portland, OR 97201', 'Cleaner'),
('567890128', 'Louise Jenkins', '528 Employee Rd, San Francisco, CA 94102', 'Manager'),
('567890129', 'Eugene Perry', '529 Staff Ave, San Francisco, CA 94102', 'Security'),
('567890130', 'Sara Powell', '530 Worker Blvd, Sacramento, CA 95814', 'Manager'),
('567890131', 'Alan Butler', '531 Employee St, Sacramento, CA 95814', 'Receptionist'),
('567890132', 'Janice Simmons', '532 Staff Lane, Oakland, CA 94607', 'Manager'),
('567890133', 'Gerald Patterson', '533 Worker Rd, Oakland, CA 94607', 'Cleaner'),
('567890134', 'Kathleen Hughes', '534 Employee Ave, Denver, CO 80202', 'Manager'),
('567890135', 'Roger Flores', '535 Staff Blvd, Denver, CO 80202', 'Maintenance'),
('567890136', 'Marilyn Washington', '536 Worker St, Boulder, CO 80302', 'Manager'),
('567890137', 'Terry Russell', '537 Employee Lane, Boulder, CO 80302', 'Security'),
('567890138', 'Gloria Griffin', '538 Staff Rd, Salt Lake City, UT 84101', 'Manager'),
('567890139', 'Willie Diaz', '539 Worker Ave, Salt Lake City, UT 84101', 'Receptionist');

-- Sample data for Hotel (5 chains with 8 hotels each)
INSERT INTO Hotel (Hotel_Address, Chain_Name, Star_Rating, Manager_SSN, Num_Rooms) VALUES
-- Stellar Hotels
('100 Coastal Road, New York, NY 10001', 'Stellar Hotels', 5, '123456789', 0),
('200 Mountain View, Boston, MA 02108', 'Stellar Hotels', 4, '123456792', 0),
('300 City Center, San Francisco, CA 94102', 'Stellar Hotels', 5, '123456794', 0),
('400 Lake Front, Denver, CO 80202', 'Stellar Hotels', 3, '123456796', 0),
('500 Maple Avenue, Toronto, ON M5H 2N2', 'Stellar Hotels', 4, '123456798', 0),
('600 Pine Street, Montreal, QC H3B 2Y5', 'Stellar Hotels', 3, '123456800', 0),
('700 Beach Boulevard, Vancouver, BC V6C 3E1', 'Stellar Hotels', 5, '123456802', 0),
('800 Mountain Road, Calgary, AB T2P 2M5', 'Stellar Hotels', 3, '123456804', 0),

-- Azure Resorts
('1100 Ocean Drive, Los Angeles, CA 90001', 'Azure Resorts', 5, '234567890', 0),
('1200 Beach Front, San Diego, CA 92101', 'Azure Resorts', 4, '234567893', 0),
('1300 Strip View, Las Vegas, NV 89101', 'Azure Resorts', 5, '234567895', 0),
('1400 Desert Road, Phoenix, AZ 85001', 'Azure Resorts', 3, '234567897', 0),
('1500 River Walk, Austin, TX 78701', 'Azure Resorts', 4, '234567899', 0),
('1600 Downtown, Dallas, TX 75201', 'Azure Resorts', 4, '234567901', 0),
('1700 City Square, Houston, TX 77002', 'Azure Resorts', 3, '234567903', 0),
('1800 French Quarter, New Orleans, LA 70112', 'Azure Resorts', 5, '234567905', 0),

-- Emerald Inns
('2100 Loop Drive, Chicago, IL 60007', 'Emerald Inns', 4, '345678901', 0),
('2200 Downtown, Detroit, MI 48201', 'Emerald Inns', 3, '345678904', 0),
('2300 Lakeside, Cleveland, OH 44113', 'Emerald Inns', 3, '345678906', 0),
('2400 Historic District, Philadelphia, PA 19102', 'Emerald Inns', 4, '345678908', 0),
('2500 Steel City, Pittsburgh, PA 15222', 'Emerald Inns', 3, '345678910', 0);

INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability, Damages) VALUES
('100 Coastal Road, New York, NY 10001', 101, 2, 'TV, AC', 'City', 150.00, FALSE, TRUE, NULL),
('100 Coastal Road, New York, NY 10001', 102, 3, 'TV, AC, Mini Bar', 'City', 200.00, FALSE, TRUE, NULL),
('200 Mountain View, Boston, MA 02108', 201, 2, 'TV, AC', 'City', 120.00, TRUE, TRUE, NULL),
('300 City Center, San Francisco, CA 94102', 301, 4, 'TV, AC, WiFi', 'City', 180.00, FALSE, TRUE, NULL);