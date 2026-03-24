<?php
require_once __DIR__ . "/NewsRepository.php";

class NewsService {
  const MAX_ARTICLES_PER_SYMBOL = 5;
  const MAX_PORTFOLIO_ARTICLES = 12;
  const LOOKBACK_DAYS = 7;
  const FETCH_LOCK_TTL_SECONDS = 15;
  const FETCH_LOCK_WAIT_RETRIES = 5;
  const FETCH_LOCK_WAIT_MICROSECONDS = 200000;

  private $repository = null;

  private const ALLOWLIST = [
    "NVDA" => ["finnhub_symbol" => "NVDA", "type" => "stock"],
    "MSFT" => ["finnhub_symbol" => "MSFT", "type" => "stock"],
    "AAPL" => ["finnhub_symbol" => "AAPL", "type" => "stock"],
    "GOOGL" => ["finnhub_symbol" => "GOOGL", "type" => "stock"],
    "AMZN" => ["finnhub_symbol" => "AMZN", "type" => "stock"],
    "META" => ["finnhub_symbol" => "META", "type" => "stock"],
    "TSLA" => ["finnhub_symbol" => "TSLA", "type" => "stock"],
    "JPM" => ["finnhub_symbol" => "JPM", "type" => "stock"],
    "^GSPC" => ["finnhub_symbol" => "SPY", "type" => "stock"],
    "BTC-USD" => ["finnhub_symbol" => "BTC", "type" => "crypto"],
    "ETH-USD" => ["finnhub_symbol" => "ETH", "type" => "crypto"]
  ];

  function __construct(NewsRepository $repository) {
    $this->repository = $repository;
  }

  function getSymbolNews($papertradeSymbol) {
    static $purgedExpiredCache = false;
    if (!$purgedExpiredCache) {
      $this->repository->purgeExpiredCache();
      $purgedExpiredCache = true;
    }

    $resolved = $this->resolveSymbol($papertradeSymbol);
    $cacheKey = "symbol:" . $resolved["papertrade_symbol"];

    $cached = $this->repository->getCachedArticles($cacheKey);
    if (is_array($cached)) {
      return [
        "papertrade_symbol" => $resolved["papertrade_symbol"],
        "finnhub_symbol" => $resolved["finnhub_symbol"],
        "articles" => array_slice($cached, 0, self::MAX_ARTICLES_PER_SYMBOL),
        "cached" => true,
        "stale" => false
      ];
    }

    $staleCached = $this->repository->getLatestCachedArticles($cacheKey);
    $lockAcquired = $this->repository->tryAcquireFetchLock($cacheKey, self::FETCH_LOCK_TTL_SECONDS);
    if (!$lockAcquired) {
      $freshCached = $this->waitForFreshCache($cacheKey);
      if (is_array($freshCached)) {
        return [
          "papertrade_symbol" => $resolved["papertrade_symbol"],
          "finnhub_symbol" => $resolved["finnhub_symbol"],
          "articles" => array_slice($freshCached, 0, self::MAX_ARTICLES_PER_SYMBOL),
          "cached" => true,
          "stale" => false
        ];
      }

      if (is_array($staleCached)) {
        return [
          "papertrade_symbol" => $resolved["papertrade_symbol"],
          "finnhub_symbol" => $resolved["finnhub_symbol"],
          "articles" => array_slice($staleCached, 0, self::MAX_ARTICLES_PER_SYMBOL),
          "cached" => true,
          "stale" => true
        ];
      }

      throw new RuntimeException("News refresh is in progress. Please retry shortly.", 503);
    }

    try {
      if (!defined("FINNHUB_API_KEY") || FINNHUB_API_KEY === "") {
        throw new RuntimeException("Finnhub API key is not configured on the server.");
      }

      $rawArticles = $resolved["type"] === "crypto"
        ? $this->fetchCryptoNews($resolved)
        : $this->fetchCompanyNews($resolved);

      $articles = $this->normalizeArticles($rawArticles, $resolved);
      $this->repository->saveCachedArticles(
        $cacheKey,
        $resolved["papertrade_symbol"],
        $resolved["finnhub_symbol"],
        $articles
      );

      return [
        "papertrade_symbol" => $resolved["papertrade_symbol"],
        "finnhub_symbol" => $resolved["finnhub_symbol"],
        "articles" => $articles,
        "cached" => false,
        "stale" => false
      ];
    } catch (RuntimeException $exception) {
      if ($this->shouldServeStaleCache($exception) && is_array($staleCached)) {
        return [
          "papertrade_symbol" => $resolved["papertrade_symbol"],
          "finnhub_symbol" => $resolved["finnhub_symbol"],
          "articles" => array_slice($staleCached, 0, self::MAX_ARTICLES_PER_SYMBOL),
          "cached" => true,
          "stale" => true
        ];
      }

      throw $exception;
    } finally {
      if ($lockAcquired) {
        $this->repository->releaseFetchLock($cacheKey);
      }
    }
  }

  function getPortfolioNews($userId) {
    $userId = (int) $userId;
    if ($userId <= 0) {
      throw new InvalidArgumentException("A valid user is required.");
    }

    $symbols = [];
    foreach ($this->repository->getPortfolioSymbolsForUser($userId) as $symbol) {
      $normalized = strtoupper(trim((string) $symbol));
      if (isset(self::ALLOWLIST[$normalized])) {
        $symbols[] = $normalized;
      }
    }
    $symbols = array_values(array_unique($symbols));

    $merged = [];
    $hasStale = false;
    foreach ($symbols as $symbol) {
      $payload = $this->getSymbolNews($symbol);
      if (!empty($payload["stale"])) {
        $hasStale = true;
      }
      foreach ($payload["articles"] as $article) {
        $key = $article["url"] !== "" ? $article["url"] : ($symbol . "|" . $article["headline"]);
        if (!isset($merged[$key])) {
          $article["papertrade_symbol"] = $symbol;
          $merged[$key] = $article;
        }
      }
    }

    $articles = array_values($merged);
    usort($articles, function ($left, $right) {
      return ((int) ($right["published_at_unix"] ?? 0)) <=> ((int) ($left["published_at_unix"] ?? 0));
    });

    return [
      "symbols" => $symbols,
      "articles" => array_slice($articles, 0, self::MAX_PORTFOLIO_ARTICLES),
      "stale" => $hasStale
    ];
  }

  function resolveSymbol($papertradeSymbol) {
    $normalized = strtoupper(trim((string) $papertradeSymbol));
    if (!isset(self::ALLOWLIST[$normalized])) {
      throw new InvalidArgumentException("Unsupported symbol requested.");
    }

    return [
      "papertrade_symbol" => $normalized,
      "finnhub_symbol" => self::ALLOWLIST[$normalized]["finnhub_symbol"],
      "type" => self::ALLOWLIST[$normalized]["type"]
    ];
  }

  private function fetchCompanyNews(array $resolved) {
    $to = gmdate("Y-m-d");
    $from = gmdate("Y-m-d", strtotime("-" . self::LOOKBACK_DAYS . " days"));
    $url = sprintf(
      "https://finnhub.io/api/v1/company-news?symbol=%s&from=%s&to=%s&token=%s",
      rawurlencode($resolved["finnhub_symbol"]),
      rawurlencode($from),
      rawurlencode($to),
      rawurlencode(FINNHUB_API_KEY)
    );

    return $this->requestJson($url);
  }

  private function fetchCryptoNews(array $resolved) {
    $url = sprintf(
      "https://finnhub.io/api/v1/news?category=crypto&token=%s",
      rawurlencode(FINNHUB_API_KEY)
    );

    $articles = $this->requestJson($url);
    if (!is_array($articles)) {
      return [];
    }

    $assetCode = $resolved["finnhub_symbol"];
    $searchTerms = $assetCode === "BTC"
      ? ["BTC", "BITCOIN", "BTC-USD"]
      : ["ETH", "ETHEREUM", "ETH-USD"];

    $filtered = [];
    foreach ($articles as $article) {
      if (!is_array($article)) {
        continue;
      }

      $haystack = strtoupper(
        implode(" ", [
          (string) ($article["related"] ?? ""),
          (string) ($article["headline"] ?? ""),
          (string) ($article["summary"] ?? "")
        ])
      );

      foreach ($searchTerms as $term) {
        if ($term !== "" && strpos($haystack, $term) !== false) {
          $filtered[] = $article;
          break;
        }
      }
    }

    return $filtered;
  }

  private function requestJson($url) {
    if (!function_exists("curl_init")) {
      throw new RuntimeException("cURL is not available on the server.", 500);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_HTTPHEADER => ["Accept: application/json"],
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $curlError !== "") {
      throw new RuntimeException("Unable to reach the news provider.", 503);
    }

    if ($statusCode === 429) {
      throw new RuntimeException("News provider rate limit reached.", 429);
    }

    if ($statusCode < 200 || $statusCode >= 300) {
      throw new RuntimeException("News provider returned an unexpected response.", $statusCode);
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
      throw new RuntimeException("News provider returned invalid data.", 502);
    }

    if (!$this->isListArray($decoded)) {
      $providerError = trim((string) ($decoded["error"] ?? ""));
      if ($providerError !== "") {
        throw new RuntimeException("News provider rejected the request.", 502);
      }
      throw new RuntimeException("News provider returned invalid data.", 502);
    }

    return $decoded;
  }

  private function normalizeArticles(array $articles, array $resolved) {
    usort($articles, function ($left, $right) {
      $leftTimestamp = is_array($left) ? (int) ($left["datetime"] ?? 0) : 0;
      $rightTimestamp = is_array($right) ? (int) ($right["datetime"] ?? 0) : 0;
      return $rightTimestamp <=> $leftTimestamp;
    });

    $normalized = [];

    foreach ($articles as $article) {
      if (!is_array($article)) {
        continue;
      }

      $headline = $this->sanitizeText($article["headline"] ?? "", 220);
      $url = filter_var((string) ($article["url"] ?? ""), FILTER_VALIDATE_URL);
      $scheme = strtolower((string) parse_url((string) $url, PHP_URL_SCHEME));
      if ($headline === "" || $url === false || !in_array($scheme, ["http", "https"], true)) {
        continue;
      }

      $timestamp = (int) ($article["datetime"] ?? 0);
      $summary = $this->sanitizeText($article["summary"] ?? "", 420);
      $source = $this->sanitizeText($article["source"] ?? "Finnhub", 80);
      $imageUrlRaw = filter_var((string) ($article["image"] ?? ""), FILTER_VALIDATE_URL);
      $imageScheme = strtolower((string) parse_url((string) $imageUrlRaw, PHP_URL_SCHEME));
      $imageUrl = ($imageUrlRaw !== false && in_array($imageScheme, ["http", "https"], true)) ? (string) $imageUrlRaw : "";

      $normalized[] = [
        "papertrade_symbol" => $resolved["papertrade_symbol"],
        "headline" => $headline,
        "summary" => $summary,
        "source" => $source !== "" ? $source : "Finnhub",
        "image_url" => $imageUrl,
        "url" => $url,
        "published_at" => $timestamp > 0 ? gmdate(DATE_ATOM, $timestamp) : "",
        "published_at_unix" => $timestamp
      ];

      if (count($normalized) >= self::MAX_ARTICLES_PER_SYMBOL) {
        break;
      }
    }

    return $normalized;
  }

  private function sanitizeText($value, $maxLength) {
    $text = trim((string) $value);
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    if ($text === null) {
      $text = "";
    }

    if (function_exists("mb_substr")) {
      return mb_substr($text, 0, $maxLength);
    }

    return substr($text, 0, $maxLength);
  }

  private function isListArray(array $value) {
    if ($value === []) {
      return true;
    }

    return array_keys($value) === range(0, count($value) - 1);
  }

  private function shouldServeStaleCache(RuntimeException $exception) {
    $code = (int) $exception->getCode();
    return $code === 429 || $code >= 500;
  }

  private function waitForFreshCache($cacheKey) {
    for ($attempt = 0; $attempt < self::FETCH_LOCK_WAIT_RETRIES; $attempt++) {
      usleep(self::FETCH_LOCK_WAIT_MICROSECONDS);
      $cached = $this->repository->getCachedArticles($cacheKey);
      if (is_array($cached)) {
        return $cached;
      }
    }

    return null;
  }
}
