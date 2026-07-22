document.addEventListener('DOMContentLoaded', function() {
    const dateItemSelect = document.querySelector('[name="dateitem"]');
    const prefixInput = document.querySelector('[name="prefix"]');
    
    if (!dateItemSelect || !prefixInput) {
        return;
    }
    
    // Update prefix on page load
    updatePrefix();
    
    // Update prefix when dropdown changes
    dateItemSelect.addEventListener('change', function() {
        updatePrefix();
    });
    
    function updatePrefix() {
        const selectedValue = dateItemSelect.value;
        
        if (selectedValue === 'awarded') {
            prefixInput.value = 'Awarded: ';     
        } else if (selectedValue === 'expires') {
            prefixInput.value = 'Expires: ';    
        }
    }
});