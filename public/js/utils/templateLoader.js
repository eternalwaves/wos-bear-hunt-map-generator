// Template Loader Utility
export class TemplateLoader {
  static async loadTemplate(templatePath) {
    try {
      const response = await fetch(templatePath);
      if (!response.ok) {
        throw new Error(`Failed to load template: ${response.statusText}`);
      }
      return await response.text();
    } catch (error) {
      console.error(`Error loading template from ${templatePath}:`, error);
      return '';
    }
  }

  static async loadCSS(cssPath) {
    try {
      const response = await fetch(cssPath);
      if (!response.ok) {
        throw new Error(`Failed to load CSS: ${response.statusText}`);
      }
      return await response.text();
    } catch (error) {
      console.error(`Error loading CSS from ${cssPath}:`, error);
      return '';
    }
  }

  static createHTMLTemplate(templateString) {
    // Convert template string to Lit html template
    // This is a simplified version - in practice, you might need more sophisticated parsing
    return templateString;
  }

  static createCSSTemplate(cssString) {
    // Convert CSS string to Lit css template
    return cssString;
  }
} 