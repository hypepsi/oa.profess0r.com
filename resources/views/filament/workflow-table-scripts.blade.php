<style>
    /* Hide sort selectors (Created at and Ascending) */
    .fi-ta-header-toolbar > div:first-child > div:first-child,
    .fi-ta-header-toolbar > div:first-child > div:nth-child(2) {
        display: none !important;
    }
    
    /* Hide group selector */
    .fi-ta-header-toolbar > div:first-child > div:nth-child(3) {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const toolbar = document.querySelector('.fi-ta-header-toolbar');
            if (toolbar) {
                const firstRow = toolbar.querySelector('> div:first-child');
                if (firstRow) {
                    // Hide sort selectors (column and direction)
                    const sortSelects = firstRow.querySelectorAll('> div');
                    if (sortSelects.length >= 2) {
                        sortSelects[0].style.display = 'none';
                        sortSelects[1].style.display = 'none';
                    }
                    // Hide group selector if exists
                    const groupSelect = firstRow.querySelector('[x-data*="grouping"], select[name*="group"]');
                    if (groupSelect) {
                        groupSelect.closest('div').style.display = 'none';
                    }
                }
            }
            
            // Remove "Created at: " prefix from group headers
            function removeCreatedAtPrefix() {
                const groupHeaders = document.querySelectorAll('.fi-ta-group-header, [class*="group-header"], tr[class*="group"]');
                groupHeaders.forEach(function(header) {
                    const text = header.textContent || header.innerText || '';
                    if (text && text.includes('Created at:')) {
                        const newText = text.replace(/Created at:\s*/gi, '').trim();
                        if (header.textContent !== undefined) {
                            header.textContent = newText;
                        } else if (header.innerText !== undefined) {
                            header.innerText = newText;
                        }
                    }
                });
            }
            
            removeCreatedAtPrefix();
            
            // Use MutationObserver to watch for DOM changes
            const observer = new MutationObserver(function() {
                removeCreatedAtPrefix();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true
            });
        }, 100);
    });
</script>

