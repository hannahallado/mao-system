// Log out when the system's tabs are closed.
//
// Browsers don't tell the server when a tab closes, and the login cookie is
// shared by every tab, so this is tracked in the browser instead:
//   - Each open system tab keeps an entry in localStorage (shared by all tabs)
//     and removes it when the tab closes or navigates away.
//   - sessionStorage (one per tab) remembers that this tab is already part of
//     the login, so moving between pages / reloading in the same tab is fine.
//   - A page opened in a tab that isn't part of the login, while no other
//     system tab is open, means every tab was closed: log out.
// Opening a record in a new tab still works, because another tab is open.
// Closing the whole browser logs out via the session cookie (see index.php).
(function () {
  const REGISTRY_KEY = "mao_open_tabs";
  const TAB_KEY = "mao_tab_id";
  const HEARTBEAT_MS = 30 * 1000;
  // Hidden tabs can be throttled to about one timer per minute, so allow
  // a few missed heartbeats before treating an entry as a dead tab
  const STALE_MS = 3 * 60 * 1000;

  try {
    localStorage.setItem("__mao_test", "1");
    localStorage.removeItem("__mao_test");
    sessionStorage.setItem("__mao_test", "1");
    sessionStorage.removeItem("__mao_test");
  } catch (e) {
    return; // storage blocked: fall back to logging out on browser close only
  }

  function readRegistry() {
    try {
      return JSON.parse(localStorage.getItem(REGISTRY_KEY)) || {};
    } catch (e) {
      return {};
    }
  }

  function writeRegistry(registry) {
    localStorage.setItem(REGISTRY_KEY, JSON.stringify(registry));
  }

  function liveTabs() {
    const registry = readRegistry();
    const now = Date.now();
    Object.keys(registry).forEach((id) => {
      if (now - registry[id] > STALE_MS) delete registry[id];
    });
    writeRegistry(registry);
    return registry;
  }

  function register() {
    const registry = liveTabs();
    registry[tabId] = Date.now();
    writeRegistry(registry);
  }

  function unregister() {
    const registry = readRegistry();
    delete registry[tabId];
    writeRegistry(registry);
  }

  function logout() {
    // Hide the page so its content doesn't flash before the redirect
    document.documentElement.style.visibility = "hidden";
    const form = document.createElement("form");
    form.method = "post";
    form.action = document.body.dataset.logoutUrl;
    const token = document.createElement("input");
    token.type = "hidden";
    token.name = "_csrf";
    token.value = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
  }

  let tabId = sessionStorage.getItem(TAB_KEY);
  const freshLogin = document.body.dataset.freshLogin === "1";

  if (!tabId) {
    const otherTabsOpen = Object.keys(liveTabs()).length > 0;
    if (!freshLogin && !otherTabsOpen) {
      logout();
      return;
    }
    tabId = Date.now().toString(36) + Math.random().toString(36).slice(2);
    sessionStorage.setItem(TAB_KEY, tabId);
  }

  register();
  setInterval(register, HEARTBEAT_MS);

  // Fires on tab close, reload and navigation; the next page in this tab
  // registers again straight away
  window.addEventListener("pagehide", unregister);
  // Back/forward cache: the page comes back without reloading
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) register();
  });
})();
