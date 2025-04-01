-- Test updating View_Type for a single room
UPDATE Room
SET View_Type = 'Test City View'
WHERE Hotel_Address = '100 Front Street West, Toronto, ON M5J 1E3' AND Room_Num = 101;

-- Add a SELECT statement to verify immediately (optional, but helpful for debugging)
SELECT Hotel_Address, Room_Num, View_Type 
FROM Room 
WHERE Hotel_Address = '100 Front Street West, Toronto, ON M5J 1E3' AND Room_Num = 101; 