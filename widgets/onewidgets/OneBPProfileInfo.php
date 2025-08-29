<?php

class OneBPProfileInfo extends WP_Widget
{

  public function __construct()
  {
    $widget_options = array(
      'classname' => 'tophive-one-profile-info-widget',
      'description' => esc_html__('BuddyPress - One profile', 'ONE_CORE_SLUG')
    );
    parent::__construct('buddypress_profile', 'Buddypress Profile', $widget_options);
  }

  public function widget($args, $instance)
  {
    $profile_id = $instance["profile_id"];
    if(!$profile_id) return ;
    $html  = '';
    $html .= $args['before_widget'];
    $html .= '<div class="widget-profile-container">';

    //user profile banner banner
    $cover = bp_attachments_get_attachment('url', ['object_dir' => 'members', 'item_id' => $profile_id,]);
    if(!$cover) {
      $cover = get_template_directory_uri() . "/assets/images/placeholder.png";
    }
    $html .= "<div class='widget-profile-banner'>";
    $html .= "<figure> <img src={$cover} alt='' /> </figure>"; //original banner
    $profile_img = bp_get_displayed_user_avatar('type=full');
    if(!$profile_img) {
      $pla_url = get_template_directory_uri() . '/assets/images/people.jpg';
      $profile_img = "<img src='{$pla_url}' />";
    }
    $html .= $profile_img;
    $html .= "</div>"; //banner end

    //name
    $name = get_the_author_meta('display_name', $profile_id);
    $html .= "<div class='widget-profile-name'>";
    $html .= "<p> {$name} </p>";
    $html .= "</div>";

    //designation
    $designation = get_user_meta($profile_id, 'designation', true);
    $html .= "<div class='widget-profile-designation'>";
    $html .= "<p> {$designation} </p>";
    $html .= "</div>";

    //FOLLOW/ING INFO
    $html .= '<div class="widget-profile-meta-info member_meta_info_con">';
    // total number of posts
    if (function_exists('bp_activity_get')) {
      $activities = bp_activity_get(['filter' => ['user_id' => $profile_id], 'count_total' => true, 'per_page' => 1, 'show_hidden' => false]);
      $activity_count = isset($activities['total']) ? intval($activities['total']) : 0;
      $html .= "<p> <span> {$activity_count} </span> <span> Posts </span> </p>";
    }
    //total follower
    $_follow_count = Tophive_BP_Members::get_instance()->get_following_count(bp_displayed_user_id());
    $html .= "<p> <span> {$_follow_count} </span> <span> Following </span> </p>";
    //total friend
    if (function_exists('friends_get_total_friend_count')) {
      $friend_count = friends_get_total_friend_count($profile_id);
      $html .= "<p> <span> {$friend_count} </span> <span> Friends </span> </p>";
    }
    $html .= '</div>'; // END FOLLOW/ING INFO


    //action btn
    $current_user = get_current_user_id();
    if (Tophive_BP_Members::get_instance()->is_already_following($current_user, $profile_id)) {
      $status = 'following';
      $text   = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>' . esc_html__(' Following', 'one');
    } else {
      $status = '';
      $text = esc_html__('+ Follow', 'one');
    }
    $follow_btn = "<a href='' class='bp-th-follow-button' data-follower-id='{$profile_id}' data-following='{$status}' data-following-id='{$current_user}'> {$text} </a>";

    $html .= "<div class='widget-profile-action-btn'>";
    $html .= $follow_btn;
    $html .= '<a href="#" class="private-msg" data-recipients-id="<?php echo tophive_sanitize_filter($user_id); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
                  <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                  <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9.06 9.06 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.437 10.437 0 0 1-.524 2.318l-.003.011a10.722 10.722 0 0 1-.244.637c-.079.186.074.394.273.362a21.673 21.673 0 0 0 .693-.125zm.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6c0 3.193-3.004 6-7 6a8.06 8.06 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a10.97 10.97 0 0 0 .398-2z"></path>
                </svg>
                Message
						  </a>';
    $html .= "</div>";

    //description
    $desc = get_the_author_meta('description', $profile_id, false);
    $html .= "<div class='widget-profile-about'>";
    $html .= '<h4> About </h4>';
    $html .= "<p> {$desc} </p>";
    $html .= "</div>";


    //end main container div
    $html .= '</div>';
    $html .= $args['after_widget'];
    echo $html;
  }

  public function form($instance)
  {
    $profile_id = ! empty($instance['profile_id']) ? $instance['profile_id'] : '';
?>
    <div class="mchimp-subs_form">
      <p>
        <label for="<?php echo $this->get_field_id('profile_id'); ?>">Profile Id:</label>
        <input
          id="<?php echo $this->get_field_id('profile_id'); ?>"
          type="text"
          name="<?php echo $this->get_field_name('profile_id'); ?>"
          value="<?php echo esc_attr($profile_id); ?>" />
      </p>
    </div>
<?php
  }

  public function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['profile_id'] = strip_tags($new_instance['profile_id']);
    return $instance;
  }
}

