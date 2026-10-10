/* Von Burg bestätigte Ticketdaten. Kontingent ist keine automatisch ermittelte Restmenge. */
window.HARDCORE_HILLS_CONFIG = Object.freeze({
  TICKET_PREIS: 35.00, // Euro pro Ticket, inkl. MwSt.
  VERSANDKOSTEN: 3.00, // Euro pro Bestellung, NICHT pro Ticket
  TICKET_BESTAND: 10, // Burgs Kontingent, keine automatische Verfügbarkeitsanzeige
  VERANSTALTUNGSADRESSE: null,
  VERANSTALTER: "Hardcore Hills Festival",
  ZAHLUNGSART: "PayPal", // Erst nach Burgs persönlicher Verfügbarkeitsbestätigung
  PAYPAL_LINK: "https://www.paypal.com/paypalme/RandaleFUNK", // Für Burgs persönliche Zahlungsinfo; kein Sofortzahlungs-Link auf der Ticketseite
  ANFRAGE_MAIL: "post@randalefunk.de",
  ANFRAGEN_AKTIV: true, // Von Burg mit FREIGABE für die Veröffentlichung aktiviert
  MAX_TICKETS_PRO_ANFRAGE: 20 // Eingabelimit gegen Fehlbedienung, KEIN Ticketbestand
});
