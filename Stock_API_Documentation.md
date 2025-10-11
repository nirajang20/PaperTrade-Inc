# 📘 Stock API Documentation  
**Version:** 1.0  
**Framework:** FastAPI  
**Library:** Yahoo Finance (`yfinance`)  
**Author:** Nirajan Ghimire  

---

## 1️⃣ Root Route
**Endpoint:** `/`  
**Method:** `GET`  
**Description:**  
Health check endpoint to verify that the API is up and running.  

**Response Example:**
```json
{
  "message": "Stock API is running with yfinance & CORS!"
}
```

---

## 2️⃣ Landing Route
**Endpoint:** `/landing`  
**Method:** `GET`  
**Query Parameters:**  
- `symbols` *(str, required)* – Comma-separated list of ticker symbols.  
  Example: `AAPL,TSLA,BTC-USD,S&P500`

**Description:**  
Fetches the **latest closing price** and **percentage change** for each symbol.  
Includes mapping for friendly names like `S&P500 → ^GSPC`.  

**Response Example:**
```json
[
  {"symbol": "AAPL", "price": 228.56, "change_pct": 1.42},
  {"symbol": "TSLA", "price": 246.83, "change_pct": -0.58},
  {"symbol": "BTC-USD", "price": 61890.34, "change_pct": 0.94},
  {"symbol": "S&P500", "price": 5268.77, "change_pct": 0.22}
]
```

**Error Example:**
```json
[
  {"symbol": "XYZ", "error": "Not found"}
]
```

---

## 3️⃣ Current Price Route
**Endpoint:** `/currentprice`  
**Method:** `GET`  
**Query Parameters:**  
- `symbols` *(str, required)* – Comma-separated tickers, e.g. `AAPL,TSLA`.

**Description:**  
Returns **latest closing price only** for each ticker.  

**Response Example:**
```json
[
  {"symbol": "AAPL", "price": 228.56},
  {"symbol": "TSLA", "price": 246.83}
]
```

**Error Example:**
```json
[
  {"symbol": "INVALID", "error": "Not found"}
]
```

---

## 4️⃣ Historical Data Route
**Endpoint:** `/history`  
**Method:** `GET`  
**Query Parameters:**  
- `symbols` *(str, required)* – Comma-separated tickers, e.g. `AAPL,TSLA`  
- `period` *(str, default="1mo")* – Range of data. Options: `1d,5d,1mo,3mo,6mo,1y`.  
- `interval` *(str, default="1d")* – Data frequency. Options: `1m,5m,15m,1h,1d,1wk,1mo`.

**Description:**  
Returns **historical close prices** for each symbol over the specified period and interval.  

**Response Example:**
```json
[
  {
    "symbol": "AAPL",
    "history": [
      {"date": "2025-09-10", "close": 224.51},
      {"date": "2025-09-11", "close": 225.38}
    ]
  }
]
```

**Error Example:**
```json
[
  {"symbol": "XYZ", "error": "Not found"}
]
```

---

## 5️⃣ Buy/Sell Detailed Panel
**Endpoint:** `/buy-sell`  
**Method:** `GET`  
**Query Parameters:**  
- `symbol` *(str, required)* – Single stock ticker, e.g. `AAPL`.

**Description:**  
Returns a **comprehensive snapshot** of a single stock for the buy/sell panel.  
Includes company info, price change, day range, 52-week range, and key metrics.  

**Response Example:**
```json
{
  "selected_stock": {
    "ticker": "AAPL",
    "company_name": "Apple Inc.",
    "exchange": "NASDAQ",
    "tags": ["Tech", "Large Cap"],
    "last_price": 228.56,
    "price_change": {
      "amount": 3.12,
      "percentage": "1.38%"
    },
    "metrics": {
      "open": 225.44,
      "previous_close": 225.44,
      "day_range": {"low": 224.88, "high": 229.12},
      "52w_range": {"low": 180.35, "high": 232.98},
      "volume": "48,222,000",
      "avg_volume": "56,512,000",
      "market_cap": "3.02T",
      "pe_ratio": "29.5x",
      "volume_1d": "48,222,000",
      "vwap": "$227.36",
      "rsi": 58.2
    }
  }
}
```

**Error Example:**
```json
{"error": "Data not available"}
```

---

## 6️⃣ Buy/Sell Summary Info
**Endpoint:** `/buy-sell-info`  
**Method:** `GET`  
**Query Parameters:**  
- `symbols` *(str, required)* – Comma-separated tickers, e.g. `AAPL,TSLA,MSFT`.

**Description:**  
Returns **summary info** for multiple symbols (used for displaying stock lists).  
Includes company name, exchange, price, % change, and auto-generated sector tags.  

**Response Example:**
```json
[
  {
    "company_name": "Apple Inc.",
    "symbol": "AAPL",
    "exchange": "NASDAQ",
    "current_price": 228.56,
    "change_pct": 1.42,
    "tags": ["Tech"]
  },
  {
    "company_name": "Tesla, Inc.",
    "symbol": "TSLA",
    "exchange": "NASDAQ",
    "current_price": 246.83,
    "change_pct": -0.58,
    "tags": ["Tech"]
  }
]
```

**Error Example:**
```json
[
  {"symbol": "INVALID", "error": "Data not available"}
]
```

---

## 7️⃣ Chart History (Candlestick)
**Endpoint:** `/chart-history`  
**Method:** `GET`  
**Query Parameters:**  
- `symbol` *(str, required)* – Stock or crypto symbol (e.g. `AAPL`, `BTC-USD`).  
- `period` *(str, default="1mo")* – Period range (`5d,1mo,3mo,6mo,1y,max`).  
- `interval` *(str, default="1d")* – Interval (`1m,15m,1h,1d,1wk,1mo`).

**Description:**  
Returns **OHLC (Open, High, Low, Close)** formatted for candlestick charts.  
Timestamps are in JavaScript milliseconds (`epoch * 1000`).  

**Response Example:**
```json
{
  "symbol": "AAPL",
  "data": [
    {"x": 1726012800000, "y": [224.5, 228.0, 223.9, 226.7]},
    {"x": 1726099200000, "y": [226.7, 229.4, 225.8, 228.5]}
  ]
}
```

**Error Example:**
```json
{"symbol": "AAPL", "error": "No data found"}
```

---

## 🧩 CORS Configuration
The API includes **CORS middleware** to allow requests from all origins:
```python
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
```

---

## 🧠 Notes
- All routes return **JSONResponse** objects.
- Uses `yfinance` for data fetching — ensure stable internet access.
- Default ports for FastAPI via `uvicorn`:  
  ```bash
  uvicorn main:app --host 0.0.0.0 --port 8000
  ```
- Health route (`/`) confirms the server status.
- Designed to integrate easily with **AdminLTE dashboards**, **trading panels**, and **chart visualizations**.
