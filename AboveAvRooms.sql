SELECT Hotel_Address, Star_Rating, Num_Rooms
FROM Hotel
WHERE Num_Rooms > (SELECT AVG(Num_Rooms) FROM Hotel);
