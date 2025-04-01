-- Add Number_of_Hotels column to Hotel_Chain table
ALTER TABLE Hotel_Chain ADD COLUMN Number_of_Hotels INT DEFAULT 0;

-- Update Number_of_Hotels for each chain based on actual hotel count
UPDATE Hotel_Chain hc 
SET Number_of_Hotels = (
    SELECT COUNT(*) 
    FROM Hotel h 
    WHERE h.Chain_Name = hc.Chain_Name
); 