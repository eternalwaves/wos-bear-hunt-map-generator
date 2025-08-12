export class FurnaceFormViewLogic {
  constructor(component) {
    this.component = component;
  }

  onSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const x = formData.get('x') ? parseFloat(formData.get('x')) : null;
    const y = formData.get('y') ? parseFloat(formData.get('y')) : null;
    
    const furnaceData = {
      name: formData.get('name'),
      level: formData.get('level'),
      power: parseInt(formData.get('power')),
      rank: formData.get('rank'),
      participation: formData.get('participation') ? parseInt(formData.get('participation')) : null,
      trap_pref: formData.get('trap_pref'),
      x: x,
      y: y,
      // Auto-assign status to "assigned" if both X and Y are set
      status: (x && y) ? 'assigned' : '',
      // Chief gear data
      cap_level: formData.get('cap_level'),
      cap_charms: formData.get('cap_charms'),
      watch_level: formData.get('watch_level'),
      watch_charms: formData.get('watch_charms'),
      vest_level: formData.get('vest_level'),
      vest_charms: formData.get('vest_charms'),
      pants_level: formData.get('pants_level'),
      pants_charms: formData.get('pants_charms'),
      ring_level: formData.get('ring_level'),
      ring_charms: formData.get('ring_charms'),
      cane_level: formData.get('cane_level'),
      cane_charms: formData.get('cane_charms')
    };

    this.component.dispatchEvent(new CustomEvent('furnace-submitted', {
      detail: furnaceData,
      bubbles: true,
      composed: true
    }));

    // Reset form
    event.target.reset();
  }
}
