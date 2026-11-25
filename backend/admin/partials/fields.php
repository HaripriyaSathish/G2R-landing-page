<?php
declare(strict_types=1);

/** Render one form field defined in core/sections.php */
function render_field(array $f, $value, string $prefix = 'data'): string
{
    $type = $f['type'];
    if ($type === 'divider') {
        return '<div class="field-divider"><span>' . e($f['label']) . '</span></div>';
    }
    $key  = $f['key'];
    $name = $prefix . '[' . $key . ']';
    $id   = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $prefix . '_' . $key);
    $req  = !empty($f['required']) ? ' required' : '';
    $ph   = (isset($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '') . (isset($f['count']) ? ' data-count="' . (int) $f['count'] . '"' : '');
    $cls  = 'field' . (!empty($f['half']) ? ' half' : '');
    $help = !empty($f['help']) ? '<small class="help">' . e($f['help']) . '</small>' : '';
    $label = '<label for="' . $id . '">' . e($f['label']) . (!empty($f['required']) ? ' <b class="req">*</b>' : '') . '</label>';

    switch ($type) {
        case 'textarea':
        case 'heading':
            $rows = $f['rows'] ?? ($type === 'heading' ? max(2, substr_count((string) $value, "\n") + 1) : 3);
            $input = '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) $rows . '"' . $req . $ph . ' class="' . ($type === 'heading' ? 'heading-input' : '') . '">' . e($value) . '</textarea>';
            if ($type === 'heading') {
                $input .= '<div class="heading-preview" data-preview-for="' . $id . '"></div>';
            }
            break;

        case 'list':
            $text = is_array($value) ? implode("\n", $value) : (string) $value;
            $input = '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) ($f['rows'] ?? 4) . '"' . $ph . '>' . e($text) . '</textarea>';
            break;

        case 'tags':
            $text = is_array($value) ? implode(', ', $value) : (string) $value;
            $input = '<input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($text) . '" data-tags' . $ph . '>';
            break;

        case 'image':
            $mid = $value ? (int) $value : 0;
            $m   = media_find($mid ?: null);
            $url = $m ? media_url($mid, false) : '';
            $input = '<div class="image-field' . ($m ? ' has-image' : '') . '" data-image-field>'
                . '<input type="hidden" name="' . e($name) . '" value="' . ($m ? $mid : '') . '">'
                . '<div class="image-thumb" data-pick-media>' . ($m ? '<img src="' . e($url) . '" alt="">' : '<span><i data-lucide="image-plus"></i>Choose image</span>') . '</div>'
                . '<div class="image-meta"><strong data-image-name>' . ($m ? e($m['original_name']) : 'No image selected') . '</strong>'
                . '<small data-image-info>' . ($m ? e(($m['width'] ? $m['width'] . '×' . $m['height'] . ' · ' : '') . human_size((int) $m['size'])) : 'JPG, PNG, WEBP, AVIF or SVG') . '</small>'
                . '<div class="image-actions"><button type="button" class="btn btn-sm btn-outline" data-pick-media><i data-lucide="folder-open"></i> ' . ($m ? 'Change' : 'Select') . '</button>'
                . '<button type="button" class="btn btn-sm btn-ghost danger" data-clear-media' . ($m ? '' : ' hidden') . '><i data-lucide="x"></i> Remove</button></div></div></div>';
            break;

        case 'icon':
            $v = (string) $value;
            $input = '<div class="icon-field" data-icon-field><span class="icon-preview" data-pick-icon>' . ($v ? '<i data-lucide="' . e($v) . '"></i>' : '<i data-lucide="plus"></i>') . '</span>'
                . '<input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($v) . '" placeholder="e.g. users" autocomplete="off">'
                . '<button type="button" class="btn btn-sm btn-outline" data-pick-icon>Browse</button></div>';
            break;

        case 'color':
            $v = (string) ($value ?: '#F36B16');
            $input = '<div class="color-field"><input type="color" value="' . e($v) . '" data-color-sync="' . $id . '"><input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($v) . '" maxlength="9"></div>';
            break;

        case 'checkbox':
            return '<div class="' . $cls . '"><label class="switch"><input type="checkbox" name="' . e($name) . '" value="1"' . ($value ? ' checked' : '') . '><span class="slider"></span><span>' . e($f['label']) . '</span></label>' . $help . '</div>';

        case 'select':
            $input = '<select id="' . $id . '" name="' . e($name) . '">';
            foreach ($f['options'] as $ov => $ol) {
                $input .= '<option value="' . e($ov) . '"' . ((string) $value === (string) $ov ? ' selected' : '') . '>' . e($ol) . '</option>';
            }
            $input .= '</select>';
            break;

        case 'number':
            $input = '<input type="number" step="any" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $req . $ph . '>';
            break;

        case 'link':
            $input = '<div class="input-icon"><i data-lucide="link"></i><input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $req . ($ph ?: ' placeholder="#section or https://"') . '></div>';
            break;

        default:
            $input = '<input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $req . $ph . '>';
    }

    return '<div class="' . $cls . '">' . $label . $input . $help . '</div>';
}

function render_fields(array $fields, array $data, string $prefix = 'data'): string
{
    $html = '<div class="form-grid">';
    foreach ($fields as $f) {
        $html .= render_field($f, isset($f['key']) ? ($data[$f['key']] ?? null) : null, $prefix);
    }
    return $html . '</div>';
}
