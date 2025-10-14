'use strict';

/**
 * @since 1.5.0
 */

console.log('EST: est-editor.js caricato correttamente');

/**
 * Gestisce la logica dell'editor per il widget Sync Template.
 *
 * @since 1.5.0
 */
class SyncTemplateEditor {

  constructor(panel, model, view) {
    this.panel = panel;
    this.model = model;
    this.view = view;
    this.fetchedKeys = null; // Contiene le chiavi recuperate, pronte per essere renderizzate.
    this.isFetching = false; // Flag per evitare chiamate multiple.

    this.init();
  }

  /**
   * Inizializza gli eventi.
   * 
   * @since 1.5.0
   */
  init() {
    console.log('EST: init()');

    // 1. Ascolta il cambio del template.
    this.panel.listenTo(
      this.model.get('settings'),
      'change:template_id',
      this.onTemplateChange.bind(this)
    );

    // 2. Ascolta il click sulla sezione usando la delegazione di eventi sul pannello principale di Elementor.
    const panelWrapper = document.querySelector('#elementor-panel-content-wrapper');

    if (panelWrapper) {
      panelWrapper.addEventListener('mousedown', (event) => {

        // Controlla se l'evento è partito dalla sezione 'dynamic overrides'.
        const sectionHeader = event.target.closest('.elementor-control-section_dynamic_overrides');
        
        if (sectionHeader) {
          this.onSectionOpen();
        }
      });
    };

    // 3. Al caricamento, se un template è già selezionato, recupera subito le chiavi.
    const initialTemplateId = this.model.get('settings').get('template_id');

    if (initialTemplateId) {
      this.fetchTemplateKeys(initialTemplateId);
    }
  }

  /**
   * Chiamato quando l'utente cambia il template selezionato.
   * 
   * @since 1.5.0
   */
  onTemplateChange(model, newTemplateId) {
    console.log(
      '>>> EVENTO: Cambio template intercettato! Nuovo ID:',
      newTemplateId
    );

    this.fetchedKeys = null; // Resetta le chiavi precedenti.
    this.fetchTemplateKeys(newTemplateId);
  }

  /**
   * Chiamato quando l'utente clicca sulla sezione.
   * 
   * @since 1.5.0
   */
  onSectionOpen() {
    console.log('>>> EVENTO: Click su Dynamic Overrides intercettato! <<<');

    // Se la sezione si sta aprendo e sono presenti delle chiavi, aggiorna il repeater.

    if (this.fetchedKeys === null) {
      return; // Non ci sono dati da mostrare.
    }

    // Controlla se il repeater è già visibile (sezione già aperta).
    // const repeaterView = this.getRepeaterView();

    // if (repeaterView) {
    //   console.log('EST: Repeater già trovato. Aggiornamento in corso...');
      
    //   this.updateRepeater(this.fetchedKeys);
    //   return;
    // }

    // Se il repeater non è ancora visibile, usa un MutationObserver per attenderlo.
    const observer = new MutationObserver((mutations, obs) => {
      const repeaterView = this.getRepeaterView();

      if (repeaterView) {
        console.log('EST: Repeater trovato dall\'observer. Aggiornamento in corso...');
        this.updateRepeater(this.fetchedKeys);

        obs.disconnect(); // Pulizia: scollega l'observer una volta eseguito il compito.
      }
    });

    // Avvia l'osservazione del pannello per le modifiche al DOM.
    observer.observe(this.panel.el, {
      childList: true, // Osserva l'aggiunta/rimozione di figli.
      subtree: true,   // Osserva anche nei sotto-elementi.
    });

    // Failsafe: scollega l'observer dopo 2 secondi per evitare memory leak.
    setTimeout(() => observer.disconnect(), 2000);
  }

  /**
   * Esegue una chiamata REST per ottenere le chiavi dinamiche del template.
   * 
   * @since 1.5.0
   */
  fetchTemplateKeys(templateId) {
    console.log('EST: fetchTemplateKeys() templateId:', templateId);

    if (!templateId) {
      this.fetchedKeys = [];
      return;
    }

    if (this.isFetching) return;
    this.isFetching = true;

    console.log('EST: Chiamata API per template ID:', templateId);

    // Aggiunge una classe al contenitore del pannello per nascondere la sezione tramite CSS.
    this.panel.el.classList.add('est-loading-keys');
    
    const repeaterControl = this.panel.el.querySelector(
      '.elementor-control-dynamic_overrides'
    );

    if (repeaterControl) repeaterControl.classList.add('elementor-loading');

    wp.apiFetch({ path: 'est/v1/templates/' + templateId + '/keys' })
      .then((data) => {
        
        this.fetchedKeys = data.keys || [];
        console.log('EST: API success. Chiavi memorizzate:', this.fetchedKeys);

      })
      .catch((error) => {

        console.error('EST: Errore API.', error);
        this.fetchedKeys = null;

      })
      .finally(() => {

        this.isFetching = false;

        // Rimuove la classe dal contenitore per mostrare di nuovo la sezione.
        this.panel.el.classList.remove('est-loading-keys');
        if (repeaterControl)
          repeaterControl.classList.remove('elementor-loading');

      });
  }

  /**
   * Trova e restituisce la vista del repeater.
   * 
   * @since 1.5.0
   * @returns {Object|null} La vista del repeater o null se non trovata.
   */
  getRepeaterView() {
    const controlView = elementor.getPanelView().getCurrentPageView();

    if (!controlView || !controlView.collection) {
      return null;
    }

    const repeaterModel = controlView.collection.findWhere({ name: 'dynamic_overrides' });

    if (!repeaterModel) {
      return null;
    }

    return controlView.children.findByModelCid(repeaterModel.cid);
  }

  /**
   * Aggiorna il repeater 'dynamic_overrides' con le nuove chiavi.
   * 
   * @since 1.5.0
   */
  updateRepeater(keys) {

    // 1. Normalizzazione dell'input:

    // Assicura che `keys` sia sempre un array.
    if (!Array.isArray(keys)) {
      keys = [];
    }

    // 2. Ottiene la "vista" del Repeater.

    // Utilizzo della funzione di supporto getRepeaterView() per trovare l'oggetto che rappresenta il controllo repeater nel pannello di Elementor.
    const repeaterView = this.getRepeaterView();

    // Se la sezione del pannello che contiene il repeater è chiusa, la "vista" (repeaterView) non esiste nel DOM
    if (!repeaterView) {
      console.error(
        'EST: Vista del repeater non trovata al momento dell\'aggiornamento.'
      );
      return;
    }

    // 3. Salvataggio dei valori esistenti.

    console.log('EST: Aggiornamento repeater con', keys.length, 'chiavi.');

    const repeaterCollection = repeaterView.collection;
    const existingValues = {};

    // Cicla (.each) su tutti gli elementi già presenti nel repeater e ne salva i valori in un oggetto existingValues.
    // Usa la override_key come chiave e la override_value come valore.
    repeaterCollection.each((model) => {
      existingValues[model.get('override_key')] = model.get('override_value');
    });

    // 4. Svuotamento e ripopolamento.

    // Svuota completamente il repeater, rimuovendo tutti gli elementi attuali.
    repeaterCollection.reset();

    // Controlla se ci sono nuove chiavi da aggiungere.
    if (keys.length > 0) {

      // Itera sul nuovo array di chiavi (keys) ricevuto dalla chiamata API.
      keys.forEach((field) => {

        // Per ogni chiave, aggiunge un nuovo elemento al repeater.
        repeaterCollection.add({

          // chiave univoca del campo
          override_key: field.key,

          // Il valore del campo. Cerca se esiste un valore in existingValues per la chiave corrente (existingValues[field.key]).
          // Se lo trova, lo usa; altrimenti, inserisce una stringa vuota (|| '')
          override_value: existingValues[field.key] || '',

          // L'etichetta visualizzata nel repeater. Usa il field.label se esiste, altrimenti ripiega sulla field.key
          _override_label: field.label || field.key,

        });

      });

    }
  }
}

/**
 * Funzione che registra gli hook del widget nell'editor di Elementor.
 * 
 * @since 1.5.0
 */
var initializeEditor = function () {
  elementor.hooks.addAction( 'panel/open_editor/widget/sync-template', function (panel, model, view) {

    new SyncTemplateEditor(panel, model, view);

  });
};

/**
 * Controlla se l'editor di Elementor è già stato inizializzato.
 * 
 * @since 1.5.0
 */
if (window.elementor && window.elementor.hooks) {
  // Se sì, esegue subito la funzione.
  initializeEditor();
} else {
  // Altrimenti, attende l'evento 'elementor:init'.
  window.addEventListener('elementor:init', initializeEditor);
}
