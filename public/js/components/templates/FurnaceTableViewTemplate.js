import { html } from 'https://esm.sh/lit@2.7.0';
import { Furnace } from '../../models/Furnace.js';

export function FurnaceTableViewTemplate(component) {
  return html`
    <div class="furnace-section">
      <h3>
        Furnaces
        <div>
          <button class="toggle-gear-btn" @click=${component._toggleGearColumns}>
            ${component.showGearColumns ? 'Hide' : 'Show'} Gear Columns
          </button>
          <button class="add-furnace-btn" @click=${component._toggleForm}>
            ${component.showForm ? 'Cancel' : 'Add Furnace'}
          </button>
        </div>
      </h3>

      ${component.showForm ? html`
        <furnace-form-view @furnace-submitted=${component._onFurnaceSubmitted} @form-cancelled=${component._onFormCancelled}></furnace-form-view>
      ` : ''}

      <table class="furnace-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Level</th>
            <th>Power</th>
            ${component.showGearColumns ? html`
              <th>Chief Gear</th>
            ` : ''}
            <th>Rank</th>
            <th>Participation</th>
            <th>Trap Pref</th>
            <th>X</th>
            <th>Y</th>
            <th>Shift</th>
            <th>Actions</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${component.furnaces.length === 0 ? html`
            <tr>
              <td colspan="${component.showGearColumns ? 13 : 12}" style="text-align: center; color: #6c757d; font-style: italic; padding: 40px;">
                No furnaces added yet
              </td>
            </tr>
          ` : component.furnaces.map((furnace, index) => html`
            <tr class="${component._getStatusClass(furnace)}">
              <td>${index + 1}.</td>
              <td>
                <input type="text" 
                       class="edit-name" 
                       value="${furnace.name}" 
                       @input=${(e) => component._onNameChange(e, furnace)}>
              </td>
              <td>
                <select class="edit-level" @change=${(e) => component._onLevelChange(e, furnace)}>
                  <option value="">Select Level</option>
                  ${Furnace.VALID_LEVELS.map(level => html`
                    <option value="${level}" ?selected=${furnace.level === level}>${level}</option>
                  `)}
                </select>
              </td>
              <td>
                <input type="number" 
                       class="edit-power" 
                       value="${furnace.power}" 
                       @input=${(e) => component._onPowerChange(e, furnace)}
                       min="0">
              </td>
              ${component.showGearColumns ? html`
                <td class="gear-cell" @click=${(e) => component._onGearClick(e, furnace)}>
                  <div class="gear-grid-container">
                    <div class="gear-grid">
                      <div class="gear-item-display">
                        <span class="gear-name">Cap</span>
                        <span class="gear-display-level">${furnace.capLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.capCharms || 'No Charms'}</span>
                      </div>
                      <div class="gear-item-display">
                        <span class="gear-name">Watch</span>
                        <span class="gear-display-level">${furnace.watchLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.watchCharms || 'No Charms'}</span>
                      </div>
                      <div class="gear-item-display">
                        <span class="gear-name">Vest</span>
                        <span class="gear-display-level">${furnace.vestLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.vestCharms || 'No Charms'}</span>
                      </div>
                      <div class="gear-item-display">
                        <span class="gear-name">Pants</span>
                        <span class="gear-display-level">${furnace.pantsLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.pantsCharms || 'No Charms'}</span>
                      </div>
                      <div class="gear-item-display">
                        <span class="gear-name">Ring</span>
                        <span class="gear-display-level">${furnace.ringLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.ringCharms || 'No Charms'}</span>
                      </div>
                      <div class="gear-item-display">
                        <span class="gear-name">Cane</span>
                        <span class="gear-display-level">${furnace.caneLevel || 'Not Set'}</span>
                        <span class="gear-display-charms">${furnace.caneCharms || 'No Charms'}</span>
                      </div>
                    </div>
                  </div>
                </td>
              ` : ''}
              <td>
                <select class="edit-rank" @change=${(e) => component._onRankChange(e, furnace)}>
                  <option value="">Select Rank</option>
                  ${Furnace.VALID_RANKS.map(rank => html`
                    <option value="${rank}" ?selected=${furnace.rank === rank}>${rank}</option>
                  `)}
                </select>
              </td>
              <td>
                <input type="number" 
                       class="edit-participation" 
                       value="${furnace.participation}" 
                       @input=${(e) => component._onParticipationChange(e, furnace)}
                       min="0" max="4">
              </td>
              <td>
                <select class="edit-trap-pref" @change=${(e) => component._onTrapPrefChange(e, furnace)}>
                  <option value="">Select Preference</option>
                  ${Furnace.VALID_TRAP_PREFERENCES.map(pref => html`
                    <option value="${pref}" ?selected=${furnace.trapPref === pref}>${pref}</option>
                  `)}
                </select>
              </td>
              <td>
                <input type="number" 
                       class="edit-coord" 
                       value="${furnace.x}" 
                       @change=${(e) => component._onXChange(e, furnace)}
                       min="0">
              </td>
              <td>
                <input type="number" 
                       class="edit-coord" 
                       value="${furnace.y}" 
                       @input=${(e) => component._onYChange(e, furnace)}
                       min="0">
              </td>
              <td class="shiftFurnaceBtns">
                <button @click=${() => component._onShift(-1, 0, furnace)}>←</button>
                <button @click=${() => component._onShift(1, 0, furnace)}>→</button>
                <button @click=${() => component._onShift(0, 1, furnace)}>↑</button>
                <button @click=${() => component._onShift(0, -1, furnace)}>↓</button>
              </td>
              <td class="actionBtns">
                <button @click=${() => component._onSave(furnace)}>Save</button>
                <button @click=${() => component._onEdit(furnace)}>Edit</button>
                <button @click=${() => component._onDelete(furnace)}>Delete</button>
              </td>
              <td>
                <select @change=${(e) => component._onStatusChange(e, furnace)}>
                  <option value="" ?selected=${!furnace.status}>-</option>
                  <option value="assigned" ?selected=${furnace.status === 'assigned'}>Assigned</option>
                  <option value="moved" ?selected=${furnace.status === 'moved'}>Moved</option>
                  <option value="messaged" ?selected=${furnace.status === 'messaged'}>Messaged</option>
                  <option value="wrong" ?selected=${furnace.status === 'wrong'}>Wrong Spot</option>
                </select>
              </td>
            </tr>
          `)}
        </tbody>
      </table>
    </div>
  `;
}
