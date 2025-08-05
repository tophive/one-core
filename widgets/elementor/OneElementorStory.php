<?php

namespace ONECORE\widgets\elementor;

use Elementor\Widget_Base;

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
    return __('One Story', 'plugin-name');
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

  protected function register_controls() { }

  protected function render()
  {
    echo do_shortcode("[bps_stories_bar]");
  }
}
