import { html } from 'https://esm.sh/lit@2.7.0';

export function PrioritySelectionViewTemplate(component) {
  return html`
    <div class="priority-selection">
      <h3>Set Sorting Priority</h3>
      <div class="priority-controls">
        <div class="priority-mode">
          <label>
            <input type="radio" name="priorityMode" value="simple" ?checked=${component.priorityMode === 'simple'} @change=${component._onPriorityModeChange}>
            Simple Priority (Drag to reorder)
          </label>
          <label>
            <input type="radio" name="priorityMode" value="weighted" ?checked=${component.priorityMode === 'weighted'} @change=${component._onPriorityModeChange}>
            Weighted Criteria
          </label>
        </div>
        
        <!-- Simple Priority Mode -->
        <div id="simplePriorityMode" style="display: ${component.priorityMode === 'simple' ? 'block' : 'none'};">
          <p>Drag items to set priority (top = highest priority)</p>
          <ol id="sortPriorityList">
            ${component.simplePriorityOrder.map(item => html`
              <li data-value="${item}">${component._getPriorityItemLabel(item)}</li>
            `)}
          </ol>
        </div>
        
        <!-- Weighted Criteria Mode -->
        <div id="weightedPriorityMode" style="display: ${component.priorityMode === 'weighted' ? 'block' : 'none'};">
          <p>Set weights for each criteria (higher weight = higher priority)</p>
          <div id="weightedCriteriaList">
            ${component.weightedCriteria.map(criteria => html`
              <div class="criteria-item">
                <label>${component._getPriorityItemLabel(criteria.criteria)}:</label>
                <input type="range" min="0" max="10" value="${criteria.weight}" step="0.1" 
                       data-criteria="${criteria.criteria}" class="weight-slider" 
                       @input=${(e) => component._onWeightSliderChange(e, criteria.criteria)}>
                <span class="weight-value">${criteria.weight.toFixed(1)}</span>
              </div>
            `)}
          </div>
        </div>
      </div>
    </div>
  `;
}
