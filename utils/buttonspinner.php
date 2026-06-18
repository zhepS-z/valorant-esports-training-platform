<script>
    function showSpinner(button) {
        // Only show spinner if it's not the currently active button
        if (!button.classList.contains('active')) {
            const spinner = button.querySelector('.spinner-border');
            const text = button.querySelector('.btn-text');
            
            // Show spinner and change text
            spinner.classList.remove('d-none');
            text.textContent = 'Loading...';
            
            // Disable all buttons to prevent multiple clicks
            document.querySelectorAll('.mode-filter .btn').forEach(btn => {
                btn.style.pointerEvents = 'none';
            });
        }
    }
    
    // Handle Apply button loading
    const applyForm = document.querySelector('form');
    if (applyForm) {
        applyForm.addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            const spinner = button.querySelector('#apply-spinner');
            const text = button.querySelector('#apply-text');
            
            spinner.classList.remove('d-none');
            text.textContent = 'Loading...';
            button.disabled = true;
        });
    }
    </script>

<style>
/* Add this to your existing styles */
.mode-filter .btn {
    position: relative;
}
.spinner-border {
    margin-right: 5px;
    vertical-align: middle;
}
</style>