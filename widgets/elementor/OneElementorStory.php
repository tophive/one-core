<?php

namespace ONECORE\widgets\elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OneElementorStory extends Widget_Base 
{

  public function get_name()
  {
    return 'one_story';
  }

  public function get_title()
  {
    return __('One Story', 'one');
  }

  public function get_icon()
  {
    return 'eicon-post';
  }

  public function get_categories()
  {
    return ['basic'];
  }

  public function get_style_depends()
  {
    return ['one-story-style'];
  }

  public function get_script_depends()
  {
    return ['one-story-script'];
  }

  protected function register_controls() {
    $this->start_controls_section(
        'style_section',
        [
            'label' => __('Style', 'one'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
    );

    $this->add_control(
        'title_color',
        [
            'label' => __('Color', 'one'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bps-username' => 'color: {{VALUE}} !important',
            ],
        ]
    );

    // Content Typography
    $this->add_group_control(
        \Elementor\Group_Control_Typography::get_type(),
        [
            'name' => 'content_typography',
            'label' => __('Typography', 'one'),
            'selector' => 'body.wp-theme-one.wp-theme-one.wp-theme-one {{WRAPPER}} .bps-username',
            'label' => __('Font', 'one'),
        ]
    );
  }

  protected function render()
  {
    echo do_shortcode("[bps_stories_bar]");
  }
}
