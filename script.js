const yearElement = document.querySelector("#current-year");
const randalfTextElement = document.querySelector("[data-randalf-spruch]");
const magazineShell = document.querySelector(".magazine-shell");
const mobileMenuToggle = document.querySelector("[data-mobile-menu-toggle]");

if (yearElement) {
  yearElement.textContent = String(new Date().getFullYear());
}

const sectionLinks = document.querySelectorAll("[data-section]");
const contentPanels = document.querySelectorAll("[data-section-panel]");
const validSections = ["news", "vorab", "reviews", "interviews", "kolumnen", "sonstiges"];
const tickerList = document.querySelector("[data-ticker-list]");
const appModal = document.querySelector("[data-app-modal]");
const appFrame = document.querySelector("[data-app-frame]");
const appOpenButtons = document.querySelectorAll("[data-open-app]");
const appCloseButtons = document.querySelectorAll("[data-close-app]");
const youtubeVideoCards = document.querySelectorAll("[data-youtube-video]");
const statsEndpoint = "/track.php";
const pollEndpoint = "/poll.php";
const fallbackRandalfSprueche = [
  "Das wird garantiert schiefgehen.",
  "Ich habe Fragen. Leider auch Antworten.",
  "Die Idee ist gut. Deshalb wird sie niemand umsetzen.",
  "Punk ist wie Satire. Nur mit anderen Mitteln.",
  "Man kann alles diskutieren. Außer die Getränkepreise.",
  "Das klingt nach Arbeit.",
  "Das klingt nach einer hervorragenden schlechten Idee.",
  "Punk kann man nicht kaufen. Merch schon.",
  "Die Revolution beginnt nach dem Soundcheck.",
  "Soundcheck ist der wahre Headliner.",
  "Festivalwetter ist eine Lebenseinstellung.",
  "Laut ist kein Genre.",
  "Mikrofon an. Gehirn hoffentlich auch.",
  "Ich hatte einen Plan. Dann kam Realität dazwischen.",
  "DIY bleibt DIY.",
  "Der Krach sortiert sich.",
  "Heute keine Weisheit. Ausverkauft.",
  "Wahrscheinlich hätte man vorher messen sollen."
];
let randalfSprueche = [...fallbackRandalfSprueche];
let lastRandalfSpruch = randalfTextElement?.textContent.trim() || "";

if (magazineShell && mobileMenuToggle) {
  const mobileMenuQuery = window.matchMedia("(max-width: 720px)");

  function syncMobileMenuState() {
    if (mobileMenuQuery.matches) {
      mobileMenuToggle.hidden = false;
      magazineShell.classList.add("has-mobile-menu");
      return;
    }

    mobileMenuToggle.hidden = true;
    mobileMenuToggle.setAttribute("aria-expanded", "false");
    magazineShell.classList.remove("has-mobile-menu", "is-mobile-menu-open");
  }

  magazineShell.classList.add("has-mobile-menu");
  syncMobileMenuState();
  mobileMenuQuery.addEventListener("change", syncMobileMenuState);

  mobileMenuToggle.addEventListener("click", () => {
    const isOpen = magazineShell.classList.toggle("is-mobile-menu-open");
    mobileMenuToggle.setAttribute("aria-expanded", String(isOpen));
  });

  sectionLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (window.matchMedia("(max-width: 720px)").matches) {
        magazineShell.classList.remove("is-mobile-menu-open");
        mobileMenuToggle.setAttribute("aria-expanded", "false");
      }
    });
  });
}

function getStatsPath(section = "") {
  const path = `${window.location.pathname}${window.location.hash}`;

  if (section && window.location.pathname.endsWith("/")) {
    return `${window.location.pathname}#${section}`;
  }

  return path || "/";
}

function getStatsSection() {
  const activeSection = document.body.dataset.activeSection || "";

  if (validSections.includes(activeSection)) {
    return activeSection;
  }

  const path = window.location.pathname;

  if (path.includes("/vorab-gehoert/")) {
    return "vorab";
  }

  if (path.includes("/reviews/")) {
    return "reviews";
  }

  if (path.includes("/randalf/")) {
    return "randalf";
  }

  if (path.includes("/wuerfel/")) {
    return "wuerfel";
  }

  return "news";
}

function sendStatsEvent(eventType, detail = {}) {
  if (window.location.protocol === "file:") {
    return;
  }

  const payload = {
    event_type: eventType,
    path: detail.path || getStatsPath(detail.section),
    section: detail.section || getStatsSection()
  };

  const body = JSON.stringify(payload);

  if (navigator.sendBeacon) {
    navigator.sendBeacon(statsEndpoint, new Blob([body], { type: "application/json" }));
    return;
  }

  fetch(statsEndpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body,
    keepalive: true
  }).catch(() => {});
}

function attachPollHandlers(pollMount) {
  const form = pollMount.querySelector("[data-poll-form]");
  const resultsButton = pollMount.querySelector("[data-poll-results]");
  const endpoint = pollMount.dataset.pollEndpoint || pollEndpoint;

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const submitButton = form.querySelector("button[type='submit']");
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Zaehle...";
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        body: new FormData(form),
        credentials: "same-origin"
      });

      if (!response.ok) {
        throw new Error("Poll request failed");
      }

      pollMount.innerHTML = await response.text();
      attachPollHandlers(pollMount);
    } catch {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Abstimmen";
      }
    }
  });

  resultsButton?.addEventListener("click", async () => {
    resultsButton.disabled = true;

    try {
      const separator = endpoint.includes("?") ? "&" : "?";
      const response = await fetch(`${endpoint}${separator}action=results`, {
        credentials: "same-origin"
      });

      if (!response.ok) {
        throw new Error("Poll results failed");
      }

      pollMount.innerHTML = await response.text();
      attachPollHandlers(pollMount);
    } catch {
      resultsButton.disabled = false;
    }
  });
}

async function loadInlinePollWidgets() {
  const pollMounts = document.querySelectorAll("[data-poll-mount][data-poll-endpoint]");

  if (window.location.protocol === "file:") {
    return;
  }

  for (const pollMount of pollMounts) {
    try {
      const response = await fetch(pollMount.dataset.pollEndpoint, {
        credentials: "same-origin"
      });

      if (!response.ok) {
        throw new Error("Inline poll widget failed");
      }

      pollMount.innerHTML = await response.text();
      attachPollHandlers(pollMount);
    } catch {
      pollMount.remove();
    }
  }
}

async function loadPollWidget() {
  const sectionNav = document.querySelector(".section-nav");

  if (!sectionNav || window.location.protocol === "file:") {
    return;
  }

  const pollMount = document.createElement("div");
  pollMount.className = "poll-mount";
  pollMount.setAttribute("data-poll-mount", "");
  sectionNav.appendChild(pollMount);

  try {
    const response = await fetch(pollEndpoint, {
      credentials: "same-origin"
    });

    if (!response.ok) {
      throw new Error("Poll widget failed");
    }

    pollMount.innerHTML = await response.text();
    attachPollHandlers(pollMount);
  } catch {
    pollMount.remove();
  }
}

function sortTickerNews() {
  if (!tickerList) {
    return;
  }

  const newsCards = [...tickerList.querySelectorAll("[data-ticker-news]")];
  const staticCards = [...tickerList.querySelectorAll("[data-ticker-static]")];

  newsCards
    .sort((firstCard, secondCard) => {
      const firstDate = Date.parse(firstCard.dataset.published || "");
      const secondDate = Date.parse(secondCard.dataset.published || "");

      return (Number.isNaN(secondDate) ? 0 : secondDate) - (Number.isNaN(firstDate) ? 0 : firstDate);
    })
    .forEach((card) => tickerList.appendChild(card));

  staticCards.forEach((card) => tickerList.appendChild(card));
}

function setActiveSection(section) {
  document.body.dataset.activeSection = section;

  sectionLinks.forEach((link) => {
    const isActive = link.dataset.section === section;
    link.classList.toggle("is-active", isActive);
    link.setAttribute("aria-current", isActive ? "page" : "false");
  });

  contentPanels.forEach((panel) => {
    panel.classList.toggle("is-active", panel.dataset.sectionPanel === section);
  });

  showRandomRandalfSpruch();
}

sectionLinks.forEach((link) => {
  link.addEventListener("click", () => {
    setActiveSection(link.dataset.section);
    sendStatsEvent("pageview", {
      path: `${window.location.pathname}#${link.dataset.section}`,
      section: link.dataset.section
    });
  });
});

const initialSection = document.body.dataset.activeSection || "news";
const hashSection = window.location.hash.replace("#", "");

sortTickerNews();
setActiveSection(validSections.includes(hashSection) ? hashSection : initialSection);
sendStatsEvent("pageview", {
  path: getStatsPath(validSections.includes(hashSection) ? hashSection : initialSection),
  section: getStatsSection()
});

function pickRandomSpruch() {
  if (randalfSprueche.length === 0) {
    return "";
  }

  if (randalfSprueche.length === 1) {
    return randalfSprueche[0];
  }

  let nextSpruch = lastRandalfSpruch;

  while (nextSpruch === lastRandalfSpruch) {
    const randomIndex = Math.floor(Math.random() * randalfSprueche.length);
    nextSpruch = randalfSprueche[randomIndex];
  }

  return nextSpruch;
}

function showRandomRandalfSpruch() {
  if (!randalfTextElement || randalfSprueche.length === 0) {
    return;
  }

  const nextSpruch = pickRandomSpruch();

  if (!nextSpruch) {
    return;
  }

  randalfTextElement.textContent = nextSpruch;
  lastRandalfSpruch = nextSpruch;
}

async function loadRandalfSprueche() {
  if (!randalfTextElement) {
    return;
  }

  try {
    const response = await fetch("data/randalf-sprueche.json");

    if (!response.ok) {
      showRandomRandalfSpruch();
      return;
    }

    const data = await response.json();
    const loadedSprueche = data.filter((spruch) => typeof spruch === "string" && spruch.trim().length > 0);

    if (loadedSprueche.length > 0) {
      randalfSprueche = loadedSprueche;
    }

    showRandomRandalfSpruch();
  } catch {
    showRandomRandalfSpruch();
  }
}

document.addEventListener("click", (event) => {
  if (event.target.closest("[data-section]")) {
    return;
  }

  showRandomRandalfSpruch();
});

loadRandalfSprueche();
loadPollWidget();
loadInlinePollWidgets();

function openAppModal() {
  if (!appModal || !appFrame) {
    return;
  }

  if (!appFrame.getAttribute("src")) {
    appFrame.setAttribute("src", appFrame.dataset.appSrc || "wuerfel/");
  }

  appModal.hidden = false;
  document.body.classList.add("is-app-modal-open");
  appModal.querySelector(".app-modal__close")?.focus();
}

function closeAppModal() {
  if (!appModal) {
    return;
  }

  appModal.hidden = true;
  document.body.classList.remove("is-app-modal-open");
}

function getYouTubeVideoId(value = "") {
  const trimmedValue = value.trim();

  if (!trimmedValue) {
    return "";
  }

  if (/^[a-zA-Z0-9_-]{11}$/.test(trimmedValue)) {
    return trimmedValue;
  }

  try {
    const url = new URL(trimmedValue);

    if (url.hostname.includes("youtu.be")) {
      return url.pathname.replace("/", "").slice(0, 11);
    }

    if (url.searchParams.has("v")) {
      return url.searchParams.get("v")?.slice(0, 11) || "";
    }

    const embedMatch = url.pathname.match(/\/embed\/([a-zA-Z0-9_-]{11})/);

    return embedMatch?.[1] || "";
  } catch {
    return "";
  }
}

appOpenButtons.forEach((button) => {
  button.addEventListener("click", () => {
    sendStatsEvent("wuerfel_click", {
      path: "/wuerfel/",
      section: "wuerfel"
    });
    openAppModal();
  });
});

appCloseButtons.forEach((button) => {
  button.addEventListener("click", closeAppModal);
});

youtubeVideoCards.forEach((card) => {
  const loadButton = card.querySelector("[data-youtube-load]");
  const frameTarget = card.querySelector("[data-youtube-frame]");
  const videoId = getYouTubeVideoId(card.dataset.youtubeId);
  const videoTitle = card.dataset.youtubeTitle || "YouTube-Video";

  loadButton?.addEventListener("click", () => {
    if (!frameTarget || !videoId) {
      if (loadButton) {
        loadButton.textContent = "YouTube-Link fehlt noch";
        loadButton.disabled = true;
      }

      return;
    }

    const iframe = document.createElement("iframe");
    iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?rel=0`;
    iframe.title = videoTitle;
    iframe.loading = "lazy";
    iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";
    iframe.allowFullscreen = true;

    frameTarget.replaceChildren(iframe);
  });
});

document.querySelectorAll('a[href*="ko-fi.com/randalefunk"]').forEach((link) => {
  link.addEventListener("click", () => {
    sendStatsEvent("kofi_click", {
      path: link.getAttribute("href") || "https://ko-fi.com/randalefunk",
      section: getStatsSection()
    });
  });
});

document.querySelectorAll('a[href$="warum-unterstuetzen.html"], a[href*="/warum-unterstuetzen.html"]').forEach((link) => {
  link.addEventListener("click", () => {
    sendStatsEvent("support_click", {
      path: "/warum-unterstuetzen.html",
      section: "sonstiges"
    });
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && appModal && !appModal.hidden) {
    closeAppModal();
  }
});
