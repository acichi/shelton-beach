-- Create sub_unit_tbl table for Shelton Beach Resort
-- This table allows facilities to have multiple sub-units (e.g., individual tables, rooms, etc.)

CREATE TABLE IF NOT EXISTS `sub_unit_tbl` (
  `sub_unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `sub_unit_name` varchar(100) NOT NULL,
  `sub_unit_type` enum('table','room','cottage','area','cabana','pavilion') NOT NULL DEFAULT 'table',
  `sub_unit_capacity` int(11) NOT NULL DEFAULT 4,
  `sub_unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sub_unit_status` enum('Available','Unavailable','Maintenance','Reserved') NOT NULL DEFAULT 'Available',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `sub_unit_details` text DEFAULT NULL,
  `sub_unit_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `sub_unit_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sub_unit_id`),
  KEY `fk_sub_unit_facility` (`facility_id`),
  KEY `idx_sub_unit_status` (`sub_unit_status`),
  KEY `idx_sub_unit_available` (`is_available`),
  KEY `idx_sub_unit_type` (`sub_unit_type`),
  CONSTRAINT `fk_sub_unit_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility_tbl` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample sub-units for demonstration
-- These are examples - you can modify or remove them as needed

-- Sample sub-units for a restaurant facility (facility_id = 1)
INSERT INTO `sub_unit_tbl` (`facility_id`, `sub_unit_name`, `sub_unit_type`, `sub_unit_capacity`, `sub_unit_price`, `sub_unit_details`) VALUES
(1, 'Table 1', 'table', 4, 500.00, 'Window seat with ocean view'),
(1, 'Table 2', 'table', 6, 750.00, 'Corner table, perfect for groups'),
(1, 'Table 3', 'table', 2, 300.00, 'Intimate table for couples'),
(1, 'Table 4', 'table', 8, 1000.00, 'Large table for family gatherings');

-- Sample sub-units for a cottage facility (facility_id = 2)
INSERT INTO `sub_unit_tbl` (`facility_id`, `sub_unit_name`, `sub_unit_type`, `sub_unit_capacity`, `sub_unit_price`, `sub_unit_details`) VALUES
(2, 'Cottage A', 'cottage', 6, 2000.00, 'Beachfront cottage with private access'),
(2, 'Cottage B', 'cottage', 4, 1500.00, 'Garden view cottage with amenities'),
(2, 'Cottage C', 'cottage', 8, 2500.00, 'Family-sized cottage with kitchen');

-- Sample sub-units for a room facility (facility_id = 3)
INSERT INTO `sub_unit_tbl` (`facility_id`, `sub_unit_name`, `sub_unit_type`, `sub_unit_capacity`, `sub_unit_price`, `sub_unit_details`) VALUES
(3, 'Room 101', 'room', 2, 1200.00, 'Standard room with queen bed'),
(3, 'Room 102', 'room', 3, 1500.00, 'Deluxe room with king bed and sofa'),
(3, 'Room 103', 'room', 4, 1800.00, 'Family room with two queen beds');

-- Add indexes for better performance
CREATE INDEX `idx_sub_unit_facility_status` ON `sub_unit_tbl` (`facility_id`, `sub_unit_status`);
CREATE INDEX `idx_sub_unit_facility_available` ON `sub_unit_tbl` (`facility_id`, `is_available`);

-- Add comments for documentation
ALTER TABLE `sub_unit_tbl` COMMENT = 'Sub-units within facilities (tables, rooms, cottages, etc.)';
