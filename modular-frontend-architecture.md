# Modular Frontend Architecture

This document outlines the new modular frontend architecture that separates JavaScript functions, HTML templates, and CSS styles into different files for better maintainability and organization.

## Directory Structure

```
public/js/
├── components/
│   ├── templates/           # HTML templates
│   │   ├── MapGeneratorView.html
│   │   ├── FurnaceTableView.html
│   │   └── GearModal.html
│   ├── styles/              # CSS stylesheets
│   │   ├── MapGeneratorView.css
│   │   ├── FurnaceTableView.css
│   │   └── GearModal.css
│   ├── logic/               # Business logic classes
│   │   ├── MapGeneratorView.js
│   │   ├── FurnaceTableView.js
│   │   └── GearModal.js
│   ├── MapGeneratorView.js  # Main component (imports separated files)
│   ├── FurnaceTableView.js  # Main component (imports separated files)
│   └── GearModal.js         # Main component (imports separated files)
├── utils/
│   └── templateLoader.js    # Utility for loading templates and CSS
└── models/                  # Frontend models (unchanged)
    ├── MapObject.js
    ├── Furnace.js
    ├── Trap.js
    ├── MiscObject.js
    ├── Map.js
    └── User.js
```

## Architecture Overview

### 1. Template Separation
- **HTML templates** are stored in separate `.html` files
- Templates contain the structure and Lit template literals
- Templates are loaded dynamically using the `TemplateLoader` utility

### 2. Style Separation
- **CSS styles** are stored in separate `.css` files
- Styles are scoped to components using Lit's CSS template literals
- Styles are loaded dynamically and applied to components

### 3. Logic Separation
- **Business logic** is extracted into separate logic classes
- Logic classes handle API calls, data manipulation, and event processing
- Components delegate to logic classes for all business operations

### 4. Component Structure
Each component now follows this pattern:

```javascript
import { LitElement, html, css } from 'lit';
import { TemplateLoader } from '../utils/templateLoader.js';
import { ComponentLogic } from './logic/ComponentName.js';

export class ComponentName extends LitElement {
  constructor() {
    super();
    this.logic = new ComponentLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
  }

  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/ComponentName.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/ComponentName.css');
    this.staticStyles = css`${this.cssString}`;
  }

  render() {
    // Template rendering logic
  }

  // Event handlers that delegate to logic class
  _onEvent(event) {
    this.logic.onEvent(event);
  }
}
```

## Benefits

### 1. Maintainability
- **Separation of Concerns**: HTML, CSS, and JavaScript are in separate files
- **Easier Debugging**: Issues can be isolated to specific file types
- **Better Organization**: Related code is grouped together

### 2. Reusability
- **Template Reuse**: HTML templates can be shared between components
- **Style Reuse**: CSS can be imported by multiple components
- **Logic Reuse**: Business logic can be shared across components

### 3. Development Workflow
- **Parallel Development**: Different developers can work on templates, styles, and logic simultaneously
- **Version Control**: Smaller, focused files are easier to track and merge
- **Code Review**: Changes are easier to review when separated by concern

### 4. Testing
- **Isolated Testing**: Each layer can be tested independently
- **Mock Testing**: Logic classes can be easily mocked for component testing
- **Template Testing**: HTML templates can be tested separately from logic

## Implementation Details

### Template Loader Utility
The `TemplateLoader` utility provides methods to:
- Load HTML templates as strings
- Load CSS files as strings
- Convert strings to Lit template literals
- Handle loading errors gracefully

### Logic Classes
Logic classes follow this pattern:
```javascript
export class ComponentLogic {
  constructor(component) {
    this.component = component;
  }

  // Business logic methods
  async onEvent(event) {
    // Handle business logic
    // Update component state
    // Make API calls
  }
}
```

### Dynamic Loading
Components load their templates and styles asynchronously:
1. Component initializes
2. Templates and styles are loaded via `TemplateLoader`
3. Styles are applied to the component
4. Component renders with loaded templates

## Migration Strategy

### Phase 1: Core Components (Completed)
- ✅ MapGeneratorView
- ✅ FurnaceTableView  
- ✅ GearModal

### Phase 2: Remaining Components
- 🔄 FurnaceTableRow
- 🔄 GearCell
- 🔄 GearItem
- 🔄 LoginView
- 🔄 Other auth components

### Phase 3: Advanced Features
- 🔄 Template parsing and dynamic rendering
- 🔄 Style composition and inheritance
- 🔄 Logic composition and mixins

## Usage Example

### Before (Monolithic)
```javascript
export class FurnaceTableView extends LitElement {
  static styles = css`/* 100+ lines of CSS */`;
  
  render() {
    return html`/* 50+ lines of HTML */`;
  }
  
  async _onFurnaceSubmitted(event) {
    // 30+ lines of business logic
  }
}
```

### After (Modular)
```javascript
// FurnaceTableView.js
export class FurnaceTableView extends LitElement {
  constructor() {
    super();
    this.logic = new FurnaceTableViewLogic(this);
  }
  
  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/templates/FurnaceTableView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/styles/FurnaceTableView.css');
  }
  
  _onFurnaceSubmitted(event) {
    this.logic.onFurnaceSubmitted(event);
  }
}
```

```html
<!-- templates/FurnaceTableView.html -->
<div class="table-header">
  <h2 class="table-title">Furnaces</h2>
  <!-- Template content -->
</div>
```

```css
/* styles/FurnaceTableView.css */
:host {
  display: block;
  margin-bottom: 30px;
}
/* CSS content */
```

```javascript
// logic/FurnaceTableView.js
export class FurnaceTableViewLogic {
  async onFurnaceSubmitted(event) {
    // Business logic implementation
  }
}
```

## Testing Strategy

### Component Testing
```javascript
import './FurnaceTableView.js';

describe('FurnaceTableView Component', () => {
  let component;
  
  beforeEach(() => {
    component = document.createElement('furnace-table-view');
    document.body.appendChild(component);
  });
  
  it('should load templates and styles', async () => {
    await component.updateComplete;
    expect(component.templateString).toBeTruthy();
    expect(component.cssString).toBeTruthy();
  });
});
```

### Logic Testing
```javascript
import { FurnaceTableViewLogic } from '../logic/FurnaceTableView.js';

describe('FurnaceTableViewLogic', () => {
  let logic;
  let mockComponent;
  
  beforeEach(() => {
    mockComponent = { /* mock component */ };
    logic = new FurnaceTableViewLogic(mockComponent);
  });
  
  it('should handle furnace submission', async () => {
    // Test business logic independently
  });
});
```

This modular architecture provides a clean separation of concerns while maintaining the benefits of Lit's component system and ensuring compatibility with the existing Jest testing setup. 