--Index on Customer_ID for Faster Booking Lookups
--Index on Renting (Start_Date), Renting(End_Date) for Faster Availability Checks
CREATE INDEX idx_booking_customer_id ON booking (customer_ID);
CREATE INDEX idx_renting_start_dates ON renting (start_Date);
CREATE INDEX idx_renting_end_dates ON renting (end_Date);