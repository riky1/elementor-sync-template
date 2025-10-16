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
   * Popola dinamicamente i campi di override nel widget "Sync Template" 
   * @since 1.5.3
   * @since 1.6.0 Aggiunto controlli condizionali
   * @since 1.6.1 Aggiunto controllo wysiwyg 
   * @since 1.7.0 passaggio da KEY a ID
   * ------------------------------------------ */
  updateRepeater(keys) {
    const repeaterView = this.getRepeaterView();
    if (!repeaterView) return console.warn('[EST] Repeater non trovato');

    if (this.state.isPopulated) return;

    const collection = repeaterView.collection;

    // 1️. Recupera i valori esistenti nel repeater
    const existingValues = {};

    collection.each((m) => {
      existingValues[m.get('override_key')] =
        m.get('override_value_text') ||
        m.get('override_value_textarea') ||
        m.get('override_value_wysiwyg') ||
        m.get('override_value_image') ||
        m.get('override_value_url') ||
        '';
    });

    // 2️. Svuota il repeater
    collection.reset();

    // 3️. Aggiungi solo i campi abilitati come dinamici
    keys.forEach((field) => {
      if (field.is_dynamic !== 'yes') return; // ignora campi non dinamici

      const overrideKey = field._id; // usa _id come chiave unica
      const type = field.type || 'text';

      const item = {
        override_key: overrideKey,
        override_type: type,
        _override_label: field.label || overrideKey,
      };

      // 4️. Imposta il valore corretto in base al tipo
      switch (type) {
        case 'textarea':
          item.override_value_textarea = existingValues[overrideKey] || '';
          break;
        case 'wysiwyg':
          item.override_value_wysiwyg = existingValues[overrideKey] || '';
          break;
        case 'image':
          item.override_value_image = existingValues[overrideKey] || '';
          break;
        case 'url':
          item.override_value_url = existingValues[overrideKey] || '';
          break;
        default:
          item.override_value_text = existingValues[overrideKey] || '';
      }

      // Mantiene anche l'_id nel repeater
      collection.add({
        ...item,
        _id: field._id,
      });
    });

    this.state.isPopulated = true;
    console.log('[EST] Repeater popolato con', collection.length, 'campi dinamici');
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
