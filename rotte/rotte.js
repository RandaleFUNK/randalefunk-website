const rotteLightbox = document.querySelector("[data-rotte-lightbox]");
const rotteLightboxImage = document.querySelector("[data-rotte-lightbox-image]");
const rotteCards = document.querySelectorAll("[data-rotte-card]");
const rotteCloseButtons = document.querySelectorAll("[data-close-rotte-lightbox]");
let previouslyFocusedCard = null;

function closeRotteLightbox() {
  if (!rotteLightbox || rotteLightbox.hidden) {
    return;
  }

  rotteLightbox.hidden = true;
  document.body.classList.remove("rotte-lightbox-open");
  rotteLightboxImage.removeAttribute("src");
  rotteLightboxImage.alt = "";
  previouslyFocusedCard?.focus();
}

function openRotteLightbox(card) {
  if (!rotteLightbox || !rotteLightboxImage) {
    return;
  }

  previouslyFocusedCard = card;
  rotteLightboxImage.src = card.dataset.cardSrc || "";
  rotteLightboxImage.alt = `Sammelkarte von ${card.dataset.cardName || "RandaleROTTE"}`;
  rotteLightbox.hidden = false;
  document.body.classList.add("rotte-lightbox-open");
  rotteLightbox.querySelector(".rotte-lightbox__close")?.focus();
}

rotteCards.forEach((card) => {
  card.addEventListener("click", () => openRotteLightbox(card));
});

rotteCloseButtons.forEach((button) => {
  button.addEventListener("click", closeRotteLightbox);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeRotteLightbox();
  }
});
