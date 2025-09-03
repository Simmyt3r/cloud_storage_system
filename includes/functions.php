<?php


/**
 * Gets the ID of the currently logged-in user.
 *
 * @return int|null The user's ID, or null if not logged in.
 */
function get_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

?>