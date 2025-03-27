--Index on Customer_ID for Faster Booking Lookups.This index helps in queries that search for bookings by a specific customer.
--Index on Renting (Start_Date), Renting(End_Date) for Faster Availability Checks.
--When checking if a room is available, we often query for overlapping dates.
CREATE INDEX idx_booking_customer_id ON booking (customer_ID);
CREATE INDEX idx_renting_start_dates ON renting (start_Date);
CREATE INDEX idx_renting_end_dates ON renting (end_Date);