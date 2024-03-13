<?php
function ViewRestriction()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Redirect('/index.html', true);
    }

    exit();
}

function Redirect($url, $permanent = false)
{
    header('Location: ' . $url, true, $permanent ? 301 : 302);

    exit();
}
