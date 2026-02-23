import { Controller } from "@hotwired/stimulus";

/**
 * Tools behavior:
 * - breathing_exercise: modal + timer + 4-7-8 guide text
 * - nature_sounds: plays /audio/nature.mp3 in loop
 * - gratitude_journal: modal + textarea + save to journal
 * - yoga_coach: modal + AI-generated plan + gifs
 * - ai_chat_coach: modal + chat with AI
 */
export default class extends Controller {
  static targets = [
    "modalRoot",
    "modalTitle",
    "modalDescription",
    "modalTime",
    "modalTimer",
    "modalIcon",
    "primaryBtn",
    "gratitudeBox",
    "gratitudeText",
    "chatBox",
    "chatMessages",
    "chatInput",
    "chatForm",
    "yogaBox",
    "yogaList",
    "statusText",
    "journalList",
  ];

  static values = {
    startUrl: String,
    finishUrl: String,
    journalUrl: String,
    aiChatUrl: String,
    yogaUrl: String,
  };

  connect() {
    this.current = null;
    this.sessionId = null;
    this.sessionClosed = false;
    this.sessionClosing = false;
    this.timer = null;
    this.remaining = 0;

    this.audio = null;
    this.chatHistory = [];

    this._onKeyDown = (e) => {
      if (e.key === "Escape") this.closeModal();
    };
    window.addEventListener("keydown", this._onKeyDown);
  }

  disconnect() {
    window.removeEventListener("keydown", this._onKeyDown);
    this._stopTimer();
    this._stopAudio();
  }

  startRecommended() {
    this._openTool({ key: "breathing_exercise", name: "Breathing Exercise", durationSeconds: 180 });
  }

  openTool(event) {
    const btn = event.currentTarget;
    const key = btn.dataset.toolKey;
    const name = btn.dataset.toolName;
    const durationSeconds = parseInt(btn.dataset.toolDuration || "0", 10) || 0;

    this._openTool({ key, name, durationSeconds });
  }

  _openTool(tool) {
    this.current = tool;
    this.sessionClosed = false;
    this.sessionClosing = false;
    this.chatHistory = [];

    this.modalTitleTarget.textContent = tool.name || "Tool";

    this.gratitudeBoxTarget.classList.add("hidden");
    this.chatBoxTarget.classList.add("hidden");
    this.yogaBoxTarget.classList.add("hidden");
    this.modalTimerTarget.classList.remove("hidden");
    this.statusTextTarget.textContent = "";

    this.modalDescriptionTarget.textContent = this._getDescription(tool.key);

    this._setIcon(tool.key);

    this.primaryBtnTarget.textContent = this._primaryLabel(tool.key);
    this.primaryBtnTarget.onclick = null;
    this.primaryBtnTarget.classList.remove("hidden");
    this.primaryBtnTarget.disabled = false;

    const seconds = tool.durationSeconds || this._defaultDuration(tool.key);
    this.remaining = seconds;

    if (tool.key === "nature_sounds" || tool.key === "ai_chat_coach" || tool.key === "yoga_coach") {
      this.modalTimerTarget.classList.add("hidden");
      this.modalTimeTarget.textContent = "";
    } else {
      this.modalTimeTarget.textContent = this._fmt(seconds);
    }

    if (tool.key === "gratitude_journal") {
      this.gratitudeBoxTarget.classList.remove("hidden");
      this.gratitudeTextTarget.value = "";
    }

    if (tool.key === "ai_chat_coach") {
      this.chatBoxTarget.classList.remove("hidden");
      this.chatMessagesTarget.innerHTML = "";
      this.chatInputTarget.value = "";
    }

    if (tool.key === "yoga_coach") {
      this.yogaBoxTarget.classList.remove("hidden");
      this.yogaListTarget.innerHTML = "";
    }

    this.modalRootTarget.classList.remove("hidden");
    this.modalRootTarget.setAttribute("aria-hidden", "false");

    if (window.lucide?.createIcons) window.lucide.createIcons();
  }

  closeModal() {
    this._stopAudio();
    this._stopTimer();

    if (this.sessionId && !this.sessionClosed && !this.sessionClosing) {
      this._finishSession("cancelled").catch(() => {});
    }

    this.sessionId = null;
    this.current = null;
    this.sessionClosed = false;
    this.sessionClosing = false;

    this.modalRootTarget.classList.add("hidden");
    this.modalRootTarget.setAttribute("aria-hidden", "true");
  }

  async startToolFromModal() {
    if (!this.current?.key) return;

    await this._startSession();

    const key = this.current.key;

    if (key === "nature_sounds") {
      this._playAudio("/audio/nature.mp3");
      this.primaryBtnTarget.textContent = "Stop";
      this.primaryBtnTarget.onclick = () => {
        this._finishSession("finished").catch(() => {});
        this.closeModal();
      };
      return;
    }

    if (key === "gratitude_journal") {
      this._startCountdown(this._defaultDuration("gratitude_journal"));
      this.primaryBtnTarget.textContent = "Save & Finish";
      this.primaryBtnTarget.onclick = () => this._saveGratitudeAndFinish();
      return;
    }

    if (key === "yoga_coach") {
      this.primaryBtnTarget.textContent = "Finish Session";
      this.primaryBtnTarget.onclick = () => this._completeNow();
      await this._loadYogaPlan();
      return;
    }

    if (key === "ai_chat_coach") {
      this.primaryBtnTarget.textContent = "Finish Session";
      this.primaryBtnTarget.onclick = () => this._completeNow();
      this.statusTextTarget.textContent = "Chat is ready. Send a message below.";
      return;
    }

    this._startCountdown(this._defaultDuration(key));
    this.primaryBtnTarget.textContent = "Finish";
    this.primaryBtnTarget.onclick = () => this._completeNow();
  }

  _completeNow() {
    this._finishSession("finished").catch(() => {});
    this.closeModal();
  }

  async _saveGratitudeAndFinish() {
    const notes = (this.gratitudeTextTarget.value || "").trim();
    if (notes && this.journalUrlValue) {
      await this._saveJournalEntry(notes);
    }
    await this._finishSession("finished");
    this.closeModal();
  }

  async _startSession() {
    if (!this.startUrlValue) return;
    this.sessionClosing = false;
    this.sessionClosed = false;

    const res = await fetch(this.startUrlValue, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ toolKey: this.current.key }),
    });

    if (!res.ok) return;
    const json = await res.json();
    if (json?.ok && json.sessionId) this.sessionId = json.sessionId;
  }

  async _finishSession(status, extra = {}) {
    if (!this.finishUrlValue || !this.sessionId) return;
    this.sessionClosing = true;

    await fetch(this.finishUrlValue, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        sessionId: this.sessionId,
        status,
        actualSeconds: this._actualSeconds(),
        ...extra,
      }),
    });

    this.sessionClosed = true;
  }

  _actualSeconds() {
    const planned = this.current?.durationSeconds || this._defaultDuration(this.current?.key);
    if (!planned) return 0;
    const used = planned - (this.remaining || 0);
    return used > 0 ? used : 0;
  }

  _startCountdown(seconds) {
    this._stopTimer();
    this.remaining = seconds;

    this.modalTimerTarget.classList.remove("hidden");
    this.modalTimeTarget.textContent = this._fmt(this.remaining);

    this.modalDescriptionTarget.textContent = this._getLiveText(this.current.key, this.remaining);

    this.timer = setInterval(() => {
      this.remaining -= 1;

      if (this.remaining <= 0) {
        this.modalTimeTarget.textContent = "00:00";
        this._stopTimer();
        this._finishSession("finished").catch(() => {});
        this.closeModal();
        return;
      }

      this.modalTimeTarget.textContent = this._fmt(this.remaining);
      this.modalDescriptionTarget.textContent = this._getLiveText(this.current.key, this.remaining);
    }, 1000);
  }

  _stopTimer() {
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = null;
    }
  }

  _playAudio(src) {
    this._stopAudio();
    this.audio = new Audio(src);
    this.audio.loop = true;
    this.audio.volume = 0.6;
    this.audio.play().catch(() => {});
  }

  _stopAudio() {
    if (this.audio) {
      try {
        this.audio.pause();
        this.audio.currentTime = 0;
      } catch (e) {}
    }
    this.audio = null;
  }

  _fmt(totalSeconds) {
    const m = Math.floor(totalSeconds / 60);
    const s = totalSeconds % 60;
    return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
  }

  _defaultDuration(key) {
    switch (key) {
      case "breathing_exercise": return 180;
      case "gratitude_journal": return 120;
      case "nature_sounds": return 0;
      case "yoga_coach": return 360;
      case "ai_chat_coach": return 0;
      default: return 180;
    }
  }

  _primaryLabel(key) {
    if (key === "nature_sounds") return "Play";
    if (key === "gratitude_journal") return "Start Writing";
    if (key === "yoga_coach") return "Generate Plan";
    if (key === "ai_chat_coach") return "Start Chat";
    return "Start Exercise";
  }

  _getDescription(key) {
    switch (key) {
      case "breathing_exercise":
        return "4-7-8 Breathing\nBreathe in for 4 seconds, hold for 7, exhale for 8. Repeat slowly.";
      case "gratitude_journal":
        return "Write three things you are grateful for. Small wins count.";
      case "nature_sounds":
        return "Relaxing ambient nature sounds (audio).";
      case "yoga_coach":
        return "Short, beginner-friendly yoga sequence with a coach demo.";
      case "ai_chat_coach":
        return "Chat with an AI coach for motivation and practical advice.";
      default:
        return "Exercise to support wellbeing.";
    }
  }

  _getLiveText(key, remaining) {
    if (key !== "breathing_exercise") return this._getDescription(key);

    const cycle = 19;
    const t = (this._defaultDuration("breathing_exercise") - remaining) % cycle;

    if (t < 4) return "Breathe in... (4s)";
    if (t < 11) return "Hold... (7s)";
    return "Exhale slowly... (8s)";
  }

  _setIcon(key) {
    const map = {
      breathing_exercise: "wind",
      gratitude_journal: "book-open",
      nature_sounds: "music",
      yoga_coach: "activity",
      ai_chat_coach: "sparkles",
    };
    const icon = map[key] || "sparkles";
    this.modalIconTarget.setAttribute("data-lucide", icon);
  }

  async _saveJournalEntry(content) {
    try {
      const res = await fetch(this.journalUrlValue, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content, inputMode: "text" }),
      });

      if (!res.ok) {
        this.statusTextTarget.textContent = "Could not save journal entry.";
        return;
      }

      const json = await res.json();
      if (json?.ok && json.entry && this.hasJournalListTarget) {
        const item = document.createElement("div");
        item.className = "p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60";
        item.innerHTML = `
          <p class="text-sm text-slate-700 dark:text-slate-200">${this._escapeHtml(json.entry.content)}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">${json.entry.createdAt}</p>
        `;
        this.journalListTarget.prepend(item);
      }

      this.statusTextTarget.textContent = "Journal entry saved.";
    } catch (e) {
      this.statusTextTarget.textContent = "Could not save journal entry.";
    }
  }

  async _loadYogaPlan() {
    if (!this.yogaUrlValue) return;

    this.statusTextTarget.textContent = "Generating yoga plan...";
    this.primaryBtnTarget.disabled = true;

    try {
      const res = await fetch(this.yogaUrlValue, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({}),
      });

      if (!res.ok) {
        this.statusTextTarget.textContent = "Could not generate a plan right now.";
        this.primaryBtnTarget.disabled = false;
        return;
      }

      const json = await res.json();
      if (!json?.ok || !Array.isArray(json.plan)) {
        this.statusTextTarget.textContent = "Could not generate a plan right now.";
        this.primaryBtnTarget.disabled = false;
        return;
      }

      this._renderYogaPlan(json.plan);
      this.statusTextTarget.textContent = "Plan ready. Follow the steps in order.";
      this.primaryBtnTarget.disabled = false;
    } catch (e) {
      this.statusTextTarget.textContent = "Could not generate a plan right now.";
      this.primaryBtnTarget.disabled = false;
    }
  }

  _renderYogaPlan(plan) {
    if (!this.yogaListTarget) return;
    this.yogaListTarget.innerHTML = "";

    plan.forEach((step, idx) => {
      const row = document.createElement("div");
      row.className = "flex items-start gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60";

      const gif = step.gifKey ? `/images/yoga_gif/${step.gifKey}.gif` : "";
      row.innerHTML = `
        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
          ${gif ? `<img src="${gif}" alt="${this._escapeHtml(step.title || "Yoga")}" class="w-full h-full object-cover" />` : ""}
        </div>
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <h4 class="font-medium text-slate-900 dark:text-white">${idx + 1}. ${this._escapeHtml(step.title || "Step")}</h4>
            <span class="text-xs text-slate-500 dark:text-slate-400">${step.seconds || 20}s</span>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">${this._escapeHtml(step.description || "")}</p>
        </div>
      `;
      this.yogaListTarget.appendChild(row);
    });
  }

  async sendChat(event) {
    event.preventDefault();
    if (!this.aiChatUrlValue) return;

    const message = (this.chatInputTarget.value || "").trim();
    if (!message) return;

    if (!this.sessionId) {
      await this._startSession();
    }

    this._appendChatMessage("user", message);
    this.chatInputTarget.value = "";

    try {
      const res = await fetch(this.aiChatUrlValue, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          message,
          history: this.chatHistory,
        }),
      });

      if (!res.ok) {
        this._appendChatMessage("assistant", "Sorry, I could not respond right now.");
        return;
      }

      const json = await res.json();
      const reply = (json?.reply || "").trim();
      if (reply) {
        this._appendChatMessage("assistant", reply);
      }
    } catch (e) {
      this._appendChatMessage("assistant", "Sorry, I could not respond right now.");
    }
  }

  _appendChatMessage(role, content) {
    this.chatHistory.push({ role, content });

    const bubble = document.createElement("div");
    const isUser = role === "user";
    bubble.className = isUser
      ? "self-end max-w-[85%] rounded-2xl rounded-br-md bg-primary-600 text-white px-4 py-2 text-sm"
      : "self-start max-w-[85%] rounded-2xl rounded-bl-md bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 px-4 py-2 text-sm";

    bubble.textContent = content;
    this.chatMessagesTarget.appendChild(bubble);
    this.chatMessagesTarget.scrollTop = this.chatMessagesTarget.scrollHeight;
  }

  _escapeHtml(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  stopPropagation(event) {
    event.stopPropagation();
  }
}
