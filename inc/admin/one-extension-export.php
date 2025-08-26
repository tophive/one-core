<?php

if (!defined('ABSPATH')) {
  exit;
}

class One_Extension_Export {
  public static function init() {
    add_action('admin_menu', [__CLASS__, 'register_menu']);
    add_action('admin_post_one_ext_export', [__CLASS__, 'handle_export']);
    add_action('admin_post_one_ext_import', [__CLASS__, 'handle_import']);
    add_action('admin_post_one_ext_install_plugins', [__CLASS__, 'handle_install_plugins']);
    add_action('admin_post_one_ext_export_menus', [__CLASS__, 'handle_export_menus']);
  }

  public static function register_menu() {
    add_options_page(
      __('One Extension Export', 'one-core'),
      __('One Extension Export', 'one-core'),
      'manage_options',
      'one-extension-export',
      [__CLASS__, 'render_admin_page']
    );
  }

  public static function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    $export_url = admin_url('admin-post.php?action=one_ext_export&_wpnonce=' . wp_create_nonce('one_ext_export'));
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('One Extension Export', 'one-core'); ?></h1>

      <h2 class="title"><?php echo esc_html__('Export', 'one-core'); ?></h2>
      <p><?php echo esc_html__('Exports up to 5 items from Tutor LMS, Directorist, WP Events, WooCommerce, and WP Job Manager into a single JSON file.', 'one-core'); ?></p>
      <p>
        <a href="<?php echo esc_url($export_url); ?>" class="button button-primary"><?php echo esc_html__('Download Export (JSON)', 'one-core'); ?></a>
      </p>

      <?php $menus_url = admin_url('admin-post.php?action=one_ext_export_menus&_wpnonce=' . wp_create_nonce('one_ext_export_menus')); ?>
      <h2 class="title"><?php echo esc_html__('Menu Export', 'one-core'); ?></h2>
      <p><?php echo esc_html__('Exports all menus with items and item meta (including icon/icon SVG) as JSON.', 'one-core'); ?></p>
      <p>
        <a href="<?php echo esc_url($menus_url); ?>" class="button"><?php echo esc_html__('Download Menus (JSON)', 'one-core'); ?></a>
      </p>

      <h2 class="title"><?php echo esc_html__('Install Required Plugins', 'one-core'); ?></h2>
      <p><?php echo esc_html__('Click to install and activate Tutor LMS, Directorist, WP Event Manager, and WP Job Manager.', 'one-core'); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('one_ext_install_plugins', '_one_ext_install_plugins_nonce'); ?>
        <input type="hidden" name="action" value="one_ext_install_plugins" />
        <button type="submit" class="button"><?php echo esc_html__('Install Required Plugins', 'one-core'); ?></button>
      </form>

      <hr />

      <h2 class="title"><?php echo esc_html__('Import', 'one-core'); ?></h2>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('one_ext_import', '_one_ext_import_nonce'); ?>
        <input type="hidden" name="action" value="one_ext_import" />
        <p>
          <input type="file" name="one_ext_file" accept="application/json,.json" required />
        </p>
        <p>
          <button type="submit" class="button button-secondary"><?php echo esc_html__('Import JSON', 'one-core'); ?></button>
        </p>
      </form>
    </div>
    <?php
  }

  public static function handle_export_menus() {
    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized', 'one-core'));
    }
    check_admin_referer('one_ext_export_menus');

    $export = [
      'generated_at' => current_time('mysql'),
      'site' => get_site_url(),
      'menus' => [],
    ];

    $menus = wp_get_nav_menus();
    foreach ($menus as $menu) {
      $menu_payload = [
        'term_id' => (int)$menu->term_id,
        'name' => $menu->name,
        'slug' => $menu->slug,
        'description' => $menu->description,
        'items' => [],
      ];

      $items = wp_get_nav_menu_items($menu->term_id, ['orderby' => 'menu_order', 'order' => 'ASC']);
      if (!empty($items)) {
        foreach ($items as $it) {
          $meta_raw = get_post_meta($it->ID);
          $meta = [];
          foreach ($meta_raw as $k => $vals) {
            $meta[$k] = count($vals) === 1 ? maybe_unserialize($vals[0]) : array_map('maybe_unserialize', $vals);
          }

          $menu_payload['items'][] = [
            'ID' => (int)$it->ID,
            'title' => $it->title,
            'url' => $it->url,
            'attr_title' => $it->attr_title,
            'target' => $it->target,
            'xfn' => $it->xfn,
            'classes' => is_array($it->classes) ? array_values(array_filter($it->classes)) : [],
            'type' => $it->type,
            'object' => $it->object,
            'object_id' => (int)$it->object_id,
            'menu_item_parent' => (int)$it->menu_item_parent,
            'menu_order' => (int)$it->menu_order,
            'description' => $it->description,
            'status' => $it->post_status,
            'meta' => $meta,
          ];
        }
      }

      $export['menus'][] = $menu_payload;
    }

    $json = wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (function_exists('mb_strlen')) {
      $length = mb_strlen($json, '8bit');
    } else {
      $length = strlen($json);
    }

    nocache_headers();
    header('Content-Description: File Transfer');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=one-menus-export-' . date('Ymd-His') . '.json');
    header('Content-Length: ' . $length);
    echo $json;
    exit;
  }

  private static function resolve_existing_post_type(array $candidates, $fallback = 'post') {
    foreach ($candidates as $pt) {
      if (post_type_exists($pt)) {
        return $pt;
      }
    }
    return post_type_exists($fallback) ? $fallback : 'post';
  }

  private static function fetch_posts_payload($post_type, $limit = 5) {
    if (!post_type_exists($post_type)) {
      return [];
    }
    $q = new WP_Query([
      'post_type' => $post_type,
      'post_status' => 'publish',
      'posts_per_page' => $limit,
      'orderby' => 'date',
      'order' => 'DESC',
      'no_found_rows' => true,
      'ignore_sticky_posts' => true,
      'fields' => 'ids',
    ]);
    $data = [];
    foreach ($q->posts as $post_id) {
      $post = get_post($post_id);
      if (!$post) { continue; }
      $meta = get_post_meta($post_id);
      // Flatten single-value metas
      $meta_clean = [];
      foreach ($meta as $key => $values) {
        $meta_clean[$key] = count($values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
      }
      // Taxonomies
      $tax_input = [];
      $taxes = get_object_taxonomies($post_type, 'names');
      foreach ($taxes as $tax) {
        $terms = wp_get_object_terms($post_id, $tax, ['fields' => 'names']);
        if (!is_wp_error($terms) && !empty($terms)) {
          $tax_input[$tax] = $terms;
        }
      }

      $data[] = [
        'post_type' => $post_type,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_date' => $post->post_date,
        'meta' => $meta_clean,
        'tax' => $tax_input,
      ];
    }
    return $data;
  }

  public static function handle_export() {
    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized', 'one-core'));
    }
    check_admin_referer('one_ext_export');

    // Resolve post types for each plugin safely
    $pt_tutor = self::resolve_existing_post_type(['tutor_course', 'courses']);
    $pt_directorist = self::resolve_existing_post_type(['at_biz_dir']);
    $pt_events = self::resolve_existing_post_type(['event', 'tribe_events']);
    $pt_product = self::resolve_existing_post_type(['product']);
    $pt_job = self::resolve_existing_post_type(['job_listing']);

    $payload = [
      'generated_at' => current_time('mysql'),
      'site' => get_site_url(),
      'data' => [
        'tutor_lms' => [
          'post_type' => $pt_tutor,
          'items' => self::fetch_posts_payload($pt_tutor, 5),
        ],
        'directorist' => [
          'post_type' => $pt_directorist,
          'items' => self::fetch_posts_payload($pt_directorist, 5),
        ],
        'events' => [
          'post_type' => $pt_events,
          'items' => self::fetch_posts_payload($pt_events, 5),
        ],
        'woocommerce' => [
          'post_type' => $pt_product,
          'items' => self::fetch_posts_payload($pt_product, 5),
        ],
        'wp_job_manager' => [
          'post_type' => $pt_job,
          'items' => self::fetch_posts_payload($pt_job, 5),
        ],
      ],
    ];

    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (function_exists('mb_strlen')) {
      $length = mb_strlen($json, '8bit');
    } else {
      $length = strlen($json);
    }

    nocache_headers();
    header('Content-Description: File Transfer');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=one-extension-export-' . date('Ymd-His') . '.json');
    header('Content-Length: ' . $length);
    echo $json;
    exit;
  }

  public static function handle_install_plugins() {
    if (!current_user_can('install_plugins')) {
      wp_die(__('You do not have permission to install plugins.', 'one-core'));
    }
    check_admin_referer('one_ext_install_plugins', '_one_ext_install_plugins_nonce');

    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    $slugs = [
      'tutor',            // Tutor LMS
      'directorist',      // Directorist
      'wp-event-manager', // WP Event Manager
      'wp-job-manager',   // WP Job Manager
    ];

    $results = [];
    WP_Filesystem();
    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());

    foreach ($slugs as $slug) {
      // If already active, skip
      if (function_exists('is_plugin_active') && is_plugin_active("{$slug}/{$slug}.php")) {
        $results[$slug] = 'active';
        continue;
      }
      // If installed but not active, activate
      $installed_plugins = get_plugins();
      $plugin_file = '';
      foreach ($installed_plugins as $file => $data) {
        if (strpos($file, "{$slug}/") === 0) { $plugin_file = $file; break; }
      }
      if ($plugin_file) {
        $activate = activate_plugin($plugin_file);
        $results[$slug] = is_wp_error($activate) ? 'activate_error' : 'activated';
        continue;
      }

      // Fetch from WordPress.org
      $api = plugins_api('plugin_information', [
        'slug' => $slug,
        'fields' => [ 'sections' => false ],
      ]);
      if (is_wp_error($api) || empty($api->download_link)) {
        $results[$slug] = 'not_found';
        continue;
      }

      // Install
      $installed = $upgrader->install($api->download_link);
      if (!$installed || is_wp_error($installed)) {
        $results[$slug] = 'install_error';
        continue;
      }

      // Find installed plugin file and activate
      $installed_plugins = get_plugins();
      $plugin_file = '';
      foreach ($installed_plugins as $file => $data) {
        if (strpos($file, "{$slug}/") === 0) { $plugin_file = $file; break; }
      }
      if ($plugin_file) {
        $activate = activate_plugin($plugin_file);
        $results[$slug] = is_wp_error($activate) ? 'activate_error' : 'activated';
      } else {
        $results[$slug] = 'installed_but_not_found';
      }
    }

    $redirect = add_query_arg([
      'page' => 'one-extension-export',
      'installed' => urlencode(wp_json_encode($results)),
    ], admin_url('options-general.php'));
    wp_safe_redirect($redirect);
    exit;
  }

  public static function handle_import() {
    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized', 'one-core'));
    }
    check_admin_referer('one_ext_import', '_one_ext_import_nonce');

    if (empty($_FILES['one_ext_file']) || !isset($_FILES['one_ext_file']['tmp_name'])) {
      wp_die(__('No file uploaded', 'one-core'));
    }

    $raw = file_get_contents($_FILES['one_ext_file']['tmp_name']);
    if (!$raw) {
      wp_die(__('Failed to read file', 'one-core'));
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
      wp_die(__('Invalid JSON file', 'one-core'));
    }

    // Detect Tutor LMS export schema and import accordingly
    if (!empty($data['schema_version']) && !empty($data['data']) && is_array($data['data'])) {
      $created = self::import_tutor_export_schema($data);
      $redirect = add_query_arg([
        'page' => 'one-extension-export',
        'imported' => $created,
      ], admin_url('options-general.php'));
      wp_safe_redirect($redirect);
      exit;
    }

    $created = 0;

    if (!empty($data['data']) && is_array($data['data'])) {
      foreach ($data['data'] as $section_key => $section) {
        if (empty($section['post_type']) || empty($section['items']) || !is_array($section['items'])) {
          continue;
        }
        $dest_post_type = $section['post_type'];
        if (!post_type_exists($dest_post_type)) {
          // Skip unknown post types on target site
          continue;
        }
        foreach ($section['items'] as $item) {
          $postarr = [
            'post_type' => $dest_post_type,
            'post_title' => isset($item['post_title']) ? $item['post_title'] : '',
            'post_content' => isset($item['post_content']) ? $item['post_content'] : '',
            'post_excerpt' => isset($item['post_excerpt']) ? $item['post_excerpt'] : '',
            'post_status' => 'draft',
          ];
          $new_id = wp_insert_post($postarr, true);
          if (is_wp_error($new_id) || !$new_id) {
            continue;
          }
          $created++;

          // Meta (generic import skips protected meta)
          if (!empty($item['meta']) && is_array($item['meta'])) {
            foreach ($item['meta'] as $key => $val) {
              if (is_protected_meta($key, 'post')) { continue; }
              delete_post_meta($new_id, $key);
              if (is_array($val)) {
                foreach ($val as $v) { add_post_meta($new_id, $key, maybe_serialize($v), false); }
              } else {
                update_post_meta($new_id, $key, maybe_serialize($val));
              }
            }
          }

          // Taxonomies (create terms if missing)
          if (!empty($item['tax']) && is_array($item['tax'])) {
            foreach ($item['tax'] as $tax => $terms) {
              if (!taxonomy_exists($tax) || empty($terms)) { continue; }
              $term_ids = [];
              foreach ((array)$terms as $term_name) {
                $term = term_exists($term_name, $tax);
                if (!$term) {
                  $created_term = wp_insert_term($term_name, $tax);
                  if (!is_wp_error($created_term) && !empty($created_term['term_id'])) {
                    $term_ids[] = (int)$created_term['term_id'];
                  }
                } else {
                  $term_ids[] = (int)(is_array($term) ? $term['term_id'] : $term);
                }
              }
              if (!empty($term_ids)) {
                wp_set_object_terms($new_id, $term_ids, $tax, false);
              }
            }
          }
        }
      }
    }

    $redirect = add_query_arg([
      'page' => 'one-extension-export',
      'imported' => $created,
    ], admin_url('options-general.php'));
    wp_safe_redirect($redirect);
    exit;
  }

  public static function import_tutor_export_schema(array $data) {
    $created = 0;

    // Determine post type slugs on this site
    $pt_course = post_type_exists('tutor_course') ? 'tutor_course' : (post_type_exists('courses') ? 'courses' : 'post');
    $pt_topic = post_type_exists('topics') ? 'topics' : false;
    $pt_lesson = post_type_exists('lesson') ? 'lesson' : false;
    $pt_quiz = post_type_exists('tutor_quiz') ? 'tutor_quiz' : false;
    $pt_assignment = post_type_exists('tutor_assignments') ? 'tutor_assignments' : false;

    foreach ($data['data'] as $section) {
      if (empty($section['content_type']) || $section['content_type'] !== 'courses') {
        continue;
      }
      if (empty($section['data']) || !is_array($section['data'])) {
        continue;
      }

      foreach ($section['data'] as $course) {
        // Create course
        $course_post = [
          'post_type' => $pt_course,
          'post_title' => isset($course['post_title']) ? $course['post_title'] : '',
          'post_content' => isset($course['post_content']) ? $course['post_content'] : '',
          'post_excerpt' => isset($course['post_excerpt']) ? $course['post_excerpt'] : '',
          'post_status' => 'draft',
        ];
        $course_id = wp_insert_post($course_post, true);
        if (is_wp_error($course_id) || !$course_id) {
          continue;
        }
        $created++;

        // Meta (include plugin private meta for Tutor)
        if (!empty($course['meta']) && is_array($course['meta'])) {
          foreach ($course['meta'] as $key => $val) {
            delete_post_meta($course_id, $key);
            if (is_array($val)) {
              foreach ($val as $v) { add_post_meta($course_id, $key, maybe_serialize($v), false); }
            } else {
              update_post_meta($course_id, $key, maybe_serialize($val));
            }
          }
        }

        // Taxonomies
        if (!empty($course['taxonomies']) && is_array($course['taxonomies'])) {
          // Categories
          if (!empty($course['taxonomies']['categories']) && is_array($course['taxonomies']['categories'])) {
            $term_ids = [];
            foreach ($course['taxonomies']['categories'] as $term_obj) {
              if (empty($term_obj['taxonomy']) || empty($term_obj['name'])) { continue; }
              $tax = $term_obj['taxonomy'];
              if (!taxonomy_exists($tax)) { continue; }
              $term = term_exists($term_obj['name'], $tax);
              if (!$term) {
                $created_term = wp_insert_term($term_obj['name'], $tax);
                if (!is_wp_error($created_term) && !empty($created_term['term_id'])) {
                  $term_ids[$tax][] = (int)$created_term['term_id'];
                }
              } else {
                $term_ids[$tax][] = (int)(is_array($term) ? $term['term_id'] : $term);
              }
            }
            foreach ($term_ids as $tax => $ids) {
              if (!empty($ids)) { wp_set_object_terms($course_id, $ids, $tax, false); }
            }
          }
          // Tags (may be associative array)
          if (!empty($course['taxonomies']['tags']) && is_array($course['taxonomies']['tags'])) {
            foreach ($course['taxonomies']['tags'] as $term_obj) {
              if (!is_array($term_obj)) { continue; }
              if (empty($term_obj['taxonomy']) || empty($term_obj['name'])) { continue; }
              $tax = $term_obj['taxonomy'];
              if (!taxonomy_exists($tax)) { continue; }
              $term = term_exists($term_obj['name'], $tax);
              $term_id = 0;
              if (!$term) {
                $created_term = wp_insert_term($term_obj['name'], $tax);
                if (!is_wp_error($created_term) && !empty($created_term['term_id'])) {
                  $term_id = (int)$created_term['term_id'];
                }
              } else {
                $term_id = (int)(is_array($term) ? $term['term_id'] : $term);
              }
              if ($term_id) { wp_set_object_terms($course_id, [$term_id], $tax, true); }
            }
          }
        }

        // Contents (topics -> lessons/quizzes/assignments)
        if (!empty($course['contents']) && is_array($course['contents'])) {
          foreach ($course['contents'] as $topic) {
            $topic_parent_id = $course_id;
            if ($pt_topic) {
              $topic_post = [
                'post_type' => $pt_topic,
                'post_title' => isset($topic['post_title']) ? $topic['post_title'] : '',
                'post_content' => isset($topic['post_content']) ? $topic['post_content'] : '',
                'post_status' => 'draft',
                'post_parent' => $course_id,
                'menu_order' => isset($topic['menu_order']) ? intval($topic['menu_order']) : 0,
              ];
              $topic_id = wp_insert_post($topic_post, true);
              if (!is_wp_error($topic_id) && $topic_id) {
                $created++;
                $topic_parent_id = $topic_id;
              }
            }

            if (!empty($topic['children']) && is_array($topic['children'])) {
              foreach ($topic['children'] as $child) {
                $child_type = isset($child['post_type']) ? $child['post_type'] : '';
                $dest_type = false;
                if ($child_type === 'lesson' && $pt_lesson) { $dest_type = $pt_lesson; }
                elseif ($child_type === 'tutor_quiz' && $pt_quiz) { $dest_type = $pt_quiz; }
                elseif ($child_type === 'tutor_assignments' && $pt_assignment) { $dest_type = $pt_assignment; }
                elseif ($pt_lesson && $child_type) { $dest_type = $pt_lesson; }

                if (!$dest_type) { continue; }

                $child_post = [
                  'post_type' => $dest_type,
                  'post_title' => isset($child['post_title']) ? $child['post_title'] : '',
                  'post_content' => isset($child['post_content']) ? $child['post_content'] : '',
                  'post_excerpt' => isset($child['post_excerpt']) ? $child['post_excerpt'] : '',
                  'post_status' => 'draft',
                  'post_parent' => $topic_parent_id,
                  'menu_order' => isset($child['menu_order']) ? intval($child['menu_order']) : 0,
                ];
                $child_id = wp_insert_post($child_post, true);
                if (is_wp_error($child_id) || !$child_id) {
                  continue;
                }
                $created++;

                if (!empty($child['meta']) && is_array($child['meta'])) {
                  foreach ($child['meta'] as $key => $val) {
                    delete_post_meta($child_id, $key);
                    if (is_array($val)) {
                      foreach ($val as $v) { add_post_meta($child_id, $key, maybe_serialize($v), false); }
                    } else {
                      update_post_meta($child_id, $key, maybe_serialize($val));
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $created;
  }

  public static function import_from_file(string $path, array $only_sections = null) {
    if (!file_exists($path)) {
      return 0;
    }
    $raw = file_get_contents($path);
    if (!$raw) return 0;
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return 0;

    // Tutor LMS schema
    if (!empty($data['schema_version'])) {
      return self::import_tutor_export_schema($data);
    }

    // Generic export schema from handle_export
    $created = 0;
    if (!empty($data['data']) && is_array($data['data'])) {
      foreach ($data['data'] as $key => $section) {
        if ($only_sections && empty($only_sections[$key])) continue;
        if (empty($section['post_type']) || empty($section['items']) || !is_array($section['items'])) continue;
        $dest_post_type = $section['post_type'];
        if (!post_type_exists($dest_post_type)) continue;
        foreach ($section['items'] as $item) {
          $postarr = [
            'post_type' => $dest_post_type,
            'post_title' => isset($item['post_title']) ? $item['post_title'] : '',
            'post_content' => isset($item['post_content']) ? $item['post_content'] : '',
            'post_excerpt' => isset($item['post_excerpt']) ? $item['post_excerpt'] : '',
            'post_status' => 'draft',
          ];
          $new_id = wp_insert_post($postarr, true);
          if (is_wp_error($new_id) || !$new_id) continue;
          $created++;
          if (!empty($item['meta']) && is_array($item['meta'])) {
            foreach ($item['meta'] as $mk => $mv) {
              if (is_protected_meta($mk, 'post')) continue;
              delete_post_meta($new_id, $mk);
              if (is_array($mv)) { foreach ($mv as $vv) add_post_meta($new_id, $mk, maybe_serialize($vv), false); }
              else { update_post_meta($new_id, $mk, maybe_serialize($mv)); }
            }
          }
          if (!empty($item['tax']) && is_array($item['tax'])) {
            foreach ($item['tax'] as $tax => $terms) {
              if (!taxonomy_exists($tax) || empty($terms)) continue;
              $term_ids = [];
              foreach ((array)$terms as $tname) {
                $term = term_exists($tname, $tax);
                if (!$term) {
                  $ins = wp_insert_term($tname, $tax);
                  if (!is_wp_error($ins) && !empty($ins['term_id'])) $term_ids[] = (int)$ins['term_id'];
                } else {
                  $term_ids[] = (int)(is_array($term) ? $term['term_id'] : $term);
                }
              }
              if ($term_ids) wp_set_object_terms($new_id, $term_ids, $tax, false);
            }
          }
        }
      }
    }
    return $created;
  }
}

One_Extension_Export::init();
