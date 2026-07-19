(function () {
    var yearEl = document.getElementById('year');

    if (yearEl) {
        yearEl.textContent = String(new Date().getFullYear());
    }

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var reveals = document.querySelectorAll('.reveal');

    if (reduceMotion || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) {
            el.classList.add('is-visible');
        });
        return;
    }

    var observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            root: null,
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.12,
        }
    );

    reveals.forEach(function (el) {
        observer.observe(el);
    });
})();
