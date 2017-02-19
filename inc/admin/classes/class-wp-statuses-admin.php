<?php
/**
 * WP Statuses Admin Class.
 *
 * @package WP Statuses\admin\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WP_Statuses_Admin {
	public function __construct() {
		//$this->setup_globals();
		//$this->includes();
		$this->hooks();
	}

	/**
	 * Starts the Admin class
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		if ( ! is_admin() ) {
			return;
		}

		$wp_statuses = wp_statuses();

		if ( empty( $wp_statuses->admin ) ) {
			$wp_statuses->admin = new self;
		}

		return $wp_statuses->admin;
	}

	/**
	 * Setups the action and filters to hook to
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
	}

	public function add_meta_box( $post_type, $post ) {
		add_meta_box(
			'wp-statuses-publish-box',
			__( 'Publishing', 'wp-statuses' ),
			array( $this, 'publish_box' ),
			$post_type,
			'advanced',
			'high'
		);
	}

	function publish_box( $post = null ) {
		if ( empty( $post->post_type ) ) {
			return;
		}

		$statuses = wp_statuses_get_metabox_statuses( $post->post_type );

		$current = $post->post_status;
		if ( 'auto-draft' === $current ) {
			$current = 'draft';
		}

		$options  = array();
		$dashicon = 'dashicons-post-status';

		foreach ( $statuses as $status ) {
			$selected = selected( $current, $status->name, false );

			if ( $selected ) {
				$dashicon = $status->dashicon;
			}

			$options[] = '<option value="' . esc_attr( $status->name ) .'" ' . $selected . ' data-dashicon="' . esc_attr( $status->dashicon ) . '">' . esc_html( $status->labels['metabox_dropdown'] ) . '</option>';
		}
		?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">

				<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( $current ); ?>" />
				<label for="post_status" class="screen-reader-text"><?php esc_html_e( 'Set status', 'wp-statuses' ); ?></label>
				<?php printf(
					'<span class="dashicons %1$s"></span> <select name="post_status" id="wp-statuses-dropdown">%2$s</select>',
					sanitize_html_class( $dashicon ),
					join( "\n", $options )
				); ?>

			</div>
		</div>
		<?php
	}
}
