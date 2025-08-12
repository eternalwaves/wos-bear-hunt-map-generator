// GearModal Logic
export class GearModalLogic {
  constructor(component) {
    this.component = component;
  }

  show(furnace) {
    this.component.furnace = furnace;
    this.component.visible = true;
    
    // Initialize gear data from furnace
    this.component.gearData = {
      cap: { 
        level: furnace.getCapLevel() || '', 
        charms: furnace.getCapCharms() || '' 
      },
      watch: { 
        level: furnace.getWatchLevel() || '', 
        charms: furnace.getWatchCharms() || '' 
      },
      vest: { 
        level: furnace.getVestLevel() || '', 
        charms: furnace.getVestCharms() || '' 
      },
      pants: { 
        level: furnace.getPantsLevel() || '', 
        charms: furnace.getPantsCharms() || '' 
      },
      ring: { 
        level: furnace.getRingLevel() || '', 
        charms: furnace.getRingCharms() || '' 
      },
      cane: { 
        level: furnace.getCaneLevel() || '', 
        charms: furnace.getCaneCharms() || '' 
      }
    };
  }

  hide() {
    this.component.visible = false;
    this.component.furnace = null;
    this.component.gearData = {
      cap: { level: '', charms: '' },
      watch: { level: '', charms: '' },
      vest: { level: '', charms: '' },
      pants: { level: '', charms: '' },
      ring: { level: '', charms: '' },
      cane: { level: '', charms: '' }
    };
  }

  onOverlayClick(event) {
    if (event.target === event.currentTarget) {
      this.onClose();
    }
  }

  onContentClick(event) {
    // Prevent clicks on content from closing modal
    event.stopPropagation();
  }

  onClose() {
    this.hide();
  }

  onGearChanged(event) {
    const { gearType, level, charms } = event.detail;
    this.component.gearData[gearType] = { level, charms };
  }

  async onSave() {
    if (!this.component.furnace) return;

    try {
      const response = await fetch('/api.php?action=update_furnace_gear', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          furnace_id: this.component.furnace.getId(),
          cap_level: this.component.gearData.cap.level,
          cap_charms: this.component.gearData.cap.charms,
          watch_level: this.component.gearData.watch.level,
          watch_charms: this.component.gearData.watch.charms,
          vest_level: this.component.gearData.vest.level,
          vest_charms: this.component.gearData.vest.charms,
          pants_level: this.component.gearData.pants.level,
          pants_charms: this.component.gearData.pants.charms,
          ring_level: this.component.gearData.ring.level,
          ring_charms: this.component.gearData.ring.charms,
          cane_level: this.component.gearData.cane.level,
          cane_charms: this.component.gearData.cane.charms
        })
      });

      if (response.ok) {
        // Update the furnace object
        this.component.furnace.setCapLevel(this.component.gearData.cap.level);
        this.component.furnace.setCapCharms(this.component.gearData.cap.charms);
        this.component.furnace.setWatchLevel(this.component.gearData.watch.level);
        this.component.furnace.setWatchCharms(this.component.gearData.watch.charms);
        this.component.furnace.setVestLevel(this.component.gearData.vest.level);
        this.component.furnace.setVestCharms(this.component.gearData.vest.charms);
        this.component.furnace.setPantsLevel(this.component.gearData.pants.level);
        this.component.furnace.setPantsCharms(this.component.gearData.pants.charms);
        this.component.furnace.setRingLevel(this.component.gearData.ring.level);
        this.component.furnace.setRingCharms(this.component.gearData.ring.charms);
        this.component.furnace.setCaneLevel(this.component.gearData.cane.level);
        this.component.furnace.setCaneCharms(this.component.gearData.cane.charms);

        this.component.dispatchEvent(new CustomEvent('gear-saved', {
          detail: { furnace: this.component.furnace, gearData: this.component.gearData }
        }));

        this.hide();
      } else {
        throw new Error('Failed to save gear');
      }
    } catch (error) {
      console.error('Error saving gear:', error);
      // Handle error (show notification, etc.)
    }
  }
} 