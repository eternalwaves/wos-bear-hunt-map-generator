import { html } from 'https://esm.sh/lit@2.7.0';

// Validation lists from ExcelService
const VALID_LEVELS = [
  '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
  '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
  '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
  'FC1', 'FC2', 'FC3', 'FC4', 'FC5', 'FC6', 'FC7', 'FC8', 'FC9', 'FC10'
];

const VALID_RANKS = ['R1', 'R2', 'R3', 'R4', 'R5'];

const VALID_TRAP_PREFERENCES = ['1', '2', 'both', 'n/a'];

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
              ${VALID_LEVELS.map(level => html`
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
              ${VALID_RANKS.map(rank => html`
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
              ${VALID_TRAP_PREFERENCES.map(pref => html`
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
                <option value="Uncommon">Uncommon</option>
                <option value="Uncommon *">Uncommon *</option>
                <option value="Rare">Rare</option>
                <option value="Rare *">Rare *</option>
                <option value="Rare **">Rare **</option>
                <option value="Rare ***">Rare ***</option>
                <option value="Epic">Epic</option>
                <option value="Epic *">Epic *</option>
                <option value="Epic **">Epic **</option>
                <option value="Epic ***">Epic ***</option>
                <option value="Epic T1">Epic T1</option>
                <option value="Epic T1 *">Epic T1 *</option>
                <option value="Epic T1 **">Epic T1 **</option>
                <option value="Epic T1 ***">Epic T1 ***</option>
                <option value="Mythic">Mythic</option>
                <option value="Mythic *">Mythic *</option>
                <option value="Mythic **">Mythic **</option>
                <option value="Mythic ***">Mythic ***</option>
                <option value="Mythic T1">Mythic T1</option>
                <option value="Mythic T1 *">Mythic T1 *</option>
                <option value="Mythic T1 **">Mythic T1 **</option>
                <option value="Mythic T1 ***">Mythic T1 ***</option>
                <option value="Mythic T2">Mythic T2</option>
                <option value="Mythic T2 *">Mythic T2 *</option>
                <option value="Mythic T2 **">Mythic T2 **</option>
                <option value="Mythic T2 ***">Mythic T2 ***</option>
                <option value="Legendary">Legendary</option>
                <option value="Legendary *">Legendary *</option>
                <option value="Legendary **">Legendary **</option>
                <option value="Legendary ***">Legendary ***</option>
                <option value="Legendary T1">Legendary T1</option>
                <option value="Legendary T1 *">Legendary T1 *</option>
                <option value="Legendary T1 **">Legendary T1 **</option>
                <option value="Legendary T1 ***">Legendary T1 ***</option>
                <option value="Legendary T2">Legendary T2</option>
                <option value="Legendary T2 *">Legendary T2 *</option>
                <option value="Legendary T2 **">Legendary T2 **</option>
                <option value="Legendary T2 ***">Legendary T2 ***</option>
                <option value="Legendary T3">Legendary T3</option>
                <option value="Legendary T3 *">Legendary T3 *</option>
                <option value="Legendary T3 **">Legendary T3 **</option>
                <option value="Legendary T3 ***">Legendary T3 ***</option>
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
