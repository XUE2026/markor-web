document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.resource-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn')) return;
        });
    });
});