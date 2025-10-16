'use strict';

/**
 * EST Editor – Gestione della sezione "Dynamic Overrides" nel widget Sync Template.
 * 
 * Gestisce:
 * - Fetch e popolamento chiavi dinamiche
 * - Attesa dell’espansione della sezione (DOM lazy load)
 * - Pulizia e sincronizzazione dello stato
 * 
 * @since 1.5.3
 */

class SyncTemplateEditor {
  constructor(panel, model, view) {
    this.panel = panel;
    this.model = model;
    this.view = view;

    this.state = {
      isFetching: false,
      isPopulated: false,
      fetchedKeys: null,
    };

    this.init();
  }

  /**------------------------------------------
   * INIT
   * @since 1.5.3
   * @since 1.5.4 - add css loader
   * @since 1.5.5 - fix classe .est-loaded non viene eliminata al caricamento
   * ------------------------------------------ */
  init() {
    console.log('[EST] === INIZIALIZZAZIONE EDITOR ===');

    this.panel.el.classList.remove('est-loaded');

    // 1. Carica subito le chiavi se un template è già selezionato
    const initialTemplateId = this.model.get('settings').get('template_id');
    console.log('[EST] Template selezionato:', initialTemplateId);

    if (initialTemplateId) {
      this.fetchTemplateKeys(initialTemplateId);
      this.state.isPopulated = true;
    }

    // 2. Ascolta cambio del template
    this.panel.listenTo(
      this.model.get('settings'),
      'change:template_id',
      this.onTemplateChange.bind(this)
    );

    // 3. Ascolta apertura della sezione
    this.observeSectionOpen();
  }

  /**------------------------------------------
   * LISTENER SEZIONE
   * @since 1.5.3
   * ------------------------------------------ */
  observeSectionOpen() {
    console.log('[EST] state.isPopulate:', this.state.isPopulated);

    if (this.state.isPopulated) {
      console.log(`[EST] state.isPopulated: ${this.state.isPopulated}, skip`);
      return;
    }

    const wrapper = document.querySelector('#elementor-panel-content-wrapper');
    if (!wrapper) return;

    wrapper.addEventListener('mousedown', (e) => {
      const section = e.target.closest('.elementor-control-section_dynamic_overrides');
      if (!section) return;

      // Se la sezione si sta aprendo, aspetta che venga renderizzata nel DOM
      this.waitForRepeater().then(() => {
        console.log('[EST] Dynamic Overrides pronto nel DOM');
        if (this.state.fetchedKeys) this.updateRepeater(this.state.fetchedKeys);
      });
    });
  }

  /**------------------------------------------
   * MUTATION OBSERVER – attende la vista repeater
   * @since 1.5.3
   * ------------------------------------------ */
  waitForRepeater() {
    return new Promise((resolve) => {
      const repeater = this.getRepeaterView();
      if (repeater) return resolve(repeater);

      const observer = new MutationObserver(() => {
        const found = this.getRepeaterView();
        if (found) {
          observer.disconnect();
          resolve(found);
        }
      });

      observer.observe(this.panel.el, { childList: true, subtree: true });

      // failsafe: disconnette dopo 3s
      setTimeout(() => observer.disconnect(), 3000);
    });
  }

  /**------------------------------------------
   * CAMBIO TEMPLATE
   * @since 1.5.3
   * ------------------------------------------ */
  onTemplateChange(model, newId) {
    const oldId = model.previous('template_id');
    if (newId === oldId) return;
    
    console.log('[EST] Cambio template:', newId);

    this.state.isPopulated = false;
    this.state.fetchedKeys = null;
    this.fetchTemplateKeys(newId);
  }

  /**------------------------------------------
   * FETCH KEYS
   * @since 1.5.3
   * @since 1.5.4 - add css loader
   * ------------------------------------------ */
  fetchTemplateKeys(templateId) {
    if (!templateId || this.state.isFetching) return;

    this.state.isFetching = true;
    this.panel.el.classList.add('est-loading-keys');

    wp.apiFetch({ path: `est/v1/templates/${templateId}/keys` })
      .then((res) => {
        this.state.fetchedKeys = res.keys || [];
        console.log('[EST] Chiavi recuperate:', this.state.fetchedKeys);
      })
      .catch((err) => {
        console.error('[EST] Errore fetch:', err);
        this.state.fetchedKeys = [];
      })
      .finally(() => {
        this.state.isFetching = false;
        this.panel.el.classList.remove('est-loading-keys');
        this.panel.el.classList.add('est-loaded');
      });
  }

  /**------------------------------------------
   * GET REPEATER VIEW
   * @since 1.5.3
   * ------------------------------------------ */
  getRepeaterView() {
    const controlView = elementor.getPanelView().getCurrentPageView();
    if (!controlView || !controlView.collection) return null;
    const repeaterModel = controlView.collection.findWhere({ name: 'dynamic_overrides' });
    return repeaterModel ? controlView.children.findByModelCid(repeaterModel.cid) : null;
  }

  /**------------------------------------------
   * UPDATE REPEATER
   * @since 1.5.3
   * @since 1.6.0 Aggiunto controlli condizionali
   * @since 1.6.1 Aggiunto controllo wysiwyg 
   * ------------------------------------------ */
  updateRepeater(keys) {
    const repeaterView = this.getRepeaterView();
    if (!repeaterView) return console.warn('[EST] Repeater non trovato');

    if (this.state.isPopulated) return;

    const collection = repeaterView.collection;
    const existingValues = {};

    collection.each((m) => {
      existingValues[m.get('override_key')] = m.get('override_value');
    });

    collection.reset();

    keys.forEach((field) => {
      collection.add({
        override_key: field.key,
        override_type: field.type || 'text',
        _override_label: field.label || field.key,
        // In base al tipo, puoi preimpostare il campo giusto
        ...(field.type === 'textarea'
          ? { override_value_textarea: existingValues[field.key] || '' }
          : field.type === 'wysiwyg'
          ? { override_value_wysiwyg: existingValues[field.key] || '' }
          : field.type === 'image'
          ? { override_value_image: existingValues[field.key] || '' }
          : field.type === 'url'
          ? { override_value_url: existingValues[field.key] || '' }
          : { override_value_text: existingValues[field.key] || '' }),
      });
    });

    this.state.isPopulated = true;
    console.log('[EST] Repeater popolato con', keys.length, 'voci');
  }
}

/**------------------------------------------
 * HOOK ELEMENTOR
 * @since 1.5.3
 * ------------------------------------------ */
const initEditor = () => {
  elementor.hooks.addAction('panel/open_editor/widget/sync-template', (panel, model, view) => {
    new SyncTemplateEditor(panel, model, view);
  });
};

if (window.elementor && window.elementor.hooks) {
  initEditor();
} else {
  window.addEventListener('elementor:init', initEditor);
}
