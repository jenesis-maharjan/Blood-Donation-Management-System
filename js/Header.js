// JavaScript for scroll behavior
window.addEventListener('scroll', () => {
    const headerTop = document.querySelector('.header-top');
    const headerBottom = document.querySelector('.header-bottom');
    const navLeftHidden = document.querySelector('.nav-left-hidden');
    const navRightHidden = document.querySelector('.nav-right-hidden');

    // Check if the top header is scrolled out of view
    if (window.scrollY >= headerTop.offsetHeight) {
        headerBottom.classList.add('sticky');
        navLeftHidden.style.visibility = 'visible';
        navRightHidden.style.visibility = 'visible';
    } else {
        headerBottom.classList.remove('sticky');
        navLeftHidden.style.visibility = 'hidden';
        navRightHidden.style.visibility = 'hidden';
    }
});

function closePopup() {
    document.getElementById("popupMessage").style.display = "none";
}
