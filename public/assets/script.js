document.addEventListener('DOMContentLoaded', function () {
    var syncForm = document.querySelector('form[action$="sync_channel.php"]');

    if (!syncForm) {
        return;
    }

    syncForm.addEventListener('submit', function () {
        var button = syncForm.querySelector('button[type="submit"]');

        if (!button) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Syncing...';
    });
});
