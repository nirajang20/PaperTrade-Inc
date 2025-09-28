const symbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM'];
const tickerEndpoint = 'script/currentprice.php';
const pollIntervalMs = 3000;
const track = document.getElementById('ticker-track');

if (track) {
    const previousPrices = new Map();

    const makeTick = (entry) => {
        const el = document.createElement('div');
        el.className = 'tick';
        const { symbol } = entry;
        if (entry.error) {
            el.innerHTML = `<span class="sym">${symbol}</span><span class="price error">${entry.error}</span><span class="delta">—</span>`;
            return el;
        }
        const price = typeof entry.price === 'number' ? entry.price : null;
        const delta = typeof entry.delta === 'number' && Number.isFinite(entry.delta) ? entry.delta : null;
        const formattedPrice = price === null ? '—' : `$${price.toFixed(2)}`;
        const positive = delta !== null && delta >= 0;
        const deltaClass = delta === null ? '' : (positive ? 'pos' : 'neg');
        const deltaText = delta === null ? '—' : `${positive ? '▲' : '▼'} ${Math.abs(delta).toFixed(2)}%`;
        el.innerHTML = `
            <span class="sym">${symbol}</span>
            <span class="price">${formattedPrice}</span>
            <span class="delta${deltaClass ? ` ${deltaClass}` : ''}">${deltaText}</span>
        `;
        return el;
    };

    const render = (entries) => {
        const row = [...entries, ...entries, ...entries];
        track.innerHTML = '';
        row.forEach((entry) => track.appendChild(makeTick(entry)));
    };

    // Fetch from backend PHP instead of generating random prices
    const fetchQuotes = async () => {
        try {
            const url = `${tickerEndpoint}?stock=${symbols.join(',')}`;
            const res = await fetch(url);
            const data = await res.json(); // array of {symbol, price, date, time}
            
            const entries = data.map((entry) => {
                const prev = previousPrices.get(entry.symbol);
                const price = entry.price;
                const delta = prev && prev > 0 ? ((price - prev) / prev) * 100 : null;
                previousPrices.set(entry.symbol, price);
                return { ...entry, delta };
            });

            render(entries);
        } catch (err) {
            console.error('Failed to fetch quotes', err);
            render(symbols.map((symbol) => ({ symbol, error: 'API error' })));
        }
    };

    // Initial blank render
    render(symbols.map((symbol) => ({ symbol })));

    // Fetch immediately + poll
    fetchQuotes();
    setInterval(fetchQuotes, pollIntervalMs);
}
