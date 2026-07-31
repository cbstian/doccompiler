(() => {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- scroll reveal ---------------- */
    const revealEls = document.querySelectorAll('[data-reveal]');

    if (reduceMotion || !('IntersectionObserver' in window)) {
        revealEls.forEach(el => el.classList.add('is-visible'));
    } else {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const delay = Array.from(el.parentElement?.children || [])
                        .filter(c => c.hasAttribute('data-reveal'))
                        .indexOf(el);
                    setTimeout(() => el.classList.add('is-visible'), Math.max(0, delay) * 70);
                    io.unobserve(el);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        revealEls.forEach(el => io.observe(el));
    }

    /* ---------------- copy to clipboard ---------------- */
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const targetId = btn.getAttribute('data-copy-target');
            const target = document.getElementById(targetId);
            if (!target) return;

            try {
                await navigator.clipboard.writeText(target.textContent.trim());
                const original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(() => { btn.textContent = original; }, 1600);
            } catch (err) {
                btn.textContent = 'Select & copy';
            }
        });
    });

    /* ---------------- hero compiler visualization ---------------- */
    const chips = Array.from(document.querySelectorAll('#chipRail .chip'));
    const pipeDot = document.getElementById('pipeDot');
    const outputStream = document.getElementById('outputStream');
    const tokenCountEl = document.getElementById('tokenCount');

    const outputLines = [
        '# Q3 Financial Summary',
        '',
        '| Region | Revenue |',
        '|--------|---------|',
        '| LATAM  | 1.2M    |',
        '',
        'Net growth reflects...'
    ];

    if (reduceMotion || !chips.length || !outputStream) {
        // Static, calm end-state for reduced motion / missing nodes
        if (chips[0]) chips[0].classList.add('is-active');
        if (outputStream) outputStream.textContent = outputLines.join('\n');
        if (tokenCountEl) tokenCountEl.textContent = '12,480';
        return;
    }

    let chipIndex = 0;
    let cancelled = false;

    function animatePipeDot(durationMs) {
        return new Promise(resolve => {
            const start = performance.now();
            function step(now) {
                if (cancelled) return resolve();
                const t = Math.min(1, (now - start) / durationMs);
                const track = pipeDot.parentElement.clientWidth;
                pipeDot.style.left = `${t * (track - 7)}px`;
                if (t < 1) requestAnimationFrame(step);
                else resolve();
            }
            requestAnimationFrame(step);
        });
    }

    function typeText(text, charDelay) {
        return new Promise(resolve => {
            let i = 0;
            outputStream.textContent = '';
            const id = setInterval(() => {
                if (cancelled) { clearInterval(id); return resolve(); }
                outputStream.textContent += text[i];
                i++;
                if (i >= text.length) { clearInterval(id); resolve(); }
            }, charDelay);
        });
    }

    function countUp(target, durationMs) {
        return new Promise(resolve => {
            const start = performance.now();
            function step(now) {
                if (cancelled) return resolve();
                const t = Math.min(1, (now - start) / durationMs);
                tokenCountEl.textContent = Math.floor(t * target).toLocaleString('en-US');
                if (t < 1) requestAnimationFrame(step);
                else resolve();
            }
            requestAnimationFrame(step);
        });
    }

    async function loop() {
        while (!cancelled) {
            chips.forEach(c => c.classList.remove('is-active'));
            chips[chipIndex].classList.add('is-active');

            pipeDot.style.left = '0px';
            tokenCountEl.textContent = '0';
            outputStream.textContent = '';

            await Promise.all([
                animatePipeDot(900),
                (async () => { await sleep(300); })()
            ]);

            await Promise.all([
                typeText(outputLines.join('\n'), 14),
                countUp(8000 + chipIndex * 1200, 1100)
            ]);

            await sleep(1400);
            chipIndex = (chipIndex + 1) % chips.length;
        }
    }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    // Start the ambient loop once the panel first enters view
    const panel = document.getElementById('compilerPanel');
    if (panel && 'IntersectionObserver' in window) {
        const panelIo = new IntersectionObserver((entries, obs) => {
            if (entries[0].isIntersecting) {
                loop();
                obs.disconnect();
            }
        }, { threshold: 0.2 });
        panelIo.observe(panel);
    } else {
        loop();
    }
})();
