<?php
// Public read-only proxy. Bootstrap WordPress so credentials use the same root .env.
require_once dirname(__DIR__, 3) . '/wp-load.php';
dornott_cdek_widget_response();
