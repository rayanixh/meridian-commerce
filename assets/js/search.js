/* search page */
(function () {
  const form = document.querySelector('[data-search-form]');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = form.querySelector('input').value.trim();
      if (q) window.location.href = MERIDIAN.base + '/search.php?q=' + encodeURIComponent(q);
    });
  }
})();
