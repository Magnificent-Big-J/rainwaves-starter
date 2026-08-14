<?php

return [

    // Bumping a document's version here is how a real content change (privacy.vue/
    // terms.vue) triggers re-acceptance — no CMS, the static pages stay static.
    'legal_versions' => [
        'terms' => 1,
        'privacy' => 1,
    ],

];
