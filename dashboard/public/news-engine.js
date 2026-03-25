(function (window, document) {
  "use strict";

  var STYLE_ID = "papertrade-news-engine-style";
  var SAVED_KEY = "papertrade:news:saved";
  var DISMISSED_KEY = "papertrade:news:dismissed";
  var READ_KEY = "papertrade:news:read";
  var EXPANDED_KEY = "papertrade:news:expanded";
  var TRUSTED = ["reuters", "bloomberg", "associated press", "ap news", "wsj", "wall street journal", "cnbc", "financial times"];
  var FILTERS = [["all", "All"], ["portfolio", "Portfolio"], ["watchlist", "Watchlist"], ["stocks", "Stocks"], ["crypto", "Crypto"], ["market", "Market"]];
  var WINDOWS = [["1d", "Last 24h", 1], ["3d", "Last 3d", 3], ["7d", "Last 7d", 7]];

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    var style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent = `
      .pt-ni-host{background:transparent;color:inherit}.pt-ni-layout,.pt-ni-asset{display:block}
      .pt-ni-toolbar{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;padding:0 0 14px;border-bottom:1px solid #d7dde5}
      .pt-ni-group{display:flex;flex-wrap:wrap;gap:8px}.pt-ni-chip{border:1px solid #d1d5db;background:#fff;color:#4b5563;padding:5px 10px;font-size:11px;font-weight:700;text-transform:uppercase;cursor:pointer}
      .pt-ni-chip.on{background:#111827;border-color:#111827;color:#fff}.pt-ni-toolbtn,.pt-ni-toolstat{border:1px solid #d1d5db;background:#fff;color:#4b5563;padding:5px 10px;font-size:11px;font-weight:700;text-transform:uppercase}
      .pt-ni-toolbtn{cursor:pointer}
      .pt-ni-feature{padding:16px 0 18px;border-bottom:1px solid #d7dde5}
      .pt-ni-kicker,.pt-ni-title-sm{margin:0 0 6px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#1f2937}
      .pt-ni-feature-main,.pt-ni-item-main{cursor:pointer}.pt-ni-feature-main:hover .pt-ni-head,.pt-ni-item-main:hover .pt-ni-subhead{text-decoration:underline}
      .pt-ni-meta{margin:0 0 6px;font-size:11px;color:#6b7280;font-weight:600}.pt-ni-unread-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#2563eb;margin-right:6px;vertical-align:middle}.pt-ni-head{margin:0;font-size:24px;line-height:1.3;font-weight:700;color:#111827}
      .pt-ni-copy{margin:10px 0 0;font-size:14px;line-height:1.7;color:#4b5563;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
      .pt-ni-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,1fr);gap:24px;padding-top:18px}.pt-ni-stack{display:grid;gap:18px}
      .pt-ni-section+.pt-ni-section{margin-top:18px;padding-top:16px;border-top:1px solid #d7dde5}.pt-ni-stack .pt-ni-section+.pt-ni-section{margin-top:0}
      .pt-ni-item{padding:10px 0;border-bottom:1px solid #e5e7eb}.pt-ni-item:last-child,.pt-ni-empty:last-child{border-bottom:none}
      .pt-ni-subhead{margin:0;font-size:15px;line-height:1.45;font-weight:600;color:#111827}.pt-ni-copy-sm{margin:5px 0 0;font-size:13px;line-height:1.55;color:#4b5563;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
      .pt-ni-rowline,.pt-ni-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}
      .pt-ni-tag{border:1px solid #d1d5db;color:#4b5563;padding:3px 8px;font-size:10px;font-weight:700;text-transform:uppercase}.pt-ni-tag.ctx{border-color:#c7d2fe;color:#4338ca;background:#eef2ff}.pt-ni-tag.trust{border-color:#bbf7d0;color:#166534;background:#f0fdf4}.pt-ni-tag.saved{border-color:#fde68a;color:#92400e;background:#fffbeb}.pt-ni-tag.dupe{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}
      .pt-ni-btn{border:none;background:none;padding:0;font-size:11px;font-weight:700;text-transform:uppercase;color:#2563eb;cursor:pointer}.pt-ni-btn.muted{color:#6b7280}
      .pt-ni-empty,.pt-ni-note{padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280}.pt-ni-skel{padding:10px 0;border-bottom:1px solid #e5e7eb}.pt-ni-skel:last-child{border-bottom:none}
      .pt-ni-flash{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#4b5563}
      .pt-ni-line{height:8px;background:#e5e7eb;margin-bottom:8px}.pt-ni-line:last-child{margin-bottom:0}.pt-ni-line.sm{width:26%}.pt-ni-line.md{width:56%}.pt-ni-line.lg{width:88%}
      .pt-ni-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(17,24,39,.62);z-index:2000}.pt-ni-modal.open{display:flex}
      .pt-ni-panel{width:min(720px,100%);max-height:min(88vh,780px);overflow:auto;background:#fff;border:1px solid #d1d5db;padding:20px}
      .pt-ni-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;border-bottom:1px solid #e5e7eb;padding-bottom:12px}.pt-ni-close{border:1px solid #d1d5db;background:#fff;color:#4b5563;padding:6px 10px;font-size:11px;font-weight:700;text-transform:uppercase;cursor:pointer}
      .pt-ni-modal-head{margin:14px 0 0;font-size:22px;line-height:1.35;font-weight:700;color:#111827}.pt-ni-modal-meta{margin:10px 0 0;font-size:12px;line-height:1.6;color:#6b7280}.pt-ni-modal-copy{margin:14px 0 0;font-size:14px;line-height:1.75;color:#374151}
      .pt-ni-modal-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid #e5e7eb}.pt-ni-link{display:inline-flex;align-items:center;border:1px solid #111827;background:#111827;color:#fff;padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;text-decoration:none}
      .pt-ni-hidden-list{margin-top:14px}.pt-ni-hidden-item{padding:10px 0;border-bottom:1px solid #e5e7eb}.pt-ni-hidden-item:last-child{border-bottom:none}
      .pt-ni-hidden-head{margin:0;font-size:14px;line-height:1.5;font-weight:600;color:#111827}.pt-ni-hidden-meta{margin:0 0 5px;font-size:11px;line-height:1.6;color:#6b7280}
      body.dark-mode .pt-ni-host{background:transparent;color:inherit} body.dark-mode .pt-ni-toolbar,body.dark-mode .pt-ni-feature,body.dark-mode .pt-ni-section+.pt-ni-section{border-color:#374151}
      body.dark-mode .pt-ni-chip,body.dark-mode .pt-ni-toolbtn,body.dark-mode .pt-ni-toolstat{background:#1f2937;border-color:#4b5563;color:#d1d5db} body.dark-mode .pt-ni-chip.on{background:#f3f4f6;border-color:#f3f4f6;color:#111827}
      body.dark-mode .pt-ni-kicker,body.dark-mode .pt-ni-title-sm{color:#f3f4f6} body.dark-mode .pt-ni-meta,body.dark-mode .pt-ni-empty,body.dark-mode .pt-ni-note,body.dark-mode .pt-ni-modal-meta{color:#9ca3af}
      body.dark-mode .pt-ni-head,body.dark-mode .pt-ni-subhead,body.dark-mode .pt-ni-modal-head{color:#f9fafb} body.dark-mode .pt-ni-copy,body.dark-mode .pt-ni-copy-sm,body.dark-mode .pt-ni-modal-copy{color:#d1d5db}
      body.dark-mode .pt-ni-item,body.dark-mode .pt-ni-empty,body.dark-mode .pt-ni-note,body.dark-mode .pt-ni-skel,body.dark-mode .pt-ni-top,body.dark-mode .pt-ni-modal-actions,body.dark-mode .pt-ni-flash,body.dark-mode .pt-ni-hidden-item{border-color:#374151}
      body.dark-mode .pt-ni-line{background:#374151} body.dark-mode .pt-ni-tag{border-color:#4b5563;color:#d1d5db;background:#243041} body.dark-mode .pt-ni-tag.ctx{border-color:#6366f1;color:#c7d2fe;background:rgba(67,56,202,.18)}
      body.dark-mode .pt-ni-tag.trust{border-color:#14532d;color:#bbf7d0;background:rgba(20,83,45,.25)} body.dark-mode .pt-ni-tag.saved{border-color:#92400e;color:#fde68a;background:rgba(146,64,14,.22)} body.dark-mode .pt-ni-tag.dupe{border-color:#1d4ed8;color:#bfdbfe;background:rgba(37,99,235,.2)}
      body.dark-mode .pt-ni-btn{color:#93c5fd} body.dark-mode .pt-ni-btn.muted{color:#9ca3af} body.dark-mode .pt-ni-flash{color:#d1d5db} body.dark-mode .pt-ni-panel{background:#1f2937;border-color:#374151} body.dark-mode .pt-ni-close{background:#1f2937;border-color:#4b5563;color:#d1d5db} body.dark-mode .pt-ni-link{background:#f3f4f6;border-color:#f3f4f6;color:#111827}
      body.dark-mode .pt-ni-hidden-head{color:#f9fafb} body.dark-mode .pt-ni-hidden-meta{color:#9ca3af}
      @media (max-width:991px){.pt-ni-head{font-size:20px}.pt-ni-grid{grid-template-columns:1fr;gap:18px}.pt-ni-stack{gap:16px}}
    `;
    document.head.appendChild(style);
  }

  function clear(el) { while (el && el.firstChild) el.removeChild(el.firstChild); }
  function host(el) { if (el) el.classList.add("pt-ni-host"); }
  function uniq(list) { var seen = {}, out = []; (list || []).forEach(function (v) { v = String(v || "").trim().toUpperCase(); if (v && !seen[v]) { seen[v] = true; out.push(v); } }); return out; }
  function read(key) { try { var v = JSON.parse(window.localStorage.getItem(key) || "[]"); return Array.isArray(v) ? v : []; } catch (e) { return []; } }
  function write(key, val) { try { window.localStorage.setItem(key, JSON.stringify(val)); } catch (e) {} }
  function readBool(key, fallback) {
    try {
      var raw = window.localStorage.getItem(key);
      if (raw === null) return !!fallback;
      return raw === "true";
    } catch (e) { return !!fallback; }
  }
  function writeBool(key, val) { try { window.localStorage.setItem(key, val ? "true" : "false"); } catch (e) {} }
  function has(key, id) { return read(key).indexOf(id) >= 0; }
  function clearKey(key) { write(key, []); }
  function setVal(key, id, on) { var list = read(key), i = list.indexOf(id); if (on && i < 0) list.push(id); if (!on && i >= 0) list.splice(i, 1); write(key, list); }
  function toggleVal(key, id) { var on = !has(key, id); setVal(key, id, on); return on; }
  function headerStamp(d) { return !(d instanceof Date) || Number.isNaN(d.getTime()) ? "Pending" : d.toLocaleString([], { month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" }); }
  function ago(raw) { if (!raw) return "Just now"; var d = new Date(raw); if (Number.isNaN(d.getTime())) return "Just now"; var s = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000)); if (s < 60) return "Now"; var m = Math.floor(s / 60); if (m < 60) return m + "m ago"; var h = Math.floor(m / 60); if (h < 24) return h + "h ago"; return Math.floor(h / 24) + "d ago"; }
  function freshness(d) { if (!(d instanceof Date) || Number.isNaN(d.getTime())) return "Pending"; var s = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000)); if (s < 60) return "Updated just now"; var m = Math.floor(s / 60); if (m < 60) return "Updated " + m + "m ago"; var h = Math.floor(m / 60); if (h < 24) return "Updated " + h + "h ago"; return "Updated " + Math.floor(h / 24) + "d ago"; }
  function trusted(src) { src = String(src || "").trim().toLowerCase(); return TRUSTED.indexOf(src) >= 0; }
  function assetKind(symbol) { symbol = String(symbol || "").toUpperCase(); return symbol === "BTC-USD" || symbol === "ETH-USD" ? "crypto" : "stock"; }
  function ctxLabel(ctx) { return ctx === "portfolio" ? "Held in portfolio" : ctx === "watchlist" ? "On your watchlist" : "Broad market context"; }
  function ctxRank(ctx) { return ctx === "portfolio" ? 3 : ctx === "watchlist" ? 2 : 1; }
  function betterItem(next, prev) {
    if (!prev) return true;
    if (ctxRank(next.context) !== ctxRank(prev.context)) return ctxRank(next.context) > ctxRank(prev.context);
    if (next.trusted !== prev.trusted) return next.trusted && !prev.trusted;
    return next.publishedAtMs > prev.publishedAtMs;
  }
  function normalizedHeadlineKey(headline) {
    var key = String(headline || "").toLowerCase().replace(/[^a-z0-9\s]/g, " ").replace(/\s+/g, " ").trim();
    if (!key) return "";
    return key.split(" ").slice(0, 14).join(" ");
  }
  function duplicateKey(item) {
    if (!item) return "";
    var h = normalizedHeadlineKey(item.headline);
    if (!h) return item.id || "";
    return String(item.papertradeSymbol || "N/A").toUpperCase() + "|" + h;
  }

  function category(article) {
    var symbol = String(article && article.papertrade_symbol ? article.papertrade_symbol : "").toUpperCase();
    var text = [article && article.headline ? article.headline : "", article && article.summary ? article.summary : ""].join(" ").toLowerCase();
    if (symbol === "BTC-USD" || symbol === "ETH-USD") return "Crypto";
    if (/earnings|guidance|revenue|results|forecast|quarter|q1|q2|q3|q4/.test(text)) return "Earnings";
    if (/analyst|rating|target|upgrade|downgrade|outperform|neutral/.test(text)) return "Analyst";
    if (/sec|regulat|lawsuit|probe|ban|approval|etf/.test(text)) return "Regulation";
    if (/fed|inflation|cpi|jobs|economy|treasury|rate|market|macro/.test(text) || symbol === "^GSPC") return "Market";
    return "General";
  }

  function normalize(items, ctx, hints) {
    return (items || []).map(function (item) {
      var symbol = String(item && item.papertrade_symbol ? item.papertrade_symbol : (hints && hints[0] ? hints[0] : "N/A")).toUpperCase();
      var source = item && item.source ? item.source : "Finnhub";
      var rawDate = item && item.published_at ? item.published_at : "";
      var d = rawDate ? new Date(rawDate) : null;
      var ms = d instanceof Date && !Number.isNaN(d.getTime()) ? d.getTime() : 0;
      return {
        id: String(item && item.url ? item.url : symbol + "|" + (item && item.headline ? item.headline : "untitled")),
        papertradeSymbol: symbol,
        category: category(item),
        timeAgo: ago(rawDate),
        headline: item && item.headline ? item.headline : "Untitled",
        summary: item && item.summary ? item.summary : "Open the source for more details.",
        source: source,
        url: item && item.url ? item.url : "#",
        context: ctx,
        contextLabel: ctxLabel(ctx),
        assetKind: assetKind(symbol),
        trusted: trusted(source),
        publishedAtMs: ms
      };
    });
  }

  function sortDedupe(items) {
    var map = {};
    (items || []).forEach(function (item) {
      if (!item || !item.id) return;
      var key = duplicateKey(item) || item.id;
      var group = map[key];
      if (!group) {
        group = { lead: item, sources: {} };
        map[key] = group;
      }
      if (betterItem(item, group.lead)) group.lead = item;
      group.sources[String(item.source || "Unknown")] = true;
    });
    return Object.keys(map).map(function (k) {
      var group = map[k];
      var lead = group.lead;
      var src = Object.keys(group.sources);
      lead.sourceCount = src.length;
      lead.sourceExtraCount = Math.max(0, src.length - 1);
      lead.sourceList = src;
      return lead;
    }).sort(function (a, b) {
      if (ctxRank(b.context) !== ctxRank(a.context)) return ctxRank(b.context) - ctxRank(a.context);
      if (b.trusted !== a.trusted) return b.trusted ? 1 : -1;
      return b.publishedAtMs - a.publishedAtMs;
    });
  }

  function sectionSplit(items) {
    var out = { portfolio: [], market: [], crypto: [] }, general = [];
    items.forEach(function (item) {
      if (item.assetKind === "crypto" || item.category === "Crypto") out.crypto.push(item);
      else if (item.category === "Market" || item.context === "market" || item.papertradeSymbol === "^GSPC") out.market.push(item);
      else if (item.category === "General") general.push(item);
      else out.portfolio.push(item);
    });
    out.market = out.market.concat(general);
    return out;
  }

  function apply(items, state) {
    var now = Date.now();
    var win = WINDOWS.filter(function (w) { return w[0] === state.window; })[0] || WINDOWS[2];
    return (items || []).filter(function (item) {
      if (has(DISMISSED_KEY, item.id)) return false;
      if (item.publishedAtMs && (now - item.publishedAtMs) > (win[2] * 86400000)) return false;
      if (state.filter === "portfolio" && item.context !== "portfolio") return false;
      if (state.filter === "watchlist" && item.context !== "watchlist") return false;
      if (state.filter === "stocks" && item.assetKind !== "stock") return false;
      if (state.filter === "crypto" && item.assetKind !== "crypto") return false;
      if (state.filter === "market" && !(item.category === "Market" || item.context === "market")) return false;
      item.saved = has(SAVED_KEY, item.id);
      item.read = has(READ_KEY, item.id);
      return true;
    });
  }

  function tag(text, cls) { var el = document.createElement("span"); el.className = "pt-ni-tag" + (cls ? " " + cls : ""); el.textContent = text; return el; }
  function action(text, fn, muted) { var el = document.createElement("button"); el.type = "button"; el.className = "pt-ni-btn" + (muted ? " muted" : ""); el.textContent = text; el.addEventListener("click", function (e) { e.preventDefault(); e.stopPropagation(); fn(); }); return el; }
  function fillMeta(el, item) {
    clear(el);
    if (!item.read) {
      var dot = document.createElement("span");
      dot.className = "pt-ni-unread-dot";
      dot.setAttribute("aria-label", "Unread");
      dot.title = "Unread";
      el.appendChild(dot);
    }
    el.appendChild(document.createTextNode(item.papertradeSymbol + " | " + item.category + " | " + item.timeAgo));
  }

  function modal() {
    var wrap = document.createElement("div"), panel = document.createElement("div"), top = document.createElement("div"), left = document.createElement("div");
    var kicker = document.createElement("p"), close = document.createElement("button"), head = document.createElement("h3"), meta = document.createElement("p"), copy = document.createElement("p"), actions = document.createElement("div");
    wrap.className = "pt-ni-modal"; panel.className = "pt-ni-panel"; top.className = "pt-ni-top"; kicker.className = "pt-ni-kicker"; close.className = "pt-ni-close"; head.className = "pt-ni-modal-head"; meta.className = "pt-ni-modal-meta"; copy.className = "pt-ni-modal-copy"; actions.className = "pt-ni-modal-actions";
    close.type = "button"; close.textContent = "Close"; left.appendChild(kicker); top.appendChild(left); top.appendChild(close); panel.appendChild(top); panel.appendChild(head); panel.appendChild(meta); panel.appendChild(copy); panel.appendChild(actions); wrap.appendChild(panel); document.body.appendChild(wrap);
    function hide() { wrap.classList.remove("open"); }
    close.addEventListener("click", hide); wrap.addEventListener("click", function (e) { if (e.target === wrap) hide(); });
    return { wrap: wrap, kicker: kicker, head: head, meta: meta, copy: copy, actions: actions, hide: hide };
  }

  function hiddenModal() {
    var wrap = document.createElement("div"), panel = document.createElement("div"), top = document.createElement("div"), left = document.createElement("div");
    var kicker = document.createElement("p"), close = document.createElement("button"), head = document.createElement("h3"), meta = document.createElement("p"), list = document.createElement("div"), actions = document.createElement("div");
    wrap.className = "pt-ni-modal"; panel.className = "pt-ni-panel"; top.className = "pt-ni-top"; kicker.className = "pt-ni-kicker"; close.className = "pt-ni-close"; head.className = "pt-ni-modal-head"; meta.className = "pt-ni-modal-meta"; list.className = "pt-ni-hidden-list"; actions.className = "pt-ni-modal-actions";
    close.type = "button"; close.textContent = "Close"; kicker.textContent = "Hidden Stories"; left.appendChild(kicker); top.appendChild(left); top.appendChild(close); panel.appendChild(top); panel.appendChild(head); panel.appendChild(meta); panel.appendChild(list); panel.appendChild(actions); wrap.appendChild(panel); document.body.appendChild(wrap);
    function hide() { wrap.classList.remove("open"); }
    close.addEventListener("click", hide); wrap.addEventListener("click", function (e) { if (e.target === wrap) hide(); });
    return { wrap: wrap, head: head, meta: meta, list: list, actions: actions, hide: hide };
  }

  function toolbar(state, scope, rerender, onManageHidden) {
    var bar = document.createElement("div"), left = document.createElement("div"), right = document.createElement("div");
    bar.className = "pt-ni-toolbar"; left.className = "pt-ni-group"; right.className = "pt-ni-group";
    if (scope === "portfolio") FILTERS.forEach(function (f) {
      var b = document.createElement("button");
      b.type = "button";
      b.className = "pt-ni-chip" + (state.filter === f[0] ? " on" : "");
      b.textContent = f[1];
      b.addEventListener("click", function () {
        state.filter = f[0];
        state.sectionExpanded = {};
        rerender();
      });
      left.appendChild(b);
    });
    var unread = document.createElement("span");
    unread.className = "pt-ni-toolstat";
    unread.textContent = "Unread " + String(state.unreadCount || 0);
    right.appendChild(unread);
    var view = document.createElement("button");
    view.type = "button";
    view.className = "pt-ni-toolbtn";
    view.textContent = state.expanded ? "Minimize" : "Expand";
    view.addEventListener("click", function () {
      state.expanded = !state.expanded;
      writeBool(EXPANDED_KEY, state.expanded);
      rerender();
    });
    right.appendChild(view);
    WINDOWS.forEach(function (w) {
      var b = document.createElement("button");
      b.type = "button";
      b.className = "pt-ni-chip" + (state.window === w[0] ? " on" : "");
      b.textContent = w[1];
      b.addEventListener("click", function () {
        state.window = w[0];
        state.sectionExpanded = {};
        rerender();
      });
      right.appendChild(b);
    });
    var hiddenCount = read(DISMISSED_KEY).length;
    if (hiddenCount > 0) {
      var manage = document.createElement("button");
      manage.type = "button";
      manage.className = "pt-ni-toolbtn";
      manage.textContent = "Hidden (" + hiddenCount + ")";
      manage.addEventListener("click", onManageHidden);
      right.appendChild(manage);
    }
    bar.appendChild(left); bar.appendChild(right); return bar;
  }

  function feature(item, label, openPreview, rerender, onDismiss) {
    var box = document.createElement("section"), kick = document.createElement("p"), main = document.createElement("div"), meta = document.createElement("p"), head = document.createElement("h3"), copy = document.createElement("p"), row = document.createElement("div");
    box.className = "pt-ni-feature"; kick.className = "pt-ni-kicker"; kick.textContent = label; main.className = "pt-ni-feature-main"; meta.className = "pt-ni-meta"; head.className = "pt-ni-head"; copy.className = "pt-ni-copy"; row.className = "pt-ni-rowline";
    main.addEventListener("click", function () { openPreview(item, rerender); });
    fillMeta(meta, item); head.textContent = item.headline; copy.textContent = item.summary;
    row.appendChild(tag(item.source, ""));
    if (item.sourceExtraCount > 0) row.appendChild(tag("+" + item.sourceExtraCount + " sources", "dupe"));
    row.appendChild(tag(item.contextLabel, "ctx")); if (item.trusted) row.appendChild(tag("Trusted", "trust")); if (item.saved) row.appendChild(tag("Saved", "saved"));
    row.appendChild(action(item.saved ? "Unsave" : "Save", function () { item.saved = toggleVal(SAVED_KEY, item.id); rerender(); }, false));
    row.appendChild(action("Dismiss", function () { onDismiss(item); }, true));
    row.appendChild(action("Preview", function () { openPreview(item, rerender); }, false));
    main.appendChild(meta); main.appendChild(head); main.appendChild(copy); box.appendChild(kick); box.appendChild(main); box.appendChild(row); return box;
  }

  function itemRow(item, openPreview, rerender, onDismiss) {
    var box = document.createElement("article"), main = document.createElement("div"), meta = document.createElement("p"), head = document.createElement("h4"), copy = document.createElement("p"), row = document.createElement("div"), act = document.createElement("div");
    box.className = "pt-ni-item"; main.className = "pt-ni-item-main"; meta.className = "pt-ni-meta"; head.className = "pt-ni-subhead"; copy.className = "pt-ni-copy-sm"; row.className = "pt-ni-rowline"; act.className = "pt-ni-actions";
    main.addEventListener("click", function () { openPreview(item, rerender); });
    fillMeta(meta, item); head.textContent = item.headline; copy.textContent = item.summary;
    row.appendChild(tag(item.source, ""));
    if (item.sourceExtraCount > 0) row.appendChild(tag("+" + item.sourceExtraCount + " sources", "dupe"));
    row.appendChild(tag(item.contextLabel, "ctx")); if (item.trusted) row.appendChild(tag("Trusted", "trust")); if (item.saved) row.appendChild(tag("Saved", "saved"));
    act.appendChild(action(item.saved ? "Unsave" : "Save", function () { item.saved = toggleVal(SAVED_KEY, item.id); rerender(); }, false));
    act.appendChild(action("Dismiss", function () { onDismiss(item); }, true));
    act.appendChild(action("Preview", function () { openPreview(item, rerender); }, false));
    main.appendChild(meta); main.appendChild(head); main.appendChild(copy); box.appendChild(main); box.appendChild(row); box.appendChild(act); return box;
  }

  function section(title, items, openPreview, rerender, onDismiss, state, sectionKey) {
    if (!items.length) return null;
    var box = document.createElement("section"), t = document.createElement("h3");
    box.className = "pt-ni-section"; t.className = "pt-ni-title-sm"; t.textContent = title; box.appendChild(t);
    var cap = Number(state && state.sectionCap ? state.sectionCap : 3);
    var expanded = !!(state && state.sectionExpanded && state.sectionExpanded[sectionKey]);
    var visible = expanded ? items : items.slice(0, cap);
    visible.forEach(function (item) { box.appendChild(itemRow(item, openPreview, rerender, onDismiss)); });
    if (items.length > cap && state && state.sectionExpanded) {
      var controls = document.createElement("div");
      controls.className = "pt-ni-actions";
      controls.appendChild(action(expanded ? "Show less" : "Show more (" + (items.length - cap) + ")", function () {
        state.sectionExpanded[sectionKey] = !expanded;
        rerender();
      }, true));
      box.appendChild(controls);
    }
    return box;
  }

  function emptyNote(text) { var el = document.createElement("div"); el.className = "pt-ni-note"; el.textContent = text; return el; }
  function dismissNotice(state, rerender) {
    if (!state || !state.dismissNotice) return null;
    var row = document.createElement("div"), text = document.createElement("span");
    row.className = "pt-ni-flash";
    text.textContent = "Story dismissed.";
    row.appendChild(text);
    row.appendChild(action("Undo", function () {
      setVal(DISMISSED_KEY, state.dismissNotice.id, false);
      if (state.noticeHandle) clearTimeout(state.noticeHandle);
      state.noticeHandle = null;
      state.dismissNotice = null;
      rerender();
    }, false));
    return row;
  }
  function loading(title, count) { var s = document.createElement("section"), t = document.createElement("h3"); s.className = "pt-ni-section"; t.className = "pt-ni-title-sm"; t.textContent = title; s.appendChild(t); for (var i = 0; i < count; i += 1) { var sk = document.createElement("div"), a = document.createElement("div"), b = document.createElement("div"), c = document.createElement("div"); sk.className = "pt-ni-skel"; a.className = "pt-ni-line sm"; b.className = "pt-ni-line lg"; c.className = "pt-ni-line md"; sk.appendChild(a); sk.appendChild(b); sk.appendChild(c); s.appendChild(sk); } return s; }

  function renderPortfolio(container, all, state, openPreview, rerender, onDismiss, onManageHidden) {
    clear(container); host(container);
    var list = apply(all, state), root = document.createElement("section");
    state.unreadCount = list.filter(function (item) { return !item.read; }).length;
    root.className = "pt-ni-layout"; root.appendChild(toolbar(state, "portfolio", rerender, onManageHidden));
    var flash = dismissNotice(state, rerender);
    if (flash) root.appendChild(flash);
    if (!list.length) { root.appendChild(emptyNote("No stories match the current filters.")); container.appendChild(root); return; }
    root.appendChild(feature(list[0], "Lead Story", openPreview, rerender, onDismiss));
    if (!state.expanded) {
      if (list.length > 1) root.appendChild(emptyNote((list.length - 1) + " more " + (list.length - 1 === 1 ? "story" : "stories") + ". Click Expand to view all."));
      container.appendChild(root);
      return;
    }
    var grouped = sectionSplit(list.slice(1)), defs = [];
    if (grouped.portfolio.length) defs.push(["Portfolio Impact", grouped.portfolio, "portfolio"]);
    if (grouped.market.length) defs.push(["Market Update", grouped.market, "market"]);
    if (grouped.crypto.length) defs.push(["Crypto Updates", grouped.crypto, "crypto"]);
    if (!defs.length) { container.appendChild(root); return; }
    if (defs.length === 1) { root.appendChild(section(defs[0][0], defs[0][1], openPreview, rerender, onDismiss, state, defs[0][2])); container.appendChild(root); return; }
    var grid = document.createElement("section"), left = document.createElement("section"), right = document.createElement("section");
    grid.className = "pt-ni-grid"; right.className = "pt-ni-stack";
    left.appendChild(section(defs[0][0], defs[0][1], openPreview, rerender, onDismiss, state, defs[0][2]));
    defs.slice(1).forEach(function (d) { var sec = section(d[0], d[1], openPreview, rerender, onDismiss, state, d[2]); if (sec) right.appendChild(sec); });
    grid.appendChild(left); if (right.childNodes.length) grid.appendChild(right); root.appendChild(grid); container.appendChild(root);
  }

  function renderAsset(container, all, state, symbol, openPreview, rerender, onDismiss, onManageHidden) {
    clear(container); host(container);
    var list = apply(all, state), root = document.createElement("section");
    state.unreadCount = list.filter(function (item) { return !item.read; }).length;
    root.className = "pt-ni-asset"; root.appendChild(toolbar(state, "symbol", rerender, onManageHidden));
    var flash = dismissNotice(state, rerender);
    if (flash) root.appendChild(flash);
    if (!list.length) { root.appendChild(emptyNote("No stories match the current time window.")); container.appendChild(root); return; }
    root.appendChild(feature(list[0], "Asset Focus | " + String(symbol || list[0].papertradeSymbol || "ASSET").toUpperCase(), openPreview, rerender, onDismiss));
    if (!state.expanded) {
      if (list.length > 1) root.appendChild(emptyNote((list.length - 1) + " more " + (list.length - 1 === 1 ? "story" : "stories") + ". Click Expand to view all."));
      container.appendChild(root);
      return;
    }
    if (list.length > 1) root.appendChild(section("Latest Coverage", list.slice(1), openPreview, rerender, onDismiss, state, "latest"));
    container.appendChild(root);
  }

  function renderLoading(container, portfolio) {
    clear(container); host(container);
    var root = document.createElement("section");
    root.className = portfolio ? "pt-ni-layout" : "pt-ni-asset";
    root.appendChild(loading(portfolio ? "Lead Story" : "Asset Focus", 1));
    if (portfolio) {
      var grid = document.createElement("section"), left = document.createElement("section"), right = document.createElement("section");
      grid.className = "pt-ni-grid"; right.className = "pt-ni-stack";
      left.appendChild(loading("Portfolio Impact", 2)); right.appendChild(loading("Market Update", 1)); right.appendChild(loading("Crypto Updates", 1));
      grid.appendChild(left); grid.appendChild(right); root.appendChild(grid);
    } else root.appendChild(loading("Latest Coverage", 2));
    container.appendChild(root);
  }

  function renderError(container, portfolio, text) {
    clear(container); host(container);
    var root = document.createElement("section");
    root.className = portfolio ? "pt-ni-layout" : "pt-ni-asset"; root.appendChild(emptyNote(text)); container.appendChild(root);
  }

  async function fetchJson(url) {
    var res = await fetch(url, { method: "GET", credentials: "same-origin", cache: "no-store" });
    var data = await res.json();
    if (!res.ok || !data || data.status !== "success") throw new Error(data && data.message ? data.message : "Unable to load news.");
    return data;
  }

  function symbolUrl(endpoint, symbol) {
    var url = new URL(endpoint, window.location.href);
    url.searchParams.set("scope", "symbol");
    url.searchParams.set("symbol", String(symbol || "").toUpperCase());
    return url.toString();
  }

  async function fetchPortfolio(endpoint, portfolioSymbols, watchlistSymbols, marketSymbol) {
    var all = [];
    var hold = uniq(portfolioSymbols), watch = uniq(watchlistSymbols), market = String(marketSymbol || "^GSPC").toUpperCase();
    var base = await fetchJson(new URL(endpoint, window.location.href).toString());
    all = all.concat(normalize(base.articles || [], "portfolio", hold));
    var watchOnly = watch.filter(function (s) { return hold.indexOf(s) < 0; });
    var watchResults = await Promise.allSettled(watchOnly.map(function (s) { return fetchJson(symbolUrl(endpoint, s)); }));
    watchResults.forEach(function (r, i) { if (r.status === "fulfilled") all = all.concat(normalize(r.value.articles || [], "watchlist", [watchOnly[i]])); });
    if (hold.indexOf(market) < 0 && watch.indexOf(market) < 0) {
      try { var broad = await fetchJson(symbolUrl(endpoint, market)); all = all.concat(normalize(broad.articles || [], "market", [market])); } catch (e) {}
    }
    return sortDedupe(all);
  }

  function createWidget(options) {
    if (!options || !options.containerId || !options.statusId || !options.endpoint) return null;
    ensureStyles();
    var container = document.getElementById(options.containerId), status = document.getElementById(options.statusId), pop = modal(), hidden = hiddenModal();
    if (!container || !status) return null;
    var state = { filter: "all", window: "7d", expanded: readBool(EXPANDED_KEY, false), unreadCount: 0, sectionCap: 3, sectionExpanded: {}, lastUpdated: null, articles: [], symbol: null, refreshHandle: null, ageHandle: null, dismissNotice: null, noticeHandle: null };

    function setStatus(text, err) { status.textContent = text; status.classList.remove("text-danger", "text-success"); status.classList.add(err ? "text-danger" : "text-muted"); }
    function tickStatus() { setStatus("Last Updated | " + freshness(state.lastUpdated), false); }
    function hiddenItems() {
      var ids = read(DISMISSED_KEY);
      var list = (state.articles || []).filter(function (item) { return ids.indexOf(item.id) >= 0; }).sort(function (a, b) { return b.publishedAtMs - a.publishedAtMs; });
      return { total: ids.length, visible: list };
    }
    function dismissItem(item) {
      if (!item || !item.id) return;
      setVal(DISMISSED_KEY, item.id, true);
      if (state.noticeHandle) clearTimeout(state.noticeHandle);
      state.dismissNotice = { id: item.id };
      state.noticeHandle = window.setTimeout(function () {
        state.noticeHandle = null;
        state.dismissNotice = null;
        rerender();
      }, 6000);
      rerender();
    }
    function openHidden() {
      var data = hiddenItems();
      clear(hidden.list);
      clear(hidden.actions);
      hidden.head.textContent = "Manage hidden stories";
      if (!data.total) {
        hidden.meta.textContent = "No hidden stories right now.";
        hidden.list.appendChild(emptyNote("Dismissed stories will appear here so you can restore them later."));
        hidden.wrap.classList.add("open");
        return;
      }
      hidden.meta.textContent = data.total + " hidden " + (data.total === 1 ? "story" : "stories") + " in this browser.";
      if (!data.visible.length) hidden.list.appendChild(emptyNote("Hidden stories from earlier sessions are not in the current feed. Use Restore all to clear them."));
      else {
        data.visible.forEach(function (item) {
          var row = document.createElement("div"), meta = document.createElement("p"), head = document.createElement("p"), actions = document.createElement("div");
          row.className = "pt-ni-hidden-item"; meta.className = "pt-ni-hidden-meta"; head.className = "pt-ni-hidden-head"; actions.className = "pt-ni-actions";
          meta.textContent = item.papertradeSymbol + " | " + item.category + " | " + item.timeAgo;
          head.textContent = item.headline;
          actions.appendChild(action("Restore", function () {
            setVal(DISMISSED_KEY, item.id, false);
            rerender();
            openHidden();
          }, false));
          row.appendChild(meta);
          row.appendChild(head);
          row.appendChild(actions);
          hidden.list.appendChild(row);
        });
      }
      hidden.actions.appendChild(action("Restore all", function () {
        clearKey(DISMISSED_KEY);
        if (state.noticeHandle) clearTimeout(state.noticeHandle);
        state.noticeHandle = null;
        state.dismissNotice = null;
        rerender();
        openHidden();
      }, false));
      hidden.wrap.classList.add("open");
    }
    function startTimers() {
      var every = Number(options.autoRefreshMs || 180000);
      if (state.ageHandle) clearInterval(state.ageHandle);
      state.ageHandle = window.setInterval(tickStatus, 30000);
      if (state.refreshHandle) clearInterval(state.refreshHandle);
      if (every > 0) state.refreshHandle = window.setInterval(function () { if (options.scope !== "symbol" || state.symbol) fetchAndRender(true); }, every);
    }

    function openPreview(item, rerender) {
      var wasUnread = !has(READ_KEY, item.id);
      if (wasUnread) {
        setVal(READ_KEY, item.id, true);
        item.read = true;
      }
      clear(pop.actions);
      pop.kicker.textContent = item.papertradeSymbol + " | " + item.category;
      pop.head.textContent = item.headline;
      pop.meta.textContent = [item.source, item.timeAgo, item.contextLabel].join(" | ");
      pop.copy.textContent = item.summary;
      if (item.sourceExtraCount > 0) pop.actions.appendChild(tag("+" + item.sourceExtraCount + " sources", "dupe"));
      if (item.trusted) pop.actions.appendChild(tag("Trusted source", "trust"));
      if (item.saved) pop.actions.appendChild(tag("Saved", "saved"));
      pop.actions.appendChild(tag(item.contextLabel, "ctx"));
      pop.actions.appendChild(action(item.saved ? "Unsave" : "Save", function () { item.saved = toggleVal(SAVED_KEY, item.id); rerender(); openPreview(item, rerender); }, false));
      pop.actions.appendChild(action("Dismiss", function () { pop.hide(); dismissItem(item); }, true));
      var link = document.createElement("a");
      link.className = "pt-ni-link"; link.href = item.url; link.target = "_blank"; link.rel = "noopener noreferrer"; link.textContent = "Open Full Article";
      pop.actions.appendChild(link);
      pop.wrap.classList.add("open");
      if (wasUnread) rerender();
    }

    function rerender() {
      if (options.scope === "portfolio") renderPortfolio(container, state.articles, state, openPreview, rerender, dismissItem, openHidden);
      else renderAsset(container, state.articles, state, state.symbol, openPreview, rerender, dismissItem, openHidden);
      tickStatus();
    }

    async function fetchAndRender(skipLoading) {
      if (!skipLoading) { renderLoading(container, options.scope === "portfolio"); setStatus("Last Updated | Pending", false); }
      try {
        if (options.scope === "portfolio") state.articles = await fetchPortfolio(options.endpoint, options.portfolioSymbols || [], options.watchlistSymbols || [], options.marketContextSymbol || "^GSPC");
        else {
          var payload = await fetchJson(symbolUrl(options.endpoint, state.symbol));
          state.articles = sortDedupe(normalize(payload.articles || [], "portfolio", [state.symbol || payload.symbol || ""]));
        }
        state.lastUpdated = new Date();
        rerender();
      } catch (e) {
        state.lastUpdated = null;
        renderError(container, options.scope === "portfolio", options.errorMessage || "We could not load the news feed right now.");
        setStatus("Last Updated | Pending", true);
      }
    }

    function load(symbol) {
      if (options.scope === "symbol") state.symbol = String(symbol || "").toUpperCase();
      if (options.scope === "symbol" && !state.symbol) {
        renderError(container, false, options.emptyMessage || "Select an asset to load news.");
        setStatus("Last Updated | Pending", false);
        return;
      }
      startTimers();
      fetchAndRender(false);
    }

    return { load: load };
  }

  window.PaperTradeNews = { createWidget: createWidget };
})(window, document);
