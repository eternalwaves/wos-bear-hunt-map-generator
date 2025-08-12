// GearCell Logic
export class GearCellLogic {
  constructor(component) {
    this.component = component;
  }

  getLevelClass() {
    if (!this.component.level) return '';
    
    const level = this.component.level.toLowerCase();
    if (level.includes('uncommon')) return 'level-uncommon';
    if (level.includes('rare')) return 'level-rare';
    if (level.includes('epic')) return 'level-epic';
    if (level.includes('mythic')) return 'level-mythic';
    if (level.includes('legendary')) return 'level-legendary';
    
    return '';
  }

  getCharmsClass() {
    if (!this.component.charms) return '';
    
    const charmArray = this.component.charms.split(',').map(c => parseInt(c.trim()));
    const avgCharm = charmArray.reduce((sum, level) => sum + level, 0) / charmArray.length;
    
    if (avgCharm >= 12) return 'charms-high';
    if (avgCharm >= 8) return 'charms-medium';
    return 'charms-low';
  }

  formatCharms() {
    if (!this.component.charms) return '';
    
    const charmArray = this.component.charms.split(',').map(c => c.trim());
    if (charmArray.length <= 3) {
      return charmArray.join(', ');
    }
    
    // Show first 2 and last charm if more than 3
    return `${charmArray.slice(0, 2).join(', ')}, ..., ${charmArray[charmArray.length - 1]}`;
  }

  onClick() {
    this.component.dispatchEvent(new CustomEvent('gear-click', {
      detail: {
        gearType: this.component.gearType,
        furnaceId: this.component.furnaceId,
        level: this.component.level,
        charms: this.component.charms
      }
    }));
  }
}
