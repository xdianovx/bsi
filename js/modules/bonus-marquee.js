export const initBonusMarquee = () => {
  const rows = document.querySelectorAll('.bonus-marquee__row');

  rows.forEach((row) => {
    const track = row.querySelector('.bonus-marquee__track');
    const content = track.querySelector('.bonus-marquee__content');
    if (!track || !content) return;

    // Точная дробная ширина без округления
    const contentWidth = content.getBoundingClientRect().width;
    const screenWidth = window.innerWidth;

    // Минимум 2 клона, чтобы экран был заполнен всегда
    const clonesNeeded = Math.max(2, Math.ceil(screenWidth / contentWidth) + 1);

    for (let i = 0; i < clonesNeeded; i++) {
      const clone = content.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      track.appendChild(clone);
    }

    // Перезамеряем после клонирования — берём точную ширину первого блока
    const exactWidth = track.children[0].getBoundingClientRect().width;

    const isRight = row.classList.contains('bonus-marquee__row--right');

    const speed = getComputedStyle(row.closest('.bonus-marquee'))
      .getPropertyValue('--marquee-speed')
      .trim();
    const duration = parseFloat(speed) || 35;

    const from = isRight ? -exactWidth : 0;
    const to = isRight ? 0 : -exactWidth;

    const keyframes = [
      { transform: `translate3d(${from}px, 0, 0)` },
      { transform: `translate3d(${to}px, 0, 0)` }
    ];

    track.animate(keyframes, {
      duration: duration * 1000,
      iterations: Infinity,
      easing: 'linear'
    });
  });
};