const symbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM', '^GSPC', 'BTC', 'ETH'];
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

    const render = (entries) => {
        const row = [...entries, ...entries, ...entries]; // repeat for scrolling ticker
        track.innerHTML = '';
        row.forEach((entry) => track.appendChild(makeTick(entry)));
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
