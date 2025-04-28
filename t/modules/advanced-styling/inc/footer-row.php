<?php
class OneCoreCustomizer_Advanced_Styling_Footer_Row extends OneCoreCustomizer_Module_Base {
	public function __construct() {
		add_filter( 'tophive/builder/footer/rows/section_configs', array( $this, 'config' ), PHP_INT_MAX, 3 );
	}

	public function config( $configs = array(), $section, $section_name ) {
		$selector           = '#cb-row--' . str_replace( '_', '-', $section );
		$skin_mode_selector = '.footer--row-inner.' . str_replace( '_', '-', $section ) . '-inner';
		$config       = array(

			array(
				'name'       => "{$section}_row_alignment",
				'type'       => 'text_align_no_justify',
				'section'    => $section,
				'title'      => __( 'Alignment', 'tophive' ),
				'device_settings' => true,
				'selector'   => "{$selector} .footer--row-inner",
				'css_format' => 'text-align: {{value}};',
			),
			array(
				'name'       => "{$section}_row_styling",
				'type'       => 'styling',
				'section'    => $section,
				'title'      => __( 'Advanced Styling', 'tophive-pro' ),
				'selector'   => array(
					'normal' => "$selector .footer--row-inner",
				),
				'css_format' => 'styling', // styling.
				'fields'     => array(
					'normal_fields' => array(
						'text_color'        => false,
						'link_color'        => false,
						'padding'           => true,
						'margin'            => true,
						'border_heading'    => true,
						'border_width'      => true,
						'border_color'      => true,
						'border_radius'     => true,
						'box_shadow'        => true,
						'border_style'      => true,
						'bg_heading'        => true,
						'bg_cover'          => true,
						'bg_repeat'         => true,
						'bg_color'          => true,
						'bg_image'          => true,
					),
					'hover_fields'  => false,
				),
			),
			array(
				'name'       => "{$section}_row_typography",
				'type'       => 'typography',
				'section'    => $section,
				'title'      => __( 'Advanced Typography', 'tophive-pro' ),
				'selector'   => "{$selector} .footer--row-inner",
				'css_format' => 'typography', // styling.
			),
			array(
				'name'       => "{$section}_row_widget_title_styling",
				'type'       => 'styling',
				'section'    => $section,
				'title'      => __( 'Widget Title Styling', 'tophive-pro' ),
				'selector'   => array(
					'normal' => "$selector .footer--row-inner .widget-title",
				),
				'css_format' => 'styling', // styling.
				'fields'     => array(
					'normal_fields' => array(
						'text_color'        => true,
						'link_color'        => false,
						'padding'           => true,
						'margin'            => true,
						'border_heading'    => true,
						'border_width'      => true,
						'border_color'      => true,
						'border_radius'     => true,
						'border_style'      => true,
						'box_shadow'        => false,
						'bg_heading'        => false,
						'bg_cover'          => false,
						'bg_repeat'         => false,
						'bg_color'          => false,
						'bg_image'          => false,
					),
					'hover_fields'  => false,
				),
			),
			array(
				'name'       => "{$section}_row_widget_title_typography",
				'type'       => 'typography',
				'section'    => $section,
				'title'      => __( 'Widget Title Typography', 'tophive-pro' ),
				'selector'   => "{$skin_mode_selector} .widget-title",
				'css_format' => 'typography', // styling.
			),
			array(
				'name'       => "{$section}_row_link_styling",
				'type'       => 'styling',
				'section'    => $section,
				'title'      => __( 'Anchor Styling', 'tophive-pro' ),
				'selector'   => array(
					'normal' => "$selector .footer--row-inner ul li a",
					'hover' => "$selector .footer--row-inner ul li a:hover",
				),
				'css_format' => 'styling', // styling.
				'fields'     => array(
					'normal_fields' => array(
						'text_color'        => false,
						'margin'            => false,
						'border_heading'    => false,
						'border_width'      => false,
						'border_color'      => false,
						'border_radius'     => false,
						'border_style'      => false,
						'box_shadow'        => false,
						'bg_heading'        => false,
						'bg_cover'          => false,
						'bg_repeat'         => false,
						'bg_color'          => false,
						'bg_image'          => false,
					),
					'hover_fields'  => array(
						'text_color'        => false,
						'margin'            => false,
						'bg_heading'        => false,
						'bg_cover'          => false,
						'bg_repeat'         => false,
						'bg_color'          => false,
						'bg_image'          => false,
						'border_heading'    => false,
						'border_width'      => false,
						'border_color'      => false,
						'border_radius'     => false,
						'border_style'      => false,
						'box_shadow'        => false,
					),
				),
			),
			array(
				'name'       => "{$section}_row_link_typography",
				'type'       => 'typography',
				'section'    => $section,
				'title'      => __( 'Anchor Typography', 'tophive-pro' ),
				'selector'   => "$selector .footer--row-inner ul li a",
				'css_format' => 'typography', // styling.
			),
		);
		return array_merge( $configs, $config );

	}

}

new OneCoreCustomizer_Advanced_Styling_Footer_Row();
