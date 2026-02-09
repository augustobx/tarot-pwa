/**
 * Shared Moon Widget Logic
 * Calculates current moon phase and renders the widget into #moon-container
 */

document.addEventListener('DOMContentLoaded', () => {
    initMoonWidget();
});

function initMoonWidget() {
    const container = document.getElementById('moon-container');
    if (!container) {
        console.warn('Moon Widget: #moon-container not found.');
        return;
    }

    // 1. Calculate Phase (Approximate)
    const phaseData = calculateMoonPhase();

    // 2. Render Widget HTML
    // Matching the structure expected by assets/css/moon.css
    const widgetHtml = `
        <div class="moon-widget" title="Fase Lunar Actual: ${phaseData.name}">
            <div class="moon-icon">${phaseData.icon}</div>
            <div class="moon-info">
                <span class="moon-phase-name">${phaseData.name}</span>
                <span class="moon-sign-badge">
                    <i class="fa-solid fa-star" style="font-size:0.6rem;"></i> ${phaseData.sign}
                </span>
            </div>
        </div>
    `;

    container.innerHTML = widgetHtml;
}

function calculateMoonPhase() {
    const date = new Date();
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    const day = date.getDate();

    // Simple calculation for visualization
    // In a real app, use a proper astronomic library

    // Julian Date approximation
    let c = 0;
    let e = 0;
    let jd = 0;
    let b = 0;

    if (month < 3) {
        year--;
        month += 12;
    }

    ++month;
    c = 365.25 * year;
    e = 30.6 * month;
    jd = c + e + day - 694039.09; // jd is total days elapsed
    jd /= 29.5305882; // divide by the moon cycle
    b = parseInt(jd); // int(jd) -> b, take integer part of jd
    jd -= b; // subtract integer part to leave fractional part of original jd
    b = Math.round(jd * 8); // scale fraction from 0-8 and round

    if (b >= 8) b = 0; // 0 and 8 are the same so turn 8 into 0

    const phases = [
        { name: 'Luna Nueva', icon: '🌑' },
        { name: 'Luna Creciente', icon: '🌒' },
        { name: 'Cuarto Creciente', icon: '🌓' },
        { name: 'Gibosa Creciente', icon: '🌔' },
        { name: 'Luna Llena', icon: '🌕' },
        { name: 'Gibosa Menguante', icon: '🌖' },
        { name: 'Cuarto Menguante', icon: '🌗' },
        { name: 'Luna Menguante', icon: '🌘' }
    ];

    // Determine Zodiac Sign (Approximate for Moon)
    // The moon spends ~2.5 days in each sign. 
    // This is a dummy calculation for visual consistency as real tracking requires ephemeris.
    const signs = ['Aries', 'Tauro', 'Géminis', 'Cáncer', 'Leo', 'Virgo', 'Libra', 'Escorpio', 'Sagitario', 'Capricornio', 'Acuario', 'Piscis'];
    // Randomize slightly based on day to simulate movement or use fixed for stability
    const signIndex = (day + month) % 12;

    return {
        name: phases[b].name,
        icon: phases[b].icon,
        sign: signs[signIndex]
    };
}
