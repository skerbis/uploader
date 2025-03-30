/* globals Dropzone, selectMedia, selectMedialist */

// Konfiguration für Dropzone verhindern, dass es automatisch Uploads findet
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function() {
    
    // Sicherstellen, dass uploader_options verfügbar ist
    if (typeof window.uploader_options === 'undefined') {
        console.error('Uploader options not found. Make sure vars.php is included before uploader.js');
        
        // Default-Optionen als Fallback erstellen
        window.uploader_options = {
            messages: {
                maxNumberOfFiles: "Maximale Anzahl an Dateien überschritten",
                acceptFileTypes: "Unzulässiger Dateityp",
                maxFileSize: "Datei zu groß",
                minFileSize: "Datei zu klein",
                selectFile: "Übernehmen"
            },
            context: "mediapool_upload",
            endpoint: "index.php?page=uploader/endpoint",
            loadImageMaxFileSize: 30000000, // 30 MB default
            imageMaxWidth: 4000,
            imageMaxHeight: 4000,
            acceptFileTypes: null
        };
    }
    
    // REDAXO MediaPool-Kategorie auswählen
    const mediaCatSelect = document.getElementById('rex-mediapool-category');
    if (!mediaCatSelect) return;
    
    const form = mediaCatSelect.closest('form');
    if (!form) return;
    
    // Dropzone Konfiguration
    const dropzoneOptions = {
        url: window.uploader_options.endpoint,
        paramName: "files", // Der Name des Datei-Parameters im Request
        maxFilesize: window.uploader_options.loadImageMaxFileSize / 1000000, // MB
        acceptedFiles: window.uploader_options.acceptFileTypes ? window.uploader_options.acceptFileTypes : null,
        addRemoveLinks: true,
        dictDefaultMessage: "Dateien hier ablegen oder klicken zum Auswählen",
        dictFallbackMessage: "Dein Browser unterstützt keine Drag'n'Drop Datei-Uploads.",
        dictFileTooBig: window.uploader_options.messages.maxFileSize,
        dictInvalidFileType: window.uploader_options.messages.acceptFileTypes,
        dictResponseError: "Server antwortete mit {{statusCode}} Code.",
        dictCancelUpload: "Upload abbrechen",
        dictUploadCanceled: "Upload abgebrochen.",
        dictRemoveFile: "Datei entfernen",
        dictRemoveFileConfirmation: null,
        thumbnailWidth: 120,
        thumbnailHeight: 120,
        previewTemplate: document.querySelector('#dropzone-preview-template')?.innerHTML || 
            '<div class="dz-preview dz-file-preview"><div class="dz-image"><img data-dz-thumbnail /></div><div class="dz-details"><div class="dz-size"><span data-dz-size></span></div><div class="dz-filename"><span data-dz-name></span></div></div><div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div><div class="dz-error-message"><span data-dz-errormessage></span></div><div class="dz-success-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M14,27 L22,35 L42,15" stroke="#228B22" stroke-width="3" fill="none"/></svg></div><div class="dz-error-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M17,17 L37,37 M37,17 L17,37" stroke="#a94442" stroke-width="3" fill="none"/></svg></div></div>',
        autoProcessQueue: false, // Nicht automatisch hochladen
        uploadMultiple: true,
        parallelUploads: 5,
        createImageThumbnails: true,
        resizeWidth: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxWidth : null,
        resizeHeight: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxHeight : null,
        resizeMethod: 'contain',
        resizeQuality: 0.8,
        init: function() {
            const myDropzone = this;
            
            // Upload-Button-Klick
            const startButton = document.querySelector(".start");
            if (startButton) {
                startButton.addEventListener("click", function() {
                    myDropzone.processQueue();
                });
            }
            
            // Kategorie-Parameter hinzufügen
            this.on("sending", function(file, xhr, formData) {
                const category = document.getElementById('rex-mediapool-category');
                if (category) {
                    formData.append("rex_file_category", category.value);
                }
                
                const title = document.querySelector('[name="ftitle"]');
                if (title) {
                    formData.append("ftitle", title.value);
                }
                
                // Dateiname als Titel verwenden wenn Option aktiviert
                const filenameAsTitle = document.getElementById('filename-as-title');
                if (filenameAsTitle && filenameAsTitle.checked) {
                    formData.append("filename-as-title", "1");
                }
                
                // Alle Metainfo-Felder hinzufügen
                document.querySelectorAll('form [name^="med_"]').forEach(function(el) {
                    formData.append(el.name, el.value);
                });
            });
            
            // Nach Upload Erfolg
            this.on("success", function(file, response) {
                // REDAXO Medienpool Integration
                if (file.previewElement) {
                    if (response && response.files && response.files[0]) {
                        const fileInfo = response.files[0];
                        
                        // Wenn eine Fehler passiert ist
                        if (fileInfo.error) {
                            const node = file.previewElement.querySelector("[data-dz-errormessage]");
                            if (node) node.textContent = fileInfo.error;
                            file.previewElement.classList.add("dz-error");
                            return;
                        }
                        
                        // Erfolgreicher Upload
                        file.previewElement.classList.add("dz-success");
                        
                        // Thumbnail aktualisieren wenn verfügbar
                        if (fileInfo.thumbnailUrl) {
                            const imgElement = file.previewElement.querySelector("[data-dz-thumbnail]");
                            if (imgElement) {
                                imgElement.src = fileInfo.thumbnailUrl;
                                imgElement.alt = fileInfo.name;
                            }
                        }
                        
                        // Übernehmen-Button für Widget hinzufügen
                        const opener_input_field = new URLSearchParams(window.location.search).get('opener_input_field');
                        if (opener_input_field) {
                            const selectButton = document.createElement('button');
                            selectButton.className = 'btn btn-xs btn-select';
                            selectButton.setAttribute('data-filename', fileInfo.name);
                            selectButton.textContent = window.uploader_options.messages.selectFile || 'Übernehmen';
                            selectButton.addEventListener('click', function(e) {
                                e.preventDefault();
                                if (opener_input_field.substr(0, 14) === 'REX_MEDIALIST_') {
                                    selectMedialist(fileInfo.name, '');
                                } else {
                                    selectMedia(fileInfo.name, '');
                                }
                            });
                            const successMark = file.previewElement.querySelector(".dz-success-mark");
                            if (successMark) {
                                successMark.appendChild(selectButton);
                            }
                        }
                    }
                }
            });
            
            // Bei Resize-Checkbox-Änderung Resizing aktivieren/deaktivieren
            const resizeCheckbox = document.getElementById('resize-images');
            if (resizeCheckbox) {
                resizeCheckbox.addEventListener('change', function() {
                    myDropzone.options.resizeWidth = this.checked ? window.uploader_options.imageMaxWidth : null;
                    myDropzone.options.resizeHeight = this.checked ? window.uploader_options.imageMaxHeight : null;
                });
            }
        }
    };
    
    // Metafelder bei Kategoriewechsel holen
    if (mediaCatSelect) {
        mediaCatSelect.addEventListener('change', function() {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'page': 'mediapool/upload',
                    'rex_file_category': mediaCatSelect.value
                })
            })
            .then(response => response.text())
            .then(html => {
                updateMetafields(html);
            })
            .catch(error => {
                console.error('Fehler beim Laden der Metafelder:', error);
            });
        });
    }
    
    // Metafelder aktualisieren
    function updateMetafields(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const localParent = mediaCatSelect.closest('.form-group').parentNode;
        if (!localParent) return;
        
        const ajaxParent = doc.querySelector('#rex-mediapool-category');
        if (!ajaxParent) return;
        
        const ajaxFieldset = ajaxParent.closest('fieldset');
        if (!ajaxFieldset) return;
        
        // Bestehende Metafelder entfernen
        localParent.querySelectorAll('.form-group:not(.preserve)').forEach(el => {
            el.remove();
        });
        
        // Neue Metafelder einfügen
        const metafields = Array.from(ajaxFieldset.querySelectorAll('.form-group'));
        const appendAfter = localParent.querySelector('.append-meta-after');
        
        if (appendAfter) {
            metafields.reverse().forEach(field => {
                const nameEl = field.querySelector('[name]');
                if (!nameEl) return;
                
                const name = nameEl.getAttribute('name');
                
                // Nicht-Meta-Felder überspringen
                if (['ftitle', 'rex_file_category', 'file_new'].includes(name)) {
                    return;
                }
                
                const existingField = document.querySelector(`[name="${name}"]`);
                if (existingField) {
                    // Wert aus bestehendem Feld übernehmen
                    const newField = field.cloneNode(true);
                    const newInput = newField.querySelector(`[name="${name}"]`);
                    if (newInput) {
                        newInput.value = existingField.value;
                    }
                    appendAfter.insertAdjacentElement('afterend', newField);
                } else {
                    // Neues Feld einfügen
                    appendAfter.insertAdjacentElement('afterend', field);
                }
            });
        }
        
        // REDAXO Events auslösen
        const event = new CustomEvent('rex:ready', { detail: localParent });
        document.dispatchEvent(event);
    }
    
    // Sicherstellen, dass die Formklasse auf dem Formular gesetzt ist
    if (form && !form.classList.contains('dropzone')) {
        form.setAttribute('action', window.uploader_options.endpoint);
    }
    
    // Dropzone-Element erstellen und initialisieren
    const uploadContainer = document.createElement('div');
    uploadContainer.className = 'uploader-dropzone dropzone';
    uploadContainer.id = 'uploader-dropzone';
    
    // Dropzone-Element in das Formular einfügen
    const formFieldset = form.querySelector('fieldset');
    if (formFieldset) {
        formFieldset.appendChild(uploadContainer);
        
        // Existierende Uploads-Elemente verstecken
        const oldUploadField = form.querySelector('input[type="file"]');
        if (oldUploadField) {
            const oldUploadParent = oldUploadField.closest('.form-group');
            if (oldUploadParent) {
                oldUploadParent.style.display = 'none';
            }
        }
        
        // Titel und Kategorie Felder markieren, damit sie nicht entfernt werden
        const titleField = form.querySelector('[name="ftitle"]');
        if (titleField) {
            const titleGroup = titleField.closest('.form-group');
            if (titleGroup) {
                titleGroup.classList.add('preserve', 'append-meta-after');
            }
        }
        
        if (mediaCatSelect) {
            const catGroup = mediaCatSelect.closest('.form-group');
            if (catGroup) {
                catGroup.classList.add('preserve');
            }
        }
    }
    
    // Dropzone initialisieren
    new Dropzone("#uploader-dropzone", dropzoneOptions);
});
