document.addEventListener('DOMContentLoaded', () => {
  const periodSelect = document.querySelector('[data-log-tc-period]');
  const customBlocks = Array.from(document.querySelectorAll('[data-log-tc-custom-range]'));

  if (!periodSelect || customBlocks.length === 0) {
    return;
  }

  const syncCustomRangeState = () => {
    const isCustom = periodSelect.value === 'custom';

    customBlocks.forEach((block) => {
      block.hidden = !isCustom;

      block.querySelectorAll('input').forEach((input) => {
        input.disabled = !isCustom;
      });
    });
  };

  periodSelect.addEventListener('change', syncCustomRangeState);
  syncCustomRangeState();
});
