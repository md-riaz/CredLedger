# CredLedger-Lite

> A tiny, self-hostable "who has what" secrets ledger

CredLedger-Lite is a lightweight, self-hosted credential management system built with OOP PHP and SQLite. It tracks who has access to what credentials, when they were granted access, and provides a complete audit trail.

## Features

- 🔐 **Secure Credential Storage** - Secrets encrypted with libsodium envelope encryption
- 👥 **User Management** - Multi-user support with role-based access (admin/user)
- 🔑 **Two-Factor Authentication** - TOTP-based 2FA for enhanced security
- 📝 **Access Request Workflow** - Request and approval system for credential access
- ⏱️ **Time-Boxed Grants** - Credentials with automatic expiration
- 📥📤 **Checkout/Check-in System** - Track when credentials are actively being used
- 🚪 **One-Click Offboarding** - Instantly revoke all grants for a user
- 📊 **Append-Only Audit Log** - Complete trail of all actions
- 🎨 **Simple PHP Views** - No framework, just clean PHP and minimal CSS
- 🚀 **PHP Built-in Server** - Quick setup with no external web server needed

## Requirements

- PHP 8.1 or higher
- PHP Extensions: PDO, SQLite3, Sodium
- Composer (for dependencies)

## Quick Start

### 1. Clone and Setup

```bash
git clone https://github.com/md-riaz/CredLedger.git
cd CredLedger

# Install dependencies
composer install

# Copy environment file
cp .env.example .env
```

### 2. Generate Encryption Key

```bash
php cli/generate-key.php
```

Copy the generated key to your `.env` file:

```env
DB_PATH=data/credledger.db
ENCRYPTION_KEY=your_generated_key_here
```

### 3. Run Migrations

```bash
php cli/migrate.php
```

### 4. Seed Sample Data (Optional)

```bash
php cli/seed.php
```

This creates test users and sample data:
- Admin: `admin@example.com` / `admin123`
- User 1: `alice@example.com` / `password123`
- User 2: `bob@example.com` / `password123`

### 5. Start the Server

```bash
php -S localhost:8000 -t public
```

Visit http://localhost:8000 to access the application.

## Usage Guide

### For Regular Users

1. **Login** - Use your credentials to access the system
2. **Browse Secrets** - View available credentials in the system
3. **Request Access** - Submit a request with reason and duration
4. **View Grants** - Once approved, view your active grants
5. **Checkout Credential** - Click to reveal the credential value
6. **Check-in** - Return the credential when finished

### For Administrators

1. **Approve/Reject Requests** - Review access requests from users
2. **Manage Users** - View all users, enable/disable accounts
3. **Manage Secrets** - Create new secrets, view existing ones
4. **Manage Grants** - View all grants, revoke access
5. **Offboard Users** - One-click to revoke all grants and deactivate user

### Two-Factor Authentication

1. Go to **Profile** page
2. Click **Setup 2FA**
3. Scan QR code with authenticator app (Google Authenticator, Authy, etc.)
4. Enter verification code to enable

## Project Structure

```
CredLedger/
├── cli/                    # Command-line tools
│   ├── migrate.php         # Run database migrations
│   ├── seed.php            # Seed sample data
│   └── generate-key.php    # Generate encryption key
├── data/                   # SQLite database (created automatically)
├── migrations/             # SQL migration files
├── public/                 # Web-accessible files
│   ├── index.php           # Login page
│   ├── dashboard.php       # User dashboard
│   ├── secrets.php         # Browse secrets
│   ├── grants.php          # User's grants
│   ├── admin.php           # Admin dashboard
│   └── ...
├── src/
│   ├── Database/           # Database connection and migrations
│   ├── Models/             # Data models (User, Secret, Grant, etc.)
│   ├── Services/           # Business logic (Auth, Encryption, TOTP, Audit)
│   ├── Views/              # Layout template
│   └── bootstrap.php       # Application initialization
├── tests/                  # Unit tests
├── .env.example            # Environment template
├── .gitignore
├── composer.json
└── README.md
```

## Database Schema

- **users** - User accounts with 2FA support
- **secrets** - Encrypted credentials
- **access_requests** - Access request workflow
- **grants** - Time-boxed credential grants with checkout tracking
- **audit_log** - Append-only log of all actions

## Security Features

### Encryption
- Secrets encrypted with libsodium's secretbox (XSalsa20-Poly1305)
- Envelope encryption with master key
- Unique nonce per encryption

### Authentication
- Password hashing with bcrypt
- Optional TOTP-based 2FA
- Session management with regeneration

### Audit Trail
- All actions logged with timestamp
- User, IP address, and user agent recorded
- Append-only log (no deletions)

## CLI Commands

### Migrate Database
```bash
php cli/migrate.php
```

### Seed Sample Data
```bash
php cli/seed.php
```

### Generate Encryption Key
```bash
php cli/generate-key.php
```

## Development

### Running Tests
```bash
composer test
```

### Directory Permissions
Ensure the `data/` directory is writable:
```bash
chmod 755 data/
```

## Configuration

Edit `.env` file:

```env
# Database location
DB_PATH=data/credledger.db

# 256-bit encryption key (hex)
ENCRYPTION_KEY=your_key_here

# Application settings
APP_NAME=CredLedger-Lite
APP_URL=http://localhost:8000
SESSION_LIFETIME=3600

# Environment
APP_ENV=production
APP_DEBUG=false
```

## Production Deployment

1. **Use a proper web server** - Apache/Nginx instead of built-in server
2. **Secure your environment** - Set `APP_DEBUG=false`
3. **Protect database** - Ensure SQLite file is outside web root or protected
4. **HTTPS only** - Use SSL/TLS for all connections
5. **Backup regularly** - Schedule backups of the database file
6. **Rotate keys** - Plan for encryption key rotation
7. **Monitor logs** - Review audit logs regularly

### Example Nginx Config
```nginx
server {
    listen 80;
    server_name credledger.example.com;
    root /path/to/CredLedger/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

## Troubleshooting

**Database locked error**
- Ensure only one process accesses the database
- Check file permissions on the database

**Encryption errors**
- Verify ENCRYPTION_KEY is 64 hex characters
- Check that sodium extension is enabled

**2FA not working**
- Ensure system time is synchronized (TOTP is time-based)
- Check that QR code was scanned correctly

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

MIT License - see LICENSE file for details

## Author

MD Riaz

## Support

For issues and questions, please open an issue on GitHub.
