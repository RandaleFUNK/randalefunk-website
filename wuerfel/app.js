const screens = {
  start: document.querySelector("#startScreen"),
  game: document.querySelector("#gameScreen"),
  gameOver: document.querySelector("#gameOverScreen"),
  quizWin: document.querySelector("#quizWinScreen"),
};

const els = {
  startButton: document.querySelector("#startButton"),
  restartButton: document.querySelector("#restartButton"),
  rollButton: document.querySelector("#rollButton"),
  playerNameInput: document.querySelector("#playerNameInput"),
  introAudio: document.querySelector("#introAudio"),
  rollVideo: document.querySelector("#rollVideo"),
  drinkVideo: document.querySelector("#drinkVideo"),
  videoStage: document.querySelector("#videoStage"),
  diceOverlay: document.querySelector(".dice-overlay"),
  roundDisplay: document.querySelector("#roundDisplay"),
  randaleMeter: document.querySelector("#randaleMeter"),
  randaleRounds: document.querySelector("#randaleRounds"),
  commentBox: document.querySelector("#commentBox"),
  playerDieOne: document.querySelector("#playerDieOne"),
  playerDieTwo: document.querySelector("#playerDieTwo"),
  burgDieOne: document.querySelector("#burgDieOne"),
  burgDieTwo: document.querySelector("#burgDieTwo"),
  playerSum: document.querySelector("#playerSum"),
  burgSum: document.querySelector("#burgSum"),
  playerDiceLabel: document.querySelector("#playerDiceLabel"),
  playerStatLabel: document.querySelector("#playerStatLabel"),
  resultTitle: document.querySelector("#resultTitle"),
  resultDetail: document.querySelector("#resultDetail"),
  quizPanel: document.querySelector("#quizPanel"),
  quizQuestion: document.querySelector("#quizQuestion"),
  quizOptions: document.querySelector("#quizOptions"),
  playerSips: document.querySelector("#playerSips"),
  burgSips: document.querySelector("#burgSips"),
  quizCount: document.querySelector("#quizCount"),
  randaleCount: document.querySelector("#randaleCount"),
  roundLog: document.querySelector("#roundLog"),
  finalStats: document.querySelector("#finalStats"),
  gameOverReason: document.querySelector("#gameOverReason"),
  gameOverQuote: document.querySelector("#gameOverQuote"),
  quizWinVideo: document.querySelector("#quizWinVideo"),
  quizWinStats: document.querySelector("#quizWinStats"),
  quizWinText: document.querySelector("#quizWinText"),
  quizWinRestartButton: document.querySelector("#quizWinRestartButton"),
};

const quotes = {
  burgLoses: [
    "Beschissene Würfel.",
    "Das war manipuliert.",
    "Ich fordere eine Nachzählung.",
    "Das zählt nicht.",
    "Ich war abgelenkt.",
    "Ich hasse dieses Spiel.",
    "Das Bier ist schuld.",
    "Die Würfel haben was gegen mich.",
    "Das ist statistisch unmöglich.",
    "Ich protestiere offiziell.",
    "Glück. Mehr war das nicht.",
    "Ich werde alt.",
    "Früher hätte ich gewonnen.",
    "Freu dich nicht zu früh.",
    "Warte ab, nächste Runde zerleg ich dich.",
    "Du Pisser!",
    "Ok. Das war peinlich.",
    "Das bleibt unter uns.",
  ],
  playerLoses: [
    "Tja.",
    "Anfänger.",
    "Das Glas leert sich nicht von allein.",
    "Los. Nicht schüchtern.",
    "Prost Mahlzeit.",
    "Runter mit dem Abwasser.",
    "Dafür hätte ich dir früher Hausverbot gegeben.",
    "Schwache Leistung.",
    "Ich hoffe, du bist nicht mit dem Auto da.",
    "Trink schneller, ich hab nicht ewig Zeit.",
    "Du würfelst wie ein Praktikant.",
    "Das war traurig anzusehen.",
    "Das läuft nicht gut für dich.",
    "Direkt in die Leber damit.",
    "Die Würfel kennen keine Gnade.",
    "Ich rieche Angst.",
    "Das Publikum ist enttäuscht.",
  ],
  general: [
    "Nächste Runde wird schlimmer.",
    "Irgendwann randaliert das hier noch.",
    "Das endet nie gut.",
    "Irgendwer bereut das morgen.",
    "Noch läuft alles legal.",
    "Gleich fliegt hier wieder irgendwas um.",
    "Absolut verantwortungslos.",
    "So fangen Geschichten an, die man später verschweigt.",
    "Das ist pädagogisch fragwürdig.",
    "Ich spüre schon den Kater von morgen.",
    "Das Spiel hasst uns beide.",
    "Willkommen im RandaleFUNK.",
    "Niemand kommt hier nüchtern raus.",
  ],
  randale: [
    "RANDALE!",
    "Jetzt wird's gefaehrlich.",
    "Oh oh.",
    "Das nimmt kein gutes Ende.",
    "Alle Systeme versagen.",
    "Zu spaet zum Aufhoeren.",
    "Genau SO entstehen Katastrophen.",
    "Das wird morgen teuer.",
    "Jetzt wird gesoffen.",
    "RANDALE!",
    "Keine Verantwortung ab diesem Punkt.",
  ],
  gameOver: ["Das war's. Bier leer. Verpiss dich."],
};

let quizBank = [];
const embeddedQuizQuestions = Array.isArray(window.RANDALE_QUIZ_QUESTIONS)
  ? window.RANDALE_QUIZ_QUESTIONS
  : [];

const initialState = {
  roundCount: 0,
  playerSips: 0,
  burgSips: 0,
  randaleCount: 0,
  randaleRoundsLeft: 0,
  quizQuestions: 0,
  playerQuizCorrect: 0,
  playerQuizWrong: 0,
  tieBreakCount: 0,
  highestRoll: 0,
  isGameOver: false,
  pendingQuiz: null,
  playerName: "Pappnase",
  correctQuizKeys: [],
  lastQuizKey: "",
};

let state = { ...initialState };
const ROLL_DICE_START_DELAY_MS = 1500;
const ROLL_DICE_END_EARLY_MS = 500;

async function loadQuizQuestions() {
  loadEmbeddedQuizQuestions();

  try {
    const response = await fetch("data/quizfragen.json", { cache: "no-store" });
    if (!response.ok) {
      throw new Error(`Quizdatei nicht geladen: ${response.status}`);
    }

    const loadedQuestions = await response.json();
    quizBank = validateQuizQuestions(loadedQuestions);
  } catch (error) {
    console.warn(error);
    loadEmbeddedQuizQuestions();
  }
}

function loadEmbeddedQuizQuestions() {
  try {
    quizBank = validateQuizQuestions(embeddedQuizQuestions);
  } catch (error) {
    console.warn(error);
    quizBank = [];
    setComment("Quizfragen konnten nicht geladen werden.");
  }
}

function validateQuizQuestions(questions) {
  if (!Array.isArray(questions)) {
    throw new Error("Quizdatei muss ein Array enthalten.");
  }

  const validQuestions = questions.filter((question) => {
    if (!question || typeof question.question !== "string" || !Array.isArray(question.answers)) {
      return false;
    }

    const correctAnswers = question.answers.filter((answer) => answer && answer.correct === true);
    return question.answers.length === 3
      && correctAnswers.length === 1
      && question.answers.every((answer) => typeof answer.text === "string" && typeof answer.correct === "boolean");
  });

  if (validQuestions.length === 0) {
    throw new Error("Quizdatei enthält keine gültigen Fragen.");
  }

  return validQuestions;
}

function shuffleAnswers(answers) {
  const shuffled = answers.map((answer) => ({ ...answer }));

  for (let index = shuffled.length - 1; index > 0; index -= 1) {
    const swapIndex = Math.floor(Math.random() * (index + 1));
    [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
  }

  return shuffled;
}

function showScreen(name) {
  Object.values(screens).forEach((screen) => screen.classList.remove("is-active"));
  screens[name].classList.add("is-active");
}

function randomItem(items) {
  return items[Math.floor(Math.random() * items.length)];
}

function rollDie() {
  return Math.floor(Math.random() * 6) + 1;
}

function quizKey(question) {
  return question.question;
}

function isDouble(roll) {
  return roll[0] === roll[1];
}

function isRoll(roll, value) {
  return roll[0] === value && roll[1] === value;
}

function sum(roll) {
  return roll[0] + roll[1];
}

function delay(ms) {
  return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function getDiceOverlayDurationMs() {
  const videoDurationMs = Number.isFinite(els.rollVideo.duration)
    ? els.rollVideo.duration * 1000
    : 0;

  if (videoDurationMs > ROLL_DICE_START_DELAY_MS + ROLL_DICE_END_EARLY_MS) {
    return videoDurationMs - ROLL_DICE_START_DELAY_MS - ROLL_DICE_END_EARLY_MS;
  }

  return 900;
}

function cleanPlayerName(rawName) {
  const trimmedName = String(rawName || "").trim().slice(0, 20);
  return trimmedName || "Pappnase";
}

function getStoredPlayerName() {
  try {
    return cleanPlayerName(window.localStorage.getItem("randalePlayerName"));
  } catch (error) {
    console.warn(error);
    return "Pappnase";
  }
}

function savePlayerName(name) {
  try {
    window.localStorage.setItem("randalePlayerName", name);
  } catch (error) {
    console.warn(error);
  }
}

function updatePlayerNameDisplays() {
  els.playerDiceLabel.textContent = state.playerName;
  els.playerStatLabel.textContent = state.playerName;
}

function rollSummary(playerTotal, burgTotal) {
  return `Burg: ${burgTotal} | ${state.playerName}: ${playerTotal}`;
}

function resetGame() {
  const playerName = cleanPlayerName(els.playerNameInput.value);
  savePlayerName(playerName);
  stopIntroAudio();
  state = { ...initialState, playerName };
  els.roundLog.innerHTML = "";
  closeQuiz();
  resetRollVideo();
  resetDrinkVideo();
  resetQuizWinVideo();
  setDiceOverlayVisible(true);
  updatePlayerNameDisplays();
  setDice([0, 0], [0, 0]);
  setResult("Bereit.", "Tippe auf Würfeln.");
  setComment("Willkommen im RandaleFUNK.");
  updateStats();
  showScreen("game");
}

function setDice(playerRoll, burgRoll) {
  const [p1, p2] = playerRoll;
  const [b1, b2] = burgRoll;
  setDie(els.playerDieOne, p1);
  setDie(els.playerDieTwo, p2);
  setDie(els.burgDieOne, b1);
  setDie(els.burgDieTwo, b2);
  els.playerSum.textContent = p1 && p2 ? sum(playerRoll) : "0";
  els.burgSum.textContent = b1 && b2 ? sum(burgRoll) : "0";
}

function setDie(element, value) {
  element.dataset.value = value || 0;
  element.textContent = value || "-";
}

function setRolling(isRolling) {
  els.videoStage.classList.toggle("is-rolling", isRolling);
  els.rollButton.disabled = isRolling || state.isGameOver || Boolean(state.pendingQuiz);
}

function setDiceRolling(isRolling) {
  [els.playerDieOne, els.playerDieTwo, els.burgDieOne, els.burgDieTwo].forEach((die) => {
    die.classList.toggle("is-rolling", isRolling);
  });
}

function setDiceOverlayVisible(isVisible) {
  els.diceOverlay.classList.toggle("is-hidden", !isVisible);
}

function setResult(title, detail) {
  renderPopText(els.resultTitle, title);
  els.resultDetail.textContent = detail;
}

function renderPopText(element, text) {
  element.textContent = "";
  element.classList.remove("is-popping");

  String(text).split("").forEach((character, index) => {
    const letter = document.createElement("span");
    letter.textContent = character === " " ? "\u00a0" : character;
    letter.style.setProperty("--letter-index", index);
    element.append(letter);
  });

  window.requestAnimationFrame(() => {
    element.classList.add("is-popping");
  });
}

function setComment(text) {
  const cleanText = String(text || "").replace(/^BURG:\s*/i, "");
  els.commentBox.textContent = `BURG: ${cleanText}`;
}

function addLog(text) {
  const item = document.createElement("li");
  item.textContent = text;
  els.roundLog.prepend(item);
  while (els.roundLog.children.length > 8) {
    els.roundLog.lastElementChild.remove();
  }
}

function updateStats() {
  els.roundDisplay.textContent = `Runde ${state.roundCount}`;
  els.playerSips.textContent = state.playerSips;
  els.burgSips.textContent = state.burgSips;
  els.quizCount.textContent = state.quizQuestions;
  els.randaleCount.textContent = state.randaleCount;

  if (state.randaleRoundsLeft > 0) {
    els.randaleRounds.textContent = `${state.randaleRoundsLeft} offen`;
    els.randaleMeter.classList.add("is-randale");
    document.body.classList.add("is-randale");
  } else {
    els.randaleRounds.textContent = "aus";
    els.randaleMeter.classList.remove("is-randale");
    document.body.classList.remove("is-randale");
  }
}

async function startIntroAudio() {
  try {
    els.introAudio.volume = 0.65;
    await els.introAudio.play();
  } catch (error) {
    console.warn(error);
  }
}

function stopIntroAudio() {
  const fadeSteps = 8;
  const startVolume = els.introAudio.volume || 0.65;
  let step = 0;

  const fade = window.setInterval(() => {
    step += 1;
    els.introAudio.volume = Math.max(0, startVolume * (1 - step / fadeSteps));

    if (step >= fadeSteps) {
      window.clearInterval(fade);
      els.introAudio.pause();
      els.introAudio.currentTime = 0;
      els.introAudio.volume = 0.65;
    }
  }, 35);
}

function drink(target, amount) {
  if (target === "player") {
    state.playerSips += amount;
    return;
  }
  state.burgSips += amount;
}

function startRandale() {
  state.randaleCount += 1;
  state.randaleRoundsLeft = 3;
  drink("player", 2);
  drink("burg", 2);
}

function tickRandaleForScoredRound() {
  if (state.randaleRoundsLeft > 0) {
    state.randaleRoundsLeft -= 1;
  }
}

function openQuiz(reason) {
  if (quizBank.length === 0) {
    setResult("Punkquiz blockiert", "Keine Quizfragen gefunden. Pruefe data/quizfragen.json.");
    setComment("Quizfragen konnten nicht geladen werden.");
    return;
  }

  const correctKeys = new Set(state.correctQuizKeys);
  let availableQuestions = quizBank.filter((question) => !correctKeys.has(quizKey(question)));

  if (availableQuestions.length === 0) {
    endQuizVictory();
    return;
  }

  if (availableQuestions.length > 1) {
    availableQuestions = availableQuestions.filter((question) => quizKey(question) !== state.lastQuizKey);
  }

  const sourceQuiz = randomItem(availableQuestions);
  state.lastQuizKey = quizKey(sourceQuiz);
  const quiz = {
    question: sourceQuiz.question,
    answers: shuffleAnswers(sourceQuiz.answers),
  };
  state.pendingQuiz = { quiz, reason };
  state.quizQuestions += 1;
  els.quizPanel.hidden = false;
  els.quizQuestion.textContent = quiz.question;
  els.quizOptions.innerHTML = "";
  quiz.answers.forEach((answer, index) => {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = answer.text;
    button.addEventListener("click", () => answerQuiz(index));
    els.quizOptions.append(button);
  });
  setRolling(false);
  setDiceRolling(false);
  els.rollButton.disabled = true;
  setResult("Punkquiz", `Pasch! Jetzt muss ${state.playerName} ran.`);
  setComment(randomItem(quotes.general));
  addLog(`Runde ${state.roundCount}: Punkquiz für Spieler (${reason}).`);
  updateStats();
}

function closeQuiz() {
  state.pendingQuiz = null;
  els.quizPanel.hidden = true;
  els.quizOptions.innerHTML = "";
}

function answerQuiz(answerIndex) {
  if (!state.pendingQuiz) {
    return;
  }

  const { quiz } = state.pendingQuiz;
  const selectedAnswer = quiz.answers[answerIndex];
  const correctAnswer = quiz.answers.find((answer) => answer.correct);
  const playerCorrect = selectedAnswer.correct === true;

  if (playerCorrect) {
    state.playerQuizCorrect += 1;
    state.correctQuizKeys = [...new Set([...state.correctQuizKeys, quizKey(quiz)])];
    setResult("Punkquiz bestanden", "Richtige Antwort. Niemand trinkt.");
    setComment(randomItem(quotes.general));
    addLog(`Quizantwort: richtig (${selectedAnswer.text}).`);
  } else {
    state.playerQuizWrong += 1;
    drink("player", 1);
    setResult("Punkquiz versemmelt", `Falsch. Richtig wäre: ${correctAnswer.text}. ${state.playerName} trinkt 1 Schluck.`);
    setComment(randomItem(quotes.playerLoses));
    addLog(`Quizantwort: falsch (${selectedAnswer.text}). Spieler trinkt 1.`);
  }

  closeQuiz();
  updateStats();
  setRolling(false);
  setDiceRolling(false);

  if (playerCorrect && state.correctQuizKeys.length >= quizBank.length) {
    window.setTimeout(endQuizVictory, 900);
  }
}

async function evaluateRound(playerRoll, burgRoll) {
  const playerTotal = sum(playerRoll);
  const burgTotal = sum(burgRoll);
  state.highestRoll = Math.max(state.highestRoll, playerTotal, burgTotal);

  if (isRoll(playerRoll, 1) || isRoll(burgRoll, 1)) {
    state.roundCount += 1;
    endGame(playerRoll, burgRoll);
    return;
  }

  if (isRoll(playerRoll, 6) || isRoll(burgRoll, 6)) {
    state.roundCount += 1;
    startRandale();
    setResult("RANDALE!", `${rollSummary(playerTotal, burgTotal)}. Beide trinken sofort 2 Schlucke. Die nächsten 3 gewerteten Runden zählen doppelt.`);
    setComment(randomItem(quotes.randale));
    addLog(`Runde ${state.roundCount}: 6-6. RANDALE startet.`);
    updateStats();
    return;
  }

  if (isDouble(playerRoll) && isDouble(burgRoll)) {
    state.roundCount += 1;
    openQuiz("Doppelpasch");
    return;
  }

  if (isDouble(playerRoll)) {
    state.roundCount += 1;
    openQuiz("Spieler-Pasch");
    return;
  }

  if (isDouble(burgRoll)) {
    state.roundCount += 1;
    openQuiz("Burg-Pasch");
    return;
  }

  if (playerTotal === burgTotal) {
    state.tieBreakCount += 1;
    setResult("Stechrunde", `${rollSummary(playerTotal, burgTotal)}. Gleichstand. Niemand trinkt.`);
    setComment(randomItem(quotes.general));
    addLog(`Stechrunde: ${playerTotal} zu ${burgTotal}.`);
    updateStats();
    return;
  }

  state.roundCount += 1;
  const loser = playerTotal < burgTotal ? "player" : "burg";
  const sipAmount = state.randaleRoundsLeft > 0 ? 2 : 1;
  drink(loser, sipAmount);
  tickRandaleForScoredRound();

  if (loser === "player") {
    setResult(`${state.playerName} verliert`, `${rollSummary(playerTotal, burgTotal)}. ${state.playerName} trinkt ${sipAmount} ${sipAmount === 1 ? "Schluck" : "Schlucke"}.`);
    setComment(randomItem(quotes.playerLoses));
  } else {
    setResult("Burg verliert", `${rollSummary(playerTotal, burgTotal)}. Burg trinkt ${sipAmount} ${sipAmount === 1 ? "Schluck" : "Schlucke"}.`);
    setComment(randomItem(quotes.burgLoses));
  }

  addLog(`Runde ${state.roundCount}: ${state.playerName} ${playerTotal}, Burg ${burgTotal}. ${loser === "player" ? state.playerName : "Burg"} trinkt ${sipAmount}.`);
  updateStats();

  if (loser === "burg") {
    await playDrinkVideo();
  }
}

function endGame(playerRoll, burgRoll) {
  state.isGameOver = true;
  const quote = quotes.gameOver[0];
  els.gameOverReason.textContent = "Einserpasch: 1-1. Bier leer.";
  els.gameOverQuote.textContent = quote;
  setResult("Bier leer", "Einserpasch: 1-1. Das Spiel endet.");
  setComment(quote);
  addLog(`Runde ${state.roundCount}: 1-1. Bier leer.`);
  updateStats();

  buildFinalStats(els.finalStats);

  window.setTimeout(() => showScreen("gameOver"), 1800);
}

function buildFinalStats(container) {
  container.innerHTML = "";
  const stats = [
    ["Runden", state.roundCount],
    [`${state.playerName}-Schlucke`, state.playerSips],
    ["Burg-Schlucke", state.burgSips],
    ["RANDALE", state.randaleCount],
    ["Quizfragen", state.quizQuestions],
    ["Quiz richtig", state.playerQuizCorrect],
    ["Quiz falsch", state.playerQuizWrong],
    ["Quiz offen", Math.max(0, quizBank.length - state.correctQuizKeys.length)],
    ["Stechrunden", state.tieBreakCount],
  ];

  stats.forEach(([label, value]) => {
    const stat = document.createElement("div");
    const statLabel = document.createElement("span");
    const statValue = document.createElement("strong");
    statLabel.textContent = label;
    statValue.textContent = value;
    stat.append(statLabel, statValue);
    container.append(stat);
  });
}

function endQuizVictory() {
  if (state.isGameOver) {
    return;
  }

  state.isGameOver = true;
  setRolling(false);
  setDiceRolling(false);
  closeQuiz();
  resetRollVideo();
  resetDrinkVideo();
  setComment("Alle Fragen richtig. Ich hasse dieses Spiel.");
  setResult("Punkquiz geschafft", `${state.playerName} hat alle ${quizBank.length} Fragen richtig beantwortet.`);
  els.quizWinText.textContent = `${state.playerName} hat alle Fragen richtig beantwortet. Burg ist kurz beleidigt.`;
  buildFinalStats(els.quizWinStats);
  showScreen("quizWin");
  playQuizWinVideo();
}

function resetRollVideo() {
  els.rollVideo.pause();
  els.rollVideo.currentTime = 0;
}

function resetDrinkVideo() {
  els.drinkVideo.pause();
  els.drinkVideo.currentTime = 0;
  els.drinkVideo.classList.remove("is-active");
}

function resetQuizWinVideo() {
  els.quizWinVideo.pause();
  els.quizWinVideo.currentTime = 0;
}

async function playRollVideo() {
  resetRollVideo();

  const ended = new Promise((resolve) => {
    els.rollVideo.addEventListener("ended", resolve, { once: true });
  });

  const safetyTimeout = new Promise((resolve) => {
    window.setTimeout(resolve, 6000);
  });

  try {
    await els.rollVideo.play();
    await Promise.race([ended, safetyTimeout]);
  } catch (error) {
    console.warn(error);
    await new Promise((resolve) => window.setTimeout(resolve, 900));
  }
}

async function playDrinkVideo() {
  resetDrinkVideo();
  els.drinkVideo.classList.add("is-active");

  const ended = new Promise((resolve) => {
    els.drinkVideo.addEventListener("ended", resolve, { once: true });
  });

  const safetyTimeout = new Promise((resolve) => {
    window.setTimeout(resolve, 6000);
  });

  try {
    await els.drinkVideo.play();
    await Promise.race([ended, safetyTimeout]);
  } catch (error) {
    console.warn(error);
    await delay(1200);
  } finally {
    resetDrinkVideo();
  }
}

async function playQuizWinVideo() {
  resetQuizWinVideo();

  try {
    await els.quizWinVideo.play();
  } catch (error) {
    console.warn(error);
  }
}

async function rollRound() {
  setRolling(true);
  setDiceRolling(false);
  setDiceOverlayVisible(false);
  setResult("Würfel rollen", "Burg guckt schon wieder komisch.");

  const videoPromise = playRollVideo();
  await delay(ROLL_DICE_START_DELAY_MS);

  setDiceOverlayVisible(true);
  setDiceRolling(true);

  const interval = window.setInterval(() => {
    setDice([rollDie(), rollDie()], [rollDie(), rollDie()]);
  }, 95);

  await Promise.race([delay(getDiceOverlayDurationMs()), videoPromise]);
  window.clearInterval(interval);
  setDiceRolling(false);

  const playerRoll = [rollDie(), rollDie()];
  const burgRoll = [rollDie(), rollDie()];
  setDice(playerRoll, burgRoll);

  await videoPromise;
  await evaluateRound(playerRoll, burgRoll);

  if (!state.pendingQuiz) {
    setRolling(false);
  }
}

els.startButton.addEventListener("click", resetGame);
els.restartButton.addEventListener("click", resetGame);
els.quizWinRestartButton.addEventListener("click", resetGame);
els.rollButton.addEventListener("click", rollRound);
els.playerNameInput.addEventListener("focus", startIntroAudio);
els.playerNameInput.addEventListener("keydown", (event) => {
  if (event.key === "Enter") {
    resetGame();
  }
});

loadQuizQuestions();
const storedPlayerName = getStoredPlayerName();
els.playerNameInput.value = storedPlayerName === "Pappnase" ? "" : storedPlayerName;
state.playerName = storedPlayerName;
updatePlayerNameDisplays();
showScreen("start");

["pointerdown", "keydown"].forEach((eventName) => {
  screens.start.addEventListener(eventName, startIntroAudio, { once: true });
});

if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register("service-worker.js").catch((error) => {
      console.warn("Service Worker konnte nicht registriert werden.", error);
    });
  });
}
