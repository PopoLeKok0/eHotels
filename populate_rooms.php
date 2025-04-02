<?php
/**
 * e-Hotels - Populate Rooms Script
 * 
 * This script automatically generates and inserts room data for all existing hotels
 * to meet the project requirement of having at least 5 rooms of different capacities per hotel.
 */

require_once 'config/database.php';

echo "<h1>Populating Rooms</h1>";

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connection successful.<br>";
    flush();

    // Fetch all hotels with their star rating
    $stmtHotels = $db->query("SELECT Hotel_Address, Star_Rating FROM Hotel");
    $hotels = $stmtHotels->fetchAll(PDO::FETCH_ASSOC);

    if (empty($hotels)) {
        throw new Exception("No hotels found in the database. Run the main population script first.");
    }

    echo "Found " . count($hotels) . " hotels to populate rooms for.<br><br>";
    flush();

    // Prepare the INSERT statement for rooms
    $roomInsertSql = "INSERT INTO Room (Hotel_Address, Room_Num, Capacity, Amenities, View_Type, Price, Extendable, Availability, Damages) 
                        VALUES (:address, :num, :capacity, :amenities, :view, :price, :extendable, TRUE, NULL)";
    $stmtRoomInsert = $db->prepare($roomInsertSql);

    // Prepare the UPDATE statement for hotel room count
    $hotelUpdateSql = "UPDATE Hotel SET Num_Rooms = :count WHERE Hotel_Address = :address";
    $stmtHotelUpdate = $db->prepare($hotelUpdateSql);

    $totalRoomsAdded = 0;
    $roomNumCounter = 101; // Start room numbers from 101 for each hotel

    // Define base price and amenities based on stars
    $starDetails = [
        1 => ['price' => 60, 'amenities' => 'Wi-Fi, TV'],
        2 => ['price' => 80, 'amenities' => 'Wi-Fi, TV, Phone'],
        3 => ['price' => 110, 'amenities' => 'Wi-Fi, TV, Phone, Coffee Maker, Safe'],
        4 => ['price' => 150, 'amenities' => 'Wi-Fi, TV, Phone, Coffee Maker, Safe, Mini Bar, Desk'],
        5 => ['price' => 220, 'amenities' => 'Wi-Fi, TV, Phone, Coffee Maker, Safe, Mini Bar, Desk, Bathtub, Premium Toiletries']
    ];

    $capacities = [1, 2, 2, 3, 4, 5]; // Ensure at least 5 unique capacities (1, 2, 3, 4, 5)
    $views = ['City', 'Garden', 'Pool', 'Mountain', 'Sea', 'None'];
    $viewIndex = 0;

    // Begin transaction for potentially faster inserts
    $db->beginTransaction();

    foreach ($hotels as $hotel) {
        $hotelAddress = $hotel['Hotel_Address'];
        $starRating = $hotel['Star_Rating'];
        $basePrice = $starDetails[$starRating]['price'];
        $baseAmenities = $starDetails[$starRating]['amenities'];
        $roomsAddedForHotel = 0;
        $roomNumCounter = 101;
        
        echo "Processing Hotel: " . htmlspecialchars($hotelAddress) . " ($starRating stars)...<br>";
        flush();

        foreach ($capacities as $capacity) {
            $roomNum = $roomNumCounter++;
            $view = $views[$viewIndex % count($views)];
            $viewIndex++;
            
            // Adjust price based on capacity and view
            $price = $basePrice + ($capacity * 15) + ($view === 'Sea' || $view === 'Mountain' ? 30 : ($view === 'Pool' ? 15 : 0));
            $extendable = ($capacity >= 3); // Example logic for extendable
            $amenities = $baseAmenities . ($capacity >= 3 ? ', Sofa Bed option' : '') . ($extendable ? ', Extendable' : '');

            try {
                $stmtRoomInsert->bindParam(':address', $hotelAddress);
                $stmtRoomInsert->bindParam(':num', $roomNum, PDO::PARAM_INT);
                $stmtRoomInsert->bindParam(':capacity', $capacity, PDO::PARAM_INT);
                $stmtRoomInsert->bindParam(':amenities', $amenities);
                $stmtRoomInsert->bindParam(':view', $view);
                $stmtRoomInsert->bindParam(':price', $price);
                $stmtRoomInsert->bindParam(':extendable', $extendable, PDO::PARAM_BOOL);
                
                $stmtRoomInsert->execute();
                $roomsAddedForHotel++;
                $totalRoomsAdded++;

            } catch (PDOException $e) {
                // Handle potential duplicate room number error (if script run twice)
                if ($e->getCode() == '23000' || $e->getCode() == '23505') { // Integrity constraint violation
                    echo "<span style='color:orange;'>Warning: Room $roomNum for hotel '$hotelAddress' likely already exists. Skipping.</span><br>";
                } else {
                    throw $e; // Re-throw other errors
                }
            }
        }

        // Update the Num_Rooms count for the hotel
        // Fetch current count first to avoid overwriting if script runs partially
        $stmtGetCount = $db->prepare("SELECT COUNT(*) FROM Room WHERE Hotel_Address = :address");
        $stmtGetCount->bindParam(':address', $hotelAddress);
        $stmtGetCount->execute();
        $currentRoomCount = $stmtGetCount->fetchColumn();

        $stmtHotelUpdate->bindParam(':count', $currentRoomCount, PDO::PARAM_INT);
        $stmtHotelUpdate->bindParam(':address', $hotelAddress);
        $stmtHotelUpdate->execute();

        echo "<span style='color:green;'>Added/Verified $roomsAddedForHotel rooms for " . htmlspecialchars($hotelAddress) . ". Total rooms in hotel: $currentRoomCount.</span><br><br>";
        flush();
    }

    // Commit transaction
    $db->commit();

    echo "<br><strong style='color:blue;'>Finished populating rooms. Total rooms added/verified in this run: $totalRoomsAdded</strong><br>";

} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<br><strong style='color:red;'>Error: " . $e->getMessage() . "</strong><br>";
    error_log("Room Population Error: " . $e->getMessage());
}

?> 