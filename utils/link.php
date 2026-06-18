<!-- Load all CSS first -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="icon" href="/VALPROJECT/favicon.ico" type="image/x-icon">

<!-- Local CSS -->
<link href="/VALPROJECT/css/sidebar.css" rel="stylesheet">
<link href="/VALPROJECT/css/navbar.css" rel="stylesheet">
<link href="/VALPROJECT/css/style.css" rel="stylesheet">

<style>
    /* Hide content until fully loaded */
    .content-wrapper {
        opacity: 0;
        transition: opacity 0.3s ease-in;
    }
    .content-wrapper.loaded {
        opacity: 1;
    }
</style>

<!-- Load JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

<?php 
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<script>
    // Wait for everything to load before showing content
    window.addEventListener('load', function() {
        // Find the closest content-wrapper or body
        const wrapper = document.querySelector('.content-wrapper') || document.body;
        wrapper.classList.add('loaded');
    });

    // Fallback: Show content after 2 seconds even if not fully loaded
    setTimeout(function() {
        const wrapper = document.querySelector('.content-wrapper') || document.body;
        wrapper.classList.add('loaded');
    }, 2000);
</script>
