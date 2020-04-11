<?php
//* Code goes here

add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

function storefront_handheld_footer_bar_cart_link() {
   ?>
      <a class="footer-cart-contents fly-cart-btn" title="<?php esc_attr_e( 'View your shopping cart', 'storefront' ); ?>">
         <span class="count"><?php echo wp_kses_data( WC()->cart->get_cart_contents_count() ); ?></span>
      </a>
   <?php
}


//* Set minimum purchase and add "Paquete Atasques" as an exception

add_action( 'woocommerce_check_cart_items', 'min_cart_amount', 'woocommerce_before_cart', 'bbloomer_check_category_in_cart' );
function min_cart_amount() {

    $min_cart_amount   = 1000;
    $cat_in_cart = false;

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
      if ( has_term( 'paquete-atasques', 'product_cat', $cart_item['product_id'] ) ) {
          $cat_in_cart = true;
          break;
      }
    }

    if ( $cat_in_cart === false ) {
      if( WC()->cart->subtotal < $min_cart_amount ) {
          wc_add_notice( sprintf(
              __( "<strong>Tu pedido debe superar la cantidad de %s </strong><br>Tu pedido actual es de %s" ),
              wc_price( $min_cart_amount ),
              wc_price( WC()->cart->subtotal )
          ), 'error' );
      }
    }
}

//* Prevents "Paquete Atasques" to be with other products at the cart

function aelia_get_cart_contents() {
  $cart_contents = array();
  $cart = WC()->session->get( 'cart', null );
 
  if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', true ) ) ) {
    $cart = $saved_cart['cart'];
  } elseif ( is_null( $cart ) ) {
    $cart = array();
  }
 
  if ( is_array( $cart ) ) {
    foreach ( $cart as $key => $values ) {
      $_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
 
      if ( ! empty( $_product ) && $_product->exists() && $values['quantity'] > 0 ) {
        if ( $_product->is_purchasable() ) {
          $session_data = array_merge( $values, array( 'data' => $_product ) );
          $cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );
        }
      }
    }
  }
  return $cart_contents;
}

add_action('wp_loaded', function() {
  if(!is_object(WC()->session)) {
    return;
  }

  global $allowed_cart_items;
  global $restricted_cart_items;
  $restricted_cart_items = array(
    13701,
    13330,
    18794,
    18705,
    18940
  );
 
  foreach(aelia_get_cart_contents() as $item) {
    if(in_array($item['data']->id, $restricted_cart_items)) {
      $allowed_cart_items[] = $item['data']->id;
      break;
    }
  }
 
  add_filter('woocommerce_is_purchasable', function($is_purchasable, $product) {
    global $restricted_cart_items;
    global $allowed_cart_items;
 
    if(!empty($allowed_cart_items)) {
      $is_purchasable = in_array($product->id, $allowed_cart_items);
    }
    return $is_purchasable;
  }, 10, 2);
}, 10);
 
add_filter('woocommerce_get_price_html', function($price_html, $product) {
  if(!$product->is_purchasable() && is_product()) {
    $price_html .= '<p>' . __('"Paquete Atasques" es un producto que sólo se puede comprar individualmente. Si deseas comprar otros productos, elimina el producto "Paquete Atasques" de tu carrito de compra.', 'woocommerce') . '</p>';
  }
  return $price_html;
}, 10, 2);