-- Select the exact Hotel_Address for a specific room
SELECT DISTINCT Hotel_Address 
FROM Room 
WHERE Hotel_Address LIKE '%100 Front Street West, Toronto%' AND Room_Num = 101; 