<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli("localhost", "sniveling-driver-sat", "sC5TE3n437N))Ac-ux", "sniveling-driver-sat");
    $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("No se pudo conectar a la base de datos.");
}
