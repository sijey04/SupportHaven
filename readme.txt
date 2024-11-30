# SupportHaven

SupportHaven is a comprehensive web application that connects users with expert technicians for tech support services. The platform streamlines the process of booking technical assistance, managing user accounts, and provides a robust admin system for service management.

## Core Features

### For Users
- **Service Booking**: Easy-to-use booking system with step-by-step process
- **Dashboard**: Personal dashboard showing recent and upcoming bookings
- **Service History**: Track past services and provide ratings
- **Profile Management**: Update personal information and preferences

### For Technicians
- **Application System**: Multi-step application process with document verification
- **Profile Management**: Manage expertise, availability, and credentials
- **Service Management**: View and manage assigned service requests
- **Rating System**: Build reputation through customer ratings

### For Administrators
- **User Management**: Comprehensive control over user accounts
- **Technician Verification**: Review and approve technician applications
- **Service Oversight**: Monitor all service bookings and completions
- **Document Management**: Review and verify technician credentials

## Technical Features

### Security
- Secure session management
- Password hashing and encryption
- Role-based access control
- Document verification system
- Two-factor authentication support

### User Interface
- Responsive design for all devices
- Modern, intuitive interface
- Progress tracking for multi-step processes
- Real-time feedback and notifications
- Interactive booking calendar

### Backend
- Robust database architecture
- Efficient query optimization
- File upload handling
- Error logging and handling
- Transaction management

## Technology Stack

- **Frontend**:
  - HTML5/CSS3
  - JavaScript
  - TailwindCSS
  - Font Awesome icons
  - Animate.css for animations

- **Backend**:
  - PHP 7.4+
  - MySQL/MariaDB
  - PDO for database connections
  - Session management

- **Security**:
  - Password hashing (PHP password_hash)
  - Prepared statements
  - CSRF protection
  - XSS prevention

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/sijey04/supporthaven.git
   ```

2. Set up the database:
   - Import `haven.sql` to your MySQL server
   - Configure database connection in `connection.php`

3. Configure your web server:
   - Point document root to the project directory
   - Ensure PHP 7.4+ is installed
   - Enable required PHP extensions (PDO, mysqli)

4. Set up file permissions:
   ```bash
   chmod 755 -R /path/to/project
   chmod 777 -R /path/to/project/uploads
   ```

5. Configure environment:
   - Set up mail server details if needed
   - Configure any API keys required
   - Set appropriate PHP settings in php.ini

## Directory Structure

```
supporthaven/
├── admin/              # Admin panel files
├── css/               # Stylesheets
├── images/            # Image assets
├── js/                # JavaScript files
├── uploads/           # User uploads
│   ├── documents/     # Technician documents
│   └── profile_images/# User profile photos
├── connection.php     # Database configuration
├── index.html         # Landing page
└── README.md         # Documentation
```

## Usage

### User Access
- Visit the homepage and register/login
- Browse available services
- Book appointments with preferred time slots
- Track service status and history

### Technician Access
- Apply through the technician portal
- Submit required documentation
- Manage service requests and schedule
- Update service status and details

### Admin Access
- Access admin panel via /admin
- Manage users and technicians
- Review applications and documents
- Monitor service bookings and completion

## Support

For technical support or queries:
- Email: faminianochristianjude@gmail.com
- Phone: (+63) 97087015677
- Address: Jongstong Subdivision, San Jose Cawa-Cawa, Zamboanga City, 7000

## License

This project is licensed under the MIT License. See LICENSE file for details.

## Credits

Developed by Christian Jude Faminiano

