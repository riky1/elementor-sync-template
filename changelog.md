### Elementor Sync Templates Changelog ###

### 1.6.1 - 16/10/2025

*   Feat: add wysiwyg control
*   Style: nascosto button 'Aggiungi elemento' dal repeater del template
*   Feat: spostato controlli nel tab Advanced e aggiunto hook anche per i widget container (non tutti i controlli hanno il Tab Content).

### 1.6.0 - 16/10/2025

*   Feat: add conditional control in widget

### 1.5.6 - 16/10/2025

*   Feat: piccole modifiche ai controlli di class-dynamic-fields.php.

### 1.5.5 - 15/10/2025

*   Style: est-editor.css: add add .est-control-disabled
*   Fix: est-editor.js: classe est-loaded viene aggiunta subito
*   Widget: piccole modifiche ai controlli

### 1.5.4 - 15/10/2025

*   Style: add css loader to est-editor.

### 1.5.3 - 15/10/2025

*   Feat: complete refactor est-editor.js.

### 1.5.2 - 15/10/2025

*   Style: refactor est-editor.js.

### 1.5.1 - 15/10/2025

*   Fix: popolamento repeater - cancella gli override se il pannello viene chiuso e riaperto.

### 1.5.0 - 14/10/2025

*   Add: logica js per popolamento automatico delle chiavi.

### 1.4.5 - 14/10/2025

*   Add: js + css admin support.

### 1.4.4 - 12/10/2025

*   Add: est-editor.js

### 1.4.3 - 10/10/2025

*   Add: logica di rendering con override dinamici.

### 1.4.2 - 10/10/2025

*   Add: repeater nel widget per i campi dinamici.

### 1.4.1 - 10/10/2025

*   Fix: la sezione 'Sync template' viene aggiunta anche in contesti non validi (es: modifica pagina).

### 1.4.0 - 10/10/2025

*   Add: sync-template-widget - widget base - codice di partenza con selettore per il template. 

### 1.3.0 - 10/10/2025

*   class-dynamic-fields.php - aggiunto repeater ai controlli di Elementor.
*   rest-api - aggiunto supporto per repeater.

### 1.2.1 - 10/10/2025

*   Fix: module class-dynamic-fields.php la classe viene caricata molte volte (per ogni componente di Elementor).

### 1.2.0 - 09/10/2025

*   Add: rest-api per controllo chiavi dinamiche con Postman.

### 1.1.0 - 09/10/2025

*   Add: modulo campi dinamici `class-dynamic-fields.php` visualizzati nella TAB Content di Elementor quando si crea/modifica un template.

### 1.0.0 - Initial Release - 09/10/2025

*   Creazione della struttura base del plugin.
*   Add: Compatibility_Manager class
*   Add: Custom Post Type `es_template`.
*   Add: `template-canvas` vuoto per nuovo/modifica template