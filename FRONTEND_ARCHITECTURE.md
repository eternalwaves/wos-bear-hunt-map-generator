# Frontend Architecture with Lit Components

## Overview

This document outlines the complete frontend architecture using **Lit** components that mirror your backend models and provide a modular, testable structure.

## Architecture Benefits

✅ **True Module Imports**: Import actual component files directly into Jest tests  
✅ **Mirrors Backend Models**: Frontend models exactly match your PHP domain entities  
✅ **Component-Based**: Each UI element is a reusable, testable component  
✅ **Event-Driven**: Components communicate via custom events  
✅ **Shadow DOM**: Style encapsulation and component isolation  
✅ **Works with Jest**: No additional build step required  

## Directory Structure

```
public/js/
├── models/                    # Frontend models (mirror backend)
│   ├── MapObject.js          # Base map object model
│   ├── Furnace.js            # Furnace model with chief gear
│   ├── Trap.js               # Trap model
│   ├── MiscObject.js         # Miscellaneous object model
│   ├── Map.js                # Map aggregate model
│   └── User.js               # User model
├── components/               # View components
│   ├── MapGeneratorView.js   # Main parent page
│   ├── MapSelector.js        # Map selection component
│   ├── VersionSelector.js    # Version selection component
│   ├── MapDisplay.js         # Map visualization
│   ├── FurnaceTableView.js   # Furnace table container
│   ├── FurnaceTableRow.js    # Individual furnace row
│   ├── FurnaceForm.js        # Add/edit furnace form
│   ├── TrapTableView.js      # Trap table container
│   ├── TrapTableRow.js       # Individual trap row
│   ├── TrapForm.js           # Add/edit trap form
│   ├── MiscObjectTableView.js # Misc object table container
│   ├── MiscObjectTableRow.js # Individual misc object row
│   ├── MiscObjectForm.js     # Add/edit misc object form
│   ├── GearModal.js          # Chief gear editing modal
│   ├── GearItem.js           # Individual gear item in modal
│   ├── GearCell.js           # Gear display in table cells
│   └── auth/                 # Authentication components
│       ├── LoginView.js      # Login form
│       ├── RegisterView.js   # Registration form
│       ├── UserManagementView.js # User management (admin)
│       └── PasswordResetView.js # Password reset
└── services/                 # Business logic services
    ├── MapService.js         # Map operations
    ├── AuthService.js        # Authentication operations
    └── ApiService.js         # API communication
```

## Component Hierarchy

### 1. **MapGeneratorView** (Parent Page)
- **Purpose**: Main application container
- **Children**: 
  - MapSelector
  - VersionSelector
  - MapDisplay
  - FurnaceTableView
  - TrapTableView
  - MiscObjectTableView
  - GearModal

### 2. **MapObject Table Views**
Each MapObject type has its own table view:

#### **FurnaceTableView**
- **Purpose**: Manages furnace table and operations
- **Children**:
  - FurnaceTableRow (for each furnace)
  - FurnaceForm (for adding/editing)
- **Events**: `furnace-added`, `furnace-updated`, `furnace-deleted`

#### **TrapTableView**
- **Purpose**: Manages trap table and operations
- **Children**:
  - TrapTableRow (for each trap)
  - TrapForm (for adding/editing)
- **Events**: `trap-added`, `trap-deleted`

#### **MiscObjectTableView**
- **Purpose**: Manages miscellaneous objects table
- **Children**:
  - MiscObjectTableRow (for each object)
  - MiscObjectForm (for adding/editing)
- **Events**: `object-added`, `object-deleted`

### 3. **Individual Row Components**
Each row component is bound to a model instance:

#### **FurnaceTableRow**
- **Bound to**: Furnace model instance
- **Children**: GearCell components (for each gear type)
- **Events**: `furnace-updated`, `furnace-deleted`, `gear-edit`

#### **TrapTableRow**
- **Bound to**: Trap model instance
- **Events**: `trap-updated`, `trap-deleted`

#### **MiscObjectTableRow**
- **Bound to**: MiscObject model instance
- **Events**: `object-updated`, `object-deleted`

### 4. **Chief Gear Components**

#### **GearModal**
- **Purpose**: Modal for editing chief gear
- **Children**: GearItem components (for each gear type)
- **Events**: `gear-saved`

#### **GearItem**
- **Purpose**: Individual gear editing form
- **Bound to**: Gear data (level, charms)
- **Events**: `gear-changed`

#### **GearCell**
- **Purpose**: Display gear in table cells
- **Bound to**: Gear data (level, charms)
- **Events**: `gear-click`

### 5. **Authentication Components**

#### **LoginView**
- **Purpose**: User login with OTP support
- **Events**: `login-success`

#### **RegisterView**
- **Purpose**: User registration
- **Events**: `register-success`

#### **UserManagementView**
- **Purpose**: Admin user management
- **Events**: `user-approved`, `user-deactivated`, `user-deleted`

## Model Architecture

### Frontend Models Mirror Backend Models

```javascript
// Frontend models exactly match your PHP domain entities
import { MapObject } from './MapObject.js';
import { Furnace } from './Furnace.js';
import { Map } from './Map.js';
import { User } from './User.js';

// Usage example
const furnace = new Furnace(
  'Test Furnace', 'FC1', 100, 'R1', 2, 'both',
  10, 20, 'furnace-1', '', false,
  'Epic', 'Rare', 'Legendary', 'Mythic', 'Epic', 'Rare',
  '3,4,3', '5,6,7', '8,9,10', '11,12,13', '14,15,16', '1,2,3'
);

const map = new Map('Test Map', '1.0');
map.addFurnace(furnace);
```

## Testing Architecture

### Jest Integration

```javascript
// Import actual component files
import './FurnaceTableView.js';
import './FurnaceTableRow.js';
import { Furnace } from '../models/Furnace.js';

describe('FurnaceTableView Component', () => {
  test('renders furnace data correctly', () => {
    const component = document.createElement('furnace-table-view');
    const furnace = new Furnace('Test', 'FC1', 100, 'R1', 2, 'both');
    
    component.map = { getFurnaces: () => [furnace] };
    component.requestUpdate();
    
    const rows = component.shadowRoot.querySelectorAll('furnace-table-row');
    expect(rows.length).toBe(1);
  });
});
```

### Benefits for Testing

✅ **Direct Imports**: Import actual component files, not HTML strings  
✅ **Model Testing**: Test models separately from components  
✅ **Component Testing**: Test components with real model instances  
✅ **Event Testing**: Test component communication via events  
✅ **Integration Testing**: Test complete workflows  

## Event-Driven Communication

### Component Events

```javascript
// Parent listens to child events
furnaceTableView.addEventListener('furnace-added', (event) => {
  console.log('Furnace added:', event.detail);
});

// Child dispatches events
this.dispatchEvent(new CustomEvent('furnace-added', {
  detail: furnace
}));
```

### Event Flow

1. **User Action** → Component Method
2. **Component Method** → API Call
3. **API Response** → Model Update
4. **Model Update** → Component Re-render
5. **Component** → Event Dispatch
6. **Parent Component** → Event Handler

## API Integration

### Service Layer

```javascript
// services/ApiService.js
export class ApiService {
  static async addFurnace(mapId, furnaceData) {
    const response = await fetch('/api.php?action=add_furnace', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ map_id: mapId, ...furnaceData })
    });
    return response.json();
  }
}
```

### Component Usage

```javascript
// In component
async _onFurnaceSubmitted(event) {
  const furnaceData = event.detail;
  const result = await ApiService.addFurnace(this.map.getId(), furnaceData);
  
  if (result.success) {
    const furnace = new Furnace(/* ... */);
    this.map.addFurnace(furnace);
    this.dispatchEvent(new CustomEvent('furnace-added', { detail: furnace }));
  }
}
```

## Styling Strategy

### Shadow DOM Benefits

✅ **Style Encapsulation**: Component styles don't leak  
✅ **Scoped Styles**: Each component manages its own styling  
✅ **No Conflicts**: Multiple components can use same class names  
✅ **Maintainable**: Styles are co-located with components  

### CSS-in-JS with Lit

```javascript
static styles = css`
  :host {
    display: block;
    font-family: Arial, sans-serif;
  }
  
  .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
`;
```

## Migration Strategy

### Phase 1: Models
1. Create frontend models that mirror backend models
2. Test models independently
3. Ensure API compatibility

### Phase 2: Core Components
1. Create main view components (MapGeneratorView)
2. Create table view components
3. Create form components

### Phase 3: Advanced Components
1. Create gear-related components
2. Create authentication components
3. Create admin components

### Phase 4: Integration
1. Replace existing HTML with components
2. Update event handlers
3. Test complete workflows

## Benefits Summary

### For Development
✅ **Modular**: Each component is self-contained  
✅ **Reusable**: Components can be used across different views  
✅ **Maintainable**: Changes are isolated to specific components  
✅ **Testable**: Each component can be tested independently  

### For Testing
✅ **Importable**: Components can be imported directly into tests  
✅ **Mockable**: Models can be easily mocked  
✅ **Event-Driven**: Test component communication  
✅ **Isolated**: Test components in isolation  

### For Performance
✅ **Lazy Loading**: Components can be loaded on demand  
✅ **Efficient Updates**: Only changed components re-render  
✅ **Memory Efficient**: Components are garbage collected when removed  

### For User Experience
✅ **Responsive**: Components adapt to different screen sizes  
✅ **Accessible**: Built-in accessibility features  
✅ **Interactive**: Rich user interactions  
✅ **Consistent**: Unified design system  

This architecture provides a solid foundation for building a maintainable, testable, and scalable frontend that perfectly mirrors your backend domain model. 