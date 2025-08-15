import { html } from 'https://esm.sh/lit@2.7.0';

export function TrapTableViewTemplate(component) {
  return html`
    <div class="trap-section">
      <h3>
        Traps
        <button class="add-trap-btn" @click=${component._toggleForm}>
          ${component.showForm ? 'Cancel' : 'Add Trap'}
        </button>
      </h3>

      ${component.showForm ? html`
        <trap-form @trap-submitted=${component._onTrapSubmitted} @form-cancelled=${component._onFormCancelled}></trap-form>
      ` : ''}

      <table class="trap-table">
        <thead>
          <tr>
            <th>#</th>
            <th>X</th>
            <th>Y</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${component.traps.length === 0 ? html`
            <tr>
              <td colspan="4" style="text-align: center; color: #6c757d; font-style: italic; padding: 40px;">
                No traps added yet
              </td>
            </tr>
          ` : component.traps.map((trap, index) => html`
            <tr>
              <td>${index + 1}.</td>
              <td>${trap.x}</td>
              <td>${trap.y}</td>
              <td class="actionBtns">
                <button @click=${() => component._onEdit(trap)}>Edit</button>
                <button @click=${() => component._onDelete(trap)}>Delete</button>
              </td>
            </tr>
          `)}
        </tbody>
      </table>
    </div>
  `;
}
