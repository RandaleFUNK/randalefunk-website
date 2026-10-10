/* Kein fetch, keine Datenbank, kein localStorage: personenbezogene Daten bleiben
   bis zur bewussten Übergabe ans Mailprogramm ausschließlich im Formular. */
(() => {
  "use strict";
  const config = window.HARDCORE_HILLS_CONFIG;
  const form = document.querySelector("#ticket-form");
  if (!config || !form) return;
  const draft = document.querySelector("#ticket-draft");
  const draftText = document.querySelector("#ticket-draft-text");
  const mailLink = document.querySelector("#ticket-mail-link");
  const result = document.querySelector("#ticket-result");
  const fields = form.elements;
  const countInput = fields.namedItem("count");
  const emailInput = fields.namedItem("email");
  const max = Number.isSafeInteger(config.MAX_TICKETS_PRO_ANFRAGE) && config.MAX_TICKETS_PRO_ANFRAGE > 0 ? config.MAX_TICKETS_PRO_ANFRAGE : 20;
  const money = (value) => typeof value === "number" && Number.isFinite(value) && value >= 0
    ? new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(value) : null;
  if (money(config.TICKET_PREIS) !== null) document.querySelector("[data-ticket-price]").textContent = `${money(config.TICKET_PREIS)} pro Ticket inkl. MwSt.`;
  if (money(config.VERSANDKOSTEN) !== null) document.querySelector("[data-ticket-shipping]").textContent = `${money(config.VERSANDKOSTEN)} pro Bestellung, nicht pro Ticket.`;
  document.querySelector("[data-ticket-stock]").textContent = Number.isSafeInteger(config.TICKET_BESTAND) && config.TICKET_BESTAND >= 0
    ? `Aktuell stehen RandaleFUNK ${config.TICKET_BESTAND} Tickets zur Verfügung. Das ist unser Kontingent, keine laufend aktualisierte Restmenge.` : "Die aktuelle Verfügbarkeit bestätigt Burg persönlich.";
  document.querySelector("[data-ticket-organizer]").textContent = config.VERANSTALTER;
  document.querySelector("[data-ticket-payment]").textContent = config.ZAHLUNGSART;
  if (config.VERANSTALTUNGSADRESSE) document.querySelector("[data-ticket-address]").textContent = config.VERANSTALTUNGSADRESSE;
  document.querySelector("[data-ticket-preview]").hidden = config.ANFRAGEN_AKTIV === true;
  document.querySelector("[data-ticket-recipient]").textContent = config.ANFRAGE_MAIL;
  countInput.max = String(max);
  document.querySelector("#ticket-count-help").textContent = `1 bis ${max} pro Anfrage. Das ist ein Eingabelimit, keine Auskunft über verfügbare Karten. Für größere Gruppen schreibt Burg direkt.`;
  document.querySelector("[data-ticket-fields]").disabled = false;

  function clearDraft() {
    draft.hidden = true;
    draftText.value = "";
    mailLink.hidden = true;
    mailLink.removeAttribute("href");
    result.textContent = "";
  }
  form.addEventListener("input", () => {
    clearDraft(); // Nach Änderungen keinen alten Entwurf oder falsche Anzahl verschicken.
    for (const input of [fields.namedItem("name"), emailInput, countInput]) input.setCustomValidity("");
  });
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const name = fields.namedItem("name");
    const count = countInput.valueAsNumber;
    name.setCustomValidity(name.value.trim() ? "" : "Bitte euren Namen eintragen.");
    emailInput.setCustomValidity(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim()) ? "" : "Bitte eine vollständige E-Mail-Adresse eintragen.");
    countInput.setCustomValidity(Number.isSafeInteger(count) && count >= 1 && count <= max ? "" : `Bitte eine ganze Anzahl zwischen 1 und ${max} eintragen.`);
    if (!form.reportValidity()) return;
    const message = fields.namedItem("message").value.trim();
    const body = [
      "Hallo Burg,", "", "ich möchte Tickets für die Hardcore Hills Gartenparty am 10.04.2027 in Menden / Asbeck anfragen.", "",
      `Name: ${name.value.trim()}`, `E-Mail: ${emailInput.value.trim()}`, `Gewünschte Anzahl: ${count}`,
      "", "Meine Versandanschrift reiche ich nach deiner Verfügbarkeitsbestätigung per E-Mail nach.",
      "", "Nachricht:", message || "Keine weitere Nachricht.", "",
      `Ticketpreis: ${money(config.TICKET_PREIS)} pro Ticket inkl. MwSt.; Versand: ${money(config.VERSANDKOSTEN)} pro Bestellung, nicht pro Ticket.`,
      "Bitte bestätige persönlich die Verfügbarkeit und schicke mir danach den Gesamtbetrag und die Zahlungsinfo für PayPal. Vorher schicke ich kein Geld.",
      "Das ist eine unverbindliche Anfrage, kein Kauf und keine bestätigte Reservierung."
    ].join("\n");
    draftText.value = body;
    draft.hidden = false;
    const recipientValid = /^[^\s@?&#]+@[^\s@?&#]+\.[^\s@?&#]+$/.test(config.ANFRAGE_MAIL);
    if (config.ANFRAGEN_AKTIV === true && recipientValid) {
      const href = `mailto:${config.ANFRAGE_MAIL}?subject=${encodeURIComponent("Ticketanfrage: Hardcore Hills Gartenparty 10.04.2027")}&body=${encodeURIComponent(body)}`;
      // Lange mailto-URLs werden nicht von allen Mailprogrammen unterstützt.
      mailLink.hidden = href.length > 1800;
      if (!mailLink.hidden) mailLink.href = href;
      result.textContent = "Entwurf vorbereitet. Noch nichts gesendet oder reserviert. Sendet die Anfrage selbst im Mailprogramm; Burg bestätigt anschließend persönlich Verfügbarkeit und Zahlungsinformationen.";
      if (mailLink.hidden) result.textContent += " Der Text ist für manche Mailprogramme zu lang. Bitte kopiert ihn in eure E-Mail.";
    } else {
      result.textContent = "Arbeitsversion: Entwurf vorbereitet. Nichts gesendet, nichts reserviert. Der Übergang ins Mailprogramm ist in dieser Vorschau ausgeschaltet.";
    }
    draft.focus();
  });
  document.querySelector("#ticket-copy").addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(draftText.value);
      result.textContent = "Entwurf kopiert. Noch nichts gesendet oder reserviert.";
    } catch {
      draftText.focus();
      draftText.select();
      result.textContent = "Bitte den markierten Entwurf selbst kopieren. Noch nichts gesendet oder reserviert.";
    }
  });
  document.querySelector("#ticket-clear").addEventListener("click", () => {
    form.reset();
    clearDraft();
    fields.namedItem("name").focus();
  });
  // Browser können Formularwerte beim Zurücknavigieren wiederherstellen.
  window.addEventListener("pagehide", () => { form.reset(); clearDraft(); });
})();
