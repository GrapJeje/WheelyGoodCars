function initTogglePasswords() {
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const wrapper = btn.closest('.password-field');
            if (!wrapper) return;

            const input = wrapper.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            btn.setAttribute('aria-pressed', !isHidden);
            btn.setAttribute('title', isHidden ? 'Wachtwoord verbergen' : 'Wachtwoord weergeven');
            btn.classList.toggle('active', !isHidden);

            const eye = btn.querySelector('.icon-eye');
            const eyeOff = btn.querySelector('.icon-eye-off');
            if (eye && eyeOff) {
                eye.style.display = isHidden ? 'none' : '';
                eyeOff.style.display = isHidden ? '' : 'none';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initTogglePasswords);
document.addEventListener('livewire:load', initTogglePasswords);
