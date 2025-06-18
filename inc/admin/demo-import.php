<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Enqueue JS + Modal Styles + Localize Steps
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('bp-demo-import', plugin_dir_url(__FILE__) . '/demo-import.js', ['jquery'], null, true);
    wp_localize_script('bp-demo-import', 'BPDemoSteps', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'steps' => [
            'install_plugins',
            'import_users',
            'import_pages',
            'enable_groups_component',
            'import_activities',
            'import_groups',
            'import_widgets',
            'import_customizer',
            'import_menus',
            'import_blog_posts',
            'import_forums',
            'setup_homepage'
        ]
    ]);

    wp_enqueue_style('bp-demo-import-style', plugin_dir_url(__FILE__) . '/demo-import.css');
});

// 2. Add Admin Page with Import Button + Modal Container
add_action('admin_menu', function () {
    add_menu_page('One Demo Import', 'One Demo Import', 'manage_options', 'bp-demo-import', 'bp_demo_import_page');
});

function bp_demo_import_page() {
    echo '<div class="wrap" style="align-items: flex-start;">
        <h1>Import One Demo</h1>
        <button id="start-demo-import" class="button button-primary">Import One Demo</button>
    </div>';

    $home_url   = esc_url( home_url( '/' ) );
    $admin_url  = esc_url( admin_url( 'themes.php?page=bp-demo-import' ) );

    echo '<div id="bp-demo-modal" class="bp-demo-modal" style="display:none;">
        <div class="bp-demo-modal-content">
            <div id="bp-demo-loader" class="loader" style="display: none;"></div>

            <h2>Importing One Demo</h2>

            <div id="bp-demo-log">Ready to import...</div>
            <div class="bp-demo-progress"><div class="bar"></div></div>

            <button id="bp-start-import" class="button button-primary">Start Importing</button>


            <div id="bp-demo-final-buttons" style="display:none; margin-top: 20px;">
                <a href="' . $home_url . '" class="button button-primary" target="_blank">Visit Website</a>
                <a href="' . $admin_url . '" class="button button-secondary">Back to Demos</a>
            </div>
        </div>
    </div>';
}

// 3. AJAX Endpoints
add_action('wp_ajax_bp_demo_import_step', function() {
    $step = sanitize_text_field($_POST['step'] ?? '');

    $Tophovive_License_Instance = new Tophive_Licence();

    // if(!$Tophovive_License_Instance->check_license()){
    //   wp_send_json( "License faield", 400 );
    // }

    try {
        switch ($step) {
            case 'install_plugins':
                bp_demo_install_plugins();
                break;
            case 'setup_homepage':
                bp_demo_setup_activity_home();
                break;
            case 'import_pages':
                bp_demo_import_pages();
                break;
            case 'import_users':
                bp_demo_import_users();
                break;
            case 'enable_groups_component':
                bp_demo_enable_groups_component_properly();
                break;
            case 'import_groups':
                bp_demo_import_groups();
                break;
            case 'import_activities':
                bp_demo_import_activities();
                break;
            case 'import_widgets':
                bp_demo_import_widgets_from_wie();
                break;
            case 'import_menus':
                bp_demo_import_menus();
                break;                
            case 'import_customizer':
                bp_demo_import_customizer();
                break;
            case 'import_blog_posts':
                bp_demo_import_blog_posts();
                break;
            case 'import_forums':
                bp_demo_import_forums();
                break;
            default:
                throw new Exception('Invalid step.');
        }

        wp_send_json_success([ 'message' => ucfirst(str_replace('_', ' ', $step)) . ' complete.' ]);

    } catch (Exception $e) {
        wp_send_json_error([ 'message' => $e->getMessage() ]);
    }
});

add_filter('get_avatar_url', 'bp_demo_custom_avatar_url', 10, 2);
function bp_demo_custom_avatar_url($url, $id_or_email) {
    if (is_admin()) return $url; // optional: skip in admin

    $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
    if ($user) {
        $custom_avatar = get_user_meta($user->ID, 'custom_avatar_url', true);
        if (!empty($custom_avatar)) {
            return esc_url($custom_avatar);
        }
    }
    return $url;
}

add_filter('bp_core_fetch_avatar', 'bp_demo_custom_avatar_for_buddypress', 10, 2);
function bp_demo_custom_avatar_for_buddypress($avatar, $args) {
    if (isset($args['item_id'])) {
        $custom = get_user_meta($args['item_id'], 'custom_avatar_url', true);
        if (!empty($custom)) {
            $size = isset($args['width']) ? $args['width'] : 96;
            return '<img src="' . esc_url($custom) . '" class="avatar avatar-' . esc_attr($size) . ' photo" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" />';
        }
    }
    return $avatar;
}


function bp_demo_import_activities() {
    $file_path = plugin_dir_path(__FILE__) . '/demo-data/buddypress_demo_activities.json';

    if (!file_exists($file_path)) {
        echo '<div class="notice notice-error"><p>JSON file not found.</p></div>';
        return;
    }

    $json = file_get_contents($file_path);
    $activities = json_decode($json, true);


    foreach ($activities as $activity) {
        $activity_id = bp_activity_add([
            'user_id'       => get_current_user_id(), // ← Use current user
            'content'       => $activity['content'],
            'component'     => 'activity',
            'type'          => 'activity_update',
            'recorded_time' => date('Y-m-d H:i:s', $activity['timestamp']),
        ]);

        if (!$activity_id) continue;

        // Reactions
        if (!empty($activity['reactions']) && is_array($activity['reactions'])) {
            $clean_reactions = [];
            foreach ($activity['reactions'] as $reaction => $data) {
                if (is_array($data) && isset($data['count']) && isset($data['users']) && is_array($data['users'])) {
                    $clean_reactions[$reaction] = [
                        'count' => (int) $data['count'],
                        'users' => array_map('intval', $data['users']),
                    ];
                }
            }
            bp_activity_add_meta($activity_id, 'tophive_activity_reactions', $clean_reactions);
        }

        // Media
        if (!empty($activity['media']) && is_array($activity['media'])) {
            $clean_media = array_filter($activity['media'], function ($item) {
                return is_array($item) && isset($item['full']);
            });
            bp_activity_add_meta($activity_id, 'activity_media', array_values($clean_media));
        }

        // Comments
        if (!empty($activity['comments']) && is_array($activity['comments'])) {
            $clean_comments = [];
            foreach ($activity['comments'] as $comment) {
                if (!is_array($comment) || !isset($comment['ID'], $comment['content'])) continue;

                $comment['reactions'] = (isset($comment['reactions']) && is_array($comment['reactions'])) ? $comment['reactions'] : [];
                $comment['replies'] = (isset($comment['replies']) && is_array($comment['replies'])) ? $comment['replies'] : [];

                foreach ($comment['replies'] as &$reply) {
                    if (!is_array($reply) || !isset($reply['reactions'])) continue;
                    $reply['reactions'] = is_array($reply['reactions']) ? $reply['reactions'] : [];
                }

                $clean_comments[] = $comment;
            }

            bp_activity_add_meta($activity_id, 'tophive_activity_comments', $clean_comments);
        }

        // Set visibility
        bp_activity_add_meta($activity_id, 'activity_accessibility', 'public');
    }

    // echo '<div class="notice notice-success"><p>Demo activities imported successfully!</p></div>';
}
function bp_demo_import_users() {
    $file_path = plugin_dir_path(__FILE__) . '/demo-data/buddypress_demo_users.json';

    if (!file_exists($file_path)) {
        echo '<div class="notice notice-error"><p>Users JSON file not found.</p></div>';
        return;
    }

    $json = file_get_contents($file_path);
    $users = json_decode($json, true);

    foreach ($users as $user) {
        if (username_exists($user['username']) || email_exists($user['email'])) {
            continue; // Skip existing users
        }

        $user_id = wp_insert_user([
            'user_login'   => $user['username'],
            'user_pass'    => $user['password'],
            'user_email'   => $user['email'],
            'display_name' => $user['display_name'],
            'first_name'   => $user['first_name'],
            'last_name'    => $user['last_name'],
            'role'         => $user['role']
        ]);

        if (!is_wp_error($user_id)) {
            $avatar_url = esc_url_raw($user['avatar']);
            $upload_dir = wp_upload_dir();
            $avatar_dir = $upload_dir['basedir'] . "/avatars/{$user_id}";
        
            wp_mkdir_p($avatar_dir);
        
            $response = wp_remote_get($avatar_url);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                if (!empty($body)) {
                    file_put_contents("{$avatar_dir}/{$user_id}-bpfull.jpg", $body);
                    file_put_contents("{$avatar_dir}/{$user_id}-bpthumb.jpg", $body);
                }
            }
        }
        
    }

    // echo '<div class="notice notice-success"><p>Demo users imported successfully (with avatars)!</p></div>';
}
function bp_demo_install_plugins() {
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    if (!class_exists('BP_Silent_Upgrader_Skin')) {
        class BP_Silent_Upgrader_Skin extends \WP_Upgrader_Skin {
            public function header() {}
            public function footer() {}
            public function feedback($string, ...$args) {}
        }
    }

    function get_main_plugin_file($slug) {
        $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
        if (!is_dir($plugin_dir)) return false;
        $files = scandir($plugin_dir);
        foreach ($files as $file) {
            if (strpos($file, '.php') !== false) {
                $contents = file_get_contents("$plugin_dir/$file");
                if (strpos($contents, 'Plugin Name:') !== false) {
                    return "$slug/$file";
                }
            }
        }
        return false;
    }

    $slugs = ['buddypress', 'bbpress', 'elementor'];
    $to_activate = [];

    foreach ($slugs as $slug) {
        if (!file_exists(WP_PLUGIN_DIR . "/$slug")) {
            $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['sections' => false]]);
            if (!is_wp_error($api)) {
                $upgrader = new Plugin_Upgrader(new BP_Silent_Upgrader_Skin());
                $upgrader->install($api->download_link);
            }
        }

        $plugin_file = get_main_plugin_file($slug);
        if ($plugin_file && !is_plugin_active($plugin_file)) {
            $to_activate[] = $plugin_file;
        }
    }

    // Install one-core from GitHub
    $core_plugin_url = 'https://github.com/tophive/one-core/releases/download/v1.2.0/one-core.zip';
    $upgrader = new Plugin_Upgrader(new BP_Silent_Upgrader_Skin());
    $result = $upgrader->install($core_plugin_url);

    if ($result && is_dir(WP_PLUGIN_DIR . '/one-core')) {
        $core_file = get_main_plugin_file('one-core');
        if ($core_file && !is_plugin_active($core_file)) {
            $to_activate[] = $core_file;
        }
    }

    if (!empty($to_activate)) {
        activate_plugins($to_activate);
    }
}

function bp_demo_import_menus() {
	$menus_path = plugin_dir_path(__FILE__) . '/demo-data/buddypress_demo_menus.json';

	if (!file_exists($menus_path)) {
		wp_send_json_error(['message' => 'Menus JSON file not found.']);
	}

	$json = file_get_contents($menus_path);
	$menus = json_decode($json, true);

	if (empty($menus)) {
		wp_send_json_error(['message' => 'No menu data to import.']);
	}

    $menu_locations = [];

	foreach ($menus as $_menu) {
        //create menu term if not exist
        //update menu location so this menu get render on that location
        //insert menu item in post table with menu order and parent menu preservation
        $menu_name = $_menu["menu"]["name"];
        $menu_exist = wp_get_nav_menu_object($menu_name);
  
        if ($menu_exist == false) {
          $menu_id = wp_create_nav_menu($menu_name);
          if (is_wp_error($menu_id)) {
            error_log("Nav menu creation fail: {$menu_name}");
            continue;
          }
  
  
  
          $menu_obj = wp_get_nav_menu_object($menu_id);
          if ($menu_obj == false) {
            error_log("wp_get_nav_menu_object fail: {$menu_id}");
            continue;
          }
  
          $menu_locations[$_menu['menu']['slug']] = $menu_id;
  
          $oldid_map_newid = [];
  
          foreach ($_menu["items"] as $m) {
            if ($m["parent"] != "0") continue;
            $full_url = $m['url'];
            if (substr($full_url, 0, 1) === '/') {
              $full_url = rtrim(get_site_url(), '/') . $full_url;
            }
            $id = wp_update_nav_menu_item($menu_id, 0, [
              'menu-item-title' => $m['title'],
              'menu-item-url' => $full_url,
              'menu-item-status' => 'publish',
              'menu-item-position' => $m['order']
            ]);
            if (is_wp_error($id)) {
              error_log("wp_update_nav_menu_item fail: {$menu_id} title: {$m['title']}");
              continue;
            }
            // Add icon if available
			if (!empty($m['icon'])) {
				update_post_meta($id, '_menu_item_menu-icon-text', esc_url($m['icon']));
			}
            $oldid_map_newid[$m["id"]] = $id;
          }
  
  
  
          foreach ($_menu["items"] as $m) {
            if ($m["parent"] == "0") continue;
            $full_url = $m['url'];
            if (substr($full_url, 0, 1) === '/') {
              $full_url = rtrim(get_site_url(), '/') . $full_url;
            }
            $id = wp_update_nav_menu_item($menu_id, 0, [
              'menu-item-title' => $m['title'],
              'menu-item-url' => $full_url,
              'menu-item-status' => 'publish',
              'menu-item-parent-id' => $oldid_map_newid[$m["parent"]],
              'menu-item-position' => $m['order']
            ]);
            if (is_wp_error($id)) {
              error_log("wp_update_nav_menu_item fail: {$menu_id} title: {$m['title']}");
              continue;
            }
            // Add icon if available
			if (!empty($m['icon'])) {
				update_post_meta($id, '_menu_item_menu-icon-text', esc_url($m['icon']));
			}
            $oldid_map_newid[$m["id"]] = $id;
          }
        }
    }

    set_theme_mod("nav_menu_locations", $menu_locations);

	wp_send_json_success(['message' => 'Menus imported and locations set.']);
}

function bp_demo_import_pages() {
    $file_path = plugin_dir_path(__FILE__) . '/demo-data/pages.json';

    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'Pages JSON file not found.'];
    }

    $json = file_get_contents($file_path);
    $pages = json_decode($json, true);

    if (empty($pages) || !is_array($pages)) {
        return ['success' => false, 'message' => 'Invalid pages data.'];
    }

    foreach ($pages as $page) {
        import_post($page, 'page');
    }

    return ['success' => true, 'message' => 'Pages imported successfully!'];
}

function bp_demo_import_forums() {
    $file = plugin_dir_path(__FILE__) . '/demo-data/forums.json';
    if (!file_exists($file)) return ['success' => false, 'message' => 'Forums JSON not found.'];

    $json = file_get_contents($file);
    $forums = json_decode($json, true);

    if (empty($forums)) return ['success' => false, 'message' => 'Invalid forums data.'];

    foreach ($forums as $forum) {
        $topic_id = bbp_insert_topic([
            'post_title'   => $forum['title'],
            'post_content' => $forum['content'],
            'post_status'  => 'publish',
            'post_author'  => $forum['author'],
            'post_date'    => $forum['date'],
        ], [
            'forum_id' => $forum['forum_id'],
        ]);

        if (!$topic_id) continue;

        if (!empty($forum['replies'])) {
            foreach ($forum['replies'] as $reply) {
                bbp_insert_reply([
                    'post_content' => $reply['content'],
                    'post_status'  => 'publish',
                    'post_author'  => $reply['author'],
                    'post_date'    => $reply['date'],
                    'post_parent'  => $topic_id,
                ], [
                    'topic_id' => $topic_id,
                    'forum_id' => $forum['forum_id'],
                ]);
            }
        }
    }

    return ['success' => true, 'message' => 'Forums imported.'];
}

function import_post(array $post, string $post_type){

    if (
      empty($post["title"]) ||
      empty($post["content"]) ||
      empty($post["slug"]) ||
      empty($post_type) ||
      empty($post["status"])
    ) {
      error_log("validation fail import_post:id-->{$post["id"]}");
      return false;
    }

    //images inside post content
    if (!empty($post["images_url"]["urls"]) && count($post["images_url"]["urls"]) > 0) {
      foreach ($post["images_url"]["urls"] as $url) {
        //download image
        $image_data = download_image_from_url($url);
        if ($image_data == false) continue;

        //upload to media directory
        $attachment = upload_raw_image_data($image_data, $url);
        if ($attachment == false) continue;

        //replace post_content src to url
        $replaced = str_replace($url, $attachment["file_url"], $post["content"]);
        if ($replaced == null) {
          error_log("preg_replace failed in import_post fn url: --> {$url}");
        }

        $post["content"] = $replaced;
      }
    }

    //images in feature image or thumbnails
    if (isset($post["thumbnail"]) && !empty($post["thumbnail"]["url"])) {
      //download image
      $image_data = download_image_from_url($post["thumbnail"]["url"]);
      if ($image_data != false) {
        //upload to media directory
        $attachment = upload_raw_image_data($image_data, $post["thumbnail"]["url"]);
        if ($attachment != false) {
          //update meta `_thumbnail_id` so this post thumbnail point this new image 
          $post["meta"]["_thumbnail_id"] = ["{$attachment['attachment_id']}"];
        };
      }
    }

    $args = [
      "post_title"    => $post["title"],
      "post_content"  => $post["content"],
      "post_status"   => $post["status"],
      "post_type"     => $post_type,
    ];

    $result = wp_insert_post($args);

    if (is_wp_error($result)) {
      error_log("post insert fail id:-->{$post['id']}");
    }

    //update post meta
    foreach ($post["meta"] as $key => $value_arr) {
      foreach ($value_arr as $va) {
        if (is_serialized($va)) {
          update_post_meta($result, $key, unserialize($va));
        } else {
          update_post_meta($result, $key, $va);
        }
      }
    }

    return $result;
}

function download_image_from_url(string $url)
  {
    if (filter_var($url, FILTER_VALIDATE_URL) ==  false) {
      error_log("validation fail of url {$url}");
      return false;
    };

    $file_content = @file_get_contents($url);
    if ($file_content == false) {
      error_log("download fail of url {$url}");
      return false;
    }

    return $file_content;
  }

  function upload_raw_image_data($image_data, $url)
  {
    // Get the filename from the URL
    $filename = basename(parse_url($url, PHP_URL_PATH));

    // Upload to WordPress
    $upload = wp_upload_bits($filename, null, $image_data);

    if ($upload['error']) {
      error_log("upload_raw_image_data fail url:--->{$url}");
      return false;
    }

    // Get the file type
    $wp_filetype = wp_check_filetype($upload['file'], null);

    // Prepare attachment data
    $attachment = [
      'post_mime_type' => $wp_filetype['type'],
      'post_title'     => sanitize_file_name($filename),
      'post_content'   => '',
      'post_status'    => 'inherit'
    ];

    // Insert the attachment into the Media Library
    $attach_id = wp_insert_attachment($attachment, $upload['file'], 0);
    if (is_wp_error($attach_id)) {
      error_log("wp_insert_attachment fail url:--->{$url}");
      return false;
    }

    // Include WordPress image functions
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // GENERATE ATTACHMENT METADATA AND UPDATE USE THIS LINE IF YOU WANT TO MAKE MULTIPLE RESULATION OF SAME IMAGE
    // $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    // wp_update_attachment_metadata($attach_id, $attach_data);

    return [
      'attachment_id' => $attach_id,
      'file_url' => $upload['url']
    ];
  }

function bp_demo_import_widgets_from_wie() {
    $file = plugin_dir_path(__FILE__) . '/demo-data/widgets.wie';

    if (!file_exists($file)) {
        echo '<div class="notice notice-error"><p>widgets.wie file not found.</p></div>';
        return;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        echo '<div class="notice notice-error"><p>Invalid .wie file format.</p></div>';
        return;
    }

    $sidebars_widgets = get_option('sidebars_widgets');
    if (!is_array($sidebars_widgets)) $sidebars_widgets = [];

    foreach ($data as $sidebar_id => $widgets) {
        if (!isset($sidebars_widgets[$sidebar_id])) {
            $sidebars_widgets[$sidebar_id] = [];
        }

        foreach ($widgets as $widget_id => $settings) {
            // Extract base widget type and index
            if (preg_match('/^(.+)-(\d+)$/', $widget_id, $matches)) {
                $base_id = $matches[1];
            } else {
                // fallback for block widgets
                $base_id = $widget_id;
            }

            // Prepare and update the widget option
            $widget_option = "widget_{$base_id}";
            $instances = get_option($widget_option, []);
            if (!is_array($instances)) $instances = [];

            // Append new instance
            $instances[] = $settings;
            end($instances); // move internal pointer to last element
            $new_key = key($instances);

            update_option($widget_option, $instances);

            // Add to sidebar_widgets
            $sidebars_widgets[$sidebar_id][] = "{$base_id}-{$new_key}";
        }
    }

    // Important: Ensure required keys exist
    if (!isset($sidebars_widgets['wp_inactive_widgets'])) {
        $sidebars_widgets['wp_inactive_widgets'] = [];
    }

    if (!isset($sidebars_widgets['array_version'])) {
        $sidebars_widgets['array_version'] = 3;
    }

    update_option('sidebars_widgets', $sidebars_widgets);

    // echo '<div class="notice notice-success"><p>Widgets imported from .wie successfully!</p></div>';
}

function bp_demo_import_block_widgets() {
    $file = plugin_dir_path(__FILE__) . '/demo-data/widget_block.txt';


    if (!file_exists($file)) return;

    $data = file_get_contents($file);
    $block_widgets = @unserialize($data);

    if (!is_array($block_widgets)) {
        echo '<div class="notice notice-error"><p>Invalid block widget format.</p></div>';
        return;
    }

    update_option('widget_block', $block_widgets);

    // echo '<div class="notice notice-success"><p>Block widgets (Gutenberg) imported successfully.</p></div>';
}

function bp_demo_import_blog_posts() {
    $file = plugin_dir_path(__FILE__) . '/demo-data/blog_posts.json';
    if (!file_exists($file)) {
        echo '<div class="notice notice-error"><p>Blog posts JSON file not found.</p></div>';
        return;
    }

    // Load dependencies required for media_handle_sideload
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $posts = json_decode(file_get_contents($file), true);
    if (!is_array($posts)) {
        echo '<div class="notice notice-error"><p>Invalid blog post JSON format.</p></div>';
        return;
    }

    foreach ($posts as $post_data) {
        $postarr = [
            'post_title'    => $post_data['post_title'],
            'post_content'  => $post_data['post_content'],
            'post_status'   => $post_data['post_status'],
            'post_author'   => $post_data['post_author'],
            'post_date'     => $post_data['post_date'],
            'post_category' => $post_data['post_category'] ?? []
        ];

        $post_id = wp_insert_post($postarr);

        if (!is_wp_error($post_id) && !empty($post_data['featured_image'])) {
            $image_url = esc_url_raw($post_data['featured_image']);
            $tmp = download_url($image_url);

            if (!is_wp_error($tmp)) {
                $filename = basename(parse_url($image_url, PHP_URL_PATH));

                // Get MIME type
                $filetype = wp_check_filetype($filename);

                $file_array = [
                    'name'     => $filename,
                    'tmp_name' => $tmp,
                    'type'     => $filetype['type'] ?? 'image/jpeg' // fallback just in case
                ];

                $attachment_id = media_handle_sideload($file_array, $post_id);

                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                } else {
                    @unlink($tmp); // Clean up temp file
                    error_log('Image sideload error: ' . $attachment_id->get_error_message());
                }
            } else {
                error_log('Download error: ' . $tmp->get_error_message());
            }
        }
    }
    $default_post = get_page_by_title('Hello world!', OBJECT, 'post');
    if ($default_post) {
        wp_delete_post($default_post->ID, true);
    }

    // echo '<div class="notice notice-success"><p>Demo blog posts (with featured images) imported successfully!</p></div>';
}
function bp_demo_enable_groups_component_properly() {
    // Load BuddyPress global
    $bp = buddypress();

    // Load upgrade and schema functions
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';

    // Clone existing components and add 'groups'
    $submitted = get_option('bp-active-components', []);
    if (!isset($submitted['groups'])) {
        $submitted['groups'] = 1;

        // Activate in BP
        $bp->active_components = bp_core_admin_get_active_components_from_submitted_settings($submitted);

        // Install schema and page mappings
        bp_core_install($bp->active_components);
        bp_core_add_page_mappings($bp->active_components);
        bp_update_option('bp-active-components', $bp->active_components);

        // Refresh permalinks
        if (array_intersect_key($bp->active_components, bp_core_get_directory_page_ids('active'))) {
            bp_delete_rewrite_rules();
        }
    }
}

function bp_demo_import_groups() {

    if (!function_exists('groups_create_group')) {
        echo '<div class="notice notice-error"><p>Groups component not initialized properly. Please reload and try again.</p></div>';
        return;
    }

    $file = plugin_dir_path(__FILE__) . '/demo-data/buddypress_groups.json';
    if (!file_exists($file)) {
        echo '<div class="notice notice-error"><p>Groups JSON file not found.</p></div>';
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $groups = json_decode(file_get_contents($file), true);
    if (!$groups) {
        echo '<div class="notice notice-error"><p>Invalid groups JSON format.</p></div>';
        return;
    }

    foreach ($groups as $group) {
        $group_id = groups_create_group([
            'creator_id'    => $group['creator_id'],
            'name'          => $group['name'],
            'description'   => $group['description'],
            'status'        => $group['status'],
            'enable_forum'  => false
        ]);

        if (!$group_id) continue;

        // Set created date
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bp_groups',
            ['date_created' => $group['created_at']],
            ['id' => $group_id]
        );

        // Avatar setup
        $avatar_path = download_url($group['group_image']);
        if (!is_wp_error($avatar_path)) {
            $avatar_dir = bp_core_avatar_upload_path() . "/group-avatars/{$group_id}";
            wp_mkdir_p($avatar_dir);
            $avatar_filename = $avatar_dir . '/' . time() . '-bpfull.jpg';
            rename($avatar_path, $avatar_filename);
        }

        // Thumb setup
        $thumb_path = download_url($group['group_image']);
        if (!is_wp_error($thumb_path)) {
            $avatar_dir = bp_core_avatar_upload_path() . "/group-avatars/{$group_id}";
            wp_mkdir_p($avatar_dir);
            $avatar_filename = $avatar_dir . '/' . time() . '-bpthumb.jpg';
            rename($thumb_path, $avatar_filename);
        }

        // Cover image setup
        $cover_path = download_url($group['cover_image']);
        if (!is_wp_error($cover_path)) {
            $cover_dir = bp_core_avatar_upload_path() . "/buddypress/groups/{$group_id}/cover-image";
            wp_mkdir_p($cover_dir);
            $cover_filename = $cover_dir . '/' . uniqid() . '-bp-cover-image.jpg';
            rename($cover_path, $cover_filename);
        }

        // Add members (excluding creator)
        foreach ($group['members'] as $member_id) {
            if ($member_id != $group['creator_id']) {
                groups_join_group($group_id, $member_id);
            }
        }
    }

    // echo '<div class="notice notice-success"><p>BuddyPress groups imported successfully!</p></div>';
}


function bp_demo_import_customizer() {
    $file = plugin_dir_path(__FILE__) . '/demo-data/customizer_one.dat';
    if (!file_exists($file)) return;

    $customizer_data = file_get_contents($file);
    $mods = unserialize($customizer_data);

    if (!empty($mods)) {
        foreach ($mods as $mod => $val) {
            set_theme_mod($mod, $val);
        }
    }

    // Upload and set logo
    $logo_url = 'https://demo.tophivetheme.com/one/classic/wp-content/uploads/sites/10/2022/07/logo-300x58.png';

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $logo_id = media_sideload_image($logo_url, 0, null, 'id');
    if (!is_wp_error($logo_id)) {
        set_theme_mod('custom_logo', $logo_id);
    }
}





function bp_demo_setup_activity_home() {
    // Make sure BuddyPress is active and activity component is enabled
    if (function_exists('bp_is_active') && bp_is_active('activity')) {
        $bp_pages = get_option('bp-pages');

        if (!empty($bp_pages['activity'])) {
            $activity_page_id = (int) $bp_pages['activity'];

            update_option('show_on_front', 'page');
            update_option('page_on_front', $activity_page_id);
        } else {
            echo '<div class="notice notice-error"><p>Activity page is not assigned in BuddyPress settings (bp-pages).</p></div>';
        }

        $bp_apperances = 'a:26:{s:12:"avatar_style";i:1;s:15:"user_front_page";i:1;s:14:"user_front_bio";i:0;s:16:"user_nav_display";i:0;s:13:"user_nav_tabs";i:0;s:16:"user_subnav_tabs";i:0;s:14:"user_nav_order";a:0:{}s:14:"members_layout";i:3;s:20:"members_group_layout";i:2;s:22:"members_friends_layout";i:2;s:19:"activity_dir_layout";i:0;s:17:"activity_dir_tabs";i:0;s:18:"members_dir_layout";i:0;s:16:"members_dir_tabs";i:0;s:17:"groups_dir_layout";i:0;s:16:"group_front_page";i:1;s:17:"group_front_boxes";i:1;s:23:"group_front_description";i:0;s:17:"group_nav_display";i:0;s:14:"group_nav_tabs";i:0;s:17:"group_subnav_tabs";i:0;s:18:"groups_create_tabs";i:1;s:15:"group_nav_order";a:0:{}s:13:"groups_layout";i:4;s:15:"groups_dir_tabs";i:0;s:16:"global_alignment";s:9:"alignnone";}';

        $bp_app_arr = unserialize($bp_apperances);

        // Save to wp_options table
        update_option('bp_nouveau_appearance', $bp_app_arr);

    } else {
        // echo '<div class="notice notice-error"><p>BuddyPress or the Activity component is not active.</p></div>';
    }

    if (get_option('permalink_structure') !== '/%postname%/') {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules(); // Regenerate permalinks
    }
}
