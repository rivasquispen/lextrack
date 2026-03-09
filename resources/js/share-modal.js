document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-share-modal]');

    if (! modal) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-share-modal-trigger]');
    const closeButtons = document.querySelectorAll('[data-share-modal-close]');
    const overlay = modal.querySelector('[data-share-modal-overlay]');

    if (! openButtons.length) {
        return;
    }

    const toggleModal = (show = false) => {
        modal.classList.toggle('hidden', ! show);
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => toggleModal(true));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => toggleModal(false));
    });

    overlay?.addEventListener('click', () => toggleModal(false));
});
