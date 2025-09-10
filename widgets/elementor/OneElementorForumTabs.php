<?php

namespace ONECORE\widgets\elementor;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!class_exists('bbPress')) {
    return;
}

/**
 * One Elementor Forum Tabs - Drag Only + Full bbPress Markup
 */
class OneElementorForumTabs extends \Elementor\Widget_Base
{
    public function get_title()
    {
        return esc_html__('BBPress Forum', 'ONE_CORE_SLUG');
    }

    public function get_name()
    {
        return 'th-forum-tabs';
    }

    public function get_icon()
    {
        return 'eicon-tabs';
    }

    public function get_categories()
    {
        return ['ONE_CORE_SLUG'];
    }

    public function get_keywords()
    {
        return ['tabs', 'forum'];
    }

    public function get_tabs()
    {
        return [
            "recent_activity" => esc_html__("Recent Activity", "ONE_CORE_SLUG"),
            "forum" => esc_html__("Forum", "ONE_CORE_SLUG"),
            "tag" => esc_html__("Tag", "ONE_CORE_SLUG"),
            "my_topic" => esc_html__("My Topic", "ONE_CORE_SLUG"),
            "fav" => esc_html__("Favourite", "ONE_CORE_SLUG"),
            "sub" => esc_html__("Subscription", "ONE_CORE_SLUG"),
            "eng" => esc_html__("Engagement", "ONE_CORE_SLUG"),
            "search" => esc_html__("Search", "ONE_CORE_SLUG"),
        ];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'th_adv_tabs_section',
            [
                'label' => esc_html__('Activity Tabs', 'ONE_CORE_SLUG'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $tabs = $this->get_tabs();

        $repeater = new Repeater();
        $repeater->add_control(
            'tab_key',
            [
                'label' => __('Select Tab', 'ONE_CORE_SLUG'),
                'type' => Controls_Manager::SELECT,
                'options' => $tabs,
                'default' => key($tabs),
            ]
        );

        $this->add_control(
            'tabs_list',
            [
                'label' => __('Tabs', 'ONE_CORE_SLUG'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => array_map(function ($key) {
                    return ['tab_key' => $key];
                }, array_keys($tabs)),
                'title_field' => '{{{ tab_key }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $tabs = $settings['tabs_list'] ?? [];

        function helper_tab_active_class($match_string)
        {
            $url = $_SERVER['REQUEST_URI'];
            return (strpos($url, $match_string) !== false) ? 'active' : '';
        }

        $get_tab_item = function ($tab_key) use ($settings) {
            switch ($tab_key) {
                case "recent_activity": ?>
                    <li><a class="<?php echo helper_tab_active_class("tab=topic"); ?>" href="?tab=topic"><?php echo esc_html__('Recent Activity', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php
                    break;
                case "forum": ?>
                    <li><a class="<?php echo helper_tab_active_class("tab=forum"); ?>" href="?tab=forum"><?php echo esc_html__('Forums', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php
                    break;
                case "tag":
                    if (!empty($settings["selected_tags"])) :
                        foreach ($settings["selected_tags"] as $value) :
                            $term = get_term_by('slug', $value, 'topic-tag'); ?>
                            <li><a class="<?php echo helper_tab_active_class("tag={$value}"); ?>" href="?tag=<?php echo $value; ?>"><?php echo esc_html($term->name); ?></a></li>
                        <?php
                        endforeach;
                    endif;
                    break;
                case "my_topic":
                    if (is_user_logged_in()) : ?>
                        <li><a class="<?php echo helper_tab_active_class("my-topic=me"); ?>" href="?my-topic=me"><?php echo esc_html__('My Topic', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php endif;
                    break;
                case "fav":
                    if (is_user_logged_in()) : ?>
                        <li><a class="<?php echo helper_tab_active_class("fav=me"); ?>" href="?fav=me"><?php echo esc_html__('Favourite', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php endif;
                    break;
                case "sub":
                    if (is_user_logged_in()) : ?>
                        <li><a class="<?php echo helper_tab_active_class("sub=me"); ?>" href="?sub=me"><?php echo esc_html__('Subscription', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php endif;
                    break;
                case "eng":
                    if (is_user_logged_in()) : ?>
                        <li><a class="<?php echo helper_tab_active_class("engagement=me"); ?>" href="?engagement=me"><?php echo esc_html__('Engagement', 'ONE_CORE_SLUG'); ?></a></li>
                    <?php endif;
                    break;
                case "search": ?>
                    <li><input type="search" placeholder="Search" id="forum-search" /></li>
                    <?php
                    break;
            }
        };

        // Default query arguments
        $args = ['post_type' => 'topic', 'post_status' => 'publish', 'posts_per_page' => 30, 'post_parent' => 0];

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
        $tag = isset($_GET['tag']) ? sanitize_text_field($_GET['tag']) : '';

        if ($tab === 'forum') $args['post_type'] = 'forum';
        if (!empty($tag)) {
            $args['tax_query'] = [['taxonomy' => 'topic-tag', 'field' => 'slug', 'terms' => $tag]];
        }

        $query = new \WP_Query($args);

        // Render tabs
        ?>
        <div class="tophive-advanced-forum-tab-container">
            <div class="tophive-forum-tabs">
                <ul>
                    <?php
                    if (!empty($tabs)) {
                        foreach ($tabs as $tab_item) {
                            $get_tab_item($tab_item['tab_key']);
                        }
                    }
                    ?>
                </ul>
            </div>

            <div class="tophive-forum-tabs-container">
                <?php
                if ($query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post(); ?>
                        <div class="tophive-forum-topic-loop-single">
                            <div class="tophive-forum-topic-loop-single-details">
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <p><?php the_content(); ?></p>
                            </div>
                        </div>
                    <?php
                    endwhile;
                else :
                    echo "<div class='center'>".esc_html__("No Content Found", "ONE_CORE_SLUG")."</div>";
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('forum-search');
                if (input) {
                    input.addEventListener('keyup', function (e) {
                        const val = e.target.value.toLowerCase();
                        document.querySelectorAll('.tophive-forum-topic-loop-single').forEach(item => {
                            item.style.display = item.textContent.toLowerCase().includes(val) ? '' : 'none';
                        });
                    });
                }
            });
        </script>
        <?php
    }
}