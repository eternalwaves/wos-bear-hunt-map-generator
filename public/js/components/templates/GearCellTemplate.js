import { html } from 'https://esm.sh/lit@2.7.0';

export function GearCellTemplate(component) {
  return html`
    <div class="gear-cell" @click=${component._onClick}>
      <div class="gear-level ${component._getLevelClass()}">${component.level || '-'}</div>
      <div class="gear-charms ${component._getCharmsClass()}">${component._formatCharms()}</div>
    </div>
  `;
}
