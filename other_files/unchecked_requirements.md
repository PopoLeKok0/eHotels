# e-Hotels Project Requirements Checklist

This list tracks the requirements based on the project description, focusing on the web application and database features.

**Note:** Final testing of all implemented features is required. Database views, indexes, and triggers have been executed.

## Database Requirements (from Section 2)

- [x] 2a. Database Implementation (Schema created)
- [x] 2b. Database Population (Initial data likely populated, can be expanded)
- [x] 2c. Database Queries (Basic queries implemented in PHP. Verify specific requirements: >=4 queries, >=1 aggregation, >=1 nested query)
- [x] 2d. Database Modifications/Triggers (Triggers created and executed from `sql/triggers.sql`)
- [x] 2e. Database Indexes (Indexes created and executed from `sql/create_indexes.sql`)
- [x] 2f. Database Views:
    - [x] Create View 1: Number of available rooms per area (View created and executed from `sql/create_views.sql`).
    - [x] Create View 2: Aggregated capacity of all rooms of a specific hotel (View created and executed from `sql/create_views.sql`).

## Web Application (Requirement 2g)

**Core Functionality:**
- [x] Design and implement User Interface (Basic structure exists)
- [x] Search available rooms with multiple criteria:
    - [x] Dates (Start, End)
    - [x] Room Capacity
    - [x] Area
    - [x] Hotel Chain
    - [x] Category (Star Rating)
    - [x] Total number of rooms in the hotel
    - [x] Price Range
- [x] Show available choices dynamically as criteria change.
- [x] User Roles:
    - [x] Customer: Search rooms, Make bookings (`search.php`, `process_booking.php` - `my_bookings.php` needs review).
    - [x] Employee:
        - [x] Turn booking to renting (Check-in process - `employee/check_in.php` implemented, needs testing).
        - [x] Direct renting without prior booking (`employee/direct_rental.php` implemented, needs testing).
- [x] Display the 2 specific SQL Views (from 2f) in the UI (`employee/index.php`).

**Data Management (CRUD):**
- [x] Customers (via `employee/manage_customers.php` - Verify Add/Edit/Delete)
- [x] Employees (via `employee/` files - Add/Edit/Delete implemented)
- [x] Hotel Chains (via `hotel_chain/` files - Add/Edit/Delete implemented)
- [x] Hotels (via `hotel/` files - Add/Edit/Delete implemented)
- [x] Rooms (via `hotel/manage_rooms.php` - Add/Edit/Delete implemented)
- [x] Hotel Chain Contact Info (Emails/Phones - multiple) (via `hotel_chain/manage_chain_contacts.php`)
- [x] Hotel Contact Info (Emails/Phones - multiple) (via `hotel/manage_hotel_contacts.php`)

**User Experience:**
- [x] User friendly (no SQL knowledge required).
- [x] Use appropriate elements (dropdowns, etc.).

## Deliverables (2nd Deliverable)

- [ ] Report:
    - [ ] DBMS and programming languages used.
    - [ ] Specific installation steps.
    - [ ] List of DDLs.
- [ ] SQL Code (All supporting code).
- [ ] Application Code (All necessary code).
- [ ] Video Presentation (Covering points 1-9).
- [ ] Filled Table 1 (Video timestamps). 