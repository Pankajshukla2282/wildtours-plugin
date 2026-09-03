<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

use PWT\PostTypes\Contracts\PostTypeInterface;

/**
 * Base class for all plugin custom post types.
 */
abstract class PostType implements PostTypeInterface
{
    /**
     * Post type slug.
     */
    protected string $postType = '';

    /**
     * Singular label.
     */
    protected string $singular = '';

    /**
     * Plural label.
     */
    protected string $plural = '';

    /**
     * Supported editor features.
     *
     * @var string[]
     */
    protected array $supports = [
        'title',
        'editor',
        'thumbnail',
        'excerpt',
    ];

    /**
     * Taxonomies attached to this post type.
     *
     * @var string[]
     */
    protected array $taxonomies = [];

    /**
     * Menu icon.
     */
    protected string $menuIcon = 'dashicons-admin-post';

    /**
     * Menu position.
     */
    protected int $menuPosition = 20;

    /**
     * Whether the post type is public.
     */
    protected bool $public = true;

    /**
     * Whether REST API is enabled.
     */
    protected bool $showInRest = true;

    /**
     * Whether archives are enabled.
     */
    protected bool $hasArchive = true;

    /**
     * Rewrite slug.
     */
    protected ?string $rewriteSlug = null;

    /**
     * Capability type.
     */
    protected string $capabilityType = 'post';

    /**
     * Register the post type.
     */
    public function register(): void
    {
        // Providers are booted during WordPress init. Register immediately when
        // init has already fired; otherwise wait for init. This prevents CPTs
        // from silently missing registration on admin requests.
        if (did_action('init')) {
            $this->create();
            return;
        }

        add_action('init', [$this, 'create'], 5);
    }

    /**
     * Create the post type.
     */
    final public function create(): void
    {
        $this->validate();

        register_post_type(
            $this->postType,
            apply_filters(
                "pwt/post_type_args/{$this->postType}",
                $this->args()
            )
        );
    }

    /**
     * Validate required configuration.
     */
    protected function validate(): void
    {
        if (
            $this->postType === ''
            || $this->singular === ''
            || $this->plural === ''
        ) {
            _doing_it_wrong(
                static::class,
                __('Post type configuration is incomplete.', 'wildtours-plugin'),
                PWT_VERSION
            );
        }
    }

    /**
     * Build post type arguments.
     */
    protected function args(): array
    {
        return [
            'labels'          => $this->labels(),
            'public'          => $this->public,
            'show_in_rest'    => $this->showInRest,
            'show_in_menu'    => false,
            'menu_position'   => $this->menuPosition,
            'menu_icon'       => $this->menuIcon,
            'supports'        => apply_filters(
                "pwt/post_type_supports/{$this->postType}",
                $this->supports
            ),
            'taxonomies'      => $this->taxonomies,
            'has_archive'     => $this->hasArchive,
            'capability_type' => $this->capabilityType,
            'rewrite'         => [
                'slug' => $this->rewriteSlug ?? $this->postType,
            ],
        ];
    }

    /**
     * Build labels.
     */
    protected function labels(): array
    {
        return apply_filters(
            "pwt/post_type_labels/{$this->postType}",
            [
                'name'               => $this->plural,
                'singular_name'      => $this->singular,
                'add_new'            => __('Add New', 'wildtours-plugin'),
                'add_new_item'       => sprintf(__('Add New %s', 'wildtours-plugin'), $this->singular),
                'edit_item'          => sprintf(__('Edit %s', 'wildtours-plugin'), $this->singular),
                'new_item'           => sprintf(__('New %s', 'wildtours-plugin'), $this->singular),
                'view_item'          => sprintf(__('View %s', 'wildtours-plugin'), $this->singular),
                'search_items'       => sprintf(__('Search %s', 'wildtours-plugin'), $this->plural),
                'not_found'          => sprintf(__('No %s found.', 'wildtours-plugin'), strtolower($this->plural)),
                'not_found_in_trash' => sprintf(__('No %s found in Trash.', 'wildtours-plugin'), strtolower($this->plural)),
                'all_items'          => sprintf(__('All %s', 'wildtours-plugin'), $this->plural),
            ]
        );
    }
}