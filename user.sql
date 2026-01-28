-- Active: 1739328966407@@127.0.0.1@3306@company_db
-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample users with plain text passwords
INSERT INTO users (username, password) VALUES
('user1', 'user1'),
('user2', 'user2'),
('user3', 'user3');
-- new id : PrincePatel password is : Ekamanu@24
