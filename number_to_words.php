<?php
function convertNumberToWords($number) {
    $ones = array(
        0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR',
        5 => 'FIVE', 6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE',
        10 => 'TEN', 11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN',
        14 => 'FOURTEEN', 15 => 'FIFTEEN', 16 => 'SIXTEEN', 17 => 'SEVENTEEN',
        18 => 'EIGHTEEN', 19 => 'NINETEEN'
    );
    
    $tens = array(
        2 => 'TWENTY', 3 => 'THIRTY', 4 => 'FORTY', 5 => 'FIFTY',
        6 => 'SIXTY', 7 => 'SEVENTY', 8 => 'EIGHTY', 9 => 'NINETY'
    );
    
    $number = round($number, 2);
    $parts = explode('.', (string)$number);
    $rupees = (int)$parts[0];
    $paise = isset($parts[1]) ? (int)substr($parts[1] . '00', 0, 2) : 0;
    
    $result = 'RUPEES ';
    
    if ($rupees == 0) {
        $result = 'ZERO';
    } else {
        $result .= convertToWords($rupees, $ones, $tens);
    }
    
    $result .= '';
    
    if ($paise > 0) {
        $result .= ' AND ' . convertToWords($paise, $ones, $tens) . ' PAISE';
    }
    
    $result .= ' ONLY';
    
    return $result;
}

function convertToWords($number, $ones, $tens) {
    if ($number < 20) {
        return $ones[$number];
    } elseif ($number < 100) {
        $ten = floor($number / 10);
        $one = $number % 10;
        return $tens[$ten] . ($one > 0 ? ' ' . $ones[$one] : '');
    } elseif ($number < 1000) {
        $hundred = floor($number / 100);
        $remainder = $number % 100;
        $result = $ones[$hundred] . ' HUNDRED';
        if ($remainder > 0) {
            $result .= ' ' . convertToWords($remainder, $ones, $tens);
        }
        return $result;
    } elseif ($number < 100000) {
        $thousand = floor($number / 1000);
        $remainder = $number % 1000;
        $result = convertToWords($thousand, $ones, $tens) . ' THOUSAND';
        if ($remainder > 0) {
            $result .= ' ' . convertToWords($remainder, $ones, $tens);
        }
        return $result;
    } elseif ($number < 10000000) {
        $lakh = floor($number / 100000);
        $remainder = $number % 100000;
        $result = convertToWords($lakh, $ones, $tens) . ' LAKH';
        if ($remainder > 0) {
            $result .= ' ' . convertToWords($remainder, $ones, $tens);
        }
        return $result;
    } else {
        $crore = floor($number / 10000000);
        $remainder = $number % 10000000;
        $result = convertToWords($crore, $ones, $tens) . ' CRORE';
        if ($remainder > 0) {
            $result .= ' ' . convertToWords($remainder, $ones, $tens);
        }
        return $result;
    }
}
?>