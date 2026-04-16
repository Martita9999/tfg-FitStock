<?php
    require "config.php";

    session_unset();
    session_destroy();

    echo json_encode(["message" => "Sesión cerrada correctamente"]);
?>
