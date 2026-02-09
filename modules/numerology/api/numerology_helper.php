<?php
/**
 * Numerology Helper Functions
 * Cálculos reales de numerología pitagórica
 */

// Tabla pitagórica para conversión letra-número
const PYTHAGOREAN_TABLE = [
    'A' => 1, 'J' => 1, 'S' => 1,
    'B' => 2, 'K' => 2, 'T' => 2,
    'C' => 3, 'L' => 3, 'U' => 3,
    'D' => 4, 'M' => 4, 'V' => 4,
    'E' => 5, 'N' => 5, 'W' => 5, 'Ñ' => 5,
    'F' => 6, 'O' => 6, 'X' => 6,
    'G' => 7, 'P' => 7, 'Y' => 7,
    'H' => 8, 'Q' => 8, 'Z' => 8,
    'I' => 9, 'R' => 9
];

// Vocales para cálculo del número del alma
const VOWELS = ['A', 'E', 'I', 'O', 'U'];

/**
 * Reduce un número a un solo dígito
 * Excepto números maestros: 11, 22, 33
 */
function reduceToSingleDigit($number) {
    while ($number > 9 && !in_array($number, [11, 22, 33])) {
        $sum = 0;
        foreach (str_split((string)$number) as $digit) {
            $sum += (int)$digit;
        }
        $number = $sum;
    }
    return $number;
}

/**
 * Calcular Número de Vida / Camino de Vida
 * Revela el propósito de vida y el camino a recorrer
 * 
 * @param int $day Día de nacimiento (1-31)
 * @param int $month Mes de nacimiento (1-12)
 * @param int $year Año de nacimiento (ej: 1990)
 * @return int Número de vida (1-9, 11, 22, 33)
 */
function calculateLifePathNumber($day, $month, $year) {
    // Reducir cada componente por separado
    $dayReduced = reduceToSingleDigit($day);
    $monthReduced = reduceToSingleDigit($month);
    
    // Año: sumar dígitos primero
    $yearSum = array_sum(str_split((string)$year));
    $yearReduced = reduceToSingleDigit($yearSum);
    
    // Sumar los tres componentes reducidos
    $total = $dayReduced + $monthReduced + $yearReduced;
    
    return reduceToSingleDigit($total);
}

/**
 * Calcular Número de Destino / Expresión
 * Revela talentos, habilidades y metas a alcanzar
 * 
 * @param string $fullName Nombre completo de nacimiento
 * @return int Número de destino (1-9, 11, 22, 33)
 */
function calculateDestinyNumber($fullName) {
    $fullName = strtoupper(normalizeString($fullName));
    $total = 0;
    
    foreach (str_split($fullName) as $letter) {
        if (isset(PYTHAGOREAN_TABLE[$letter])) {
            $total += PYTHAGOREAN_TABLE[$letter];
        }
    }
    
    return reduceToSingleDigit($total);
}

/**
 * Calcular Número del Alma / Impulso del Alma
 * Revela deseos profundos y motivaciones internas
 * Solo usa las VOCALES del nombre
 * 
 * @param string $fullName Nombre completo de nacimiento
 * @return int Número del alma (1-9, 11, 22, 33)
 */
function calculateSoulUrgeNumber($fullName) {
    $fullName = strtoupper(normalizeString($fullName));
    $total = 0;
    
    foreach (str_split($fullName) as $letter) {
        if (in_array($letter, VOWELS) && isset(PYTHAGOREAN_TABLE[$letter])) {
            $total += PYTHAGOREAN_TABLE[$letter];
        }
    }
    
    return reduceToSingleDigit($total);
}

/**
 * Calcular Número de Personalidad
 * Revela la imagen proyectada hacia el exterior
 * Solo usa las CONSONANTES del nombre
 * 
 * @param string $fullName Nombre completo de nacimiento
 * @return int Número de personalidad (1-9, 11, 22, 33)
 */
function calculatePersonalityNumber($fullName) {
    $fullName = strtoupper(normalizeString($fullName));
    $total = 0;
    
    foreach (str_split($fullName) as $letter) {
        if (!in_array($letter, VOWELS) && isset(PYTHAGOREAN_TABLE[$letter])) {
            $total += PYTHAGOREAN_TABLE[$letter];
        }
    }
    
    return reduceToSingleDigit($total);
}

/**
 * Normalizar cadena: quitar acentos y caracteres especiales
 * Mantiene Ñ
 */
function normalizeString($string) {
    // Remover espacios
    $string = str_replace(' ', '', $string);
    
    // Convertir caracteres acentuados
    $replacements = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
        'Ü' => 'U', 'ü' => 'U'
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $string);
}

/**
 * Generar lectura numerológica completa
 * 
 * @param string $fullName Nombre completo de nacimiento
 * @param string $birthDate Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return array|null Array con todos los números o null si error
 */
function generateNumerologyReading($fullName, $birthDate) {
    // Parsear fecha
    $day = null;
    $month = null;
    $year = null;
    
    // Intentar DD/MM/YYYY
    if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $birthDate, $matches)) {
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
    }
    // Intentar YYYY-MM-DD
    elseif (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $birthDate, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
    }
    
    if (!$day || !$month || !$year) {
        return null;
    }
    
    return [
        'life_path' => calculateLifePathNumber($day, $month, $year),
        'destiny' => calculateDestinyNumber($fullName),
        'soul_urge' => calculateSoulUrgeNumber($fullName),
        'personality' => calculatePersonalityNumber($fullName),
        'full_name' => $fullName,
        'birth_date' => sprintf('%02d/%02d/%04d', $day, $month, $year),
        'calculated_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener interpretación breve de un número
 */
function getNumberMeaning($number) {
    $meanings = [
        1 => 'Liderazgo, independencia, pionero',
        2 => 'Cooperación, diplomacia, sensibilidad',
        3 => 'Creatividad, expresión, comunicación',
        4 => 'Estabilidad, trabajo duro, fundamentos',
        5 => 'Libertad, cambio, aventura',
        6 => 'Responsabilidad, armonía, servicio',
        7 => 'Espiritualidad, análisis, introspección',
        8 => 'Poder, abundancia, materialismo',
        9 => 'Humanitarismo, compasión, finalización',
        11 => 'Intuición, iluminación, maestro espiritual',
        22 => 'Maestro constructor, grandes logros',
        33 => 'Maestro sanador, servicio supremo'
    ];
    
    return $meanings[$number] ?? 'Desconocido';
}
?>
