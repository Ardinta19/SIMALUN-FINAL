(() => {
    // Direction is available in browsers implementing cross-document View Transitions.
    // No navigation is intercepted; unsupported browsers simply navigate normally.
    const setDirection = event => {
        const activation = event.activation || window.navigation?.activation;
        const from = activation?.from?.index;
        const to = activation?.entry?.index;
        const back = activation?.navigationType === 'traverse' && Number.isInteger(from) && Number.isInteger(to) && to < from;
        document.documentElement.dataset.pageDirection = back ? 'back' : 'forward';
    };
    window.addEventListener('pageswap', setDirection);
    window.addEventListener('pagereveal', setDirection);
})();
