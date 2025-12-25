// background.js
chrome.action.onClicked.addListener(async (tab) => {
    try {
      if (!tab?.id) return;
  
      const url = tab.url || "";
      const is_sa =
        url.startsWith("https://seekingalpha.com/") ||
        url.startsWith("https://www.seekingalpha.com/");
  
      if (!is_sa) {
        console.error("[SA Exporter] Not a Seeking Alpha page:", url);
        return;
      }
  
      const injection_results = await chrome.scripting.executeScript({
        target: { tabId: tab.id }, // main frame
        func: () => {
          const clean = (s) => String(s ?? "").replace(/\s+/g, " ").trim();
  
          const extract_title = () =>
            clean(document.querySelector('h1[data-test-id="post-title"]')?.textContent) ||
            clean(document.querySelector("h1")?.textContent) ||
            clean(document.title.replace(/\s*\|\s*Seeking Alpha\s*$/i, "")) ||
            null;
  
          const extract_published_at = () => {
            const dt = document.querySelector("time[datetime]")?.getAttribute("datetime");
            if (dt) return dt;
  
            return (
              document.querySelector('meta[property="article:published_time"]')?.content ||
              document.querySelector('meta[name="article:published_time"]')?.content ||
              document.querySelector('meta[property="og:updated_time"]')?.content ||
              null
            );
          };
  
          const extract_tickers = () => {
            const tickers = new Set();

            // 1) Primary ticker in header
            document
              .querySelectorAll('[data-test-id="post-primary-tickers"] a[href*="/symbol/"]')
              .forEach((a) => {
                const m = (a.getAttribute("href") || "").match(/\/symbol\/([A-Z.\-]{1,12})/);
                if (m?.[1]) tickers.add(m[1]);
              });
          
            // 2) "About this article" block (mobile/desktop)
            // Look for the label "Ticker" and its adjacent symbol link(s)
            const about = Array.from(document.querySelectorAll("h4, div"))
              .find((el) => (el.textContent || "").trim() === "About this article")
              ?.closest("div");
          
            if (about) {
              about.querySelectorAll('a[href^="/symbol/"], a[href*="/symbol/"]').forEach((a) => {
                const m = (a.getAttribute("href") || "").match(/\/symbol\/([A-Z.\-]{1,12})/);
                if (m?.[1]) tickers.add(m[1]);
              });
            }
          
            // 3) Fallback: only symbols referenced inside the main article body
            if (tickers.size === 0) {
              const articleRoot =
                document.querySelector("div.R6FbO div.T2G6W") ||
                document.querySelector("div.T2G6W") ||
                document.querySelector("article");
          
              articleRoot?.querySelectorAll('a[href*="/symbol/"]').forEach((a) => {
                const m = (a.getAttribute("href") || "").match(/\/symbol\/([A-Z.\-]{1,12})/);
                if (m?.[1]) tickers.add(m[1]);
              });
            }
          
            return Array.from(tickers);
          };
  
          const extract_summary_bullets = () => {
            const items = Array.from(document.querySelectorAll('[data-test-id="article-summary-item"]'));
            const bullets = items.map((el) => clean(el.textContent)).filter(Boolean);
            return bullets.length ? bullets : null;
          };
  
          const extract_snippet = () => {
            const meta = document.querySelector('meta[name="description"]')?.content?.trim();
            if (meta) return meta;
  
            const container =
              document.querySelector('div.R6FbO div[data-test-id="content-container"]') ||
              document.querySelector('div[data-test-id="content-container"]') ||
              document.querySelector("article");
  
            return clean(container?.querySelector("p")?.textContent) || null;
          };
  
          const extract_article_text = () => {
            const root =
              document.querySelector("div.R6FbO div.T2G6W") ||
              document.querySelector("div.T2G6W") ||
              document.querySelector("article");
  
            if (!root) return null;
  
            const content =
              root.querySelector('div[data-test-id="content-container"]') ||
              root.querySelector('[data-test-id="content-container"]') ||
              root;
  
            const has_paywall_full = !!content.querySelector(".paywall-full-content");
  
            const stop_selectors = [
              "#a-disclosure",
              "#a-disclosure-more",
              '[data-test-id="author-root"]',
              '[data-test-id="post-footer"]',
              "#comments-card"
            ];
  
            const should_stop = (el) =>
              stop_selectors.some((sel) => el.matches?.(sel) || el.closest?.(sel));
  
            const blocks = [];
            const nodes = content.querySelectorAll("h2, h3, p, li");
  
            for (const el of nodes) {
              if (should_stop(el)) break;
  
              const t = clean(el.textContent);
              if (!t) continue;
  
              if (t.startsWith("Analyst’s Disclosure:") || t.startsWith("Analyst's Disclosure:")) break;
              if (t.startsWith("Seeking Alpha's Disclosure:")) break;
              if (t === "Summary") continue;
  
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
            return joined.length >= 200 ? joined : null;
          };
  
          return {
            source: "seeking_alpha",
            url: location.href.split("?")[0],
            captured_at: new Date().toISOString(),
            title: extract_title(),
            published_at: extract_published_at(),
            tickers: extract_tickers(),
            snippet: extract_snippet(),
            summary_bullets: extract_summary_bullets(),
            article_text: extract_article_text()
          };
        }
      });
  
      const first = injection_results?.[0];
  
      // IMPORTANT: show real errors (not [object Object])
      if (!first || first.result == null) {
        console.error("[SA Exporter] executeScript returned no result.");
        console.error("[SA Exporter] injection_results:", injection_results);
        if (first?.error) console.error("[SA Exporter] executeScript error:", first.error);
        return;
      }
  
      const data = first.result;
  
      const json = JSON.stringify(data, null, 2);
      const data_url = "data:application/json;charset=utf-8," + encodeURIComponent(json);
  
      await chrome.downloads.download({
        url: data_url,
        filename: `seeking_alpha_export_${Date.now()}.json`,
        saveAs: true
      });
    } catch (e) {
      console.error("[SA Exporter] Export failed:", e?.message || e, e);
    }
  });
  