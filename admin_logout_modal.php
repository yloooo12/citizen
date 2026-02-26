<style>
.logout-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.logout-modal.show {
    display: flex;
}

.logout-modal-content {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.logout-modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.logout-modal-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    font-size: 1.5rem;
}

.logout-modal-header h3 {
    font-size: 1.25rem;
    color: #2d3748;
    font-weight: 700;
}

.logout-modal-body {
    color: #718096;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.logout-modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.modal-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-block;
}

.modal-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.modal-btn-cancel:hover {
    background: #e5e7eb;
}

.modal-btn-confirm {
    background: #ef4444;
    color: white;
}

.modal-btn-confirm:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
</style>

<div class="logout-modal" id="logoutModal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <div class="logout-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            Are you sure you want to logout? You will need to login again to access the admin panel.
        </div>
        <div class="logout-modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
            <a href="?logout" class="modal-btn modal-btn-confirm">Yes, Logout</a>
        </div>
    </div>
</div>

<script>
function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}

function hideLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}

document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});
</script>
