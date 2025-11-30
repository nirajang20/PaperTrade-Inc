import requests
import json

URL = "http://74.208.158.229/stock-api/landing?symbols=NVDA,MSFT,AAPL,GOOGL,AMZN,META,TSLA,JPM,^GSPC,BTC-USD,ETH-USD"
OUTPUT_FILE = "landing.txt"

def fetch_and_save():
    try:
        response = requests.get(URL, timeout=10)
        response.raise_for_status()  # Raises error for bad responses
        
        data = response.json()

        with open(OUTPUT_FILE, "w") as file:
            json.dump(data, file, indent=4)

        print("Data saved to landing.txt")

    except Exception as e:
        print("Error:", e)

if __name__ == "__main__":
    fetch_and_save()
