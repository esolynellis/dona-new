<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

// Fetch the category page HTML and extract sidebar structure
$html = file_get_contents('http://localhost/categories/10');
if (!$html) {
    // Try via actual domain
    $html = @file_get_contents('https://dona-trade.com/categories/10');
}

if (!$html) {
    echo json_encode(['error' => 'Could not fetch page']);
    exit;
}

// Extract left column HTML
preg_match('/<div[^>]*class="[^"]*left-column[^"]*"[^>]*>(.*?)<\/div>\s*<div[^>]*class="[^"]*right-column/si', $html, $m);
$leftCol = $m[1] ?? '';

// Also extract just the sidebar-widget part
preg_match('/<ul[^>]*class="[^"]*sidebar-widget[^"]*"[^>]*>.*?<\/ul>/si', $html, $m2);
$sidebarWidget = $m2[0] ?? '';

// Get left column div opening tag
preg_match('/<div[^>]*class="[^"]*left-column[^"]*"[^>]*>/i', $html, $m3);
$leftColTag = $m3[0] ?? '';

// Check for sticky
$hasSticky = (strpos($html, 'sticky') !== false);
preg_match_all('/sticky[^"\';\s]*/i', $html, $stickyMatches);

// Get all class attributes that contain 'left'
preg_match_all('/class="[^"]*left[^"]*"/i', $html, $leftClasses);

header('Content-Type: application/json');
echo json_encode([
    'left_col_tag' => $leftColTag,
    'has_sticky' => $hasSticky,
    'sticky_matches' => array_unique($stickyMatches[0]),
    'left_classes' => array_unique($leftClasses[0]),
    'left_col_snippet' => substr($leftCol, 0, 500),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
