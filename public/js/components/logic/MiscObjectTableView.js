// MiscObjectTableView Logic
import { MiscObject } from '../../models/MiscObject.js';

export class MiscObjectTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
    this.component.requestUpdate();
  }

  async onMiscObjectSubmitted(event) {
    const miscObjectData = event.detail;
    const miscObject = new MiscObject(
      miscObjectData.x, 
      miscObjectData.y, 
      miscObjectData.size, 
      miscObjectData.name
    );
    
    try {
      const response = await fetch('/api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'add_misc_object',
          map_id: this.component.map.getId(),
          misc_object: miscObject.toArray()
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          this.component.miscObjects.push(miscObject);
          this.component.showForm = false;
          this.component.requestUpdate();
          
          this.component.dispatchEvent(new CustomEvent('misc-object-added', {
            detail: { miscObject }
          }));
        } else {
          alert('Failed to add miscellaneous object: ' + result.message);
        }
      } else {
        alert('Failed to add miscellaneous object');
      }
    } catch (error) {
      console.error('Error adding miscellaneous object:', error);
      alert('Error adding miscellaneous object');
    }
  }

  onFormCancelled() {
    this.component.showForm = false;
    this.component.requestUpdate();
  }

  async onMiscObjectEdit(event) {
    const miscObject = event.detail;
    // Handle misc object editing - could open a modal or form
    this.component.dispatchEvent(new CustomEvent('misc-object-edit', {
      detail: { miscObject }
    }));
  }

  async onMiscObjectDelete(event) {
    const miscObject = event.detail;
    
    try {
      const response = await fetch('/api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete_misc_object',
          map_id: this.component.map.getId(),
          misc_object_id: miscObject.getId()
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          const index = this.component.miscObjects.findIndex(m => m.getId() === miscObject.getId());
          if (index > -1) {
            this.component.miscObjects.splice(index, 1);
            this.component.requestUpdate();
          }
          
          this.component.dispatchEvent(new CustomEvent('misc-object-deleted', {
            detail: { miscObject }
          }));
        } else {
          alert('Failed to delete miscellaneous object: ' + result.message);
        }
      } else {
        alert('Failed to delete miscellaneous object');
      }
    } catch (error) {
      console.error('Error deleting miscellaneous object:', error);
      alert('Error deleting miscellaneous object');
    }
  }

  updateMiscObjectsFromMap() {
    if (this.component.map) {
      this.component.miscObjects = this.component.map.getMiscObjects();
      this.component.requestUpdate();
    }
  }
}
