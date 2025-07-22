<?php

class One_Imports_Controllers
{
  private $API_ROOT = "http://app.tophivetheme.com/api";
  private $API_ENDPOINT = "";
  private $API_ENDPOINT_LICENSE_ACTIVATE = "";
  private $API_ENDPOINT_LICENSE_CHECK = "";
  private $API_ENDPOINT_UPDATE_CHECK = "";
  private $IMPORTER = null;

  function __construct()
  {
    add_action("wp_ajax_tophive/api/templates/{resource_type}", [$this, "get_templates"]);
    add_action("wp_ajax_tophive_import_resource", [$this, "import_resource"]);
    add_action("wp_ajax_tophive_import_plugin", [$this, "import_plugin"]);
    add_action("wp_ajax_tophive_activate_license", [$this, "activate_license"]);
    add_action("wp_ajax_tophive_check_licence", [$this, "check_license"]);
    add_filter('site_transient_update_themes', [$this, "check_theme_update"]);

    $this->API_ENDPOINT = "{$this->API_ROOT}/templates/{resource_type}";
    $this->API_ENDPOINT_LICENSE_ACTIVATE = "{$this->API_ROOT}/license/activate";
    $this->API_ENDPOINT_LICENSE_CHECK = "{$this->API_ROOT}/license/check";
    $this->API_ENDPOINT_UPDATE_CHECK  = "{$this->API_ROOT}/product";
  }

  private function restrict_for_admin()
  {
    if (! current_user_can('administrator')) {
      wp_send_json("Not an administrator", 401);
    }
  }

  private function get_product_id_key()
  {
    return get_option("stylesheet") . "_tophive_product_id";
  }

  private function get_licence_key()
  {
    return get_option("stylesheet") . "_tophive_license";
  }

  private function get_product_id()
  {
    return get_option($this->get_product_id_key(), "");
  }

  private function get_licence()
  {
    return get_option($this->get_licence_key(), "");
  }

  public function check_theme_update( $transient )
  {
    if (!is_object($transient)) {
      $transient = new stdClass();
    }

    if (!isset($transient->response) || !is_array($transient->response)) {
      $transient->response = [];
    }

    $theme_slug = wp_get_theme()->get_stylesheet();
    $current_version = wp_get_theme($theme_slug)->get('Version');

    $license_value = $this->get_licence();
    $product_id =  $this->get_product_id();
    $domain = get_site_url();
    $url = "{$this->API_ENDPOINT_UPDATE_CHECK}/{$product_id}?domain={$domain}";

    $remote_response  = wp_remote_request($url, [
      "method" => "GET",
      "headers" => [
        'Authorization' => $license_value,
        'content-type' => 'application/json',
      ],
    ]);

    if (!is_wp_error($remote_response) && wp_remote_retrieve_response_code($remote_response) === 200) {
      $remote_data = json_decode(wp_remote_retrieve_body($remote_response), true)["product"];
      if ($remote_data && version_compare($current_version, $remote_data["version"], '<')) {
        $transient->response[$theme_slug] = [
          'theme'       => $theme_slug,
          'new_version' => $remote_data["version"],
          'url'         => "",
          'package'     => $remote_data["download_url"], // ZIP file URL
        ];
      }
    }

    return $transient;
  }

  public function check_license()
  {
    $this->restrict_for_admin();

    if (empty($this->get_licence()) || empty($this->get_product_id())) {
      wp_send_json("license required", 400);
    }

    $res = wp_remote_request($this->API_ENDPOINT_LICENSE_CHECK, [
      "method" => "POST",
      "headers" => ['content-type' => 'application/json'],
      "body" => json_encode(['license_key' => $this->get_licence(), 'domain' => get_site_url()])
    ]);

    if (is_wp_error($res)) {
      wp_send_json($res, 400);
    }

    if (wp_remote_retrieve_response_code($res) != 200) {
      wp_send_json($res, wp_remote_retrieve_response_code($res));
    } else {
      $body = json_decode(wp_remote_retrieve_body($res), true);
      wp_send_json(
        [
          "status_code" => wp_remote_retrieve_response_code($res),
          "data" => $body
        ],
        200
      );
    }
  }

  public function activate_license()
  {
    $this->restrict_for_admin();

    if (!isset($_POST["options"]) || empty($_POST["options"])) {
      wp_send_json("options is missing", 400);
    }
    $options = map_deep($_POST["options"], "sanitize_text_field");

    if (!isset($options["license"]) || empty($options["license"])) {
      wp_send_json("license required", 400);
    }
    if (!isset($options["secret"]) || empty($options["secret"])) {
      wp_send_json("secret required", 400);
    }

    $res = wp_remote_request($this->API_ENDPOINT_LICENSE_ACTIVATE, [
      "method" => "POST",
      "headers" => ['content-type' => 'application/json'],
      "body" => json_encode([
        'license_key' => $options["license"],
        'domain' => get_site_url(),
        'product_secret' => $options["secret"],
      ])
    ]);

    if (is_wp_error($res)) {
      wp_send_json($res, 400);
    }

    if (wp_remote_retrieve_response_code($res) != 200) {
      wp_send_json($res, wp_remote_retrieve_response_code($res));
    } else {
      $body = json_decode(wp_remote_retrieve_body($res), true);
      //save license_key 
      update_option($this->get_licence_key(), $options["license"]);
      update_option($this->get_product_id_key(), $body["data"]["product_id"]);

      wp_send_json(
        [
          "status_code" => wp_remote_retrieve_response_code($res),
          "data" => $body
        ],
        200
      );
    }
  }

  public function import_plugin()
  {
    $this->restrict_for_admin();

    if (!isset($_POST["options"]) || empty($_POST["options"])) {
      wp_send_json("options is missing", 400);
    }
    $options = map_deep($_POST["options"], "sanitize_text_field");
    if (!isset($options["download_link"]) || empty($options["download_link"]) || !isset($options["path"]) || empty($options["path"])) {
      wp_send_json("invalid options", 400);
    }
    $res = $this->IMPORTER->download_and_install_plugin(["download_link" => $options["download_link"], "path" => $options["path"]]);
    if (is_wp_error($res)) {
      wp_send_json("plugin install fail", 400);
    }

    wp_send_json("plugin installed", 200);
  }

  public function _get_templates($options)
  {
    if (empty($options)) {
      return new WP_Error(400, "params missing");
    }

    if (!isset($options["resource_type"]) || empty($options["resource_type"])) {
      return new WP_Error(400, "resource_type cant be blank");
    }

    $resource_type = $options["resource_type"];
    $page = 1;

    if (isset($options["page"]) && !empty($options["page"])) {
      $page = $options["page"];
    }
    $url = str_replace("{resource_type}", $resource_type, $this->API_ENDPOINT) . "?page={$page}";

    if (isset($options["id"]) && !empty($options["id"])) {
      $url .= "&id={$options['id']}";
    }

    //ADD LICENSE ADD PRODUCT_ID
    $license_value = $this->get_licence();
    $product_id = $this->get_product_id();
    $url .= "&product_id={$product_id}";
    $domain = get_site_url();
    $url .= "&domain={$domain}";

    $res = wp_remote_request($url, [
      "method" => "GET",
      "headers" => [
        'Authorization' => $license_value,
        'content-type' => 'application/json',
      ],
    ]);

    if (is_wp_error($res)) {
      return new WP_Error(400, "request fail", $res);
    }

    return [
      "status_code" => wp_remote_retrieve_response_code($res),
      "data" => json_decode(wp_remote_retrieve_body($res), true),
    ];
  }

  public function get_templates()
  {
    $this->restrict_for_admin();
    $url = str_replace("{resource_type}", 'page', $this->API_ENDPOINT);

    //ADD LICENSE ADD PRODUCT_ID
    $license_value = $this->get_licence();
    $product_id = $this->get_product_id();
    $url .= "?product_id={$product_id}";
    $domain = get_site_url();
    $url .= "&domain={$domain}";

    $res = wp_remote_request($url, [
      "method" => "GET",
      "headers" => [
        'Authorization' => $license_value,
        'content-type' => 'application/json',
      ],
    ]);

    $body = wp_remote_retrieve_body($res);
    $data = json_decode($body, true);
    return $data['templates'] ?? [];
  }

  public function import_resource()
  {
    $this->restrict_for_admin();

    if (!isset($_POST["params"]) || empty($_POST["params"])) {
      wp_send_json("params missing", 400);
    }
    $params = $_POST["params"];

    if (
      empty($params["resource_type"]) || empty($params["id"])
    ) {
      wp_send_json("resource_type or id missing", 400);
    }

    $id = $params["id"];
    $resource_type = $params["resource_type"];
    $res = $this->_get_templates(["resource_type" => $resource_type, "id" => $id]);

    if ($res["status_code"] !== 200) {
      return wp_send_json(["Fetch resource id:{$id} fail", $res], 400);
    }

    if (empty($res["data"]["templates"])) {
      return wp_send_json(["No resource found with id:{$id}", $res], 400);
    }

    $data = json_decode($res["data"]["templates"][0]["json_code"], true);


    //import resource based on their type
    //post,page,customizer,menu,fullsite
    if ($resource_type == "page" || $resource_type == "post") {
      $res = $this->IMPORTER->import_post($data, $resource_type);
      if ($res == false || is_wp_error($res)) {
        wp_send_json(["import fail check error log", $res], 400);
      }
      wp_send_json(["status" => "success", "data" => $res], 200);
    } elseif ($resource_type == "customizer") {
      $res = $this->IMPORTER->import_customizer($data);
      if ($res == false) {
        wp_send_json(["import customizer fail check error log", $res], 400);
      }
      wp_send_json(["status" => "success", "data" => $res], 200);
    } elseif ($resource_type == "menus") {
      $res = $this->IMPORTER->import_menus($data);
      if ($res == false) {
        wp_send_json(["import  menus fail check error log", $res], 400);
      }
      wp_send_json(["status" => "success", "data" => $res], 200);
    } else {
      wp_send_json("unknown resource_type ", 400);
    }
  }
}

