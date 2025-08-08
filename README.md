# Whiteout Survival - Bear Hunt Map Management System

A comprehensive map management system with RESTful API endpoints, intelligent map generation algorithms, dynamic SVG visualization, advanced furnace management with chief gear properties, and robust user authentication system.

## 💝 **Support This Project**

If you find this project helpful, please consider supporting its development:

[![PayPal](https://img.shields.io/badge/PayPal-Donate-blue?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/ekilrain)
[![Venmo](https://img.shields.io/badge/Venmo-Donate-green?style=for-the-badge&logo=venmo)](https://venmo.com/theophila)

Your support helps maintain and improve this open-source project! 🚀

## 🏗️ **Architecture Overview**

This system follows **Clean Architecture** and **Domain-Driven Design (DDD)** principles with clear separation of concerns:

### **Domain Layer** (`src/Domain/`)
- **Map Objects**: `Trap`, `MiscObject`, `Furnace` - Core business entities with collision detection
- **Map**: Main aggregate root managing map objects and collision detection
- **MapGenerator**: Domain service for intelligent furnace placement algorithms with spiral positioning
- **SvgGenerator**: Domain service for SVG generation and visualization
- **CriteriaWeight**: Domain entity for weighted criteria configuration
- **User**: User domain entity with authentication and authorization logic

### **Application Layer** (`src/Application/`)
- **MapService**: Orchestrates map operations, business logic, and CSV import/export
- **MapGenerationService**: Coordinates map generation workflows
- **SvgService**: Manages SVG generation and file operations
- **AuthenticationService**: Handles user authentication, registration, and session management
- **EmailService**: Manages email notifications and verification
- **ExcelService**: Handles Excel/CSV import/export operations
- **WeightedCriteriaService**: Manages weighted criteria calculations for furnace prioritization

### **Infrastructure Layer** (`src/Infrastructure/`)
- **Api Controllers**: Handle HTTP requests and responses
- **DatabaseMapRepository**: Data persistence using MySQL/PostgreSQL
- **DatabaseUserRepository**: User data persistence
- **DatabaseConnection**: Database connection management
- **API Endpoints**: Consolidated RESTful endpoints for all operations

## 🚀 **API Endpoints**

All endpoints are consolidated in `/api.php` and `/auth.php` for simplicity and consistency.

### **Authentication Endpoints** (`/auth.php`)
- `POST /auth.php?action=register` - Register a new user with email verification
- `POST /auth.php?action=login` - User login with rate limiting
- `POST /auth.php?action=verify_otp` - Verify OTP code for non-master users
- `GET /verify-email.php?token=X` - Verify email address
- `POST /auth.php?action=request_password_reset` - Request password reset
- `POST /auth.php?action=reset_password` - Reset password with token
- `POST /auth.php?action=approve_user` - Approve user (master only)
- `POST /auth.php?action=deactivate_user` - Deactivate user (master only)
- `POST /auth.php?action=delete_user` - Delete user (master only)
- `GET /auth.php?action=get_pending_approvals` - Get pending approvals (master only)
- `GET /auth.php?action=get_all_users` - Get all users (master only)
- `GET /auth.php?action=check_session` - Check current session status
- `POST /auth.php?action=logout` - Logout user

### **Map Management**
- `POST /api.php?action=create_map` - Create a new map
- `GET /api.php?action=get_map` - Get map data
- `GET /api.php?action=get_all_maps` - List all maps

### **Object Management**
- `POST /api.php?action=add_trap` - Add a trap
- `POST /api.php?action=add_object` - Add miscellaneous object (any size ≥ 1)
- `POST /api.php?action=add_furnace` - Add a furnace with chief gear properties
- `PUT /api.php?action=update_furnace` - Update furnace properties (preserves chief gear)
- `DELETE /api.php?action=delete_trap` - Remove a trap
- `DELETE /api.php?action=delete_object` - Remove miscellaneous object
- `DELETE /api.php?action=delete_furnace` - Remove a furnace

### **Bulk Operations**
- `PUT /api.php?action=update_all_furnaces` - Bulk update furnaces with collision detection
- `PUT /api.php?action=reset_furnaces` - Reset all furnace positions
- `PUT /api.php?action=reset` - Reset entire map

### **Map Generation & Visualization**
- `POST /api.php?action=generate_map` - Generate optimal furnace placement
- `POST /api.php?action=regenerate_svg` - Generate SVG visualization
- `POST /api.php?action=get_occupied_positions` - Get occupied positions
- `GET /map.svg?map_id=X&version=Y` - Dynamic SVG generation (handled by .htaccess rewrite)

### **Version Management**
- `PUT /api.php?action=save_version` - Save current map as version
- `GET /api.php?action=get_version` - Load specific version
- `GET /api.php?action=get_versions` - List all versions
- `DELETE /api.php?action=delete_version` - Delete version

### **CSV Import/Export**
- `GET /api.php?action=download_template&map_id=X` - Download Excel template
- `GET /api.php?action=export_furnaces&map_id=X` - Export furnaces to Excel
- `POST /api.php?action=upload_csv&map_id=X` - Import furnaces from Excel/CSV

## 🔧 **Key Features**

### **1. Advanced User Authentication System**
- **User Registration**: Self-registration with email verification and OTP setup
- **Master User**: Administrative user with full system access and user management
- **OTP Security**: One-time password required for all non-master users
- **Password Reset**: Secure password reset via email with token expiration
- **User Approval**: Master user can approve, deactivate, or delete user registrations
- **User Management**: Admin interface for managing all users with status tracking
- **Session Management**: Secure session handling with token-based authentication
- **Rate Limiting**: Protection against brute force attacks (5 attempts per 15 minutes)
- **Email Notifications**: Automated emails for verification, password reset, and approvals

### **2. Intelligent Map Generation Algorithm**
- **Spiral Placement**: Furnaces placed in expanding spiral from trap zones
- **Trap Preferences**: Respects furnace trap preferences (1, 2, both, n/a)
- **Chief Gear Priority**: Prioritizes furnaces based on mean gear levels and charms
- **Locked Furnaces**: Preserves positions of locked furnaces during generation
- **Configurable Sorting**: Customizable priority order with weighted criteria
- **Zone-Based Positioning**: Intelligent positioning around 4x4 trap zones
- **Collision Prevention**: Comprehensive collision detection and avoidance

### **3. Chief Gear System**
- **6 Gear Types**: Cap, Watch, Vest, Pants, Ring, Cane
- **41 Gear Levels**: From Uncommon to Legendary T3 *** with comprehensive validation
- **Charm System**: 3 comma-separated charm levels per gear (1-16 range)
- **Smart Prioritization**: Ring/Cane with highest mean levels and charms prioritized first
- **Data Preservation**: Chief gear data preserved during updates and operations
- **CSV Integration**: Full import/export support for chief gear data in Excel format
- **Editable Table Columns**: Gear columns positioned before Rank column with inline editing
- **Toggle Visibility**: Show/Hide gear columns with clean column grouping
- **Validation**: Comprehensive validation for gear levels and charm values

### **4. Furnace Status System**
- **Unassigned**: No color (default state)
- **Assigned**: Blue (#2DCCFF) - Furnace has been assigned a location on the map
- **Messaged**: Orange (#FFAF3D) - Player has been contacted about their assigned furnace location
- **Moved**: Green (#00E200) - Player has moved their furnace to the correct location
- **Wrong Spot**: Red (#FF2A04) - Furnace is in an incorrect location
- **Unsaved Edits**: Yellow (#FAD800) - Furnace data has been modified but not saved (takes visual precedence over status colors)
- **Visual Feedback**: Status colors are displayed both on the map SVG and in the furnace table rows

### **5. Dynamic SVG Visualization**
- **Real-time Generation**: SVG generated dynamically based on map state
- **Dynamic Sizing**: SVG dimensions automatically calculated based on content bounds
- **Status Colors**: Visual indicators for furnace status with proper color coding
- **Coordinate Display**: Shows object coordinates on the map for easy reference
- **Responsive Design**: Adapts to different screen sizes and orientations
- **Version Support**: Displays specific map versions with proper data isolation
- **Server-Compatible**: Uses .htaccess rewrite rules for servers that don't process .svg as PHP
- **Export Options**: Download SVG and PNG formats for external use

### **6. Multi-Map Support with Versioning**
- **Map Isolation**: Each map operates independently with separate data storage
- **Version Control**: Save and load different map versions with full data preservation
- **Cross-Map Operations**: Support for multiple concurrent maps
- **Unlimited Versions**: No limit on version creation or editing
- **Version Comparison**: Compare different versions of the same map

### **7. Advanced Collision Detection**
- **Automatic Validation**: Prevents overlapping objects during placement
- **Bulk Update Safety**: Ensures atomic updates considering final positions
- **Position Swapping**: Handles object position exchanges correctly
- **Real-time Feedback**: Provides detailed collision information and error messages
- **Temporary Validation**: Validates bulk operations before applying changes

### **8. Database Storage with Migration Support**
- **MySQL/PostgreSQL Support**: Robust database persistence with proper character encoding
- **Migration Tools**: Easy migration from JSON to database with data preservation
- **Data Integrity**: ACID compliance and transaction support
- **Scalability**: Handles large datasets efficiently with optimized queries
- **UTF-8 Support**: Full Unicode support for international character sets

### **9. Excel/CSV Integration**
- **Template Download**: Download Excel templates with proper formatting
- **Bulk Import**: Import furnace data from Excel/CSV files with validation
- **Export Functionality**: Export current map data to Excel format
- **Chief Gear Support**: Full import/export of chief gear data
- **Error Handling**: Comprehensive error reporting for import issues

### **10. Weighted Criteria System**
- **Configurable Weights**: Set custom weights for different criteria
- **Multiple Criteria**: Power, Level, Rank, Participation, Chief Gear and Charms
- **Normalized Scoring**: All criteria normalized to 0-100 scale for fair comparison
- **Dynamic Sorting**: Real-time sorting based on weighted criteria
- **Priority Calculation**: Advanced priority calculation using chief gear data

## 📁 **File Structure**

```
src/ (24 PHP files)
├── Domain/
│   ├── Map/
│   │   ├── Map.php                 # Main aggregate root with collision detection
│   │   ├── MapGenerator.php        # Intelligent furnace placement algorithm
│   │   ├── SvgGenerator.php        # SVG generation service
│   │   └── CriteriaWeight.php      # Weighted criteria configuration
│   ├── MapObject/
│   │   ├── MapObject.php           # Base map object
│   │   ├── Trap.php               # Trap entity
│   │   ├── MiscObject.php         # Miscellaneous object entity (any size)
│   │   └── Furnace.php            # Furnace entity with chief gear
│   ├── Repository/
│   │   ├── MapRepositoryInterface.php
│   │   └── UserRepositoryInterface.php
│   └── User/
│       └── User.php               # User domain entity
├── Application/
│   ├── Service/
│   │   ├── MapService.php         # Main application service
│   │   ├── AuthenticationService.php # User authentication and management
│   │   ├── EmailService.php       # Email notifications and verification
│   │   ├── ExcelService.php       # Excel/CSV import/export
│   │   └── WeightedCriteriaService.php # Weighted criteria calculations
│   └── Exception/
│       ├── ValidationException.php
│       ├── AuthenticationException.php
│       └── MapNotFoundException.php
└── Infrastructure/
    ├── Api/
    │   ├── MapController.php      # Consolidated API controller
    │   └── AuthenticationController.php # Authentication API controller
    ├── Database/
    │   └── DatabaseConnection.php # Database connection management
    └── Repository/
    │   ├── DatabaseMapRepository.php # Database-based persistence
    │   └── DatabaseUserRepository.php # User data persistence

public/ (3 JS files)
├── api.php                       # Consolidated API endpoint
├── auth.php                      # Authentication API endpoint
├── map.svg                       # Dynamic SVG generation endpoint
├── index.html                    # Frontend interface with chief gear support
├── script.js                     # Frontend JavaScript (comprehensive)
├── auth.js                       # Authentication JavaScript
├── js/
│   └── session-manager.js        # Session management utilities
└── styles.css                    # Frontend styling

tests/ (11 test files)
├── MapServiceTest.php            # Comprehensive tests with proper isolation
├── AuthenticationTest.php        # Authentication system tests
├── EnhancedAuthenticationTest.php # Advanced authentication tests
├── ExcelServiceTest.php          # Excel import/export tests
├── FurnacePriorityTest.php       # Chief gear priority algorithm tests
├── WeightedCriteriaTest.php      # Weighted criteria system tests
├── frontend.test.js              # Frontend functionality tests
├── frontend-security.test.js     # Frontend security tests
├── frontend-weighted-criteria.test.js # Frontend weighted criteria tests
├── setup.js                      # Frontend test setup
└── run-frontend-tests.js         # Frontend test runner

scripts/
├── create_initial_tables.php     # Database table creation
├── migrate_json_to_db.php        # Migration script from JSON to database
```

## 🎯 **Usage Examples**

### **Add Furnace with Chief Gear**
```javascript
const formData = new FormData();
formData.append('map_id', 'map_123');
formData.append('name', 'High Priority Furnace');
formData.append('level', 'FC1');
formData.append('power', '100');
formData.append('rank', 'R1');
formData.append('participation', '2');
formData.append('trap_pref', 'both');
formData.append('cap_level', 'Legendary T1');
formData.append('cap_charms', '8,9,10');
formData.append('ring_level', 'Legendary T3');
formData.append('ring_charms', '12,12,12');

fetch('api.php?action=add_furnace', {
    method: 'POST',
    body: formData
});
```

### **Bulk Update Furnaces with Collision Detection**
```javascript
const updates = [
    {
        id: 'furnace1', 
        x: 10, y: 20, 
        name: 'F1', level: 'FC1', power: 100, 
        rank: 'R1', participation: 2, trap_pref: '1'
    },
    {
        id: 'furnace2', 
        x: 15, y: 25, 
        name: 'F2', level: 'FC2', power: 150, 
        rank: 'R2', participation: 1, trap_pref: '2'
    }
];

const formData = new URLSearchParams();
formData.append('map_id', 'map_123');

fetch('api.php?action=update_all_furnaces', {
    method: 'PUT',
    body: formData.toString(),
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Furnace-Updates': JSON.stringify(updates)
    }
});
```

### **Generate SVG for Specific Version**
```javascript
// Fetch SVG directly from dynamic endpoint
fetch('map.svg?map_id=map_123&version=2.0')
    .then(response => response.text())
    .then(svg => {
        document.getElementById('map-container').innerHTML = svg;
    });
```

### **Export Furnaces with Chief Gear**
```javascript
// Download Excel with all chief gear data
window.location.href = 'api.php?action=export_furnaces&map_id=map_123';
```

### **User Registration and Authentication**
```javascript
// Register new user
const formData = new FormData();
formData.append('username', 'newuser');
formData.append('email', 'user@example.com');
formData.append('password', 'securepassword123');

fetch('auth.php?action=register', {
    method: 'POST',
    body: formData
});

// Login with OTP verification
const loginData = new FormData();
loginData.append('username', 'newuser');
loginData.append('password', 'securepassword123');

fetch('auth.php?action=login', {
    method: 'POST',
    body: loginData
}).then(response => response.json())
  .then(data => {
    if (data.needs_otp) {
        // Prompt for OTP code
        const otpCode = prompt('Enter OTP code:');
        // Verify OTP
        const otpData = new FormData();
        otpData.append('user_id', data.user.id);
        otpData.append('otp_code', otpCode);
        
        fetch('auth.php?action=verify_otp', {
            method: 'POST',
            body: otpData
        });
    }
  });
```

## 🧪 **Testing**

The system includes comprehensive testing for both backend and frontend with proper isolation:

### **Backend Testing (PHPUnit)**
- **Test Isolation**: Each test is completely self-contained with fresh mocks and data
- **Predictable IDs**: Test-specific map IDs prevent collisions
- **No Shared State**: Tests don't depend on each other's execution order
- **Proper Setup/Teardown**: Clean test lifecycle management

### **Frontend Testing (Jest)**
- **JavaScript Testing**: Comprehensive frontend functionality testing
- **DOM Testing**: UI interactions and component behavior
- **API Mocking**: Simulated backend responses for frontend testing
- **User Event Testing**: Realistic user interactions and form submissions

### **Test Coverage**

#### **Backend Tests (11 test files)**
- **Domain Logic**: Map object interactions and collision detection
- **Application Services**: Business logic and workflow coordination
- **API Controllers**: Request handling and response formatting
- **Chief Gear Logic**: Priority calculation and validation
- **Authentication**: User registration, login, and session management
- **Email Service**: Email notifications and verification
- **Excel Service**: Import/export functionality
- **Weighted Criteria**: Priority calculation algorithms
- **Edge Cases**: Error conditions and validation scenarios

#### **Frontend Tests (3 test files)**
- **Map Management**: Map selection, version loading
- **Furnace Operations**: Add, edit, delete furnaces
- **Gear Columns**: Toggle visibility, editable inputs
- **Status System**: Visual status indicators and precedence
- **Download Functionality**: SVG and PNG export
- **Error Handling**: API errors and user feedback
- **Form Validation**: Required fields and data validation
- **UI Interactions**: Button clicks, form submissions
- **Security**: Authentication and authorization testing
- **Weighted Criteria**: Frontend criteria configuration

### **Running Tests**

#### **Backend Tests**
```bash
composer test
# or
./vendor/bin/phpunit tests/
```

#### **Frontend Tests**
```bash
npm install          # Install Node.js dependencies
npm test             # Run all frontend tests
npm run test:watch   # Run tests in watch mode
npm run test:coverage # Run tests with coverage report
```

**Backend Test Results**: 11 test files with comprehensive coverage ✅
**Frontend Test Results**: 3 test files with comprehensive UI functionality testing ✅

## 🔄 **Migration from Legacy Code**

The system has been completely refactored from legacy procedural code to clean architecture:

### **Before (Legacy)**
- Multiple separate API files (`api/regenerate_svg.php`, `api/generate_map.php`, etc.)
- Procedural code with mixed concerns
- JSON file storage
- No collision detection
- No chief gear system
- No proper test isolation
- No user authentication
- No email notifications

### **After (Refactored)**
- **Single API Endpoint**: All operations through `/api.php` and `/auth.php`
- **Domain Services**: `MapGenerator`, `SvgGenerator` with intelligent algorithms
- **Application Services**: Consolidated services with comprehensive features
- **Database Storage**: Robust MySQL/PostgreSQL persistence with migrations
- **Chief Gear System**: Advanced furnace properties with smart prioritization
- **Collision Detection**: Comprehensive collision prevention and handling
- **Proper Test Isolation**: 11 test files with complete independence
- **Dynamic SVG**: Real-time SVG generation with version support
- **User Authentication**: Complete authentication system with OTP and email verification
- **Email Service**: Automated email notifications and verification
- **Excel Integration**: Full Excel/CSV import/export support
- **Weighted Criteria**: Advanced priority calculation system

### **Benefits**
- ✅ **Maintainability**: Clear separation of concerns with 24 PHP files
- ✅ **Testability**: Isolated, testable components with proper isolation
- ✅ **Extensibility**: Easy to add new features with clean architecture
- ✅ **Reliability**: Comprehensive error handling and validation
- ✅ **Performance**: Optimized algorithms and database storage
- ✅ **Consolidation**: Single API endpoints for all operations
- ✅ **Advanced Features**: Chief gear system and intelligent prioritization
- ✅ **Data Integrity**: Database storage with ACID compliance
- ✅ **Security**: Robust authentication with rate limiting and OTP
- ✅ **User Management**: Complete user lifecycle management

## 🚀 **Getting Started**

### **Prerequisites**
- PHP 7.4+ (tested on PHP 7.4.3)
- MySQL 5.7+ or PostgreSQL 10+
- Composer
- Node.js (for frontend testing)

### **Installation**

1. **Clone and Install Dependencies**
   ```bash
   git clone <repository>
   cd bh
   composer install
   npm install  # For frontend testing
   ```

2. **Configure Environment**
   ```bash
   cp env.example .env
   # Edit .env with your database and authentication settings:
   # - Database credentials (DB_DRIVER, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
   # - Master user credentials (MASTER_USERNAME, MASTER_EMAIL, MASTER_PASSWORD)
   # - Email settings for notifications (MAIL_ENABLED, MAIL_FROM_EMAIL, etc.)
   # - SMTP configuration (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD)
   ```

3. **Run Database Migration**
   ```bash
   php scripts/create_initial_tables.php
   # If migrating from JSON:
   php scripts/migrate_json_to_db.php
   ```

4. **Run Tests**
   ```bash
   composer test  # Backend tests
   npm test       # Frontend tests
   ```

5. **Start Development Server**
   ```bash
   php -S localhost:8000 -t public
   ```

6. **Access Application**
   - Open `http://localhost:8000` in your browser
   - You'll be redirected to the login page
   - Login with the master user credentials from your `.env` file
   - For new users: Register and wait for master user approval
   - Create a new map and start adding objects
   - Add furnaces with chief gear properties
   - Use the map generation feature to automatically place furnaces
   - View the generated SVG visualization
   - Export/import data using Excel
   - Access user management (master user only) via the admin link

## 📝 **Contributing**

1. Follow the existing architecture patterns (Clean Architecture + DDD)
2. Add comprehensive tests with proper isolation for new features
3. Update documentation for API changes
4. Ensure all tests pass before submitting
5. Follow the established coding standards
6. Test both backend (PHPUnit) and frontend (Jest) functionality

## 📄 **License**

This project is licensed under the GNU General Public License 3.0.
