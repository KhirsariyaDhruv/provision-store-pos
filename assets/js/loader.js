// assets/js/loader.js

window.addEventListener("load", function () {
    const loader = document.getElementById('loader-wrapper');
    const minLoadTime = 2500; // 2.5 seconds minimum
    const startTime = window.performance.now();

    // Ensure body is locked during load
    document.body.classList.add('loading');

    function hideLoader() {
        if (loader) {
            // Fade out
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';

            // Unlock body scroll
            document.body.classList.remove('loading');

            // Remove from DOM after transition
            setTimeout(() => {
                loader.remove();
            }, 500);
        }
    }

    // Calculate remaining time to meet minimum duration
    const elapsedTime = window.performance.now() - startTime;
    const remainingTime = Math.max(0, minLoadTime - elapsedTime);

    // Initial check (if page loaded super fast)
    if (document.readyState === 'complete') {
        setTimeout(hideLoader, remainingTime);
    } else {
        window.addEventListener('load', () => {
            setTimeout(hideLoader, remainingTime);
        });
    }

    // Fallback: Force hide after 5 seconds if something hangs
    setTimeout(hideLoader, 5000);
});
