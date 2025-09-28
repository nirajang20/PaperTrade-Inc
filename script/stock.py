import yfinance as yf
import json

# Your 8 tickers
tickers = ["AMZN","AAPL","TSLA","AMD","JPM","META","MSFT","GOOGL"]

results = []

for t in tickers:
    stock = yf.Ticker(t)
    price = stock.info.get("regularMarketPrice")
    if price is not None:
        results.append({"symbol": t, "price": round(price, 2)})
    else:
        results.append({"symbol": t, "error": "Price not available"})

# Print JSON output
print(json.dumps(results, indent=2))
