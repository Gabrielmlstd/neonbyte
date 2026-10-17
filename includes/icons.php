<?php
// Custom icon set (24x24, stroke based). Usage: echo icon('heart');
function icon(string $name, int $size = 0): string {
  static $p = [
    'home' => '<path d="M3 11.5 12 4l9 7.5M5.5 10v9.5h13V10M10 19.5v-5h4v5"/>',
    'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
    'grid' => '<path d="M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z"/>',
    'library' => '<path d="M5 4v16M9.5 4v16M14 6.5l4.2-1 2.3 14-4.2 1z"/>',
    'heart' => '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.4a4.3 4.3 0 0 1 7.5 2.4C19.5 15.4 12 20 12 20z"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'bell' => '<path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0"/>',
    'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-3.6 3.4-5.5 7-5.5s6.4 1.9 7 5.5"/>',
    'chart' => '<path d="M5 20v-7M12 20V5M19 20v-10"/>',
    'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    'settings' => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
    'logout' => '<path d="M10 4H5v16h5M15 8l4 4-4 4M19 12H9"/>',
    'file' => '<path d="M7 3.5h7l4 4V20.5H7zM14 3.5v4h4"/>',
    'flag' => '<path d="M6 21V4M6 4h11l-2 4 2 4H6"/>',
    'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    'star' => '<path d="m12 3.5 2.6 5.5 6 .8-4.4 4.2 1.1 6-5.3-2.9-5.3 2.9 1.1-6-4.4-4.2 6-.8z"/>',
    'bookmark' => '<path d="M7 4h10v16l-5-3.6L7 20z"/>',
    'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>',
    'x' => '<path d="m6 6 12 12M18 6 6 18"/>',
    'chevron-left' => '<path d="m14.5 6-6 6 6 6"/>',
    'chevron-right' => '<path d="m9.5 6 6 6-6 6"/>',
    'chevron-down' => '<path d="m6 9.5 6 6 6-6"/>',
    'arrow-up-right' => '<path d="M7 17 17 7M8.5 7H17v8.5"/>',
    'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'sidebar' => '<path d="M4 5h16v14H4zM9.5 5v14"/>',
    'gamepad' => '<path d="M7 8h10a4 4 0 0 1 4 4.2l-.6 3.3a2.5 2.5 0 0 1-4.3 1.3L14.6 15H9.4l-1.5 1.8a2.5 2.5 0 0 1-4.3-1.3L3 12.2A4 4 0 0 1 7 8zM8 10.5v3M6.5 12h3M15.5 11.5h.01M17.5 13h.01"/>',
    'wrench' => '<path d="M15 5.5a4.2 4.2 0 0 0-5.2 5.4l-5.3 5.3a1.9 1.9 0 0 0 2.7 2.7l5.3-5.3A4.2 4.2 0 0 0 18 8.5l-2.4 2.4-2.5-.5-.5-2.5z"/>',
    'terminal' => '<path d="M4 5h16v14H4zM7.5 10l3 2.5-3 2.5M13 15h3.5"/>',
    'film' => '<path d="M4 5h16v14H4z"/><path d="M10.5 9.5v5l4.2-2.5z"/>',
    'shield' => '<path d="M12 3 19.5 6v5.5c0 4.5-3.200 7.8-7.5 9.500-4.300-1.700-7.500-5-7.500-9.500V6z"/><path d="m9 12 2.200 2.200L15 10.500"/>',
    'chip' => '<rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M9.5 3.5V7M14.5 3.5V7M9.5 17v3.5M14.5 17v3.5M3.5 9.5H7M3.5 14.5H7M17 9.5h3.5M17 14.5h3.5"/>',
    'folder' => '<path d="M3.5 6.5h6l2 2.5h9v9.5h-17z"/>',
    'download' => '<path d="M12 4v11M7.5 11 12 15.5 16.5 11M5 20h14"/>',
  ];
  if (!isset($p[$name])) return '';
  $cls = 'ic ic-'.$name . ($name === 'star' ? ' ic-fill' : '');
  $dim = $size ? ' width="'.$size.'" height="'.$size.'"' : '';
  return '<svg class="'.$cls.'" viewBox="0 0 24 24"'.$dim.' aria-hidden="true" focusable="false">'.$p[$name].'</svg>';
}
function logo_mark(int $size = 32): string {
  return '<svg class="logo-mark" viewBox="0 0 32 32" width="'.$size.'" height="'.$size.'" aria-hidden="true"><path class="bg" d="M2 2h22l6 6v22H2z"/><path class="n" d="M9 23V9l14 14V9"/></svg>';
}
