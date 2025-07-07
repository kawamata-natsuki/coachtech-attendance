console.log('clock js loaded');

// 時計リアルタイム更新
document.addEventListener('DOMContentLoaded', function () {
  function updateClock() {
    const now = new Date();
    const hour = String(now.getHours()).padStart(2, '0');
    const minute = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('clock').textContent = `${hour}:${minute}`;
  }

  updateClock();
  setInterval(updateClock, 1000);
});

// 多重送信防止
document.querySelectorAll('.button-wrapper').forEach(form => {
  form.addEventListener('submit', function () {
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
      setTimeout(() => {
        submitButton.disabled = true;
      }, 100);
    }
  });
});