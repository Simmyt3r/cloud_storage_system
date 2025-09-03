# Cloud Storage System with Hierarchical Access Control and Folder-Level Authentication

## Overview
This is a web-based cloud storage system designed for organizational use with hierarchical access control and folder-level authentication. The system allows organizations to securely store, manage, and share files with granular access controls.

## Features
1. **Organization Registration and Approval**
   - Organizations can register for the system
   - Super admin approval is required for new organizations
   - Only approved organizations can have users

2. **User Management**
   - Three user roles: Regular User, Admin, and Super Admin
   - Each organization has its own user base
   - Admins can manage folders and files within their organization
   - Super admins can approve organizations and monitor system-wide activities

3. **Folder Management**
   - Create, view, and delete folders
   - Hierarchical folder structure (parent/child relationships)
   - Optional password protection for folders
   - Breadcrumb navigation for folder paths

4. **File Management**
   - Upload and download files
   - View file information (name, size, type, upload date)
   - Delete files
   - Track file download logs

5. **Access Control**
   - Role-based access control (RBAC)
   - Folder-level permissions (read, write, admin)
   - Password-protected folders require additional authentication
   - Permission management by admins

6. **Activity Monitoring**
   - File download logs
   - Folder access logs
   - Super admin dashboard for system monitoring

## Technology Stack
- **Backend**: PHP 8.2
- **Database**: MariaDB (MySQL compatible)
- **Web Server**: Apache 2.4
- **Frontend**: HTML, CSS, JavaScript

## System Requirements
- Apache web server
- MariaDB or MySQL database
- PHP 7.4 or higher
- php-mysql extension

## Installation
1. Clone or copy the project files to your web server directory
2. Create a database named `cloud_storage`
3. Import the database schema from `database_schema.sql`
4. Update the database connection settings in `includes/db_connection.php` if needed
5. Ensure the `uploads` directory is writable by the web server
6. Access the system through your web browser

## User Roles
1. **Super Admin**
   - Approve/reject organization registration requests
   - Monitor system-wide activities
   - View all organizations and users

2. **Admin**
   - Manage users within their organization
   - Create and manage folders
   - Set folder-level permissions
   - Upload and manage files

3. **Regular User**
   - Access folders based on granted permissions
   - Upload and download files in permitted folders
   - View folder contents

## Security Features
- Password hashing using PHP's `password_hash()` function
- Session management for user authentication
- Folder-level password protection
- Permission-based access control
- Activity logging for security monitoring

## Directory Structure
```
cloud_storage_system/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── controllers/
├── includes/
├── models/
├── uploads/
└── views/
```

## Default Super Admin Credentials
- **Username**: superadmin
- **Password**: password (as defined in the database setup)
- **Email**: superadmin@example.com

## Testing
To test the database connection, access `test_db.php` in your browser.

## License
This project is for educational purposes and demonstrates the implementation of a cloud storage system with hierarchical access control and folder-level authentication.