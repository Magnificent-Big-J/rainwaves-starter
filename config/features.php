<?php

return [

    // RS-106: showcase/demo pages (component catalogue, foundation, about, PayFast
    // browser test) are starter-authoring aids, not something a shipped product
    // should carry in its nav. Default true (dev-friendly); set false in any
    // deployed environment via SHOW_SHOWCASE_PAGES=false. This only controls
    // *navigation and routing exposure* in the SPA — the PayFast inspection/
    // simulation API routes are already hard-removed outside local/testing
    // regardless of this flag (see routes/payfast-local.php).
    'show_showcase_pages' => (bool) env('SHOW_SHOWCASE_PAGES', true),

];
