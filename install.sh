#!/bin/bash

# Cloud Storage System Installation Script

echo "Cloud Storage System Installation"
echo "=================================="

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo "This script should not be run as root"
   exit 1
fi

# Create uploads directory if it doesn't exist
if [ ! -d "uploads" ]; then
    mkdir uploads
    echo "Created uploads directory"
fi

# Set permissions for uploads directory
chmod 755 uploads
echo "Set permissions for uploads directory"

# Check if database exists
DB_EXISTS=$(mysql -u root -e "SHOW DATABASES LIKE 'cloud_storage';" | grep -c cloud_storage)

if [ $DB_EXISTS -eq 0 ]; then
    echo "Creating database..."
    mysql -u root -e "CREATE DATABASE cloud_storage;"
    echo "Database created successfully"
else
    echo "Database already exists"
fi

# Import database schema
echo "Importing database schema..."
mysql -u root cloud_storage < database_schema.sql
echo "Database schema imported successfully"

# Create database user
echo "Creating database user..."
mysql -u root -e "CREATE USER 'cloud_user'@'localhost' IDENTIFIED BY 'cloud_password';"
mysql -u root -e "GRANT ALL PRIVILEGES ON cloud_storage.* TO 'cloud_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"
echo "Database user created successfully"

echo "Installation completed!"
echo "Access the system at http://localhost/cloud_storage_system/"