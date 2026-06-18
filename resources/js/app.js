import './bootstrap';
import confetti from 'canvas-confetti';

/**
 * Global confetti helper used by Modal views via Alpine.
 * type: 'achievement' | 'fireworks' | 'ranking_top1' | 'ranking_top2' | 'ranking_top3'
 */
window.fireConfetti = (type = 'achievement') => {
    if (type === 'fireworks') {
        // 3 bursts staggered
        const burst = (delay) => setTimeout(() => confetti({
            particleCount: 80,
            spread: 100,
            startVelocity: 40,
            origin: { x: Math.random() * 0.6 + 0.2, y: 0.5 },
            colors: ['#FFD700', '#FF6347', '#00CED1', '#FF69B4', '#7CFC00'],
        }), delay);
        burst(0); burst(500); burst(1000);
    } else if (type === 'ranking_top1') {
        confetti({
            particleCount: 150,
            spread: 80,
            origin: { y: 0.6 },
            colors: ['#FFD700', '#FFA500', '#FFEC00'],
        });
    } else if (type === 'ranking_top2') {
        confetti({ particleCount: 80, spread: 60, origin: { y: 0.65 }, colors: ['#C0C0C0', '#E8E8E8', '#A8A8A8'] });
    } else if (type === 'ranking_top3') {
        confetti({ particleCount: 60, spread: 55, origin: { y: 0.65 }, colors: ['#CD7F32', '#E8A468', '#B87333'] });
    } else {
        // achievement default
        confetti({
            particleCount: 120,
            spread: 80,
            origin: { y: 0.6 },
            colors: ['#FFD700', '#FF6347', '#00CED1', '#FF69B4'],
        });
    }
};
