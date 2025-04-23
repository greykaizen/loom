document.addEventListener('DOMContentLoaded', function() {
    // Handle post form submission
    const postForm = document.getElementById('post-form');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            if (!validatePostForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Handle comment form submission
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            if (!validateCommentForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Initialize tag handling
    initTags();
});

function validatePostForm() {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    let isValid = true;
    
    // Clear previous error messages
    clearErrors();
    
    if (title === '') {
        displayError('title', 'Title is required');
        isValid = false;
    } else if (title.length < 5) {
        displayError('title', 'Title must be at least 5 characters');
        isValid = false;
    }
    
    if (content === '') {
        displayError('content', 'Content is required');
        isValid = false;
    }
    
    return isValid;
}

function validateCommentForm() {
    const comment = document.getElementById('comment-content').value.trim();
    let isValid = true;
    
    // Clear previous error messages
    clearErrors();
    
    if (comment === '') {
        displayError('comment-content', 'Comment cannot be empty');
        isValid = false;
    }
    
    return isValid;
}

function initTags() {
    const tagInput = document.getElementById('tag-input');
    const tagList = document.getElementById('tag-list');
    const hiddenTagField = document.getElementById('tags');
    
    if (!tagInput || !tagList || !hiddenTagField) return;
    
    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(this.value.trim());
            this.value = '';
        }
    });
    
    // Handle clicking on existing tags
    tagList.addEventListener('click', function(e) {
        if (e.target.classList.contains('tag-remove')) {
            e.target.parentNode.remove();
            updateHiddenTagField();
        }
    });
}

function addTag(tagName) {
    if (!tagName) return;
    
    // Remove commas and trim
    tagName = tagName.replace(/,/g, '').trim();
    
    if (tagName === '') return;
    
    // Check if tag already exists
    const existingTags = document.querySelectorAll('.tag-item');
    for (let tag of existingTags) {
        if (tag.dataset.tag.toLowerCase() === tagName.toLowerCase()) {
            return; // Tag already exists
        }
    }
    
    // Limit to 5 tags
    if (existingTags.length >= 5) {
        alert('Maximum 5 tags allowed');
        return;
    }
    
    const tagList = document.getElementById('tag-list');
    const tagItem = document.createElement('div');
    tagItem.className = 'tag-item';
    tagItem.dataset.tag = tagName;
    tagItem.innerHTML = `
        <span class="tag-name">${tagName}</span>
        <span class="tag-remove">&times;</span>
    `;
    
    tagList.appendChild(tagItem);
    updateHiddenTagField();
}

function updateHiddenTagField() {
    const tagItems = document.querySelectorAll('.tag-item');
    const tagField = document.getElementById('tags');
    
    const tags = Array.from(tagItems).map(item => item.dataset.tag);
    tagField.value = tags.join(',');
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