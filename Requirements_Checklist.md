# e-Hotels Project - Requirements Checklist for A+ Grade

This checklist ensures all requirements from the project description are met for the highest possible grade.

## Database Implementation (2a - 10%)

- [ ] Create complete database schema based on Deliverable 1
- [ ] Implement all primary key constraints
- [ ] Implement all foreign key constraints
- [ ] Implement all domain and attribute constraints
- [ ] Implement all user-defined constraints
- [ ] Ensure referential integrity for all relationships

## Database Population (2b - 5%)

- [ ] Insert data for 5 hotel chains with unique names and central office addresses
- [ ] Each hotel chain must have at least 8 hotels
- [ ] Hotels must belong to at least 3 different star categories
- [ ] At least 2 hotels must be in the same area
- [ ] Each hotel must have at least 5 rooms of different capacity
- [ ] Include sufficient sample data for customers, employees, bookings, and rentings

## Database Queries (2c - 10%)

- [ ] Implement at least 4 queries total
- [ ] At least 1 query must use aggregation
- [ ] At least 1 query must be a nested query
- [ ] Queries must demonstrate useful functionality for the hotel system

## Database Modifications & Triggers (2d - 10%)

- [ ] Allow insert, delete, and update operations that respect all constraints
- [ ] Implement at least 2 triggers
- [ ] Ensure triggers enforce user-defined constraints
- [ ] Handle archiving of booking/renting history when rooms or customers are deleted

## Database Indexes (2e - 5%)

- [ ] Implement at least 3 indexes on database relations
- [ ] Provide justification for each index
- [ ] Explain what queries will be accelerated by each index
- [ ] Explain how indexes improve overall database performance

## Database Views (2f - 5%)

- [ ] Implement View 1: Number of available rooms per area
- [ ] Implement View 2: Aggregated capacity of all rooms of a specific hotel
- [ ] Optional: Implement additional custom views

## Web Application (2g - 30%)

- [ ] Implement room search with multiple filter criteria:
  - [ ] Dates (start, end) of booking/renting
  - [ ] Room capacity
  - [ ] Area
  - [ ] Hotel chain
  - [ ] Category of hotel (star rating)
  - [ ] Total number of rooms in hotel
  - [ ] Price range
- [ ] Real-time updating of available choices when criteria change
- [ ] Support for two user roles:
  - [ ] Customer role for searching and booking
  - [ ] Employee role for check-ins and direct rentals
- [ ] Customer features:
  - [ ] Registration and login
  - [ ] Room searching and filtering
  - [ ] Room booking
  - [ ] View booking history
- [ ] Employee features:
  - [ ] Convert bookings to rentings at check-in
  - [ ] Process direct rentings (without prior booking)
  - [ ] Insert customer payment for rentings
  - [ ] Manage customer, employee, hotel, and room data
- [ ] User interface integration of database views
- [ ] User-friendly interface with forms, dropdowns, radio buttons, etc.

## Report and Documentation

- [ ] Report with technologies used
- [ ] Installation guide
- [ ] List of DDL statements
- [ ] All SQL code supporting application functionality
- [ ] All application code (PHP, HTML, JavaScript, CSS)

## Video Presentation

- [ ] 10-15 minute video (max 30MB)
- [ ] Cover all 9 required presentation points:
  1. [ ] Software technologies used
  2. [ ] Relational database schema
  3. [ ] Integrity constraints
  4. [ ] Database population
  5. [ ] SQL query execution
  6. [ ] Trigger demonstration
  7. [ ] Index implementation
  8. [ ] View implementation
  9. [ ] User interface demonstration
- [ ] Complete timestamp table for each requirement

## Critical Requirements for Perfect Score

- [ ] Full referential integrity
- [ ] Room availability shown in real-time
- [ ] Booking to renting conversion
- [ ] Direct renting capability
- [ ] Archival of booking/renting history
- [ ] Both user roles (customer and employee) fully functional
- [ ] All specified filtering criteria for room search
- [ ] Both database views properly implemented and displayed
- [ ] Proper transaction handling and data validation 