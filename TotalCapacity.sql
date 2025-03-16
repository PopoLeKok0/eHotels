SELECT h.Hotel_Address, SUM(r.Capacity) AS Total_Capacity
FROM Hotel h
JOIN Room r ON h.Hotel_Address = r.Hotel_Address
GROUP BY h.Hotel_Address;
