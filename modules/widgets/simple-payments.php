<?php
/*
Plugin Name: Simple Payments
Description: Simple Payments button implemented as a widget.
Version: 1.0
Author: Automattic Inc.
Author URI: http://automattic.com/
License: GPLv2 or later
*/

function jetpack_register_widget_simple_payments() {
	register_widget( 'Simple_Payments_Widget' );
}
add_action( 'widgets_init', 'jetpack_register_widget_simple_payments' );

class Simple_Payments_Widget extends WP_Widget {
	private static $dir       = null;
	private static $url       = null;
	private static $labels    = null;
	private static $defaults  = null;
	private static $config_js = null;

	private static $currencies = array(
		'USD' => array(
			'symbol' => '$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'EUR' => array(
			'symbol' => '€',
			'grouping' => '.',
			'decimal' => ',',
			'precision' => 2,
		),
		'AUD' => array(
			'symbol' => 'A$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'BRL' => array(
			'symbol' => 'R$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'CAD' => array(
			'symbol' => 'C$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'CZK' => array(
			'symbol' => 'Kč',
			'grouping' => ' ',
			'decimal' => ',',
			'precision' => 2,
		),
		'DKK' => array(
			'symbol' => 'kr.',
			'grouping' => '',
			'decimal' => ',',
			'precision' => 2,
		),
		'HKD' => array(
			'symbol' => 'HK$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'HUF' => array(
			'symbol' => 'Ft',
			'grouping' => '.',
			'decimal' => ',',
			'precision' => 0,
		),
		'ILS' => array(
			'symbol' => '₪',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'JPY' => array(
			'symbol' => '¥',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 0,
		),
		'MYR' => array(
			'symbol' => 'RM',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'MXN' => array(
			'symbol' => 'MX$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'TWD' => array(
			'symbol' => 'NT$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'NZD' => array(
			'symbol' => 'NZ$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'NOK' => array(
			'symbol' => 'kr',
			'grouping' => ' ',
			'decimal' => ',',
			'precision' => 2,
		),
		'PHP' => array(
			'symbol' => '₱',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'PLN' => array(
			'symbol' => 'zł',
			'grouping' => ' ',
			'decimal' => ',',
			'precision' => 2,
		),
		'GBP' => array(
			'symbol' => '£',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'RUB' => array(
			'symbol' => '₽',
			'grouping' => ' ',
			'decimal' => ',',
			'precision' => 2,
		),
		'SGD' => array(
			'symbol' => '$',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'SEK' => array(
			'symbol' => 'kr',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
		'CHF' => array(
			'symbol' => 'CHF',
			'grouping' => '\'',
			'decimal' => '.',
			'precision' => 2,
		),
		'THB' => array(
			'symbol' => '฿',
			'grouping' => ',',
			'decimal' => '.',
			'precision' => 2,
		),
	);

	function __construct() {
		$widget = array(
			'classname'   => 'simple-payments',
			'description' => __( 'Add a simple payment button.', 'jetpack' ),
		);

		parent::__construct(
			'Simple_Payments_Widget',
			/** This filter is documented in modules/widgets/facebook-likebox.php */
			apply_filters( 'jetpack_widget_name', __( 'Simple Payments', 'jetpack' ) ),
			$widget
		);

		self::$dir = trailingslashit( dirname( __FILE__ ) );
		self::$url = plugin_dir_url( __FILE__ );
		// add form labels for translation
		/*
		self::$labels = array(
			'year'    => __( 'year', 'jetpack' ),
		);
		*/

		// add_action( 'wp_enqueue_scripts', array( __class__, 'enqueue_template' ) );
		add_action( 'admin_enqueue_scripts', array( __class__, 'enqueue_admin_styles' ) );
	}

	public static function enqueue_admin_styles( $hook_suffix ) {
		if ( 'widgets.php' == $hook_suffix ) {
			wp_enqueue_style( 'simple-payments-widget-admin', self::$url . '/simple-payments/style-admin.css', array(), '201710151520' );
			wp_enqueue_media();
			wp_enqueue_script( 'simple-payments-widget-admin', self::$url . '/simple-payments/admin.js', array( 'jquery' ), '20171015', true );
		}
	}

	public static function enqueue_template() {
		// wp_enqueue_script( 'milestone', self::$url . 'milestone.js', array( 'jquery' ), '20160520', true );
	}

	protected function get_product_args( $product_id ) {
		$product = $product_id ? get_post( $product_id ) : null;
		$product_args = array();
		if ( $product && ! is_wp_error( $product ) && $product->post_type === Jetpack_Simple_Payments::$post_type_product ) {
			$product_args = array(
				'name' => get_the_title( $product ),
				'description' => $product->post_content,
				'currency' => get_post_meta( $product->ID, 'spay_currency', true ),
				'price' => get_post_meta( $product->ID, 'spay_price', true ),
				'multiple' => get_post_meta( $product->ID, 'spay_multiple', true ),
				'email' => get_post_meta( $product->ID, 'spay_email', true ),
			);
		} else {
			$product_id = null;
		}

		$current_user = wp_get_current_user();
		return wp_parse_args( $product_args, array(
			'name' => '',
			'description' => '',
			'currency' => 'USD', // TODO: Geo-locate?
			'price' => '',
			'multiple' => '0',
			'email' => $current_user->user_email,
		) );
	}

    /**
     * Widget
     */
    function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, array(
			'title' => '',
			'product_id' => null,
		) );

		$product_args = $this->get_product_args( $instance['product_id'] );

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo '<div class="simple-payments-content">';

		$attrs = array( 'id' => $instance['product_id'] );

		$JSP = Jetpack_Simple_Payments::getInstance();
		// display the product on the front end here
		echo $JSP->parse_shortcode( $attrs );

		echo '</div><!--simple-payments-->';

		echo $args['after_widget'];

	    /** This action is documented in modules/widgets/gravatar-profile.php */
	    do_action( 'jetpack_stats_extra', 'widget_view', 'simple-payments' );
    }

    protected function sanitize_price( $currency_code, $price ) {
		if ( '-' === substr( $price, 0, 1 ) ) {
			return false;
		}
		$currency = self::$currencies[ $currency_code ];
		$decimal = $currency['decimal'];
		$precision = $currency['precision'];
		$chars = count_chars( $price, 1 );
		for( $i = 0; $i <= 9; $i++ ) {
			unset( $chars[ ord( (string) $i ) ] );
		}
		if ( count( $chars ) > 1 || reset( $chars ) > 1 ) {
			return false;
		}

		// Allow the decimal separator to be the currency decimal separator or "."
		if ( ! empty( $chars ) ) {
			$decimal_char = chr( key( $chars ) );
			if ( $decimal_char !== $decimal && $decimal_char !== '.' ) {
				return false;
			}
			$price = str_replace( $decimal_char, '.', $price );
		}

		return round( (float) $price, $precision );
	}

    /**
     * Update
     */
    function update( $new_instance, $old_instance ) {
		$product_id = isset( $old_instance['product_id'] ) ? $old_instance['product_id'] : null;
		$product = $product_id ? get_post( $product_id ) : 0;
		if ( ! $product || is_wp_error( $product ) || $product->post_type !== Jetpack_Simple_Payments::$post_type_product ) {
			$product_id = 0;
		}

		if ( ! empty( $new_instance['name'] ) && ! empty( $new_instance['price'] ) ) {
			return array(
				'title' => $new_instance['title'],
				'product_id' => wp_insert_post( array(
					'ID' => $product_id,
					'post_type' => Jetpack_Simple_Payments::$post_type_product,
					'post_status' => 'publish',
					'post_title' => $new_instance['name'],
					'post_content' => $new_instance['description'],
					'_thumbnail_id' => isset( $new_instance['image'] ) ? $new_instance['image'] : -1,
					'meta_input' => array(
						'spay_currency' => $new_instance['currency'],
						'spay_price' => $this->sanitize_price( $new_instance['currency'], $new_instance['price'] ),
						'spay_multiple' => $new_instance['multiple'],
						'spay_email' => is_email( $new_instance['email'] ),
					),
				) ),
			);
		}
    }

    /**
     * Form
     */
    function form( $instance ) {
		$instance = wp_parse_args( $instance, array(
			'title' => '',
			'product_id' => null,
		) );
		if ( ! $instance['product_id'] ) {
			$output = array();

			// list existing products + add button
			$args = array(
				'numberposts' => -1,
				'orderby' => 'date',
				'post_type' => 'jp_pay_product',
			);

			$products = get_posts( $args );
			?>
		<div class="add-product">
			<button id="simple-payments-add-product" class="button"><?php _e( 'Add New', 'jetpack' ); ?></button>
		</div>
		<ul class="simple-payments-products">
			<?php
			foreach ( $products as $product ):
				$meta = get_post_meta( $product->ID );
				$product->price = $meta['spay_price'][0] . " " . $meta['spay_currency'][0];

				// start building the product list
				$image = ( has_post_thumbnail( $product->ID ) ) ? get_the_post_thumbnail( $product->ID, 'medium' ) : '';
			?>
			<li>
				<input type="radio" name="simple-payments-products_<?php echo $this->id; ?>" value="<?php esc_html_e( $product->ID ); ?>">
				<div class="product-info">
					<?php esc_html_e( $product->post_title ); ?><br>
					<?php esc_html_e( $product->price ); ?>
				</div>
				<div class="image"><?php echo $image; ?></div>
			</li>
			<?php endforeach; ?>
		</ul>
		<div class="insert-product">
			<button id="simple-payments-insert-product" class="button"><?php _e( 'Insert', 'jetpack' ); ?></button>
		</div>
		<?php
		}

		$product_args = $this->get_product_args( $instance['product_id'] );

		$price = ( $product_args['price'] ) ? esc_attr(  number_format( $product_args['price'], self::$currencies[ $product_args['currency'] ]['precision'], '.', '' ) ) : '';
        ?>

	<div class="simple-payments">
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'jetpack' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'name' ); ?>"><?php _e( 'What are you selling?', 'jetpack' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" type="text" placeholder="<?php echo esc_attr_e( 'Product name', 'jetpack' ); ?>" value="<?php echo esc_attr( $product_args['name'] ); ?>" />
		</p>
		<div class="simple-payments-image-fieldset">
			<div class="placeholder <?php if ( has_post_thumbnail( $instance['product_id'] ) ) echo 'hide'; ?>">No image selected</div> <!-- TODO: actual placeholder, i18n -->
			<div class="simple-payments-image" data-image-field="<?php echo $this->get_field_name( 'image' ); ?>"> <!-- TODO: hide if empty, CSS? -->
				<?php
					if ( has_post_thumbnail( $instance['product_id'] ) ) {
						$image_id = get_post_thumbnail_id( $instance['product_id'] );
						list( $src, $width, $height ) = wp_get_attachment_image_src( $image_id, 'full' );
						// TODO: caption, title
						echo '<img width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '" src="' . esc_attr( $src ) . '" />';
						echo '<input type="hidden" name="' . $this->get_field_name( 'image' ) . '" value="' . esc_attr( $image_id ) . '" />';
					}
				?>
			</div>
			<button class="button simple-payments-add-image">Add Image</button>
			<button class="button simple-payments-remove-image">Remove Image</button>
		</div>
		<p>
			<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description', 'jetpack' ); ?></label>
			<textarea class="widefat" rows=5 id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>"><?php echo esc_html( $product_args['description'] ); ?></textarea>
		</p>
		<p class="cost">
			<label for="<?php echo $this->get_field_id( 'price' ); ?>"><?php _e( 'Price', 'jetpack' ); ?></label>
			<select class="currency widefat" id="<?php echo $this->get_field_id( 'currency' ); ?>" name="<?php echo $this->get_field_name( 'currency' ); ?>">
				<?php foreach( self::$currencies as $code => $currency ) { ?>
					<option value="<?php echo esc_attr( $code ) ?>" <?php if ( $code === $product_args['currency'] ) { ?>selected="selected"<?php } ?>>
						<?php echo esc_html( $currency['symbol'] === $code ? $code : ( $code . ' ' . rtrim( $currency['symbol'], '.' ) ) ) ?>
					</option>
				<?php } ?>
			</select>
			<input class="price widefat" id="<?php echo $this->get_field_id( 'price' ); ?>" name="<?php echo $this->get_field_name( 'price' ); ?>" type="text" value="<?php echo $price; ?>" />
		</p>
		<p>
			<input id="<?php echo $this->get_field_id( 'multiple' ); ?>" name="<?php echo $this->get_field_name( 'multiple' ); ?>" type="checkbox" <?php if ( '1' === $product_args['multiple'] ) { ?>checked="checked"<?php } ?> />
			<label for="<?php echo $this->get_field_id( 'multiple' ); ?>"><?php _e( 'Allow people to buy more than one item at a time.', 'jetpack' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'email' ); ?>"><?php _e( 'Email', 'jetpack' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'email' ); ?>" name="<?php echo $this->get_field_name( 'email' ); ?>" type="email" value="<?php echo esc_attr( $product_args['email'] ); ?>" />
			<em><?php printf( esc_html__( 'This is where PayPal will send your money. To claim a payment, you\'ll need a %1$sPayPal account%2$s connected to a bank account.', 'jetpack' ), '<a href="https://paypal.com" target="_blank">', '</a>' ) ?></em>
		</p>
	</div>

		<?php
    }
}
