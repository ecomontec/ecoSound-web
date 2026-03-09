// Legacy modal tag editing code removed - all tag editing now uses sidebar (tagSidebar.js)
// Kept only the Enter key shortcut handler below

// Add Enter key shortcut to save tag without closing
$(document).on('keydown', function (e) {
    // Check if tag form is visible - either in modal popup or sidebar
    const inModalPopup = $('#modal-div').is(':visible');
    const inSidebar = $('.tag-sidebar-wrapper').length > 0;
    const formExists = $('#tagForm').length;
    
    // Only handle Enter key when tag form is visible (in modal or sidebar)
    if (e.key === 'Enter' && (inModalPopup || inSidebar) && formExists) {
        // Don't interfere with bootstrap-select dropdown navigation
        const activeElement = document.activeElement;
        const isInBootstrapSelect = $(activeElement).closest('.bootstrap-select').length > 0;
        
        if (isInBootstrapSelect) {
            // Let bootstrap-select handle Enter key for dropdown navigation
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // Set flag to keep form open, then trigger normal form submission
        // This ensures all coordinate calculations and updates work properly
        $('#tagForm').data('keepOpen', true).submit();
    }
});
