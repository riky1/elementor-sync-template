(function ($) {
  'use strict';

  console.log("= EST = 1 - est-editor.js caricato correttamente");

  /** 
   * Gestisce la logica dell'editor per il widget Sync Template.
   * 
   * @since 1.4.4
   * @param {Object} panel La vista del pannello di controllo di Elementor.
   * @param {Object} model Il modello di dati del widget.
   */
  var SyncTemplateEditor = function (panel, model, element) {
    
    var self = this;

    /**
     * Inizializza gli eventi.
     * 
     * @since 1.4.4
     */
    this.init = function () {
      console.log("= EST = 4 - SyncTemplateEditor init");

      // Recupero id del template selezionato
      
      // Ascolta quando viene aperta la sezione Dynamic Overrides.      

      // Ascolta le modifiche al controllo 'template_id'.

    };

    this.init();
  };

  /**
   * Inizializza l'editor di Elementor quando è pronto.
   * 
   * @since 1.4.4
   */
  $(window).on("elementor/frontend/init", () => {
    console.log("= EST = 2 - elementor/frontend/init");

    elementor.hooks.addAction(
      "panel/open_editor/widget",
      function (panel, model, view) {

        if ("sync-template" !== model.get("widgetType")) {
          console.log("= EST = 3 -Esce perché non è sync-template");
          return;
        }

        console.log("= EST = 3 - Inizializza SyncTemplateEditor");

        var $element = view.$el.find('.elementor-control');
        
        new SyncTemplateEditor(panel, model, $element);

      }
    );
  });
})(jQuery);
