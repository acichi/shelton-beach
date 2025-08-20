-- Create password reset tokens table for forgot password functionality
-- Run this script in your database to create the required table

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  KEY `used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint if users table exists
-- ALTER TABLE `password_reset_tokens` 
-- ADD CONSTRAINT `fk_password_reset_user` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
-- ON DELETE CASCADE ON UPDATE CASCADE;

-- Optional: Create index for better performance
-- CREATE INDEX idx_token_expires_used ON password_reset_tokens(token, expires_at, used);
