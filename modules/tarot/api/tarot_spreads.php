<?php
/**
 * Tarot Spreads Helper
 * Handles card selection and tarot reading logic
 */

class TarotDeck {
    // 22 Major Arcana
    private static $majorArcana = [
        ['id' => 0, 'name' => 'El Loco', 'name_en' => 'The Fool', 'image' => 'assets/images/tarot/major_arcana/00_the_fool.png'],
        ['id' => 1, 'name' => 'El Mago', 'name_en' => 'The Magician', 'image' => 'assets/images/tarot/major_arcana/01_the_magician.png'],
        ['id' => 2, 'name' => 'La Sacerdotisa', 'name_en' => 'The High Priestess', 'image' => 'assets/images/tarot/major_arcana/02_high_priestess.png'],
        ['id' => 3, 'name' => 'La Emperatriz', 'name_en' => 'The Empress', 'image' => 'assets/images/tarot/major_arcana/03_the_empress.png'],
        ['id' => 4, 'name' => 'El Emperador', 'name_en' => 'The Emperor', 'image' => 'assets/images/tarot/major_arcana/04_the_emperor.png'],
        ['id' => 5, 'name' => 'El Hierofante', 'name_en' => 'The Hierophant', 'image' => 'assets/images/tarot/major_arcana/05_the_hierophant.png'],
        ['id' => 6, 'name' => 'Los Enamorados', 'name_en' => 'The Lovers', 'image' => 'assets/images/tarot/major_arcana/06_the_lovers.png'],
        ['id' => 7, 'name' => 'El Carro', 'name_en' => 'The Chariot', 'image' => 'assets/images/tarot/major_arcana/07_the_chariot.png'],
        ['id' => 8, 'name' => 'La Fuerza', 'name_en' => 'Strength', 'image' => 'assets/images/tarot/major_arcana/08_strength.png'],
        ['id' => 9, 'name' => 'El Ermitaño', 'name_en' => 'The Hermit', 'image' => 'assets/images/tarot/major_arcana/09_the_hermit.png'],
        ['id' => 10, 'name' => 'La Rueda de la Fortuna', 'name_en' => 'Wheel of Fortune', 'image' => 'assets/images/tarot/major_arcana/10_wheel_of_fortune.png'],
        ['id' => 11, 'name' => 'La Justicia', 'name_en' => 'Justice', 'image' => 'assets/images/tarot/major_arcana/11_justice.png'],
        ['id' => 12, 'name' => 'El Colgado', 'name_en' => 'The Hanged Man', 'image' => 'assets/images/tarot/major_arcana/12_the_hanged_man.png'],
        ['id' => 13, 'name' => 'La Muerte', 'name_en' => 'Death', 'image' => 'assets/images/tarot/major_arcana/13_death.png'],
        ['id' => 14, 'name' => 'La Templanza', 'name_en' => 'Temperance', 'image' => 'assets/images/tarot/major_arcana/14_temperance.png'],
        ['id' => 15, 'name' => 'El Diablo', 'name_en' => 'The Devil', 'image' => 'assets/images/tarot/major_arcana/15_the_devil.png'],
        ['id' => 16, 'name' => 'La Torre', 'name_en' => 'The Tower', 'image' => 'assets/images/tarot/major_arcana/16_the_tower.png'],
        ['id' => 17, 'name' => 'La Estrella', 'name_en' => 'The Star', 'image' => 'assets/images/tarot/major_arcana/17_the_star.png'],
        ['id' => 18, 'name' => 'La Luna', 'name_en' => 'The Moon', 'image' => 'assets/images/tarot/major_arcana/18_the_moon.png'],
        ['id' => 19, 'name' => 'El Sol', 'name_en' => 'The Sun', 'image' => 'assets/images/tarot/major_arcana/19_the_sun.png'],
        ['id' => 20, 'name' => 'El Juicio', 'name_en' => 'Judgement', 'image' => 'assets/images/tarot/major_arcana/20_judgement.png'],
        ['id' => 21, 'name' => 'El Mundo', 'name_en' => 'The World', 'image' => 'assets/images/tarot/major_arcana/21_the_world.png'],
    ];
    
    // Minor Arcana suits
    private static $suits = [
        'bastos' => ['name' => 'Bastos', 'name_en' => 'Wands', 'folder' => 'wands'],
        'copas' => ['name' => 'Copas', 'name_en' => 'Cups', 'folder' => 'cups'],
        'espadas' => ['name' => 'Espadas', 'name_en' => 'Swords', 'folder' => 'swords'],
        'oros' => ['name' => 'Oros', 'name_en' => 'Pentacles', 'folder' => 'pentacles']
    ];
    
    private static $ranks = [
        'as' => ['name' => 'As', 'name_en' => 'Ace', 'value' => 1],
        '2' => ['name' => 'Dos', 'name_en' => 'Two', 'value' => 2],
        '3' => ['name' => 'Tres', 'name_en' => 'Three', 'value' => 3],
        '4' => ['name' => 'Cuatro', 'name_en' => 'Four', 'value' => 4],
        '5' => ['name' => 'Cinco', 'name_en' => 'Five', 'value' => 5],
        '6' => ['name' => 'Seis', 'name_en' => 'Six', 'value' => 6],
        '7' => ['name' => 'Siete', 'name_en' => 'Seven', 'value' => 7],
        '8' => ['name' => 'Ocho', 'name_en' => 'Eight', 'value' => 8],
        '9' => ['name' => 'Nueve', 'name_en' => 'Nine', 'value' => 9],
        '10' => ['name' => 'Diez', 'name_en' => 'Ten', 'value' => 10],
        'sota' => ['name' => 'Sota', 'name_en' => 'Page', 'value' => 11],
        'caballero' => ['name' => 'Caballero', 'name_en' => 'Knight', 'value' => 12],
        'reina' => ['name' => 'Reina', 'name_en' => 'Queen', 'value' => 13],
        'rey' => ['name' => 'Rey', 'name_en' => 'King', 'value' => 14],
    ];
    
    /**
     * Get all 78 cards (22 Major + 56 Minor)
     */
    public static function getAllCards() {
        $allCards = self::$majorArcana;
        
        // Generate Minor Arcana
        foreach (self::$suits as $suitKey => $suit) {
            foreach (self::$ranks as $rankKey => $rank) {
                $allCards[] = [
                    'id' => count($allCards),
                    'name' => $rank['name'] . ' de ' . $suit['name'],
                    'name_en' => $rank['name_en'] . ' of ' . $suit['name_en'],
                    'suit' => $suitKey,
                    'rank' => $rankKey,
                    'image' => "assets/images/tarot/minor_arcana/{$suit['folder']}_{$rankKey}.png"
                ];
            }
        }
        
        return $allCards;
    }
    
    /**
     * Select N random unique cards
     */
    public static function drawCards($count = 3) {
        $deck = self::getAllCards();
        shuffle($deck);
        
        $drawn = [];
        for ($i = 0; $i < $count; $i++) {
            $card = $deck[$i];
            // 50% chance of being reversed
            $card['reversed'] = (rand(0, 1) === 1);
            $drawn[] = $card;
        }
        
        return $drawn;
    }
    
    /**
     * Build prompt for Gemini with specific cards drawn
     */
    public static function buildTarotPrompt($cards, $question, $userData) {
        require_once __DIR__ . '/conversation_state.php';
        
        $basePrompt = buildSystemPrompt($userData);
        
        $tarotInfo = "\n\n========== TIRADA DE TAROT ==========\n";
        $tarotInfo .= "El consultante ha pedido una tirada de tarot. Las cartas que salieron son:\n\n";
        
        $positions = ['PASADO', 'PRESENTE', 'FUTURO'];
        foreach ($cards as $index => $card) {
            $position = $positions[$index] ?? "CARTA " . ($index + 1);
            $orientation = $card['reversed'] ? 'INVERTIDA' : 'DERECHA';
            $tarotInfo .= "$position: **{$card['name']}** ($orientation)\n";
        }
        
        $tarotInfo .= "\nIMPORTANTE: Debes interpretar EXACTAMENTE estas cartas en relación a la pregunta del consultante.\n";
        $tarotInfo .= "No inventes otras cartas. Usa tu conocimiento del tarot para dar una lectura profunda y mística.\n";
        $tarotInfo .= "=======================================\n\n";
        
        return $basePrompt . $tarotInfo;
    }
}
?>
