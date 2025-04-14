document.addEventListener('DOMContentLoaded', function() {
    // Initialize vote buttons
    initVoteButtons();
    
    // Initialize report modal
    initReportModal();
});

function initVoteButtons() {
    const voteButtons = document.querySelectorAll('.vote-btn');
    
    voteButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Check if user is logged in
            if (!document.body.classList.contains('logged-in')) {
                alert('Please log in to vote');
                return;
            }
            
            const voteType = this.dataset.vote;
            const postId = this.closest('.post-card')?.dataset.postId;
            const commentId = this.closest('.comment')?.dataset.commentId;
            
            vote(voteType, postId, commentId, this);
        });
    });
}

function vote(voteType, postId, commentId, buttonElement) {
    const data = new FormData();
    data.append('vote_type', voteType);
    
    if (postId) {
        data.append('post_id', postId);
    } else if (commentId) {
        data.append('comment_id', commentId);
    }
    
    fetch('ajax/vote.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateVoteUI(buttonElement, voteType, data.voteCount);
        } else {
            alert(data.message || 'Error processing vote');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateVoteUI(buttonElement, voteType, newCount) {
    const voteContainer = buttonElement.closest('.vote-column');
    const upvoteButton = voteContainer.querySelector('.vote-up');
    const downvoteButton = voteContainer.querySelector('.vote-down');
    const countElement = voteContainer.querySelector('.vote-count');
    
    // Reset classes
    upvoteButton.classList.remove('upvoted');
    downvoteButton.classList.remove('downvoted');
    
    // Set new class based on vote type
    if (voteType === '1') {
        upvoteButton.classList.add('upvoted');
    } else if (voteType === '-1') {
        downvoteButton.classList.add('downvoted');
    }
    
    // Update count
    countElement.textContent = newCount;
}

function initReportModal() {
    const reportButtons = document.querySelectorAll('.report-btn');
    const reportModal = document.getElementById('report-modal');
    const closeModal = document.querySelector('.close-modal');
    
    if (!reportModal) return;
    
    reportButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!document.body.classList.contains('logged-in')) {
                alert('Please log in to report content');
                return;
            }
            
            const contentType = this.dataset.type;
            const contentId = this.dataset.id;
            
            document.getElementById('report-type').value = contentType;
            document.getElementById('report-id').value = contentId;
            
            reportModal.style.display = 'block';
        });
    });
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            reportModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === reportModal) {
            reportModal.style.display = 'none';
        }
    });
}

// Add this to your existing main.js
document.addEventListener('DOMContentLoaded', function() {
    // Existing code...
    
    // Mark notifications as read when clicked
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            if (notificationId) {
                fetch('ajax/mark_notification_read.php', {
                    method: 'POST',
                    body: new FormData().append('notification_id', notificationId)
                });
            }
        });
    });
});