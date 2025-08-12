import { html } from 'https://esm.sh/lit@2.7.0';

export function GearItemTemplate(component) {
  return html`
    <div class="gear-item">
      <h4>${component.gearType.charAt(0).toUpperCase() + component.gearType.slice(1)}</h4>
      <div class="form-row">
        <div class="form-group">
          <label for="${component.gearType}-level">Level:</label>
          <select id="${component.gearType}-level" 
                  name="${component.gearType}_level" 
                  @change=${component._onLevelChange}>
            <option value="">None</option>
            <option value="1" ?selected=${component.level === '1'}>1</option>
            <option value="2" ?selected=${component.level === '2'}>2</option>
            <option value="3" ?selected=${component.level === '3'}>3</option>
            <option value="4" ?selected=${component.level === '4'}>4</option>
            <option value="5" ?selected=${component.level === '5'}>5</option>
          </select>
        </div>
        <div class="form-group">
          <label for="${component.gearType}-charms">Charms:</label>
          <input type="text" 
                 id="${component.gearType}-charms" 
                 name="${component.gearType}_charms" 
                 value="${component.charms}" 
                 @change=${component._onCharmsChange}
                 placeholder="e.g., +1,+2">
        </div>
      </div>
    </div>
  `;
}
