import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['card', 'front', 'back', 'current', 'progress'];

    connect() {
        this.cards = window.flashcardData || [];
        this.currentIndex = 0;
        this.isFlipped = false;
        this.scores = { easy: 0, hard: 0, wrong: 0 };
        
        if (this.cards.length > 0) {
            this.updateCard();
        }
    }

    flip() {
        this.isFlipped = !this.isFlipped;
        if (this.hasCardTarget) {
            this.cardTarget.classList.toggle('flipped', this.isFlipped);
        }
    }

    markEasy() {
        this.scores.easy++;
        this.nextCard();
    }

    markHard() {
        this.scores.hard++;
        this.nextCard();
    }

    markWrong() {
        this.scores.wrong++;
        // Add card back to end of deck for review
        if (this.currentIndex < this.cards.length) {
            this.cards.push({ ...this.cards[this.currentIndex] });
        }
        this.nextCard();
    }

    nextCard() {
        // Reset flip state
        if (this.isFlipped) {
            this.flip();
        }

        this.currentIndex++;

        if (this.currentIndex >= this.cards.length) {
            this.showResults();
            return;
        }

        this.updateCard();
    }

    previousCard() {
        if (this.currentIndex > 0) {
            if (this.isFlipped) {
                this.flip();
            }
            this.currentIndex--;
            this.updateCard();
        }
    }

    updateCard() {
        const card = this.cards[this.currentIndex];
        if (!card) return;

        if (this.hasFrontTarget) {
            this.frontTarget.textContent = card.front;
        }
        if (this.hasBackTarget) {
            this.backTarget.textContent = card.back;
        }
        if (this.hasCurrentTarget) {
            this.currentTarget.textContent = this.currentIndex + 1;
        }
        if (this.hasProgressTarget) {
            const progress = ((this.currentIndex + 1) / this.cards.length) * 100;
            this.progressTarget.style.width = `${progress}%`;
        }
    }

    showResults() {
        const total = this.scores.easy + this.scores.hard + this.scores.wrong;
        const accuracy = total > 0 ? Math.round((this.scores.easy / total) * 100) : 0;

        if (this.hasFrontTarget && this.hasCardTarget) {
            // Show results on the card
            this.cardTarget.classList.remove('flipped');
            this.frontTarget.innerHTML = `
                <div class="text-center">
                    <h3 class="text-2xl font-bold mb-4">Session Complete!</h3>
                    <div class="space-y-2 text-left max-w-xs mx-auto">
                        <div class="flex justify-between">
                            <span class="text-green-500">Easy:</span>
                            <span class="font-semibold">${this.scores.easy}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-yellow-500">Hard:</span>
                            <span class="font-semibold">${this.scores.hard}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-500">Again:</span>
                            <span class="font-semibold">${this.scores.wrong}</span>
                        </div>
                        <hr class="my-2 border-current opacity-20">
                        <div class="flex justify-between">
                            <span>Accuracy:</span>
                            <span class="font-bold">${accuracy}%</span>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-6" onclick="location.reload()">
                        Study Again
                    </button>
                </div>
            `;
        }

        if (this.hasProgressTarget) {
            this.progressTarget.style.width = '100%';
        }
    }

    // Keyboard navigation
    keydown(event) {
        switch (event.key) {
            case ' ':
            case 'Enter':
                event.preventDefault();
                this.flip();
                break;
            case 'ArrowLeft':
                this.previousCard();
                break;
            case 'ArrowRight':
                if (this.isFlipped) {
                    this.markHard();
                } else {
                    this.flip();
                }
                break;
            case '1':
                this.markWrong();
                break;
            case '2':
                this.markHard();
                break;
            case '3':
                this.markEasy();
                break;
        }
    }
}
