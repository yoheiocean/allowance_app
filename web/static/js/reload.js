let startY;
const threshold = 50; // Adjust as necessary

window.addEventListener('touchstart', function (e) {
    startY = e.touches[0].clientY; // Record the starting Y position
});

window.addEventListener('touchmove', function (e) {
    const currentY = e.touches[0].clientY;
    if (startY < currentY && window.scrollY === 0) { // Check if swiping down at the top of the page
        e.preventDefault(); // Prevent default scroll behavior
    }
});

window.addEventListener('touchend', function (e) {
    const currentY = e.changedTouches[0].clientY;
    if (currentY - startY > threshold && window.scrollY === 0) { // Check if the swipe distance exceeds the threshold
        location.reload(); // Reload the page
    }
});
