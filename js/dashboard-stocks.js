const symbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM', '^GSPC', 'BTC-USD', 'ETH-USD'];
const track = document.getElementById('ticker-track');

if (track) {
    const makeTick = (entry) => {
        const el = document.createElement('div');
        el.className = 'tick';
        let { symbol } = entry;

        if (symbol === '^GSPC') {
            symbol = 'S&P 500';
        }

        if (entry.error) {
            el.innerHTML = `
                <span class="sym">${symbol}</span>
                <span class="price error">${entry.error}</span>
                <span class="delta">—</span>
            `;
            return el;
        }

        const price = typeof entry.price === 'number' ? entry.price : null;
        const changePct = typeof entry.change_pct === 'number' ? entry.change_pct : null;

        const formattedPrice = price === null ? '—' : `$${price.toFixed(2)}`;
        const positive = changePct !== null && changePct >= 0;
        const deltaClass = changePct === null ? '' : (positive ? 'pos' : 'neg');
        const deltaText = changePct === null
            ? '—'
            : `${positive ? '▲' : '▼'} ${Math.abs(changePct).toFixed(2)}%`;
        el.innerHTML = `
            <span class="sym">${symbol}</span>
            <span class="price">${formattedPrice}</span>
            <span class="delta${deltaClass ? ` ${deltaClass}` : ''}">${deltaText}</span>
        `;
        return el;
    };

    const restartAnimation = () => {
        track.style.animation = 'none';
        // Force reflow so the browser picks up the fresh animation cycle
        void track.offsetWidth;
        track.style.removeProperty('animation');
    };

    const applyTickerTiming = () => {
        const baseWidth = track.scrollWidth / 2;
        if (!baseWidth) {
            track.style.removeProperty('--ticker-duration');
            restartAnimation();
            return;
        }

        const SPEED_PX_PER_SEC = 55;
        const MIN_DURATION = 20; // keep a relaxed pace when there are few items
        const duration = Math.max(baseWidth / SPEED_PX_PER_SEC, MIN_DURATION);
        track.style.setProperty('--ticker-duration', `${duration.toFixed(2)}s`);
        restartAnimation();
    };

    const render = (entries) => {
        track.innerHTML = '';

        const baseNodes = entries.map((entry) => makeTick(entry));
        baseNodes.forEach((node) => track.appendChild(node));
        baseNodes.forEach((node) => track.appendChild(node.cloneNode(true)));

        applyTickerTiming();
    };

    const fetchQuotes = async () => {
        try {
            const apiUrl = `http://74.208.158.229/stock-api/landing?symbols=${symbols.join(',')}`;
            const res = await fetch(apiUrl, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            if (!Array.isArray(data) || !data.length) {
                throw new Error("Empty or invalid response");
            }

            render(data);

            // Dispatch the custom event with the fetched data
            document.dispatchEvent(new CustomEvent('stockDataLoaded', { detail: data }));
        } catch (err) {
            console.error("Failed to fetch quotes", err);
            render(symbols.map((symbol) => ({ symbol, error: 'API error' })));
        }
    };

    // Initial placeholders (just symbols, no data yet)
    render(symbols.map((symbol) => ({ symbol })));

    // Fetch immediately + refresh every 15s
    fetchQuotes();
    setInterval(fetchQuotes, 60000);
}
