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
    var mediaCatSelect = document.getElementById('rex-mediapool-category');
    if (!mediaCatSelect) return;
    
    var form = mediaCatSelect.closest('form');
    if (!form) return;
    
    // Uploader im MediaPool hinzufügen, wenn wir im richtigen Kontext sind
    if (window.uploader_options.context === 'mediapool_upload') {
        // MediaPool Formular vorbereiten und unser Template einfügen
        var rexMediapoolChooseFile = document.getElementById('rex-mediapool-choose-file');
        if (rexMediapoolChooseFile) {
            // Original-Templates aus unserem versteckten Div holen
            var templateDiv = document.getElementById('uploader-buttonbar-template');
            if (templateDiv) {
                // Dropzone-Template einfügen
                var uploaderRow = templateDiv.querySelector('#uploader-row');
                if (uploaderRow) {
                    // Vor dem MediaPool-Choose-Div einfügen
                    rexMediapoolChooseFile.parentNode.insertBefore(uploaderRow.cloneNode(true), rexMediapoolChooseFile);
                    
                    // Das Original-Input-Feld verstecken
                    rexMediapoolChooseFile.style.display = 'none';
                }
            }
        }
        
        // Dropzone-Container erstellen und auf den existierenden Bereich anwenden
        var dropzoneContainer = document.querySelector('.uploader-dropzone');
        if (dropzoneContainer) {
            // Dropzone Konfiguration
            var dropzoneOptions = {
                url: window.uploader_options.endpoint || form.getAttribute('action'),
                paramName: "files", // Der Name des Datei-Parameters im Request
                maxFilesize: window.uploader_options.loadImageMaxFileSize / 1000000, // MB
                acceptedFiles: window.uploader_options.acceptFileTypes || null,
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
                previewTemplate: document.querySelector('#dropzone-preview-template') ? 
                    document.querySelector('#dropzone-preview-template').innerHTML : 
                    '<div class="dz-preview dz-file-preview"><div class="dz-image"><img data-dz-thumbnail /></div><div class="dz-details"><div class="dz-size"><span data-dz-size"></span></div><div class="dz-filename"><span data-dz-name"></span></div></div><div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div><div class="dz-error-message"><span data-dz-errormessage></span></div><div class="dz-success-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M14,27 L22,35 L42,15" stroke="#228B22" stroke-width="3" fill="none"/></svg></div><div class="dz-error-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M17,17 L37,37 M37,17 L17,37" stroke="#a94442" stroke-width="3" fill="none"/></svg></div></div>',
                autoProcessQueue: false, // Nicht automatisch hochladen
                parallelUploads: 5,
                createImageThumbnails: true,
                
                // Chunked Upload Konfiguration
                chunking: true,
                forceChunking: true, // Erzwinge Chunking auch für kleine Dateien
                chunkSize: 5000000, // 5 MB pro Chunk
                retryChunks: true, // Wiederhole fehlgeschlagene Chunks
                retryChunksLimit: 3, // Maximale Anzahl von Wiederholungsversuchen
                parallelChunkUploads: true, // Paralleles Hochladen von Chunks
                
                resizeWidth: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxWidth : null,
                resizeHeight: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxHeight : null,
                resizeMethod: 'contain',
                resizeQuality: 0.8,
                init: function() {
                    var myDropzone = this;
                    
                    // Upload-Button-Klick
                    var startButton = document.querySelector(".start");
                    if (startButton) {
                        startButton.addEventListener("click", function() {
                            myDropzone.processQueue();
                        });
                    }
                    
                    // Kategorie-Parameter hinzufügen
                    this.on("sending", function(file, xhr, formData) {
                        var category = document.getElementById('rex-mediapool-category');
                        if (category) {
                            formData.append("rex_file_category", category.value);
                        }
                        
                        var title = document.querySelector('[name="ftitle"]');
                        if (title) {
                            formData.append("ftitle", title.value);
                        }
                        
                        // Dateiname als Titel verwenden wenn Option aktiviert
                        var filenameAsTitle = document.getElementById('filename-as-title');
                        if (filenameAsTitle && filenameAsTitle.checked) {
                            formData.append("filename-as-title", "1");
                        }
                        
                        // Alle Metainfo-Felder hinzufügen
                        var metaFields = document.querySelectorAll('form [name^="med_"]');
                        for (var i = 0; i < metaFields.length; i++) {
                            formData.append(metaFields[i].name, metaFields[i].value);
                        }
                    });
                    
                    // Nach Upload Erfolg
                    this.on("success", function(file, response) {
                        // REDAXO Medienpool Integration
                        if (file.previewElement) {
                            if (response && response.files && response.files[0]) {
                                var fileInfo = response.files[0];
                                
                                // Wenn eine Fehler passiert ist
                                if (fileInfo.error) {
                                    var node = file.previewElement.querySelector("[data-dz-errormessage]");
                                    if (node) node.textContent = fileInfo.error;
                                    file.previewElement.classList.add("dz-error");
                                    return;
                                }
                                
                                // Erfolgreicher Upload
                                file.previewElement.classList.add("dz-success");
                                
                                // Thumbnail aktualisieren wenn verfügbar
                                if (fileInfo.thumbnailUrl) {
                                    var imgElement = file.previewElement.querySelector("[data-dz-thumbnail]");
                                    if (imgElement) {
                                        imgElement.src = fileInfo.thumbnailUrl;
                                        imgElement.alt = fileInfo.name;
                                    }
                                }
                                
                                // Übernehmen-Button für Widget hinzufügen
                                var urlParams = new URLSearchParams(window.location.search);
                                var opener_input_field = urlParams.get('opener_input_field');
                                if (opener_input_field) {
                                    var selectButton = document.createElement('button');
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
                                    var successMark = file.previewElement.querySelector(".dz-success-mark");
                                    if (successMark) {
                                        successMark.appendChild(selectButton);
                                    }
                                }
                            }
                        }
                    });
                    
                    // Bei Resize-Checkbox-Änderung Resizing aktivieren/deaktivieren
                    var resizeCheckbox = document.getElementById('resize-images');
                    if (resizeCheckbox) {
                        resizeCheckbox.addEventListener('change', function() {
                            myDropzone.options.resizeWidth = this.checked ? window.uploader_options.imageMaxWidth : null;
                            myDropzone.options.resizeHeight = this.checked ? window.uploader_options.imageMaxHeight : null;
                        });
                    }
                }
            };
            
            // Dropzone auf den Container anwenden
            new Dropzone(dropzoneContainer, dropzoneOptions);
        }
    } else if (window.uploader_options.context === 'addon_upload') {
        // Für die eigenständige Upload-Seite
        var uploadContainer = document.getElementById('uploader-dropzone');
        if (uploadContainer) {
            // Dropzone Konfiguration (gleiche wie oben)
            var dropzoneOptions = {
                url: window.uploader_options.endpoint || form.getAttribute('action'),
                paramName: "files",
                maxFilesize: window.uploader_options.loadImageMaxFileSize / 1000000,
                acceptedFiles: window.uploader_options.acceptFileTypes || null,
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
                previewTemplate: document.querySelector('#dropzone-preview-template') ? 
                    document.querySelector('#dropzone-preview-template').innerHTML : 
                    '<div class="dz-preview dz-file-preview"><div class="dz-image"><img data-dz-thumbnail /></div><div class="dz-details"><div class="dz-size"><span data-dz-size></span></div><div class="dz-filename"><span data-dz-name></span></div></div><div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div><div class="dz-error-message"><span data-dz-errormessage></span></div><div class="dz-success-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M14,27 L22,35 L42,15" stroke="#228B22" stroke-width="3" fill="none"/></svg></div><div class="dz-error-mark"><svg width="54px" height="54px" viewBox="0 0 54 54"><circle cx="27" cy="27" r="25" fill="white"/><path d="M17,17 L37,37 M37,17 L17,37" stroke="#a94442" stroke-width="3" fill="none"/></svg></div></div>',
                autoProcessQueue: false,
                parallelUploads: 5,
                createImageThumbnails: true,
                
                // Chunked Upload Konfiguration
                chunking: true,
                forceChunking: true,
                chunkSize: 5000000, // 5 MB pro Chunk
                retryChunks: true,
                retryChunksLimit: 3,
                parallelChunkUploads: true,
                
                resizeWidth: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxWidth : null,
                resizeHeight: document.getElementById('resize-images') && document.getElementById('resize-images').checked ? window.uploader_options.imageMaxHeight : null,
                resizeMethod: 'contain',
                resizeQuality: 0.8,
                init: function() {
                    var myDropzone = this;
                    
                    // Upload-Button-Klick
                    var startButton = document.querySelector(".start");
                    if (startButton) {
                        startButton.addEventListener("click", function() {
                            myDropzone.processQueue();
                        });
                    }
                    
                    // Parameter hinzufügen
                    this.on("sending", function(file, xhr, formData) {
                        var category = document.getElementById('rex-mediapool-category');
                        if (category) {
                            formData.append("rex_file_category", category.value);
                        }
                        
                        var title = document.querySelector('[name="ftitle"]');
                        if (title) {
                            formData.append("ftitle", title.value);
                        }
                        
                        var filenameAsTitle = document.getElementById('filename-as-title');
                        if (filenameAsTitle && filenameAsTitle.checked) {
                            formData.append("filename-as-title", "1");
                        }
                        
                        var metaFields = document.querySelectorAll('form [name^="med_"]');
                        for (var i = 0; i < metaFields.length; i++) {
                            formData.append(metaFields[i].name, metaFields[i].value);
                        }
                    });
                    
                    // Nach Upload Erfolg
                    this.on("success", function(file, response) {
                        if (file.previewElement) {
                            if (response && response.files && response.files[0]) {
                                var fileInfo = response.files[0];
                                
                                if (fileInfo.error) {
                                    var node = file.previewElement.querySelector("[data-dz-errormessage]");
                                    if (node) node.textContent = fileInfo.error;
                                    file.previewElement.classList.add("dz-error");
                                    return;
                                }
                                
                                file.previewElement.classList.add("dz-success");
                                
                                if (fileInfo.thumbnailUrl) {
                                    var imgElement = file.previewElement.querySelector("[data-dz-thumbnail]");
                                    if (imgElement) {
                                        imgElement.src = fileInfo.thumbnailUrl;
                                        imgElement.alt = fileInfo.name;
                                    }
                                }
                                
                                var urlParams = new URLSearchParams(window.location.search);
                                var opener_input_field = urlParams.get('opener_input_field');
                                if (opener_input_field) {
                                    var selectButton = document.createElement('button');
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
                                    var successMark = file.previewElement.querySelector(".dz-success-mark");
                                    if (successMark) {
                                        successMark.appendChild(selectButton);
                                    }
                                }
                            }
                        }
                    });
                    
                    var resizeCheckbox = document.getElementById('resize-images');
                    if (resizeCheckbox) {
                        resizeCheckbox.addEventListener('change', function() {
                            myDropzone.options.resizeWidth = this.checked ? window.uploader_options.imageMaxWidth : null;
                            myDropzone.options.resizeHeight = this.checked ? window.uploader_options.imageMaxHeight : null;
                        });
                    }
                }
            };
            
            new Dropzone(uploadContainer, dropzoneOptions);
        }
    }
    
    // Metafelder bei Kategoriewechsel holen
    if (mediaCatSelect) {
        mediaCatSelect.addEventListener('change', function() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    updateMetafields(xhr.responseText);
                }
            };
            xhr.send('page=mediapool/upload&rex_file_category=' + mediaCatSelect.value);
        });
    }
    
    // Metafelder aktualisieren
    function updateMetafields(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        
        var localParent = mediaCatSelect.closest('.form-group').parentNode;
        if (!localParent) return;
        
        var ajaxParent = doc.querySelector('#rex-mediapool-category');
        if (!ajaxParent) return;
        
        var ajaxFieldset = ajaxParent.closest('fieldset');
        if (!ajaxFieldset) return;
        
        // Bestehende Metafelder entfernen
        var oldFields = localParent.querySelectorAll('.form-group:not(.preserve)');
        for (var i = 0; i < oldFields.length; i++) {
            oldFields[i].parentNode.removeChild(oldFields[i]);
        }
        
        // Neue Metafelder einfügen
        var metafields = ajaxFieldset.querySelectorAll('.form-group');
        var appendAfter = localParent.querySelector('.append-meta-after');
        
        if (appendAfter) {
            var metafieldsArray = Array.prototype.slice.call(metafields).reverse();
            
            for (var j = 0; j < metafieldsArray.length; j++) {
                var field = metafieldsArray[j];
                var nameEl = field.querySelector('[name]');
                if (!nameEl) continue;
                
                var name = nameEl.getAttribute('name');
                
                // Nicht-Meta-Felder überspringen
                if (['ftitle', 'rex_file_category', 'file_new'].indexOf(name) !== -1) {
                    continue;
                }
                
                var existingField = document.querySelector('[name="' + name + '"]');
                if (existingField) {
                    // Wert aus bestehendem Feld übernehmen
                    var newField = field.cloneNode(true);
                    var newInput = newField.querySelector('[name="' + name + '"]');
                    if (newInput) {
                        newInput.value = existingField.value;
                    }
                    appendAfter.parentNode.insertBefore(newField, appendAfter.nextSibling);
                } else {
                    // Neues Feld einfügen
                    appendAfter.parentNode.insertBefore(field, appendAfter.nextSibling);
                }
            }
        }
        
        // REDAXO Events auslösen
        if (typeof CustomEvent === 'function') {
            var event = new CustomEvent('rex:ready', { detail: localParent });
            document.dispatchEvent(event);
        } else {
            // Fallback für ältere Browser
            var legacyEvent = document.createEvent('CustomEvent');
            legacyEvent.initCustomEvent('rex:ready', true, true, { detail: localParent });
            document.dispatchEvent(legacyEvent);
        }
    }
});
