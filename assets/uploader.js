/* Datei: assets/uploader.css */
:root {
  --uploader-border-color: #c1c9d4;
  --uploader-error-color: #a94442;
  --uploader-bg-color: #fff;
  --uploader-text-color: #333;
  --uploader-success-color: #228B22;
  --uploader-progress-color: #333;
}

.uploader-dropzone {
  background: var(--uploader-bg-color);
  border: 2px dashed var(--uploader-border-color);
  min-height: 150px;
  padding: 20px;
  position: relative;
  margin: 1em 0;
}

.uploader-dropzone.dz-clickable {
  cursor: pointer;
}

.uploader-dropzone.dz-clickable .dz-message,
.uploader-dropzone.dz-clickable .dz-message * {
  cursor: pointer;
}

.uploader-dropzone.dz-drag-hover {
  border-style: solid;
  background: rgba(0, 0, 0, 0.05);
}

.uploader-dropzone .dz-message {
  text-align: center;
  margin: 2em 0;
  font-size: 1.5em;
  color: var(--uploader-border-color);
}

.uploader-dropzone .dz-preview {
  position: relative;
  display: inline-block;
  vertical-align: top;
  margin: 16px;
  min-height: 100px;
}

.uploader-dropzone .dz-preview.dz-file-preview .dz-image {
  border-radius: 5px;
  background: #999;
  background: linear-gradient(to bottom, #eee, #ddd);
}

.uploader-dropzone .dz-preview .dz-image {
  border-radius: 5px;
  overflow: hidden;
  width: 120px;
  height: 120px;
  position: relative;
  display: block;
  z-index: 10;
}

.uploader-dropzone .dz-preview .dz-image img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.uploader-dropzone .dz-preview .dz-details {
  position: absolute;
  top: 0;
  left: 0;
  opacity: 0;
  font-size: 13px;
  min-width: 100%;
  max-width: 100%;
  padding: 10px;
  text-align: center;
  color: var(--uploader-text-color);
  line-height: 150%;
  z-index: 20;
  transition: opacity 0.2s linear;
  background: rgba(255, 255, 255, 0.8);
}

.uploader-dropzone .dz-preview:hover .dz-details {
  opacity: 1;
}

.uploader-dropzone .dz-preview .dz-details .dz-size,
.uploader-dropzone .dz-preview .dz-details .dz-filename {
  white-space: nowrap;
}

.uploader-dropzone .dz-preview .dz-progress {
  opacity: 1;
  z-index: 1000;
  pointer-events: none;
  position: absolute;
  height: 16px;
  left: 50%;
  top: 50%;
  margin-top: -8px;
  width: 80px;
  margin-left: -40px;
  background: rgba(255, 255, 255, 0.9);
  transform: scale(1);
  border-radius: 8px;
  overflow: hidden;
}

.uploader-dropzone .dz-preview .dz-progress .dz-upload {
  background: var(--uploader-progress-color);
  background: linear-gradient(to bottom, #666, #444);
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 0;
  transition: width 300ms ease-in-out;
}

.uploader-dropzone .dz-preview.dz-success .dz-progress {
  display: none;
}

.uploader-dropzone .dz-preview.dz-error .dz-error-message {
  display: block;
}

.uploader-dropzone .dz-preview .dz-error-message {
  pointer-events: none;
  z-index: 1000;
  position: absolute;
  display: none;
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 8px;
  font-size: 13px;
  top: 130px;
  left: -10px;
  width: 140px;
  background: var(--uploader-error-color);
  background: linear-gradient(to bottom, var(--uploader-error-color), #8a3333);
  padding: 0.5em 1em;
  color: white;
}

.uploader-dropzone .dz-preview:hover .dz-error-message {
  opacity: 1;
  display: block;
}

.uploader-dropzone .dz-preview .dz-success-mark,
.uploader-dropzone .dz-preview .dz-error-mark {
  pointer-events: none;
  opacity: 0;
  z-index: 500;
  position: absolute;
  display: block;
  top: 50%;
  left: 50%;
  margin-left: -27px;
  margin-top: -27px;
}

.uploader-dropzone .dz-preview .dz-success-mark svg,
.uploader-dropzone .dz-preview .dz-error-mark svg {
  display: block;
  width: 54px;
  height: 54px;
}

.uploader-dropzone .dz-preview.dz-success .dz-success-mark {
  opacity: 1;
  animation: slide-in 3s cubic-bezier(0.77, 0, 0.175, 1);
}

.uploader-dropzone .dz-preview.dz-error .dz-error-mark {
  opacity: 1;
  animation: slide-in 3s cubic-bezier(0.77, 0, 0.175, 1);
}

.uploader-dropzone .dz-preview .btn-select {
  display: none;
}

.uploader-dropzone .dz-preview.dz-success .btn-select {
  display: block;
  position: absolute;
  top: 5px;
  right: 5px;
  z-index: 30;
}

.uploader-options {
  margin-top: 15px;
  margin-bottom: 15px;
  padding: 10px;
  background: #f5f5f5;
  border-radius: 5px;
}

.uploader-options .checkbox {
  margin-bottom: 10px;
}

@keyframes slide-in {
  0% {
    opacity: 0;
    transform: translateY(-50px);
  }
  30% {
    opacity: 1;
    transform: translateY(0px);
  }
  70% {
    opacity: 1;
    transform: translateY(0px);
  }
  100% {
    opacity: 0;
    transform: translateY(-50px);
  }
}

/* Dark Mode Support */
body.rex-theme-dark {
  --uploader-border-color: rgba(27, 35, 44, 0.6);
  --uploader-bg-color: rgba(32, 43, 53, 0.6);
  --uploader-text-color: rgba(255, 255, 255, 0.75);
}

body.rex-theme-dark .uploader-options {
  background: rgba(32, 43, 53, 0.6);
}

body.rex-theme-dark .uploader-dropzone .dz-message {
  color: rgba(255, 255, 255, 0.45);
}

body.rex-theme-dark .uploader-dropzone .dz-preview .dz-details {
  background: rgba(32, 43, 53, 0.8);
  color: rgba(255, 255, 255, 0.9);
}

body.rex-theme-dark .uploader-dropzone .dz-preview .dz-progress {
  background: rgba(32, 43, 53, 0.9);
}

@media (prefers-color-scheme: dark) {
  body.rex-has-theme:not(.rex-theme-light) {
    --uploader-border-color: rgba(27, 35, 44, 0.6);
    --uploader-bg-color: rgba(32, 43, 53, 0.6);
    --uploader-text-color: rgba(255, 255, 255, 0.75);
  }
  
  body.rex-has-theme:not(.rex-theme-light) .uploader-options {
    background: rgba(32, 43, 53, 0.6);
  }
  
  body.rex-has-theme:not(.rex-theme-light) .uploader-dropzone .dz-message {
    color: rgba(255, 255, 255, 0.45);
  }
  
  body.rex-has-theme:not(.rex-theme-light) .uploader-dropzone .dz-preview .dz-details {
    background: rgba(32, 43, 53, 0.8);
    color: rgba(255, 255, 255, 0.9);
  }
  
  body.rex-has-theme:not(.rex-theme-light) .uploader-dropzone .dz-preview .dz-progress {
    background: rgba(32, 43, 53, 0.9);
  }
}
