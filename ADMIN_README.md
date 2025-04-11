# Resume Builder Admin Panel

This document provides instructions on how to set up and use the admin panel for the Resume Builder application.

## Setup Instructions

1. **Database Setup**
   - Navigate to `admin_setup.php` in your browser
   - This will create the necessary database tables and default roles/permissions
   - A default admin user will be created with the following credentials:
     - Email: admin@example.com
     - Password: admin123
   - **Important:** Change the default password after your first login!

2. **Accessing the Admin Panel**
   - Navigate to `admin_login.php` to access the admin login page
   - Log in with your admin credentials
   - You will be redirected to the admin dashboard

## Admin Panel Features

The admin panel provides the following features based on user permissions:

### Dashboard
- Overview of system statistics
- Recent activity
- Quick access to common tasks

### User Management (requires 'manage_users' permission)
- View all users
- Edit user roles and permissions
- Activate/deactivate users

### Resume Management (requires 'manage_resumes' permission)
- View all resumes
- Delete resumes
- View resume details

### Template Management (requires 'manage_templates' permission)
- Add, edit, and delete templates
- Set template status (active, inactive, draft)
- Upload template thumbnails

### Analytics (requires 'view_analytics' permission)
- View system usage statistics
- Track user activity

## User Roles and Permissions

The system uses a role-based access control system:

### Admin Role
- Full access to all features
- Can manage users, resumes, templates, and view analytics

### Editor Role
- Limited access to manage resumes and templates
- Can view analytics

### User Role
- Regular user with no admin access

## Security Considerations

1. **Password Security**
   - Always use strong passwords
   - Change the default admin password immediately after setup
   - Regularly update passwords

2. **Access Control**
   - Only grant admin access to trusted users
   - Regularly review user permissions
   - Remove unnecessary permissions

3. **Session Management**
   - Log out when finished using the admin panel
   - Use the dedicated admin logout page

## Troubleshooting

If you encounter issues with the admin panel:

1. **Login Issues**
   - Verify your credentials
   - Check if your account has the necessary permissions
   - Clear your browser cache and cookies

2. **Permission Issues**
   - Ensure your user account has the correct roles assigned
   - Check if the roles have the necessary permissions

3. **Database Issues**
   - Run the setup script again to ensure all tables are created correctly
   - Check database connection settings in `config.php`

## Support

For additional support, please contact the system administrator. 