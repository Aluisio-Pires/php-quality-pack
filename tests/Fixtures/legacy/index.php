<?php

require __DIR__.'/includes/functions.php';

if ($_GET['page'] == 'home') {
    echo render_home();
}
