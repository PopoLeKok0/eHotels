SELECT b.Booking_ID, c.Full_Name, b.Start_Date, b.End_Date
FROM Booking b
JOIN Customer c ON b.Customer_ID = c.Customer_ID;
