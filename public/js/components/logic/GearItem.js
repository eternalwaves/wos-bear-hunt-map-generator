// GearItem Logic
export class GearItemLogic {
  constructor(component) {
    this.component = component;
  }

  getPreviewContent() {
    if (!this.component.level && !this.component.charms) {
      return html`<span class="preview-empty">No gear configured</span>`;
    }

    let preview = '';
    
    if (this.component.level) {
      preview += html`<div><strong>Level:</strong> ${this.component.level}</div>`;
    }
    
    if (this.component.charms) {
      const charmArray = this.component.charms.split(',').map(c => c.trim());
      const validCharms = charmArray.filter(c => {
        const num = parseInt(c);
        return !isNaN(num) && num >= 0 && num <= 16;
      });
      
      if (validCharms.length > 0) {
        const avgCharm = validCharms.reduce((sum, level) => sum + parseInt(level), 0) / validCharms.length;
        preview += html`
          <div><strong>Charms:</strong> ${validCharms.join(', ')}</div>
          <div><strong>Average:</strong> ${avgCharm.toFixed(1)}</div>
        `;
      } else {
        preview += html`<div><strong>Charms:</strong> <span style="color: #dc3545;">Invalid format</span></div>`;
      }
    }
    
    return preview;
  }

  onLevelChange(event) {
    this.component.level = event.target.value;
    this.notifyChange();
  }

  onCharmsChange(event) {
    this.component.charms = event.target.value;
    this.notifyChange();
  }

  notifyChange() {
    this.component.dispatchEvent(new CustomEvent('gear-changed', {
      detail: {
        gearType: this.component.gearType,
        level: this.component.level,
        charms: this.component.charms
      }
    }));
  }
}
