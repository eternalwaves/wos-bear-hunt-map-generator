import { html } from 'https://esm.sh/lit@2.7.0';

export function GearModalTemplate(component) {
  if (!component.visible) return html``;
  
  return html`
    <div class="gear-modal" @click=${component._onOverlayClick}>
      <div class="gear-modal-content" @click=${component._onContentClick}>
        <div class="gear-modal-header">
          <h3>Edit Gear - ${component.furnace ? component.furnace.name : ''}</h3>
          <button class="close-btn" @click=${component._onClose}>&times;</button>
        </div>
        
        <div class="gear-items">
          <div class="gear-item">
            <h4>Cap</h4>
            <div class="form-row">
              <div class="form-group">
                <label for="capLevel">Level:</label>
                <select id="capLevel" @change=${component._onGearChanged}>
                  <option value="">Select Level</option>
                  <option value="Uncommon">Uncommon</option>
                  <option value="Rare">Rare</option>
                  <option value="Epic">Epic</option>
                  <option value="Mythic">Mythic</option>
                  <option value="Legendary">Legendary</option>
                </select>
              </div>
              <div class="form-group">
                <label for="capCharms">Charms:</label>
                <input type="text" id="capCharms" placeholder="e.g., 1,2,3" @change=${component._onGearChanged}>
              </div>
            </div>
          </div>
          
          <div class="gear-item">
            <h4>Watch</h4>
            <div class="form-row">
              <div class="form-group">
                <label for="watchLevel">Level:</label>
                <select id="watchLevel" @change=${component._onGearChanged}>
                  <option value="">Select Level</option>
                  <option value="Uncommon">Uncommon</option>
                  <option value="Rare">Rare</option>
                  <option value="Epic">Epic</option>
                  <option value="Mythic">Mythic</option>
                  <option value="Legendary">Legendary</option>
                </select>
              </div>
              <div class="form-group">
                <label for="watchCharms">Charms:</label>
                <input type="text" id="watchCharms" placeholder="e.g., 1,2,3" @change=${component._onGearChanged}>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button class="btn btn-primary" @click=${component._onSave}>Save</button>
          <button class="btn btn-secondary" @click=${component._onClose}>Cancel</button>
        </div>
      </div>
    </div>
  `;
}
