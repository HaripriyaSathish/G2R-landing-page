<?php
/**
 * Section definitions: what each landing-page section contains and how the
 * admin panel renders its form. Every field here is editable in the admin.
 *
 * Field types: text, textarea, heading, image, icon, link, color, tags, list, checkbox, select, number
 *  - heading: text that supports {orange words} and [indigo words] and line breaks
 *  - list: one entry per line
 *  - tags: comma separated
 */

$HEADING_HELP = 'Wrap words in {curly braces} for orange, [square brackets] for indigo. Press Enter for a new line.';

return [

    'header' => [
        'name' => 'Header & Navigation',
        'icon' => 'panel-top',
        'description' => 'Top bar: menu links and the "Get in Touch" button. Logo and company name are in Site Settings.',
        'fields' => [
            ['key' => 'cta_text', 'label' => 'Button text', 'type' => 'text', 'half' => true],
            ['key' => 'cta_link', 'label' => 'Button link', 'type' => 'link', 'half' => true, 'help' => 'Use #contact to scroll to a section, or a full URL.'],
        ],
        'groups' => [
            'nav' => ['label' => 'Menu links', 'singular' => 'Menu link', 'title' => 'label', 'subtitle' => 'link', 'fields' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'link', 'label' => 'Link', 'type' => 'link', 'half' => true, 'help' => 'e.g. #about'],
            ]],
        ],
    ],

    'hero' => [
        'name' => 'Hero Banner',
        'icon' => 'sparkles',
        'description' => 'The first screen visitors see.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label above title', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Main heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'help' => 'Wrap words in **double stars** to make them bold.'],
            ['key' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'half' => true],
            ['key' => 'primary_btn_link', 'label' => 'Primary button link', 'type' => 'link', 'half' => true],
            ['key' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'half' => true],
            ['key' => 'secondary_btn_link', 'label' => 'Secondary button link', 'type' => 'link', 'half' => true],
            ['key' => 'image', 'label' => 'Hero person image', 'type' => 'image', 'help' => 'Transparent PNG cut-out works best.'],
        ],
        'groups' => [
            'floating' => ['label' => 'Floating badges around image', 'singular' => 'Badge', 'title' => 'title', 'subtitle' => 'subtitle', 'icon' => 'icon', 'fields' => [
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text'],
            ]],
            'features' => ['label' => 'Highlight points (below buttons)', 'singular' => 'Highlight', 'title' => 'title', 'subtitle' => 'description', 'icon' => 'icon', 'fields' => [
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
            ]],
        ],
    ],

    'about' => [
        'name' => 'About Us',
        'icon' => 'users',
        'description' => 'Who you are, your expertise and core belief.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 6, 'help' => 'Leave an empty line between paragraphs.'],
            ['key' => 'image', 'label' => 'Person image', 'type' => 'image'],
            ['key' => 'image_tag', 'label' => 'Tag on image', 'type' => 'text', 'half' => true],
            ['key' => 'image_tag_badge', 'label' => 'Tag badge text', 'type' => 'text', 'half' => true],
            ['key' => 'expertise_label', 'label' => 'Expertise heading', 'type' => 'text'],
            ['key' => 'belief_icon', 'label' => 'Core belief icon', 'type' => 'icon', 'half' => true],
            ['key' => 'belief_title', 'label' => 'Core belief title', 'type' => 'text', 'half' => true],
            ['key' => 'belief_text', 'label' => 'Core belief text', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'btn_text', 'label' => 'Button text', 'type' => 'text', 'half' => true],
            ['key' => 'btn_link', 'label' => 'Button link', 'type' => 'link', 'half' => true],
        ],
        'groups' => [
            'expertise' => ['label' => 'Expertise cards', 'singular' => 'Expertise', 'title' => 'title', 'subtitle' => 'description', 'fields' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ]],
        ],
    ],

    'services' => [
        'name' => 'Our Services',
        'icon' => 'briefcase',
        'description' => 'Service cards. Visitors can click a card to see full scope & SLA.',
        'fields' => [
            ['key' => 'title_top', 'label' => 'Heading line 1', 'type' => 'text', 'half' => true],
            ['key' => 'title_bottom', 'label' => 'Heading line 2 (orange)', 'type' => 'text', 'half' => true],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'hint', 'label' => 'Hint text', 'type' => 'text'],
            ['key' => 'bg_image', 'label' => 'Background decoration (globe)', 'type' => 'image'],
        ],
        'groups' => [
            'services' => ['label' => 'Services', 'singular' => 'Service', 'title' => 'title', 'subtitle' => 'description', 'icon' => 'icon', 'fields' => [
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'scope', 'label' => 'Full scope (popup)', 'type' => 'list', 'help' => 'One point per line.'],
                ['key' => 'sla', 'label' => 'SLA / turnaround', 'type' => 'text'],
            ]],
        ],
    ],

    'goals' => [
        'name' => 'Our Goals',
        'icon' => 'target',
        'description' => 'Short, mid and long-term goals.',
        'fields' => [
            ['key' => 'label', 'label' => 'Small heading', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'image', 'label' => 'Centre image (target)', 'type' => 'image'],
        ],
        'groups' => [
            'goals' => ['label' => 'Goals', 'singular' => 'Goal', 'title' => 'title', 'subtitle' => 'description', 'icon' => 'icon', 'fields' => [
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ]],
        ],
    ],

    'process' => [
        'name' => 'Recruitment Process',
        'icon' => 'git-commit-horizontal',
        'description' => 'Step-by-step hiring timeline and call-to-action strip.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'cta_icon', 'label' => 'CTA icon', 'type' => 'icon', 'half' => true],
            ['key' => 'cta_title', 'label' => 'CTA title', 'type' => 'text', 'half' => true],
            ['key' => 'cta_text', 'label' => 'CTA text', 'type' => 'text'],
            ['key' => 'cta_btn_text', 'label' => 'CTA button text', 'type' => 'text', 'half' => true],
            ['key' => 'cta_btn_link', 'label' => 'CTA button link', 'type' => 'link', 'half' => true],
        ],
        'groups' => [
            'stages' => ['label' => 'Stages', 'singular' => 'Stage', 'title' => 'title', 'subtitle' => 'stage_label', 'icon' => 'icon', 'fields' => [
                ['key' => 'stage_label', 'label' => 'Stage label', 'type' => 'text', 'half' => true, 'placeholder' => 'STAGE 01'],
                ['key' => 'color', 'label' => 'Gradient start colour', 'type' => 'color', 'half' => true],
                ['key' => 'color2', 'label' => 'Gradient end colour', 'type' => 'color', 'half' => true],
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ]],
        ],
    ],

    'industries' => [
        'name' => 'Industries We Serve',
        'icon' => 'factory',
        'description' => 'Industry practice areas with images and tags.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'bg_image', 'label' => 'Background globe / world map', 'type' => 'image', 'help' => 'Large faint image behind the industries list.'],
            ['key' => 'cta_label', 'label' => 'CTA small label', 'type' => 'text'],
            ['key' => 'cta_title', 'label' => 'CTA title', 'type' => 'text'],
            ['key' => 'cta_text', 'label' => 'CTA text', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'cta_btn_text', 'label' => 'CTA button text', 'type' => 'text', 'half' => true],
            ['key' => 'cta_btn_link', 'label' => 'CTA button link', 'type' => 'link', 'half' => true],
        ],
        'groups' => [
            'industries' => ['label' => 'Industries', 'singular' => 'Industry', 'title' => 'title', 'subtitle' => 'practice_label', 'image' => 'image', 'fields' => [
                ['key' => 'practice_label', 'label' => 'Practice label', 'type' => 'text', 'half' => true, 'placeholder' => 'PRACTICE 01'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'tags', 'label' => 'Tags', 'type' => 'tags', 'help' => 'Comma separated, e.g. Cloud & DevOps, Cybersecurity'],
                ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
            ]],
        ],
    ],

    'training' => [
        'name' => 'Training & Development',
        'icon' => 'graduation-cap',
        'description' => 'Bento grid with stats, featured programme and images.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['type' => 'divider', 'label' => 'Stat card 1 (top left)'],
            ['key' => 'stat1_intro', 'label' => 'Intro text', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'stat1_value', 'label' => 'Value', 'type' => 'text', 'half' => true, 'placeholder' => '98.5%'],
            ['key' => 'stat1_label', 'label' => 'Label', 'type' => 'text', 'half' => true],
            ['key' => 'stat1_text', 'label' => 'Small text', 'type' => 'textarea', 'rows' => 2],
            ['type' => 'divider', 'label' => 'Featured programme (centre)'],
            ['key' => 'feature_image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'feature_badge', 'label' => 'Badge', 'type' => 'text', 'half' => true],
            ['key' => 'feature_rating', 'label' => 'Rating', 'type' => 'text', 'half' => true, 'placeholder' => '4.9 / 5'],
            ['key' => 'feature_title', 'label' => 'Title', 'type' => 'text'],
            ['key' => 'feature_text', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'feature_footer', 'label' => 'Footer left text', 'type' => 'text', 'half' => true],
            ['key' => 'feature_footer_highlight', 'label' => 'Footer right (orange)', 'type' => 'text', 'half' => true],
            ['type' => 'divider', 'label' => 'Image card (top right)'],
            ['key' => 'card2_image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'card2_caption', 'label' => 'Caption', 'type' => 'text'],
            ['type' => 'divider', 'label' => 'Image card (bottom left)'],
            ['key' => 'card3_image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'card3_label', 'label' => 'Small label', 'type' => 'text', 'half' => true],
            ['key' => 'card3_title', 'label' => 'Title', 'type' => 'text', 'half' => true],
            ['type' => 'divider', 'label' => 'Stat card 2 (bottom right)'],
            ['key' => 'stat2_value', 'label' => 'Value', 'type' => 'text', 'half' => true, 'placeholder' => '250+'],
            ['key' => 'stat2_label', 'label' => 'Label', 'type' => 'text', 'half' => true],
            ['key' => 'stat2_text', 'label' => 'Text', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'stat2_link_text', 'label' => 'Link text', 'type' => 'text', 'half' => true],
            ['key' => 'stat2_link', 'label' => 'Link', 'type' => 'link', 'half' => true],
            ['key' => 'stat2_tag', 'label' => 'Tag', 'type' => 'text', 'half' => true],
        ],
        'groups' => [],
    ],

    'careers' => [
        'name' => 'Careers Banner',
        'icon' => 'rocket',
        'description' => 'Career call-to-action banner.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'image', 'label' => 'Person image', 'type' => 'image'],
            ['key' => 'btn_text', 'label' => 'Button text', 'type' => 'text', 'half' => true],
            ['key' => 'btn_link', 'label' => 'Button link', 'type' => 'link', 'half' => true],
        ],
        'groups' => [],
    ],

    'testimonials' => [
        'name' => 'Client Testimonials',
        'icon' => 'message-square-quote',
        'description' => 'Slider of client testimonial cards (partner logo, photo, endorsement and quote).',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'autoplay', 'label' => 'Auto-slide every 6 seconds', 'type' => 'checkbox'],
        ],
        'groups' => [
            'testimonials' => ['label' => 'Testimonials', 'singular' => 'Testimonial', 'title' => 'name', 'subtitle' => 'role', 'image' => 'photo', 'fields' => [
                ['key' => 'logo', 'label' => 'Partner / company logo', 'type' => 'image', 'half' => true],
                ['key' => 'photo', 'label' => 'Person photo', 'type' => 'image', 'half' => true],
                ['key' => 'story_label', 'label' => 'Story label', 'type' => 'text', 'half' => true, 'placeholder' => 'G2R Partner Story'],
                ['key' => 'heading_top', 'label' => 'Heading line 1 (italic)', 'type' => 'text', 'half' => true, 'placeholder' => 'Client'],
                ['key' => 'heading_bottom', 'label' => 'Heading line 2', 'type' => 'text', 'half' => true, 'placeholder' => 'TESTIMONIAL'],
                ['key' => 'badge', 'label' => 'Endorsement badge', 'type' => 'text', 'half' => true, 'placeholder' => 'Verified Corporate Endorsement'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'name', 'label' => 'Person name', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'role', 'label' => 'Role & company', 'type' => 'text', 'half' => true],
                ['key' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'rows' => 4],
                ['key' => 'rating', 'label' => 'Star rating (1-5)', 'type' => 'number', 'half' => true],
            ]],
        ],
    ],

    'partners' => [
        'name' => 'Trusted Partners',
        'icon' => 'handshake',
        'description' => 'Partner logos shown in a scrolling strip.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
        ],
        'groups' => [
            'partners' => ['label' => 'Partners', 'singular' => 'Partner', 'title' => 'name', 'subtitle' => 'website', 'image' => 'logo', 'fields' => [
                ['key' => 'name', 'label' => 'Company name', 'type' => 'text', 'required' => true],
                ['key' => 'logo', 'label' => 'Logo', 'type' => 'image'],
                ['key' => 'website', 'label' => 'Website (optional)', 'type' => 'link'],
            ]],
        ],
    ],

    'states' => [
        'name' => 'Manpower Supporting States',
        'icon' => 'map',
        'description' => 'Regional hubs and states you support.',
        'fields' => [
            ['key' => 'badge', 'label' => 'Small label', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['type' => 'divider', 'label' => 'Pan-India strip'],
            ['key' => 'pan_label', 'label' => 'Label', 'type' => 'text', 'half' => true],
            ['key' => 'pan_states', 'label' => 'States', 'type' => 'tags', 'half' => true],
            ['key' => 'pan_note', 'label' => 'Note', 'type' => 'text'],
            ['key' => 'pan_btn_text', 'label' => 'Button text', 'type' => 'text', 'half' => true],
            ['key' => 'pan_btn_link', 'label' => 'Button link', 'type' => 'link', 'half' => true],
        ],
        'groups' => [
            'hubs' => ['label' => 'Regional hubs', 'singular' => 'Hub', 'title' => 'name', 'subtitle' => 'subtitle', 'icon' => 'icon', 'fields' => [
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'half' => true],
                ['key' => 'name', 'label' => 'Hub name', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'half' => true, 'placeholder' => '5 STATES SUPPORTED'],
                ['key' => 'status', 'label' => 'Status badge', 'type' => 'text', 'half' => true, 'placeholder' => 'Active'],
                ['key' => 'states', 'label' => 'States', 'type' => 'list', 'help' => 'One per line as  State | Tag  (e.g. Tamil Nadu | Headquarters)'],
                ['key' => 'note', 'label' => 'Note', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'highlight', 'label' => 'Highlight this card (orange border)', 'type' => 'checkbox'],
            ]],
        ],
    ],

    'contact' => [
        'name' => 'Contact Us',
        'icon' => 'mail',
        'description' => 'Contact form and info. Address / phone / email come from Site Settings.',
        'fields' => [
            ['key' => 'title', 'label' => 'Heading', 'type' => 'heading', 'help' => $HEADING_HELP],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'image', 'label' => 'Main image', 'type' => 'image'],
            ['key' => 'image_badge', 'label' => 'Badge on image', 'type' => 'text'],
            ['key' => 'small_image', 'label' => 'Small image', 'type' => 'image'],
            ['key' => 'sla_label', 'label' => 'Speed label', 'type' => 'text', 'half' => true],
            ['key' => 'sla_value', 'label' => 'Speed value', 'type' => 'text', 'half' => true],
            ['key' => 'enquiry_types', 'label' => 'Enquiry type options', 'type' => 'list', 'help' => 'One option per line (dropdown in form).'],
            ['key' => 'submit_text', 'label' => 'Submit button text', 'type' => 'text', 'half' => true],
            ['key' => 'success_message', 'label' => 'Success message', 'type' => 'text', 'half' => true],
            ['key' => 'office_label', 'label' => 'Office card label', 'type' => 'text', 'half' => true],
            ['key' => 'direct_label', 'label' => 'Contact card label', 'type' => 'text', 'half' => true],
            ['key' => 'social_label', 'label' => 'Social card label', 'type' => 'text', 'half' => true],
        ],
        'groups' => [],
    ],

    'legal' => [
        'name' => 'Privacy Policy & Terms',
        'icon' => 'scale',
        'description' => 'Content shown in the Privacy Policy and Terms of Service popups (opened from the footer links).',
        'fields' => [
            ['key' => 'updated', 'label' => 'Last updated date', 'type' => 'text', 'placeholder' => '24 November 2026'],
            ['type' => 'divider', 'label' => 'Privacy Policy'],
            ['key' => 'privacy_title', 'label' => 'Popup title', 'type' => 'text'],
            ['key' => 'privacy_content', 'label' => 'Content', 'type' => 'textarea', 'rows' => 16, 'help' => 'Start a line with ## for a heading, - for a bullet point. Leave an empty line between paragraphs. **bold** works too.'],
            ['type' => 'divider', 'label' => 'Terms of Service'],
            ['key' => 'terms_title', 'label' => 'Popup title', 'type' => 'text'],
            ['key' => 'terms_content', 'label' => 'Content', 'type' => 'textarea', 'rows' => 16, 'help' => 'Start a line with ## for a heading, - for a bullet point. Leave an empty line between paragraphs. **bold** works too.'],
        ],
        'groups' => [],
    ],

    'footer' => [
        'name' => 'Footer',
        'icon' => 'panel-bottom',
        'description' => 'Footer columns. Navigation column reuses the header menu links.',
        'fields' => [
            ['key' => 'description', 'label' => 'About text', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'nav_title', 'label' => 'Navigation column title', 'type' => 'text', 'half' => true],
            ['key' => 'services_title', 'label' => 'Services column title', 'type' => 'text', 'half' => true],
            ['key' => 'contact_title', 'label' => 'Contact column title', 'type' => 'text', 'half' => true],
            ['key' => 'copyright', 'label' => 'Copyright text', 'type' => 'text', 'half' => true, 'help' => '{year} is replaced with the current year.'],
            ['key' => 'privacy_text', 'label' => 'Privacy link text', 'type' => 'text', 'half' => true],
            ['key' => 'privacy_link', 'label' => 'Privacy link', 'type' => 'link', 'half' => true, 'help' => 'Keep #privacy to open the popup (edit its text in Privacy Policy & Terms).'],
            ['key' => 'terms_text', 'label' => 'Terms link text', 'type' => 'text', 'half' => true],
            ['key' => 'terms_link', 'label' => 'Terms link', 'type' => 'link', 'half' => true, 'help' => 'Keep #terms to open the popup.'],
            ['key' => 'credit_text', 'label' => 'Credit text', 'type' => 'text', 'half' => true, 'placeholder' => 'Designed & Developed by'],
            ['key' => 'credit_name', 'label' => 'Credit company name', 'type' => 'text', 'half' => true],
            ['key' => 'credit_link', 'label' => 'Credit link', 'type' => 'link'],
        ],
        'groups' => [
            'nav_links' => ['label' => 'Navigation column links (empty = use header menu)', 'singular' => 'Link', 'title' => 'label', 'subtitle' => 'link', 'fields' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'link', 'label' => 'Link', 'type' => 'link', 'half' => true],
            ]],
            'service_links' => ['label' => 'Services column links', 'singular' => 'Link', 'title' => 'label', 'subtitle' => 'link', 'fields' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'half' => true],
                ['key' => 'link', 'label' => 'Link', 'type' => 'link', 'half' => true],
            ]],
        ],
    ],
];
