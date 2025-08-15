import { html } from 'https://esm.sh/lit@2.7.0';
import { Furnace } from '../../models/Furnace.js';

export function FurnaceFormViewTemplate(component) {
  return html`
    <div class="furnace-form">
      <h3>Add Furnace</h3>
      <form @submit=${component._onSubmit}>
        <div class="form-row">
          <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label for="level">Level:</label>
            <select id="level" name="level" required>
              <option value="">Select Level</option>
              ${Furnace.VALID_LEVELS.map(level => html`
                <option value="${level}">${level}</option>
              `)}
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="power">Power:</label>
            <input type="number" id="power" name="power" min="0" required>
          </div>
          <div class="form-group">
            <label for="rank">Rank:</label>
            <select id="rank" name="rank" required>
              <option value="">Select Rank</option>
              ${Furnace.VALID_RANKS.map(rank => html`
                <option value="${rank}">${rank}</option>
              `)}
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="participation">Participation:</label>
            <input type="number" id="participation" name="participation" min="0" max="4">
          </div>
          <div class="form-group">
            <label for="trapPref">Trap Preference:</label>
            <select id="trapPref" name="trapPref">
              <option value="">Select Preference</option>
              ${Furnace.VALID_TRAP_PREFERENCES.map(pref => html`
                <option value="${pref}">${pref}</option>
              `)}
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="x">X Coordinate:</label>
            <input type="number" id="x" name="x" min="0">
          </div>
          <div class="form-group">
            <label for="y">Y Coordinate:</label>
            <input type="number" id="y" name="y" min="0">
          </div>
        </div>
        
        <!-- Chief Gear Section -->
        <div class="gear-section">
          <h3>Chief Gear</h3>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="capLevel">Cap Level:</label>
              <select id="capLevel" name="capLevel">
                <option value="">Select Cap Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="capCharms">Cap Charms:</label>
              <input type="text" id="capCharms" name="capCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="watchLevel">Watch Level:</label>
              <select id="watchLevel" name="watchLevel">
                <option value="">Select Watch Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="watchCharms">Watch Charms:</label>
              <input type="text" id="watchCharms" name="watchCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="vestLevel">Vest Level:</label>
              <select id="vestLevel" name="vestLevel">
                <option value="">Select Vest Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="vestCharms">Vest Charms:</label>
              <input type="text" id="vestCharms" name="vestCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="pantsLevel">Pants Level:</label>
              <select id="pantsLevel" name="pantsLevel">
                <option value="">Select Pants Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="pantsCharms">Pants Charms:</label>
              <input type="text" id="pantsCharms" name="pantsCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="ringLevel">Ring Level:</label>
              <select id="ringLevel" name="ringLevel">
                <option value="">Select Ring Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="ringCharms">Ring Charms:</label>
              <input type="text" id="ringCharms" name="ringCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
          
          <div class="gear-row">
            <div class="form-group">
              <label for="caneLevel">Cane Level:</label>
              <select id="caneLevel" name="caneLevel">
                <option value="">Select Cane Level</option>
                ${Furnace.VALID_GEAR_LEVELS.map(level => html`
                  <option value="${level}">${level}</option>
                `)}
              </select>
            </div>
            <div class="form-group">
              <label for="caneCharms">Cane Charms:</label>
              <input type="text" id="caneCharms" name="caneCharms" placeholder="e.g., 3,4,3">
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="submit">Add Furnace</button>
          <button type="button" @click=${component._onCancel}>Cancel</button>
        </div>
      </form>
    </div>
  `;
}
