<?php

class TH_Exporter
{
  private $customizer_option_table_key = "theme_mods_tophive-core-theme";
  private $customizer_json_file_name = "customizer.json";
  private $menus_json_file_name = "menus.json";
  private $pages_json_file_name = "pages.json";
  private $posts_json_file_name = "posts.json";
  private $plugins_json_file_name = "plugins.json";

  function __construct()
  {
    //test
    // add_action("after_setup_theme", [$this, "save_customizer_to_json_file"]);
    // add_action("after_setup_theme", [$this, "save_wp_menus_to_json_file"]);
    // add_action("after_setup_theme", [$this, "save_pages_to_json_file"]);
    // add_action("after_setup_theme", [$this, "save_posts_to_json_file"]);
    //
    // * Requires Plugins: elementor
    // * Elementor tested up to: 3.21.0
    // * Elementor Pro tested up to: 3.21.0
    //
    //
    // add_action("wp_ajax_the_listaa", [$this, "get_active_plugin_list"]);
  }

  public function get_active_plugin_list()
  {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins');

    $plugins = [];
    foreach ($active_plugins as $plugin_path) {
      if (isset($all_plugins[$plugin_path])) {
        $data = $all_plugins[$plugin_path];
        $data["path"] = $plugin_path;
        $plugins[] = $data;
      }
    }

    $json = json_encode($plugins);
    if ($json == false) return error_log("plugins json encoding fail");
    file_put_contents(plugin_dir_path(__FILE__) . "/assets/data/{$this->plugins_json_file_name}", $json);
  }

  private function get_customizer_value()
  {
    $cus_option = get_option($this->customizer_option_table_key, []);
    if (isset($cus_option["custom_logo"]) && !empty($cus_option["custom_logo"])) {
      $cus_option["custom_logo"] = wp_get_attachment_url($cus_option["custom_logo"]);
    }

    //also add home_page,permalink_structure
    $cus_option["show_on_front"] = get_option("show_on_front", "page");
    $cus_option["permalink_structure"] = get_option("permalink_structure", "/%postname%/");

    return $cus_option;
  }

  public function save_customizer_to_json_file()
  {
    $json = json_encode($this->get_customizer_value());
    if ($json == false) return error_log("customizer value json encoding fail");
    file_put_contents(plugin_dir_path(__FILE__) . "/assets/data/{$this->customizer_json_file_name}", $json);
  }

  private function get_wp_menus()
  {
    $menus = wp_get_nav_menus();
    $all_menus = [];

    foreach ($menus as $menu) {
      $menu_items = wp_get_nav_menu_items($menu->term_id);

      $menu_data = [
        'menu' => [
          'id' => $menu->term_id,
          'name' => $menu->name,
          'slug' => $menu->slug
        ],
        'items' => []
      ];

      if ($menu_items) {
        foreach ($menu_items as $item) {
          $relative_url = str_replace(home_url(), '', $item->url);

          $menu_data['items'][] = [
            'id' => $item->ID,
            'title' => $item->title,
            'url' => $relative_url,
            'parent' => $item->menu_item_parent,
            'order' => $item->menu_order
          ];
        }
      }

      $all_menus[] = $menu_data;
    }

    // var_dump($menu_items);
    return $all_menus;
  }

  private function get_image_urls_from_post_content($post, $post_meta)
  {
    // Use regex to find all <img> tags
    preg_match_all('/<img[^>]+src="([^">]+)"/i', $post->post_content, $matches);

    $image_urls = $matches[1];
    $el_image_urls = [];
    //collect elementor image url from post meta
    //for now disable this feature importing elementor image has not solved

    if (isset($post_meta) && !empty($post_meta) && isset($post_meta["_elementor_data"]) && !empty($post_meta["_elementor_data"])) {
      //Remove escape characters
      $cleanstr = stripslashes($post_meta["_elementor_data"][0]);
      //Regex to match only image URLs
      preg_match_all(
        '#https?:\\\\?/\\\\?/[^"\\\\]+\.(?:jpg|jpeg|png|webp|gif)#i',
        $cleanstr,
        $matches
      );
      //Fix slashes 
      $el_image_urls = array_map(function ($url) {
        return str_replace('\\/', '/', $url);
      }, $matches[0]);
    }

    $image_urls = array_merge($image_urls, $el_image_urls);

    return [
      'count' => count($image_urls),
      'urls' => $image_urls,
    ];
  }

  public function save_wp_menus_to_json_file()
  {
    $json = json_encode($this->get_wp_menus());
    if ($json == false) return error_log("menus json encoding fail");
    file_put_contents(plugin_dir_path(__FILE__) . "/assets/data/{$this->menus_json_file_name}", $json);
  }

  private function get_all_post_by_post_type($post_type = "page")
  {
    $front_page_id = get_option("page_on_front");
    $blog_page_id = get_option("page_for_posts");

    $posts = get_posts(["post_type" => $post_type, 'numberposts' => -1]);
    $all_posts = [];

    foreach ($posts as $post) {
      $relative_url = str_replace(home_url(), '', get_permalink($post->ID));

      // Get post meta
      $post_meta = get_post_meta($post->ID);

      // Get attachments
      $attachments = get_posts([
        'post_type' => 'attachment',
        'post_parent' => $post->ID,
        'posts_per_page' => -1
      ]);

      //thumbnail
      $thumbnail = [];
      if (has_post_thumbnail($post->ID)) {
        $thumbnail["url"] = get_the_post_thumbnail_url($post->ID);
      }

      $attachments_data = [];
      foreach ($attachments as $attachment) {
        $attachments_data[] = [
          'id' => $attachment->ID,
          'url' => wp_get_attachment_url($attachment->ID),
          'title' => $attachment->post_title
        ];
      }

      $args  = [
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'slug' => $post->post_name,
        'parent' => $post->post_parent,
        'url' => $relative_url,
        'status' => $post->post_status,
        'meta' => $post_meta,
        'attachments' => $attachments_data,
        'thumbnail' => $thumbnail,
        'images_url' => $this->get_image_urls_from_post_content($post, $post_meta)
      ];

      if ($front_page_id == $post->ID) {
        $args["page_on_front"] = true;
      }
      if ($blog_page_id == $post->ID) {
        $args["page_for_posts"] = true;
      }

      $all_posts[] = $args;
    }

    return $all_posts;
  }

  public function save_pages_to_json_file()
  {
    $json = json_encode($this->get_all_post_by_post_type("page"));
    if ($json == false) return error_log("pages json encoding fail");
    file_put_contents(WP_MF_CORE_PATH . "/assets/data/{$this->pages_json_file_name}", $json);
  }

  public function save_posts_to_json_file()
  {
    $json = json_encode($this->get_all_post_by_post_type("post"));
    if ($json == false) return error_log("posts json encoding fail");
    file_put_contents(plugin_dir_path(__FILE__) . "/assets/data/{$this->posts_json_file_name}", $json);
  }
}

new TH_Exporter();
