<script>
// Filament Paste Upload Support
// Allows pasting images directly into file upload fields using Ctrl+V
document.addEventListener('DOMContentLoaded', function() {
    // Use MutationObserver to watch for dynamically added file upload fields
    const observer = new MutationObserver(function(mutations) {
        setupPasteUpload();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Initial setup
    setupPasteUpload();
    
    function setupPasteUpload() {
        // Find all file upload containers
        const fileUploadContainers = document.querySelectorAll('.fi-fo-file-upload, [data-filament-file-upload]');
        
        fileUploadContainers.forEach(container => {
            // Skip if already set up
            if (container.dataset.pasteUploadEnabled) return;
            container.dataset.pasteUploadEnabled = 'true';
            
            const input = container.querySelector('input[type="file"]');
            if (!input) return;
            
            // Make container focusable for paste events
            if (!container.hasAttribute('tabindex')) {
                container.setAttribute('tabindex', '0');
            }
            
            // Handle paste event
            container.addEventListener('paste', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const items = e.clipboardData?.items;
                if (!items) return;
                
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    
                    // Only handle image files
                    if (item.type.indexOf('image') !== -1) {
                        const file = item.getAsFile();
                        if (file) {
                            // Create a new FileList-like object
                            const dataTransfer = new DataTransfer();
                            
                            // Add existing files
                            if (input.files) {
                                for (let j = 0; j < input.files.length; j++) {
                                    dataTransfer.items.add(input.files[j]);
                                }
                            }
                            
                            // Add new pasted file with a proper name
                            const fileName = 'pasted-image-' + Date.now() + '.png';
                            const renamedFile = new File([file], fileName, { type: file.type });
                            dataTransfer.items.add(renamedFile);
                            input.files = dataTransfer.files;
                            
                            // Trigger change event for Filament
                            const changeEvent = new Event('change', { bubbles: true });
                            input.dispatchEvent(changeEvent);
                            
                            // Also trigger input event
                            const inputEvent = new Event('input', { bubbles: true });
                            input.dispatchEvent(inputEvent);
                            
                            // Show visual feedback
                            container.style.border = '2px solid #10b981';
                            container.style.transition = 'border 0.3s';
                            setTimeout(() => {
                                container.style.border = '';
                            }, 1000);
                        }
                    }
                }
            });
            
            // Add visual feedback on focus
            container.addEventListener('focus', function() {
                if (!container.querySelector('.paste-hint')) {
                    const hint = document.createElement('div');
                    hint.className = 'paste-hint';
                    hint.style.cssText = 'font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem;';
                    hint.textContent = '💡 Tip: You can paste images here (Ctrl+V)';
                    container.appendChild(hint);
                }
            });
        });
    }
});
</script>

