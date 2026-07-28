/**
 * Lightweight POS UI sounds via Web Audio API (no asset files).
 */
let ctx = null;
let enabled = true;

function audio() {
    if (!ctx) {
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        ctx = new AC();
    }
    if (ctx.state === 'suspended') {
        ctx.resume().catch(() => {});
    }
    return ctx;
}

function tone(freq, duration = 0.08, type = 'sine', gain = 0.05, when = 0) {
    const ac = audio();
    if (!ac || !enabled) return;
    const osc = ac.createOscillator();
    const g = ac.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    g.gain.value = gain;
    g.gain.exponentialRampToValueAtTime(0.0001, ac.currentTime + when + duration);
    osc.connect(g);
    g.connect(ac.destination);
    osc.start(ac.currentTime + when);
    osc.stop(ac.currentTime + when + duration + 0.02);
}

export function setSoundEnabled(value) {
    enabled = Boolean(value);
}

export function isSoundEnabled() {
    return enabled;
}

export function playSound(kind) {
    switch (kind) {
        case 'add':
            tone(880, 0.06, 'square', 0.04);
            tone(1175, 0.07, 'square', 0.035, 0.05);
            break;
        case 'remove':
            tone(320, 0.1, 'triangle', 0.045);
            break;
        case 'click':
            tone(600, 0.03, 'sine', 0.025);
            break;
        case 'success':
            tone(523, 0.08, 'sine', 0.05);
            tone(659, 0.08, 'sine', 0.045, 0.07);
            tone(784, 0.12, 'sine', 0.05, 0.14);
            break;
        case 'error':
            tone(180, 0.18, 'sawtooth', 0.04);
            tone(140, 0.22, 'sawtooth', 0.035, 0.1);
            break;
        case 'scan':
            tone(1400, 0.04, 'square', 0.03);
            break;
        default:
            tone(500, 0.03, 'sine', 0.02);
    }
}
