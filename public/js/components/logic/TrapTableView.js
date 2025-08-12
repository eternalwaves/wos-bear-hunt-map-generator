// TrapTableView Logic
import { Trap } from '../../models/Trap.js';

export class TrapTableViewLogic {
  constructor(component) {
    this.component = component;
  }

  toggleForm() {
    this.component.showForm = !this.component.showForm;
    this.component.requestUpdate();
  }

  async onTrapSubmitted(event) {
    const trapData = event.detail;
    const trap = new Trap(trapData.x, trapData.y);
    
    try {
      const response = await fetch('/api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'add_trap',
          map_id: this.component.map.getId(),
          trap: trap.toArray()
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          this.component.traps.push(trap);
          this.component.showForm = false;
          this.component.requestUpdate();
          
          this.component.dispatchEvent(new CustomEvent('trap-added', {
            detail: { trap }
          }));
        } else {
          alert('Failed to add trap: ' + result.message);
        }
      } else {
        alert('Failed to add trap');
      }
    } catch (error) {
      console.error('Error adding trap:', error);
      alert('Error adding trap');
    }
  }

  onFormCancelled() {
    this.component.showForm = false;
    this.component.requestUpdate();
  }

  async onTrapEdit(event) {
    const trap = event.detail;
    // Handle trap editing - could open a modal or form
    this.component.dispatchEvent(new CustomEvent('trap-edit', {
      detail: { trap }
    }));
  }

  async onTrapDelete(event) {
    const trap = event.detail;
    
    try {
      const response = await fetch('/api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete_trap',
          map_id: this.component.map.getId(),
          trap_id: trap.getId()
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          const index = this.component.traps.findIndex(t => t.getId() === trap.getId());
          if (index > -1) {
            this.component.traps.splice(index, 1);
            this.component.requestUpdate();
          }
          
          this.component.dispatchEvent(new CustomEvent('trap-deleted', {
            detail: { trap }
          }));
        } else {
          alert('Failed to delete trap: ' + result.message);
        }
      } else {
        alert('Failed to delete trap');
      }
    } catch (error) {
      console.error('Error deleting trap:', error);
      alert('Error deleting trap');
    }
  }

  updateTrapsFromMap() {
    if (this.component.map) {
      this.component.traps = this.component.map.getTraps();
      this.component.requestUpdate();
    }
  }
}
