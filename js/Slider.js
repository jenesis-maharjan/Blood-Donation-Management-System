let sliderIndex = 0;
const slides = document.querySelectorAll('.slide');
let interval = setInterval(nextSlide, 3000);

function nextSlide() {
    slides[sliderIndex].style.display = 'none'; // Hide the current slide
    sliderIndex = (sliderIndex + 1) % slides.length; // Move to the next slide
    slides[sliderIndex].style.display = 'block'; // Show the next slide
}

// Add hover events to each slide
slides.forEach(slide => {
    slide.addEventListener('mouseenter', () => clearInterval(interval)); // Stop sliding on hover
    slide.addEventListener('mouseleave', () => {
        nextSlide(); // Change to the next slide when the mouse leaves
        interval = setInterval(nextSlide, 3000); // Restart the interval
    });
});

// Initial setup
slides[sliderIndex].style.display = 'block'; // Show the first slide
