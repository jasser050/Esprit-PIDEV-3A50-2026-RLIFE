import { Controller } from "@hotwired/stimulus";

/**
 * Tools behavior:
 * - breathing: modal + timer + 4-7-8 guide text
 * - meditation: plays /audio/meditation.mp3 in loop
 * - nature: plays /audio/nature.mp3 in loop
 * - gratitude: modal + textarea + save at finish
 * - stretching: modal + timer + short instructions
 * - pomodoro: modal + 25min timer
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
  ];

  static values = {
    startUrl: String,
    finishUrl: String,
  };

  connect() {
    this.current = null;
    this.sessionId = null;
    this.timer = null;
    this.remaining = 0;

    this.audio = null;

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
    this._openTool({ key: "breathing", name: "Breathing Exercise", durationSeconds: 180 });
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

    this.modalTitleTarget.textContent = tool.name || "Tool";

    this.gratitudeBoxTarget.classList.add("hidden");
    this.modalTimerTarget.classList.remove("hidden");

    this.modalDescriptionTarget.textContent = this._getDescription(tool.key);

    this._setIcon(tool.key);

    this.primaryBtnTarget.textContent = this._primaryLabel(tool.key);
    this.primaryBtnTarget.onclick = null;

    const seconds = tool.durationSeconds || this._defaultDuration(tool.key);
    this.remaining = seconds;

    if (tool.key === "nature") {
      this.modalTimerTarget.classList.add("hidden");
      this.modalTimeTarget.textContent = "";
    } else {
      this.modalTimeTarget.textContent = this._fmt(seconds);
    }

    if (tool.key === "gratitude") {
      this.gratitudeBoxTarget.classList.remove("hidden");
      this.gratitudeTextTarget.value = "";
    }

    this.modalRootTarget.classList.remove("hidden");
    this.modalRootTarget.setAttribute("aria-hidden", "false");

    if (window.lucide?.createIcons) window.lucide.createIcons();
  }

  closeModal() {
    this._stopAudio();
    this._stopTimer();

    if (this.sessionId) {
      this._finishSession("cancelled").catch(() => {});
    }

    this.sessionId = null;
    this.current = null;

    this.modalRootTarget.classList.add("hidden");
    this.modalRootTarget.setAttribute("aria-hidden", "true");
  }

  async startToolFromModal() {
    if (!this.current?.key) return;

    await this._startSession();

    const key = this.current.key;

    if (key === "nature") {
      this._playAudio("/audio/nature.mp3");
      this.primaryBtnTarget.textContent = "Stop";
      this.primaryBtnTarget.onclick = () => {
        this._finishSession("finished").catch(() => {});
        this.closeModal();
      };
      return;
    }

    if (key === "meditation") {
      this._playAudio("/audio/meditation.mp3");
      this._startCountdown(this._defaultDuration("meditation"));
      this.primaryBtnTarget.textContent = "Finish";
      this.primaryBtnTarget.onclick = () => this._completeNow();
      return;
    }

    if (key === "gratitude") {
      this._startCountdown(this._defaultDuration("gratitude"));
      this.primaryBtnTarget.textContent = "Save & Finish";
      this.primaryBtnTarget.onclick = () => this._saveGratitudeAndFinish();
      return;
    }

    if (key === "pomodoro") {
      this._startCountdown(25 * 60);
      this.primaryBtnTarget.textContent = "Finish";
      this.primaryBtnTarget.onclick = () => this._completeNow();
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
    await this._finishSession("finished", { notes });
    this.closeModal();
  }

  async _startSession() {
    if (!this.startUrlValue) return;

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
      case "breathing": return 180;
      case "meditation": return 300;
      case "stretching": return 240;
      case "pomodoro": return 1500;
      case "gratitude": return 120;
      case "nature": return 0;
      default: return 180;
    }
  }

  _primaryLabel(key) {
    if (key === "nature") return "Play";
    if (key === "gratitude") return "Start Writing";
    return "Start Exercise";
  }

  _getDescription(key) {
    switch (key) {
      case "breathing":
        return "4-7-8 Breathing\nBreathe in for 4 seconds, hold for 7, exhale for 8. Repeat slowly.";
      case "meditation":
        return "Guided mindfulness session (audio). Find a quiet place and relax.";
      case "stretching":
        return "Desk-friendly stretches: neck rolls, shoulder circles, gentle back stretch.";
      case "pomodoro":
        return "25 minutes focus. Work with no distractions, then take a short break.";
      case "gratitude":
        return "Write three things you’re grateful for. Small wins count!";
      case "nature":
        return "Relaxing ambient nature sounds (audio).";
      default:
        return "Exercise to support wellbeing.";
    }
  }

  _getLiveText(key, remaining) {
    if (key !== "breathing") return this._getDescription(key);

    const cycle = 19;
    const t = (this._defaultDuration("breathing") - remaining) % cycle;

    if (t < 4) return "Breathe in… (4s)";
    if (t < 11) return "Hold… (7s)";
    return "Exhale slowly… (8s)";
  }

  _setIcon(key) {
    const map = {
      breathing: "wind",
      meditation: "sparkles",
      stretching: "heart",
      pomodoro: "clock",
      gratitude: "book",
      nature: "music",
    };
    const icon = map[key] || "sparkles";
    this.modalIconTarget.setAttribute("data-lucide", icon);
  }
  stopPropagation(event) {
    event.stopPropagation();
  }

}
