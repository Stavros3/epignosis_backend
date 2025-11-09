-- Vacation Management System Database Schema
-- Run this SQL to create the necessary tables

-- Create vacations_status table
CREATE TABLE IF NOT EXISTS vacations_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default statuses
INSERT INTO vacations_status (id, status) VALUES
(1, 'APPROVED'),
(2, 'REJECTED'),
(3, 'PENDING')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Create vacations table
CREATE TABLE IF NOT EXISTS vacations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status_id INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES vacations_status(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status_id (status_id),
    INDEX idx_created_at (created_at),
    INDEX idx_date_from (date_from),
    INDEX idx_date_to (date_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Insert some test data (remove in production)
-- Uncomment the lines below if you want sample data

/*
-- Assuming you have users with id 1 (admin) and id 2 (regular user)
INSERT INTO vacations (user_id, date_from, date_to, reason, status_id) VALUES
(2, '2025-12-01', '2025-12-10', 'Family vacation to beach resort', 3),
(2, '2025-11-15', '2025-11-20', 'Personal reasons - attending wedding', 1),
(2, '2025-10-10', '2025-10-12', 'Medical appointment', 2),
(3, '2025-12-20', '2025-12-31', 'Holiday season break', 3);
*/
