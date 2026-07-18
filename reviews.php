<?php
require_once __DIR__ . '/include/bootstrap.inc.php';

$page = new_page('customers', 'frame-public');
$block = new_block('reviews');

try {
    $stmt = db()->query('
        SELECT r.*, u.first_name, u.last_name, rc.name as category_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN room_categories rc ON r.room_category_id = rc.id
        ORDER BY r.created_at DESC
    ');
    $reviews = $stmt->fetchAll();

    foreach ($reviews as $rev) {
        $block->setContent('review_list.id', (string)$rev['id']);
        $block->setContent('review_list.author', htmlspecialchars($rev['first_name'] . ' ' . substr($rev['last_name'], 0, 1) . '.'));
        $initials = strtoupper(substr(trim($rev['first_name']), 0, 1) . substr(trim($rev['last_name']), 0, 1));
        if ($initials === '') $initials = 'U';
        $block->setContent('review_list.initials', htmlspecialchars($initials));
        $block->setContent('review_list.category_name', htmlspecialchars($rev['category_name'] ?? 'ParadiseResort General'));
        $block->setContent('review_list.date', date('d/m/Y', strtotime($rev['created_at'])));
        $block->setContent('review_list.comment', htmlspecialchars($rev['comment']));
        
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= (int)$rev['rating']) {
                $stars .= '<i class="fas fa-star text-warning me-1"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted me-1"></i>';
            }
        }
        $block->setContent('review_list.stars', $stars);
    }
} catch (Exception $e) {
    // Gestione errore
}

$page->setContent('body', $block->get());
$page->close();
