<?php
// =============================================================================
// includes/functions.php — Funksione ndihmëse
// =============================================================================

/**
 * Redirect me header
 */
function redirect(string $url): never {
    header("Location: {$url}");
    exit;
}

// --- Flash Messages ---
function flash_set(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_success(string $msg): void { flash_set('success', $msg); }
function flash_error(string $msg): void   { flash_set('error', $msg); }
function flash_info(string $msg): void    { flash_set('info', $msg); }
function flash_warning(string $msg): void { flash_set('warning', $msg); }

function flash_render(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $flash) {
        $type = e($flash['type']);
        $msg  = e($flash['message']);
        $icons = [
            'success' => '✓', 'error' => 'X',
            'info'    => 'ℹ', 'warning' => '⚠'
        ];
        $icon = $icons[$type] ?? 'ℹ';
        $html .= "<div class=\"flash flash--{$type}\">
                    <span class=\"flash__icon\">{$icon}</span>
                    <span>{$msg}</span>
                    <button class=\"flash__close\" onclick=\"this.parentElement.remove()\">×</button>
                  </div>";
    }
    unset($_SESSION['flash']);
    return $html;
}

// --- Formatting ---
function format_price(float $price, string $period = 'total'): string {
    $formatted = number_format($price, 0, ',', '.');
    if ($period === 'monthly') return "€{$formatted}/muaj";
    if ($period === 'yearly')  return "€{$formatted}/vit";
    return "€{$formatted}";
}

function format_area(float $area): string {
    return number_format($area, 0, ',', '.') . ' m²';
}

function format_date(string $date, string $format = 'd/m/Y'): string {
    return date($format, strtotime($date));
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)       return 'Tani';
    if ($diff < 3600)     return (int)($diff/60) . ' min më parë';
    if ($diff < 86400)    return (int)($diff/3600) . ' orë më parë';
    if ($diff < 2592000)  return (int)($diff/86400) . ' ditë më parë';
    if ($diff < 31536000) return (int)($diff/2592000) . ' muaj më parë';
    return (int)($diff/31536000) . ' vit më parë';
}

function property_type_label(string $type): string {
    $map = [
        'apartment'  => 'Apartament',
        'house'      => 'Shtëpi',
        'villa'      => 'Vilë',
        'commercial' => 'Komerciale',
        'office'     => 'Zyrë',
        'land'       => 'Truall',
        'garage'     => 'Garazh',
    ];
    return $map[$type] ?? ucfirst($type);
}

function property_status_label(string $status): string {
    $map = [
        'for_sale' => 'Për Shitje',
        'for_rent' => 'Me Qira',
        'sold'     => 'Shitur',
        'rented'   => 'Me Qira (Zënë)',
    ];
    return $map[$status] ?? $status;
}

function role_label(string $role): string {
    $map = [
        'admin'  => 'Administrator',
        'agent'  => 'Agjent',
        'owner'  => 'Pronar',
        'client' => 'Klient',
    ];
    return $map[$role] ?? ucfirst($role);
}

function gender_label(?string $gender): string {
    $map = [
        'female' => 'Femer',
        'male' => 'Mashkull',
        'other' => 'Tjeter',
        'unspecified' => 'E pacaktuar',
    ];
    return $map[$gender ?: 'unspecified'] ?? 'E pacaktuar';
}

function appointment_status_label(string $status): string {
    $map = [
        'pending'   => 'Në Pritje',
        'confirmed' => 'Konfirmuar',
        'cancelled' => 'Anuluar',
        'completed' => 'Kryer',
    ];
    return $map[$status] ?? $status;
}

// --- Images ---
function get_property_primary_image(int $property_id, string $size = 'medium'): string {
    $img = db_row(
        "SELECT filename FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1",
        [$property_id]
    );
    if ($img) {
        $path = SITE_URL . '/uploads/properties/' . $img['filename'];
        return $path;
    }
    // Kthe imazh placeholder sipas tipeve me ngjyra gradiente unike
    return SITE_URL . '/assets/images/property-placeholder.svg';
}

function get_property_images(int $property_id): array {
    return db_rows(
        "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, sort_order ASC",
        [$property_id]
    );
}

function get_avatar_url(?string $avatar): string {
    if ($avatar && file_exists(UPLOAD_BASE_DIR . 'avatars/' . $avatar)) {
        return SITE_URL . '/uploads/avatars/' . $avatar;
    }
    return SITE_URL . '/assets/images/default-avatar.svg';
}

// --- Pagination ---
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current_page,
        'total_pages' => $total_pages,
        'offset'      => $offset,
        'has_prev'    => $current_page > 1,
        'has_next'    => $current_page < $total_pages,
        'prev'        => $current_page - 1,
        'next'        => $current_page + 1,
    ];
}

function render_pagination(array $p, string $base_url): string {
    if ($p['total_pages'] <= 1) return '';
    $html = '<div class="pagination">';
    if ($p['has_prev']) {
        $html .= "<a href=\"{$base_url}&page={$p['prev']}\" class=\"pagination__btn\">Para</a>";
    }
    for ($i = max(1, $p['current'] - 2); $i <= min($p['total_pages'], $p['current'] + 2); $i++) {
        $active = $i === $p['current'] ? ' pagination__btn--active' : '';
        $html .= "<a href=\"{$base_url}&page={$i}\" class=\"pagination__btn{$active}\">{$i}</a>";
    }
    if ($p['has_next']) {
        $html .= "<a href=\"{$base_url}&page={$p['next']}\" class=\"pagination__btn\">Pas</a>";
    }
    $html .= '</div>';
    return $html;
}

// --- Activity Log ---
function log_activity(?int $user_id, string $action, string $description = '', string $ip = ''): void {
    try {
        db_query(
            "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$user_id, $action, $description, $ip ?: get_client_ip(),
             substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
        );
    } catch (Exception $e) {
        // Mos ndalo ekzekutimin për shkak të log errors
    }
}

// --- Stars Rating ---
function render_stars(float $rating, bool $readonly = true): string {
    $html = '<div class="stars' . ($readonly ? '' : ' stars--interactive') . '">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'star star--filled' : 'star';
        $html .= "<span class=\"{$class}\">★</span>";
    }
    $html .= '</div>';
    return $html;
}

// --- Slug ---
function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $map  = ['ë' => 'e', 'ç' => 'c', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'é' => 'e'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// --- File helpers ---
function human_filesize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// --- Stats cache (i thjeshtë, pa cache layer) ---
function get_site_stats(): array {
    return [
        'total_properties' => db_count("SELECT COUNT(*) FROM properties WHERE is_active = 1 AND approval_status='approved'"),
        'total_agents'     => db_count("SELECT COUNT(*) FROM users WHERE role = 'agent' AND is_active = 1"),
        'total_clients'    => db_count("SELECT COUNT(*) FROM users WHERE role IN ('client','owner') AND is_active = 1"),
        'sold_rented'      => db_count("SELECT COUNT(*) FROM properties WHERE status IN ('sold','rented')"),
    ];
}

// --- Property Card HTML (reused across pages) ---
function render_property_card(array $p, bool $show_actions = false): string {
    $img     = get_property_primary_image((int)$p['id']);
    $price   = format_price((float)$p['price'], $p['price_period']);
    $type    = property_type_label($p['type']);
    $status  = property_status_label($p['status']);
    $scode   = $p['status'] === 'for_sale' ? 'sale' : ($p['status'] === 'for_rent' ? 'rent' : 'other');
    $area    = $p['area'] ? format_area((float)$p['area']) : '-';
    $rooms   = $p['rooms'] ? (int)$p['rooms'] . ' dhoma' : '';
    $city    = e($p['city']);
    $neigh   = $p['neighborhood'] ? ', ' . e($p['neighborhood']) : '';
    $title   = e($p['title']);
    $id      = (int)$p['id'];
    $site    = SITE_URL;
    $placeholder = SITE_URL . '/assets/images/property-placeholder.svg';

    $rooms_html = $rooms ? "<span>{$rooms}</span>" : '';
    $area_html  = $area  ? "<span>{$area}</span>"  : '';

    return <<<HTML
    <div class="property-card reveal" data-id="{$id}">
      <div class="property-card__inner">
        <a href="{$site}/property.php?id={$id}" class="property-card__img-wrap">
          <img src="{$img}" alt="{$title}" loading="lazy" onerror="this.src='{$placeholder}'">
          <span class="badge badge--{$scode}">{$status}</span>
          <button class="btn-fav" data-id="{$id}" title="Ruaj si favorite" onclick="toggleFav(event,{$id})">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          </button>
        </a>
        <div class="property-card__body">
          <div class="property-card__price">{$price}</div>
          <h3 class="property-card__title"><a href="{$site}/property.php?id={$id}">{$title}</a></h3>
          <div class="property-card__location">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:3px;opacity:.6"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{$city}{$neigh}
          </div>
          <div class="property-card__meta">
            <span class="meta-tag">{$type}</span>
            {$rooms_html}
            {$area_html}
          </div>
        </div>
      </div>
    </div>
HTML;
}
