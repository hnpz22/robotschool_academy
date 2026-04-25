<?php
// includes/ec_helpers.php
// Helpers reutilizables para el modulo de extracurriculares

/**
 * Calcula distancia en km entre dos puntos geograficos usando formula haversine.
 * Devuelve null si cualquier coordenada esta vacia.
 *
 * @param float|null $lat1 Latitud punto 1
 * @param float|null $lng1 Longitud punto 1
 * @param float|null $lat2 Latitud punto 2
 * @param float|null $lng2 Longitud punto 2
 * @return float|null Distancia en km en linea recta
 */
function ec_haversine_km($lat1, $lng1, $lat2, $lng2) {
    if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) return null;
    if ($lat1 == 0 && $lng1 == 0) return null;
    if ($lat2 == 0 && $lng2 == 0) return null;

    $R = 6371; // Radio de la Tierra en km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

/**
 * Estima distancia real por calles aplicando factor 1.4 a la haversine.
 * (en linea recta vs ruta vial)
 */
function ec_distancia_vial_km($lat1, $lng1, $lat2, $lng2) {
    $km = ec_haversine_km($lat1, $lng1, $lat2, $lng2);
    return $km === null ? null : $km * 1.4;
}

/**
 * Mapa de meses en espaniol para labels del calendario
 */
function ec_nombre_mes($n) {
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
              7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    return $meses[$n] ?? '';
}

/**
 * Nombre corto de dia semana 1=Lunes..7=Domingo
 */
function ec_nombre_dia($n) {
    $dias = [1=>'Lun',2=>'Mar',3=>'Mi&eacute;',4=>'Jue',5=>'Vie',6=>'S&aacute;b',7=>'Dom'];
    return $dias[$n] ?? '';
}
