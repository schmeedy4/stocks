// content.js
function text(el) {
    return el?.textContent?.replace(/\s+/g, " ").trim() || null;
  }
  
  function attr(el, name) {
    return el?.getAttribute?.(name) || null;
  }
  
  function extract_title() {
    return (
      text(document.querySelector('h1[data-test-id="post-title"]')) ||
      text(document.querySelector("h1")) ||
      document.title?.replace(/\s*\|\s*Seeking Alpha\s*$/i, "").trim() ||
      null
    );
  }
  
  function extract_published_at() {
    // Prefer a machine-readable time tag if present
    const timeEl = document.querySelector("time[datetime]");
    const dt = attr(timeEl, "datetime");
    if (dt) return dt;
  
    // Meta fallbacks
    const meta =
      document.querySelector('meta[property="article:published_time"]')?.content ||
      document.querySelector('meta[name="article:published_time"]')?.content ||
      document.querySelector('meta[property="og:updated_time"]')?.content;
  
    return meta || null;
  }
  
  function extract_tickers() {
    const tickers = new Set();
  
    // /symbol/XXX links
    document.querySelectorAll('a[href*="/symbol/"]').forEach((a) => {
      const href = attr(a, "href");
      const m = href && href.match(/\/symbol\/([A-Z.\-]{1,12})/);
      if (m?.[1]) tickers.add(m[1]);
    });
  
    // meta keywords sometimes contains tickers
    const keywords = document.querySelector('meta[name="keywords"]')?.content;
    if (keywords) {
      keywords
        .split(",")
        .map((s) => s.trim())
        .forEach((k) => {
          if (/^[A-Z.\-]{1,8}$/.test(k)) tickers.add(k);
        });
    }
  
    return Array.from(tickers);
  }
  
  function extract_snippet() {
    const metaDesc = document.querySelector('meta[name="description"]')?.content?.trim();
    if (metaDesc) return metaDesc;
  
    // First non-empty paragraph in content container
    const container =
      document.querySelector('div.R6FbO div[data-test-id="content-container"]') ||
      document.querySelector('div[data-test-id="content-container"]') ||
      document.querySelector("article");
  
    const p = container?.querySelector("p");
    return text(p);
  }
  
  function extract_summary_bullets() {
    const items = Array.from(document.querySelectorAll('[data-test-id="article-summary-item"]'));
    const bullets = items
      .map((el) => el.textContent?.replace(/\s+/g, " ").trim())
      .filter(Boolean);
    return bullets.length ? bullets : null;
  }
  
  // SA-specific: pull main body text and stop before disclosures / comments
  function extract_article_text() {
    const root =
      document.querySelector("div.R6FbO div.T2G6W") ||
      document.querySelector("div.T2G6W") ||
      document.querySelector("article");
  
    if (!root) return null;
  
    const content =
      root.querySelector('div[data-test-id="content-container"]') ||
      root.querySelector('[data-test-id="content-container"]') ||
      root;
  
    const stop_selectors = [
      "#a-disclosure",
      "#a-disclosure-more",
      '[data-test-id="author-root"]',
      '[data-test-id="post-footer"]',
      "#comments-card"
    ];
  
    const should_stop = (el) =>
      stop_selectors.some((sel) => el.matches?.(sel) || el.closest?.(sel));
  
    const has_paywall_full = !!content.querySelector(".paywall-full-content");
  
    const blocks = [];
    const nodes = content.querySelectorAll("h2, h3, p, li");
  
    for (const el of nodes) {
      if (should_stop(el)) break;
  
      const t = el.textContent?.replace(/\s+/g, " ").trim();
      if (!t) continue;
  
      // Hard stops
      if (t.startsWith("Analyst’s Disclosure:") || t.startsWith("Analyst's Disclosure:")) break;
      if (t.startsWith("Seeking Alpha's Disclosure:")) break;
  
      // Skip empty / junky labels
      if (t === "Summary") continue;
  
      // If full content exists, prefer it for paragraphs/lists.
      // (Keep headings even without paywall class so structure remains.)
      if (
        has_paywall_full &&
        !el.classList.contains("paywall-full-content") &&
        el.tagName !== "H2" &&
        el.tagName !== "H3"
      ) {
        continue;
      }
  
      blocks.push(t);
    }
  
    const joined = blocks.join("\n\n");
    if (joined.length < 200) return null;
  
    return joined;
  }
  
  function build_export() {
    const url = location.href.split("?")[0];
  
    return {
      source: "seeking_alpha",
      url,
      captured_at: new Date().toISOString(),
      title: extract_title(),
      published_at: extract_published_at(),
      tickers: extract_tickers(),
      snippet: extract_snippet(),
      summary_bullets: extract_summary_bullets(),
      article_text: extract_article_text()
    };
  }
  
  chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
    if (msg?.type !== "EXPORT_SA") return;
  
    try {
      console.log("[SA Exporter] EXPORT_SA request", location.href);
      const data = build_export();
      sendResponse({ ok: true, data });
    } catch (e) {
      sendResponse({ ok: false, error: String(e) });
    }
  });
  