document.addEventListener('DOMContentLoaded', () => {
    const periodSelect = document.querySelector('[data-visitor-period]');
    const customRanges = document.querySelectorAll('[data-visitor-custom-range]');
    const copyButtons = document.querySelectorAll('[data-copy-target]');

    const syncCustomRange = () => {
        if (!periodSelect) {
            return;
        }

        const isCustom = periodSelect.value === 'custom';

        customRanges.forEach((field) => {
            field.hidden = !isCustom;
        });
    };

    periodSelect?.addEventListener('change', syncCustomRange);
    syncCustomRange();

    copyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy-target') || '';
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                const previous = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied';

                window.setTimeout(() => {
                    button.innerHTML = previous;
                }, 1200);
            } catch (error) {
                window.alert('Data gagal disalin.');
            }
        });
    });
});
