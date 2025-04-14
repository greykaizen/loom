document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            if (!validateLoginForm()) {
                e.preventDefault();
            }
        });
    }

    if (signupForm) {
        signupForm.addEventListener('submit', function (e) {
            if (!validateSignupForm()) {
                e.preventDefault();
            }
        });
    }
});

function validateLoginForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    let isValid = true;

    // Clear previous error messages
    clearErrors();

    if (username === '') {
        displayError('username', 'Username is required');
        isValid = false;
    }

    if (password === '') {
        displayError('password', 'Password is required');
        isValid = false;
    }

    return isValid;
}

function validatePassword(password) {
    // At least 8 characters, containing uppercase, lowercase, and number
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
    return regex.test(password);
}

function validateSignupForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const confirmPassword = document.getElementById('confirm-password').value.trim();
    let isValid = true;

    // Clear previous error messages
    clearErrors();

    if (username === '') {
        displayError('username', 'Username is required');
        isValid = false;
    } else if (username.length < 3) {
        displayError('username', 'Username must be at least 3 characters');
        isValid = false;
    }

    if (email === '') {
        displayError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        displayError('email', 'Please enter a valid email address');
        isValid = false;
    }

    if (password === '') {
        displayError('password', 'Password is required');
        isValid = false;
    } else if (!validatePassword(password)) {
        displayError('password', 'Password must be at least 8 characters and include uppercase, lowercase, and numbers');
        isValid = false;
    } else if (password.length < 6) {
        displayError('password', 'Password must be at least 6 characters');
        isValid = false;
    }

    if (confirmPassword === '') {
        displayError('confirm-password', 'Please confirm your password');
        isValid = false;
    } else if (password !== confirmPassword) {
        displayError('confirm-password', 'Passwords do not match');
        isValid = false;
    }

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function displayError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    errorElement.style.color = '#721c24';
    errorElement.style.fontSize = '0.8rem';
    errorElement.style.marginTop = '5px';

    field.style.borderColor = '#dc3545';
    field.parentNode.appendChild(errorElement);
}

function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    document.querySelectorAll('.form-control').forEach(el => el.style.borderColor = '');
}