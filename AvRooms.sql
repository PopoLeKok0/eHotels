SELECT h.Hotel_Address, COUNT(*) AS Available_Rooms
FROM Room r
JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
WHERE r.Availability = TRUE
GROUP BY h.Hotel_Address;
