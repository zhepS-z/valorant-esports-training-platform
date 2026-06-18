<div id="loadingScreen">
  <div class="loading-container">
      <div class="logo">
          <div class="logo-text">MINIMAL</div>
      </div>
      <div class="loading-animation">
          <div class="dot"></div>
          <div class="dot"></div>
          <div class="dot"></div>
      </div>
      <div class="loading-text">Loading your experience</div>
      <div class="progress-container">
          <div class="progress-bar"></div>
      </div>
  </div>
</div>

<style>
/* Place all your CSS here */
</style>

<script>
// Place all your scripts here
window.addEventListener('load', () => {
    const loadingScreen = document.getElementById('loadingScreen');
    loadingScreen.classList.add('fade-out');
    setTimeout(() => {
        loadingScreen.style.display = 'none';
    }, 800);
});
</script>
