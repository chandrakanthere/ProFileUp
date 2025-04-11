-- Add specific admin user
-- This script adds a user with email chandrakant_236053@saitm.ac.in as an admin

-- First, ensure the admin role exists
INSERT IGNORE INTO roles (name, description) VALUES
('admin', 'Administrator with full access');

-- Get the admin role ID
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1);

-- Add the user if not exists
INSERT IGNORE INTO users (name, email, password, created_at)
VALUES ('Chandrakant', 'chandrakant_236053@saitm.ac.in', '$2y$10$8KzO8NxX5X5X5X5X5X5X5O8KzO8NxX5X5X5X5X5X5X5O8KzO8NxX5', NOW());

-- Get the user ID
SET @user_id = (SELECT id FROM users WHERE email = 'chandrakant_236053@saitm.ac.in' LIMIT 1);

-- Assign admin role to the user if not already assigned
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (@user_id, @admin_role_id);

-- Ensure all necessary permissions exist
INSERT IGNORE INTO permissions (name, description) VALUES
('manage_users', 'Can manage users'),
('manage_roles', 'Can manage roles'),
('manage_permissions', 'Can manage permissions'),
('view_dashboard', 'Can view dashboard'),
('manage_settings', 'Can manage settings'),
('manage_content', 'Can manage content'),
('view_reports', 'Can view reports'),
('manage_backups', 'Can manage backups'),
('access_admin', 'Can access admin panel'),
('manage_templates', 'Can manage resume templates'),
('manage_subscriptions', 'Can manage subscriptions'),
('view_analytics', 'Can view analytics');

-- Delete existing role permissions for admin role to avoid duplicates
DELETE FROM role_permissions WHERE role_id = @admin_role_id;

-- Assign all permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin_role_id, id FROM permissions; 