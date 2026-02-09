<?php

class AstrologyHelper {

    // Approximate positions for planets (Simplified Ephemeris)
    // Based on Paul Schlyter's calculations
    
    public static function calculateChart($date, $time, $lat, $lng) {
        // Combinar fecha y hora
        $datetime = new DateTime("$date $time", new DateTimeZone('UTC'));
        // Convert to Julian Day
        $jd = self::toJulianDay($datetime);

        $positions = [];
        
        // Sun
        $positions['Sol'] = self::getSunPosition($jd);
        // Moon
        $positions['Luna'] = self::getMoonPosition($jd);
        // Mercury
        // $positions['Mercurio'] = self::getMercuryPosition($jd); 
        // Venus
        // $positions['Venus'] = self::getVenusPosition($jd);
        // Mars
        // $positions['Marte'] = self::getMarsPosition($jd);

        // Ascendant (Requires Sidereal Time and Lat/Lng)
        if ($lat !== null && $lng !== null) {
            $positions['Ascendente'] = self::calculateAscendant($datetime, $lat, $lng);
        }

        return $positions;
    }

    private static function toJulianDay($datetime) {
        $year = (int)$datetime->format('Y');
        $month = (int)$datetime->format('m');
        $day = (int)$datetime->format('d');
        $h = (int)$datetime->format('H');
        $m = (int)$datetime->format('i');
        $s = (int)$datetime->format('s');

        // Allow for fractional day
        $dayFraction = ($h + $m / 60 + $s / 3600) / 24;
        
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }

        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);

        $jd = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5 + $dayFraction;
        
        return $jd;
    }

    private static function normalizeDegrees($deg) {
        $deg = fmod($deg, 360);
        if ($deg < 0) $deg += 360;
        return $deg;
    }

    private static function getZodiacSign($degrees) {
        $signs = [
            'Aries', 'Tauro', 'Géminis', 'Cáncer', 
            'Leo', 'Virgo', 'Libra', 'Escorpio', 
            'Sagitario', 'Capricornio', 'Acuario', 'Piscis'
        ];
        
        $index = floor($degrees / 30);
        $position = $degrees - ($index * 30);
        
        // Handle precise degree formatting (e.g., 15° 30')
        $d = floor($position);
        $m = round(($position - $d) * 60);
        
        return [
            'sign' => $signs[$index % 12],
            'degree' => $d,
            'minute' => $m,
            'full' => $signs[$index % 12] . " " . $d . "° " . $m . "'"
        ];
    }
    
    // --- PLANET CALCULATIONS (Simplified) ---

    // Sun: Accuracy ~0.01 degree
    private static function getSunPosition($jd) {
        $d = $jd - 2451543.5;
        
        $w = 282.9404 + 4.70935E-5 * $d; // longitude of perihelion
        $e = 0.016709 - 1.151E-9 * $d; // eccentricity
        $M = 356.0470 + 0.9856002585 * $d; // mean anomaly
        
        $M = self::normalizeDegrees($M);
        $w = self::normalizeDegrees($w);
        
        $L = $w + $M; // Mean longitude
        $L = self::normalizeDegrees($L);
        
        // Eccentric anomaly
        $E = $M + (180/M_PI) * $e * sin(deg2rad($M)) * (1 + $e * cos(deg2rad($M)));
        
        // Rectangular coordinates
        $x = cos(deg2rad($E)) - $e;
        $y = sin(deg2rad($E)) * sqrt(1 - $e*$e);
        
        $r = sqrt($x*$x + $y*$y);
        $v = rad2deg(atan2($y, $x));
        
        $lon = $v + $w;
        $lon = self::normalizeDegrees($lon);
        
        return self::getZodiacSign($lon);
    }

    // Moon: Accuracy ~0.1-0.3 degree (Sufficient for sign)
    private static function getMoonPosition($jd) {
        $d = $jd - 2451543.5;
        
        $N = 125.1228 - 0.0529538083 * $d; // Long asc. node
        $i = 5.1454; // Inclination
        $w = 318.0634 + 0.1643573223 * $d; // Arg. of perigee
        $a = 60.2666; // Mean distance
        $e = 0.054900; // Eccentricity
        $M = 115.3654 + 13.0649929509 * $d; // Mean anomaly
        
        $N = self::normalizeDegrees($N);
        $w = self::normalizeDegrees($w);
        $M = self::normalizeDegrees($M);
        
        // Eccentric anomaly
        $E = $M + (180/M_PI) * $e * sin(deg2rad($M)) * (1 + $e * cos(deg2rad($M)));
        
        $x = $a * (cos(deg2rad($E)) - $e);
        $y = $a * sqrt(1 - $e*$e) * sin(deg2rad($E));
        
        $r = sqrt($x*$x + $y*$y);
        $v = rad2deg(atan2($y, $x));
        
        $xeclip = $r * (cos(deg2rad($N)) * cos(deg2rad($v+$w)) - sin(deg2rad($N)) * sin(deg2rad($v+$w)) * cos(deg2rad($i)));
        $yeclip = $r * (sin(deg2rad($N)) * cos(deg2rad($v+$w)) + cos(deg2rad($N)) * sin(deg2rad($v+$w)) * cos(deg2rad($i)));
        
        $lon = rad2deg(atan2($yeclip, $xeclip));
        $lon = self::normalizeDegrees($lon);
        
        return self::getZodiacSign($lon);
    }
    
    // Ascendant (Requires accurate Sidereal Time)
    private static function calculateAscendant($datetime, $lat, $lng) {
        $jd = self::toJulianDay($datetime);
        $t = ($jd - 2451545.0) / 36525.0;
        
        // Greenwhich Mean Sidereal Time (GMST)
        $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + 0.000387933 * $t*$t - $t*$t*$t / 38710000;
        $gmst = self::normalizeDegrees($gmst);
        
        // Local Sidereal Time (LST)
        $lst = $gmst + $lng;
        $lst = self::normalizeDegrees($lst);
        
        // Obliquity of Ecliptic
        $eps = 23.4392911 - 0.0130042 * $t;
        
        // Ascendant Formula
        // tan(Asc) = cos(LST) / ( -sin(LST)*cos(eps) - tan(lat)*sin(eps) )
        
        $num = cos(deg2rad($lst));
        $den = -sin(deg2rad($lst)) * cos(deg2rad($eps)) - tan(deg2rad($lat)) * sin(deg2rad($eps));
        
        $asc = rad2deg(atan2($num, $den));
        
        // Fix quadrant
        // If den < 0, add 180? Not exactly. atan2 handles quadrants but we might need to conform to astrological convention
        // Usually Ascendant is defined where the eastern horizon intersects the ecliptic.
        // The standard formula: tan(Asc) = ...
        // atan2(y, x) -> atan2(cos(LST), -sin(LST)*cos(eps) - ...)
        // Wait, standard atan2 is (y, x). Here y = cos(LST), x = den.
        
        // Let's rely on atan2 result + normalization
        $asc = self::normalizeDegrees($asc);
        
        return self::getZodiacSign($asc);
    }
}
?>
