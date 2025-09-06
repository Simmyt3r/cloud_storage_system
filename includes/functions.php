<?php



function get_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

?>