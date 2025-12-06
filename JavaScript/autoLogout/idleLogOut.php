<script>
    // --- Idle Logout Logic ---
    let idleTimer;
    const idleTimeout = 3 * 60 * 1000;

    const resetIdleTimer = () => {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            // User is idle, show a message and redirect to logout.
            alert('You have been logged out due to inactivity.');
            window.location.href = '../../function/_auth/_logout.php';
        }, idleTimeout);
    };

    const activityEvents = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'];

    // Add event listeners to reset the timer on user activity
    activityEvents.forEach(event => {
        window.addEventListener(event, resetIdleTimer, true);
    });

    resetIdleTimer(); // Initialize the timer when the page loads
</script>