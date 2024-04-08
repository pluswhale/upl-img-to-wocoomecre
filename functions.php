<?php

add_action('init', 'start_session', 1);

function start_session() {
    if (!session_id()) {
    session_start();
    }
}