import { html } from 'https://esm.sh/lit@2.7.0';

export function MiscObjectFormTemplate(component) {
  return html`
    <form @submit=${component._onSubmit}>
      <h3>Add Miscellaneous Object</h3>
      <div class="form-row">
        <div class="form-group">
          <label for="object-name">Name:</label>
          <input type="text" 
                 id="object-name" 
                 name="name" 
                 value="${component.name}" 
                 @change=${component._onNameChange}
                 required>
        </div>
        <div class="form-group">
          <label for="object-size">Size:</label>
          <input type="number" 
                 id="object-size" 
                 name="size" 
                 value="${component.size}" 
                 @change=${component._onSizeChange}
                 required 
                 min="1" 
                 max="10">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="object-x">X Coordinate:</label>
          <input type="number" 
                 id="object-x" 
                 name="x" 
                 value="${component.x || ''}" 
                 @change=${component._onXChange}
                 required 
                 min="0" 
                 max="100">
        </div>
        <div class="form-group">
          <label for="object-y">Y Coordinate:</label>
          <input type="number" 
                 id="object-y" 
                 name="y" 
                 value="${component.y || ''}" 
                 @change=${component._onYChange}
                 required 
                 min="0" 
                 max="100">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Object</button>
        <button type="button" class="btn btn-secondary" @click=${component._onCancel}>Cancel</button>
      </div>
    </form>
  `;
}
