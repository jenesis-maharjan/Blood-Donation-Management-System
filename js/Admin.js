document.getElementById('submitButton').addEventListener('click', function() {
const username = document.getElementById('username').value;
const password = document.getElementById('password').value;

// Check credentials
if (username === 'admin' && password === 'admin') {
    // Redirect to the admin page
    window.location.href = '9.AdminMainPage.php';
} else {
    // Display error message
    document.getElementById('errorMessage').style.display = 'block';
}
});

document.addEventListener('DOMContentLoaded', function() {
    const loginButton = document.getElementById('loginButton');
    const loginForm = document.getElementById('loginForm');
  
    loginButton.addEventListener('click', function() {
      // Toggle the "show" class on the dropdown content
      loginForm.classList.toggle('show');
    });
  
    // Close the dropdown if clicked outside
    window.addEventListener('click', function(event) {
      if (!loginButton.contains(event.target) && !loginForm.contains(event.target)) {
        loginForm.classList.remove('show');
      }
    });
  });

