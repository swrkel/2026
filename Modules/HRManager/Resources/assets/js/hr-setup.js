document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-hr-tabs] button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-hr-tabs] button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('[data-panel]').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const panel = document.querySelector('[data-panel="' + btn.dataset.tab + '"]');
      if (panel) panel.classList.add('active');
    });
  });
  document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const modal = document.querySelector('[data-modal="' + btn.dataset.openModal + '"]');
      if (modal) modal.classList.add('active');
    });
  });
  document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('.hr-modal').classList.remove('active'); });
  });
  document.querySelectorAll('.hr-modal').forEach(function (modal) {
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('active'); });
  });
});
