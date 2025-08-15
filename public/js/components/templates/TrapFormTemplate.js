import { html } from 'https://esm.sh/lit@2.7.0';

export function TrapFormTemplate(component) {
  return html`
    <form @submit=${component._onSubmit}>
      <h3>Add Trap</h3>
      <div class="form-row">
        <div class="form-group">
          <label for="trap-x">X Coordinate:</label>
          <input type="number" 
                 id="trap-x" 
                 name="x" 
                 value="${component.x || ''}" 
                 @change=${component._onXChange}
                 required 
                 min="0" 
                 max="100">
        </div>
        <div class="form-group">
          <label for="trap-y">Y Coordinate:</label>
          <input type="number" 
                 id="trap-y" 
                 name="y" 
                 value="${component.y || ''}" 
                 @change=${component._onYChange}
                 required 
                 min="0" 
                 max="100">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Trap</button>
        <button type="button" class="btn btn-secondary" @click=${component._onCancel}>Cancel</button>
      </div>
    </form>
  `;
}
