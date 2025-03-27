--This view will show the number of available rooms in each area
--(we assume "area" refers to Hotel_Address since hotels are uniquely identified by their addresses).
CREATE VIEW Available_Rooms_Per_Area AS
SELECT h.Hotel_Address, COUNT(r.Room_Num) AS Available_Rooms
FROM Hotel h
JOIN Room r ON h.Hotel_Address = r.Hotel_Address
WHERE r.Availability = TRUE
GROUP BY h.Hotel_Address;

--This view will show the total capacity (sum of all room capacities) for each hotel.
CREATE VIEW Aggregated_Capacity_Per_Hotel AS
SELECT h.Hotel_Address, SUM(r.Capacity) AS Total_Capacity
FROM Hotel h
JOIN Room r ON h.Hotel_Address = r.Hotel_Address
GROUP BY h.Hotel_Address;


