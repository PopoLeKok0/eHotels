-- Update customer 'cust-001' with test login data
UPDATE Customer
SET 
    ID_Type = 'Driver License',
    ID_Value = 'TEST-LICENSE-123',
    Email_Address = 'testcustomer@example.com',
    Password_Hash = 'password123' -- TEMPORARY PLAINTEXT - REPLACE WITH HASH LATER
WHERE Customer_ID = 'cust-001';

-- Select to verify (optional)
SELECT Customer_ID, Full_Name, ID_Type, ID_Value, Email_Address, Password_Hash 
FROM Customer 
WHERE Customer_ID = 'cust-001'; 