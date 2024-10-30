<?php

return [
  'registered_hooks' => [
    "post" => [
      'untrashed_post' => ["id"],
      "save_post_post" => [
        "id",
        "post",
        "update"
      ],
    ],
    "comment" => [
      'deleted_comment' => ["id"],
      'trashed_comment' => ["id"],
      'untrashed_comment' => ["id"],
      "wp_insert_comment" => [
        "id",
        "comment"
      ],
    ],
    "page" => [
      'untrashed_post' => ["id"],
      "save_post_page" => [
        "id",
        "post",
        "update"
      ],
    ],
    "attachment" => [
      'add_attachment' => ["id"],
      'delete_attachment' => ["id"],
    ],
  ]
];