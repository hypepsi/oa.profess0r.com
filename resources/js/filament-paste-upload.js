// Filament Paste Upload Support
// Allows pasting images directly into file upload fields using Ctrl+V

document.addEventListener('DOMContentLoaded', function() {
    // Find all file upload inputs
    const fileUploadInputs = document.querySelectorAll('input[type="file"][multiple]');
    
    fileUploadInputs.forEach(input => {
        const container = input.closest('.fi-fo-file-upload');
        if (!container) return;
        
        // Make container focusable for paste events
        container.setAttribute('tabindex', '0');
        
        // Handle paste event
        container.addEventListener('paste', function(e) {
            e.preventDefault();
            
            const items = e.clipboardData?.items;
            if (!items) return;
            
            const files = [];
            
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
                        
                        // Add new pasted file
                        dataTransfer.items.add(file);
                        input.files = dataTransfer.files;
                        
                        // Trigger change event for Filament
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                        
                        // Show success message
                        if (window.Livewire) {
                            window.Livewire.dispatch('notify', {
                                type: 'success',
                                message: 'Image pasted successfully!'
                            });
                        }
                    }
                }
            }
        });
        
        // Add visual feedback
        container.addEventListener('focus', function() {
            container.style.outline = '2px dashed #3b82f6';
            container.style.outlineOffset = '2px';
        });
        
        container.addEventListener('blur', function() {
            container.style.outline = '';
        });
    });
});

